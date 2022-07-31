<?php
namespace App\Models;
use App\Models\Config;
use Mailgun\Mailgun;
class EmailEngine {

	public $publicMailgun;
	public $privateMailgun;
	public $domain;
	public $emailEngineLogger;

	public function __construct($publicKey, $privateKey, $domain, $fromEmail, $emailEngineLogger) {
		$this->publicMailgun = new Mailgun($publicKey);
		$this->privateMailgun = new Mailgun($privateKey);
		$this->domain = $domain;
		$this->fromEmail = $fromEmail;
		$this->emailEngineLogger = $emailEngineLogger;
	}

	/**
	 * Returns a private instance of the mailgun endpoint API
	 * @return Mailgun Object
	 */
	public function getPrivateMailgunInstance() {
		return $this->privateMailgun;
	}

	/**
	 * Returns a public instance of the mailgun endpoint API
	 * @return Mailgun Object
	 */
	public function getPublicMailgunInstance() {
		return $this->publicMailgun;
	}

	/**
	 * Get domain name
	 * @return String domain for mailgun
	 */
	public function getDomain() {
		return $this->domain;
	}

	/**
	 * Get from email
	 * @return String from email for mailgun
	 */
	public function getFromEmail() {
		return $this->fromEmail;
	}

	/**
	 * Get unsubscribes
	 * @return Array email unsubscribes
	 */
	public function getUnsubscribes() {
		return $this->privateMailgun->get($this->domain . '/unsubscribes');
	}

	/**
	 * Get bounces
	 * @return Array email bounces
	 */
	public function getBounces() {
		return $this->privateMailgun->get($this->domain . '/bounces');
	}

	/**
	 * Get complaints
	 * @return Array email complaints
	 */
	public function getComplaints() {
		return $this->privateMailgun->get($this->domain . '/complaints');
	}

	/**
	 * Get events
	 * @link https://documentation.mailgun.com/en/latest/api-events.html#examples
	 * @return Array events
	 */
	public function getEvents($query = []) {
		if (empty($query)) return null;
		return $this->privateMailgun->get($this->domain . '/events', $query);
	}

	/**
	 * Validate that an email address is a valid email.
	 * @param  Mailgun  $mailgun Mailgun instance
	 * @param  string  $email Email address to be checked
	 * @return boolean If email is valid
	 */
	public function isValidEmail($email = '', $container) {
		$this->emailEngineLogger->info("EmailEngine>> Checking email address: {$email}");

		// basic sanity check
		$emailPreCheck = !filter_var($email, FILTER_VALIDATE_EMAIL) === false;
		if ($emailPreCheck) {
			$this->emailEngineLogger->info('EmailEngine>> Email matches regex check.. Testing with Mailgun..');
		}

		// if no external checking
		if (!Config::get('external_validation', false) && $emailPreCheck) {
			$this->emailEngineLogger->info('EmailEngine>> External validation disabled, email passed');
			return $emailPreCheck;
		}

		// try the external validation
		try {
			$result = $this->publicMailgun->get('address/validate', ['address' => $email]);
			$this->emailEngineLogger->info("EmailEngine>> API response: " . var_export($result, true));

			$valid = $result->http_response_body->is_valid > 0;
			$this->emailEngineLogger->info("EmailEngine>> API.http_response_body.is_valid=" . ($valid ? "true" : "false") . "");

			if ($emailPreCheck && $valid) {
				$this->emailEngineLogger->info('EmailEngine>> Email pre-check and API check both passed');
			}

			if ($emailPreCheck) {
				if (!$valid) {
					$this->emailEngineLogger->info('EmailEngine>> Email pre-check passed but API check did not pass');
				}
			}

			return $valid;
		} catch(Exception $e) {
			$this->emailEngineLogger->error("EmailEngine>> Failure while checking email: {$email}");
			$this->emailEngineLogger->error("EmailEngine>> {$e->getMessage()}");
			$this->emailEngineLogger->error('EmailEngine>> Using normal means to check email..');

			return $emailPreCheck;
		}
	}

	/**
	 * Validate that an email address is a valid email.
	 * @param  Mailgun  $mailgun Mailgun instance
	 * @param  string  $email Email address to be checked
	 * @return boolean If email is valid
	 */
	public function isDisposableEmail($email = '', $container) {
		if (!Config::get('external_validation', false)) {
			return false;
		}
		
		$this->emailEngineLogger->info("EmailEngine>> Checking if disposable email: {$email}");
		try {
			$result = $this->publicMailgun->get("address/validate", ['address' => $email]);
			$this->emailEngineLogger->info("EmailEngine>> API response: " . var_export($result, true));

			$valid = $result->http_response_body->is_disposable_address > 0;
			$this->emailEngineLogger->info("EmailEngine>> API.http_response_body.is_disposable_address=" . ($valid ? "true" : "false") . "");
			return $valid;
		} catch(Exception $e) {
			$this->emailEngineLogger->error("EmailEngine>> Failure while checking email: {$email}");
			$this->emailEngineLogger->error("EmailEngine>> {$e->getMessage()}");
			return false;
		}
	}

	/**
	 * Create and send an email to a user
	 * @param  array $parameters settings for email
	 */
	public function createEmail($parameters) {
		$to = $parameters['to'];
		$bcc = $parameters['bcc'];
		$from = $parameters['from'];
		$replyTo = $parameters['reply-to'];
		$subject = $parameters['subject'];
		$text = $parameters['text'];
		$html = $parameters['html'];

		if (!isset($parameters['from'])) {
			$parameters['from'] = getenv('ADMINTOOLS_FROM');
		}

		$response = false;
		try {
			$response = $this->getPrivateMailgunInstance()->sendMessage($this->getDomain(), [
				'from' => $from,
				'to' => $to,
				'bcc' => $bcc,
				'h:reply-to' => $replyTo,
				'subject' => $subject,
				'text' => $text,
				'html' => $html
			]);
		} catch (\RuntimeException $e) {
			$this->emailEngineLogger->error('EmailEngine>> RuntimeException thrown');
			$this->emailEngineLogger->error("EmailEngine>> {$e->getMessage()}");
			$this->emailEngineLogger->error("EmailEngine>> " . json_encode($parameters));
		}
		return $response;
	}
}