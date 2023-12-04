<?php

namespace App\Controllers;

use Carbon\Carbon;
use App\Models\Config;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Delight\Cookie\Session;
use App\Controllers\BaseController;
use Respect\Validation\Validator as v;
use DI\Container;

class ContactController extends BaseController
{

	protected $container;
	private $rules;
	private $siteName;

	const FORM_MESSAGE_REQUEST_SENT = 'Your contact request has been submitted successfully.';
	const FORM_ERROR_VALIDATION = 'It looks like there was a problem with the information you provided on the form.';
	const FORM_ERROR_UNKNOWN = 'There was an unknown error while submitting your contact form request.';

	// OBFUSCATION & SPAM CHECK
	const SESSION_NAME = 'submissionRecentlyUsed';
	const SESSION_TIME = 'submissionTimestamp';
	const SPAM_CHECK_ATTRIBUTE = '_honey';
	const FORM_READY_AT_SESSION_NAME = 'post_request_ready_at';
	const FORM_OBFUSCATION_SESSION_NAME = 'post_request_obfuscation_hash';
	const HUMAN_CHECK_TIME = 10;

	public function __construct(Container $container)
	{
		$this->container = $container;
		$this->logger = $this->container->get('contactFormLogger');
		$this->siteName = 'Joey Gallegos';
		$this->rules = [
			'name' => [
				'clean_name' => 'Full name',
				'type' => 'input',
				'validator' => v::notEmpty()
			],
			'orgname' => [
				'clean_name' => 'Your organization',
				'type' => 'input',
				'validator' => v::notEmpty()
			],
			'phone' => [
				'clean_name' => 'Phone number',
				'type' => 'input',
				'validator' => v::notEmpty()->noWhitespace()->phone()
			],
			'email' => [
				'clean_name' => 'Email',
				'type' => 'input',
				'validator' => v::notEmpty()->noWhitespace()->email()
			],
			'location' => [
				'clean_name' => 'Your location',
				'type' => 'input',
				'validator' => v::notEmpty()
			],
			'what' => [
				'clean_name' => 'Your reason',
				'type' => 'input',
				'validator' => v::notEmpty()
			],
			'message' => [
				'clean_name' => 'Your message',
				'type' => 'textarea',
				'validator' => v::notEmpty()->notBlank()
			],

			// spam check
			// ex: https://formsubmit.co/
			// ex: https://gist.github.com/andrewlimaza/958826feac907114a57462bfc8d535ff
			self::SPAM_CHECK_ATTRIBUTE => [
				'clean_name' => self::SPAM_CHECK_ATTRIBUTE,
				'type' => 'input',
				'validator' => v::noneOf(
					v::notEmpty(),
					v::notBlank()
				)
			]
		];
	}

	public function getContactPage(Request $request, Response $response, array $args)
	{
		$this->logger->info(sprintf('getContactPage %s value = %s', self::FORM_OBFUSCATION_SESSION_NAME, Session::get(self::FORM_OBFUSCATION_SESSION_NAME)));
		return $this->container->get('view')->render($response, 'contact.twig', [
			'header_space_after' => true,
			'page' => [
				'title' => 'Contact | ' . $this->siteName
			],
			'data' => $this->container->get('flash')->getFirstMessage('data'),
			'config' => Config::getArrayableData(),

			// data for spam check
			'backend' => [
				self::FORM_READY_AT_SESSION_NAME => Session::get(self::FORM_READY_AT_SESSION_NAME),
				self::FORM_OBFUSCATION_SESSION_NAME => Session::get(self::FORM_OBFUSCATION_SESSION_NAME),
			]
		]);
	}

	public function postContactPage(Request $request, Response $response, array $args)
	{
		// obfuscation hash check
		$pathHash = sanitize($args['hash']);
		$sessionHash = Session::get(self::FORM_OBFUSCATION_SESSION_NAME);

		// check if the hashes match
		$this->log('info', __FUNCTION__, 'Checking if the obfuscation hashes matched');
		if ($this->doesNotMatch($pathHash, $sessionHash)) {
			$this->log('error', __FUNCTION__, 'pathHash:' . $pathHash);
			$this->log('error', __FUNCTION__, 'sessionHash:' . $sessionHash);
			$this->log('error', __FUNCTION__, 'Request and session obfuscation hashes did not match');
			$data = [
				'flashes' => [
					[
						'type' => 'error',
						'message' => 'Sorry, but an error with your session occurred. Please refresh your page and try again.'
					]
				]
			];
			$this->log('info', __FUNCTION__, '---END');
			return $this->responseWithFlash($request, $response, $data, 'contact-get', []);
		}
		$this->log('info', __FUNCTION__, 'Request and session obfuscation hashes did match');

		// TIME CHECK
		// if now is not greater than readyAt
		// they submitted the form way to quickly
		$readyAt = new Carbon(Session::get(self::FORM_READY_AT_SESSION_NAME));
		$now = new Carbon;
		$diff = $readyAt->diffInSeconds($now);

		$this->log('info', __FUNCTION__, sprintf('Carbon:now() = %s', $now));
		$this->log('info', __FUNCTION__, sprintf('ReadyAt = %s', $readyAt));
		$this->log('info', __FUNCTION__, sprintf('Diff = %s', $diff));

		// stop execution if the time is bad
		if (!$now->gt($readyAt)) {
			$this->log('error', __FUNCTION__, 'Form was submitted too quickly, very likely a bot request');
			$data = [
				'flashes' => [
					[
						'type' => 'error',
						'message' => 'It seems like you submitted that request way too quickly. You will need to resubmit your contact form request, but not as quickly.'
					]
				]
			];
			$this->log('info', __FUNCTION__, 'Redirecting back to the contact form page with error');
			$this->log('info', __FUNCTION__, '---END');
			return $this->responseWithFlash($request, $response, $data, 'contact-get', []);
		}

		$this->log('info', __FUNCTION__, 'Contact form request was submitted in a reasonable amount of time, unlikely bot');
		$this->log('info', __FUNCTION__, 'Validating the contact form rules');

		// used to indicate if we should block this request IP
		$shouldBlockRequest = false;

		// vanilla validation
		$validation = $this->container->get('validator')->validate($request, $this->rules);
		if ($validation->failed()) {
			$this->log('error', __FUNCTION__, 'Form validation error, but checking for honeypot detection');

			// validation engine check
			if (!empty($validation->errors()[self::SPAM_CHECK_ATTRIBUTE])) {
				$this->log('error', __FUNCTION__, 'Validation failed for ' . self::SPAM_CHECK_ATTRIBUTE . '');
				$shouldBlockRequest = true;
			}

			// check actual param
			if (!isNullOrEmptyString($request->getParsedBody()[self::SPAM_CHECK_ATTRIBUTE])) {
				$this->log('error', __FUNCTION__, 'isNullOrEmptyString = false for ' . self::SPAM_CHECK_ATTRIBUTE . '');
				$shouldBlockRequest = true;
			}

			// create a rejection object for this web request
			if ($shouldBlockRequest) {
				$this->log('info', __FUNCTION__, 'Created a rejection for ' . getRequestAddress());
				// TOOD: Block the IP
			}

			$data = [
				'success' => false,
				'message' => self::FORM_ERROR_VALIDATION,
				'flashes' => [
					[
						'type' => 'error',
						'message' => self::FORM_ERROR_VALIDATION
					]
				]
			];
			$this->log('error', __FUNCTION__, 'Form validation error, redirecting user to contact page');
			$this->log('error', __FUNCTION__, 'JSON payload:');
			$this->log('error', __FUNCTION__, json_encode($validation->errors()));
			return $this->responseWithFlash($request, $response, $data, 'contact-get', []);
		}

		$contactParams = $request->getParsedBody();
		$formArr = [];
		foreach ($contactParams as $paramKey => $paramValue) {
			$formArr[$paramKey] = sanitize($paramValue);
		}

		$this->log('info', __FUNCTION__, 'Contact form data:');
		foreach ($formArr as $paramKey => $paramValue) {
			$this->log('info', __FUNCTION__, "{$paramKey}: " . $paramValue);
		}

		$html = 'You have a new contact request from the contact page:<br><br>';
		foreach ($this->rules as $field => $rules) {
			$this->log('info', __FUNCTION__, 'Field: ' . ucfirst($field) . ' - Value: ' . $formArr[$field]);
			if ($rules['type'] == 'input') {
				$html .= "<strong>" . ucfirst($field) . ":</strong><br><span color='#8592a5' style='color: #8592a5;'>" . $formArr[$field] . "</span><br><br>";
			} else if ($rules['type'] == 'textarea') {
				$html .= "<strong>" . ucfirst($field) . ":</strong><br><span color='#8592a5' style='color: #8592a5;'>" . nl2br($formArr[$field]) . "</span><br><br>";
			}
		}

		$emailArr = [
			'from' => getenv('MAILGUN_FROM'),
			'to' => getenv('CONTACT_FORM_TO'),
			'bcc' => getenv('CONTACT_FORM_BCC'),
			'subject' => 'AdminTools - New Contact Request',
			'text' => strip_tags($html),
			'html' => $html,
		];
		$this->log('info', __FUNCTION__, json_encode($emailArr));
		$notified = $this->container->get('emailEngine')->createEmail($emailArr);

		if ($notified) {
			$this->log('info', __FUNCTION__, 'Email notification sent without error!');
			$this->log('info', __FUNCTION__, 'Contact form data:');
			foreach ($formArr as $paramKey => $paramValue) {
				$this->log('info', __FUNCTION__, "{$paramKey}: " . $paramValue);
			}

			// remove stored data
			$_SESSION['old_params'] = null;

			// return with success message
			$data = [
				'success' => true,
				'message' => self::FORM_MESSAGE_REQUEST_SENT,
				'flashes' => [
					[
						'type' => 'success',
						'message' => self::FORM_MESSAGE_REQUEST_SENT
					]
				]
			];
			$this->log('info', __FUNCTION__, self::FORM_MESSAGE_REQUEST_SENT);
			return $this->responseWithFlash($request, $response, $data, 'contact-get', []);
		}

		// return back to page with error message
		$data = [
			'success' => false,
			'message' => self::FORM_ERROR_UNKNOWN,
			'flashes' => [
				[
					'type' => 'error',
					'message' => self::FORM_ERROR_UNKNOWN
				]
			]
		];
		$this->log('error', __FUNCTION__, 'Error while sending email notification via EmailEngine');
		$this->log('error', __FUNCTION__, json_encode($notified));
		return $this->responseWithFlash($request, $response, $data, 'contact-get', []);
	}

	/**
	 * String comparison to see if the hashes match
	 * @param  string $requestHash the hash provided in the request
	 * @param  string $sessionHash the hash provided in the session
	 * @return bool | if the strings match or not
	 */
	protected function doesNotMatch(string $requestHash, string $sessionHash)
	{
		return $requestHash != $sessionHash;
	}
}
