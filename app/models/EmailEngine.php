<?php

namespace App\Models;

use App\Models\Config;
use Mailgun\Mailgun;

class EmailEngine
{

	private $publicMailgun;
	private $privateMailgun;
	private $domain;
	private $fromEmail;
	private $logger;

	public function __construct(string $publicKey, string $privateKey, string $domain, string $fromEmail, $logger)
	{
		$this->publicMailgun = Mailgun::create($publicKey);
		$this->privateMailgun = Mailgun::create($privateKey);
		$this->domain = $domain;
		$this->fromEmail = $fromEmail;
		$this->logger = $logger;
	}

	/**
	 * Returns a private instance of the mailgun endpoint API
	 * @return Mailgun Object
	 */
	public function getPrivateMailgunInstance()
	{
		return $this->privateMailgun;
	}

	/**
	 * Returns a public instance of the mailgun endpoint API
	 * @return Mailgun Object
	 */
	public function getPublicMailgunInstance()
	{
		return $this->publicMailgun;
	}

	/**
	 * Get domain name
	 * @return String domain for mailgun
	 */
	public function getDomain()
	{
		return $this->domain;
	}

	/**
	 * Get from email
	 * @return String from email for mailgun
	 */
	public function getFromEmail()
	{
		return $this->fromEmail;
	}

	/**
	 * Get unsubscribes
	 * @return Array email unsubscribes
	 */
	public function getUnsubscribes()
	{
		return $this->privateMailgun->suppressions()->unsubscribes()->index($this->domain);
	}

	/**
	 * Get bounces
	 * @return Array email bounces
	 */
	public function getBounces()
	{
		// return $this->privateMailgun->get($this->domain . '/bounces');
		return $this->privateMailgun->suppressions()->bounces()->index($this->domain);
	}

	/**
	 * Get complaints
	 * @return Array email complaints
	 */
	public function getComplaints()
	{
		return $this->privateMailgun->suppressions()->complaints()->index($this->domain);
	}

	/**
	 * Get events
	 * @link https://documentation.mailgun.com/en/latest/api-events.html#examples
	 * @return Array events
	 */
	public function getEvents($query = [])
	{
		if (empty($query)) return null;
		return $this->privateMailgun->events()->get($this->domain, $query);
	}

	/**
	 * Validate that an email address is a valid email.
	 * @param  Mailgun  $mailgun Mailgun instance
	 * @param  string  $email Email address to be checked
	 * @return boolean If email is valid
	 */
	public function isValidEmail(string $email = '')
	{
		$this->logger->info('EmailEngine > Checking email address');

		// basic sanity check
		$emailPreCheck = !filter_var($email, FILTER_VALIDATE_EMAIL) === false;
		if ($emailPreCheck) {
			$this->logger->info('EmailEngine > Email matches regex check.. Testing with Mailgun..');
		}

		// if no external checking
		if (!Config::get('external_validation', false) && $emailPreCheck) {
			$this->logger->info('EmailEngine > External validation disabled, email passed');
			return $emailPreCheck;
		}

		// try the external validation
		try {
			$result = $this->publicMailgun->emailValidationV4()->validate($email);

			$valid = $result->getResult() === 'deliverable';
			$this->logger->info('EmailEngine > Email validation API check ' . ($valid ? 'passed' : 'failed'));

			if ($emailPreCheck && $valid) {
				$this->logger->info('EmailEngine > Email pre-check and API check both passed');
			}

			if ($emailPreCheck) {
				if (!$valid) {
					$this->logger->info('EmailEngine > Email pre-check passed but API check did not pass');
				}
			}

			return $valid;
		} catch (\Exception) {
			// Provider errors may echo the submitted address or credentials, so retain only the fallback outcome.
			$this->logger->error('EmailEngine > Email validation API request failed');
			$this->logger->error('EmailEngine > Using normal means to check email..');

			return $emailPreCheck;
		}
	}

	/**
	 * Validate that an email address is a valid email.
	 * @param  Mailgun  $mailgun Mailgun instance
	 * @param  string  $email Email address to be checked
	 * @return boolean If email is valid
	 */
	public function isDisposableEmail($email = '')
	{
		if (!Config::get('external_validation', false)) {
			return false;
		}

		$this->logger->info('EmailEngine > Checking if email address is disposable');
		try {
			$result = $this->publicMailgun->emailValidationV4()->validate($email);

			$valid = $result->isDisposableAddress();
			$this->logger->info('EmailEngine > Disposable-email API check ' . ($valid ? 'matched' : 'did not match'));
			return $valid;
		} catch (\Exception) {
			// Keep enough information to alert operators without persisting the address or provider response.
			$this->logger->error('EmailEngine > Disposable-email API request failed');
			return false;
		}
	}

	/**
	 * Create and send an email to a user
	 * @param  array $parameters settings for email
	 */
	public function createEmail($parameters = [])
	{
		// Validate before indexing the payload so malformed internal calls fail clearly instead of emitting PHP notices.
		foreach (['to', 'subject', 'html'] as $requiredParameter) {
			if (!isset($parameters[$requiredParameter])) {
				throw new \InvalidArgumentException("Missing required email parameter: {$requiredParameter}");
			}
		}

		// Callers may override the configured sender for legitimate reply workflows, but most mail uses the service default.
		$from = $parameters['from'] ?? $this->getFromEmail();
		if ($from === '') {
			throw new \InvalidArgumentException('Missing required email parameter: from');
		}

		// usually by default the email has all these
		$messagePayload = [
			'from' => $from,
			'to' => $parameters['to'],
			'subject' => $parameters['subject'],
			'html' => $parameters['html']
		];

		// TODO: Cleanup these checks
		if (isset($parameters['bcc'])) {
			$messagePayload['bcc'] = $parameters['bcc'];
		}
		if (isset($parameters['text'])) {
			$messagePayload['text'] = $parameters['text'];
		}

		// must add "h:reply-to" to signify a header
		if (isset($parameters['reply-to'])) {
			$messagePayload['h:reply-to'] = $parameters['reply-to'];
		}

		// check for attachment
		if (isset($parameters['attachment'])) {
			$messagePayload['attachment'] = $parameters['attachment'];
		}

		$response = false;
		try {
			$response = $this->getPrivateMailgunInstance()->messages()->send($this->getDomain(), $messagePayload);
		} catch (\RuntimeException) {
			// Delivery exceptions and parameters can include recipients, message bodies, and provider secrets.
			$this->logger->error('EmailEngine > Email delivery request failed');
		}
		return $response;
	}
}
