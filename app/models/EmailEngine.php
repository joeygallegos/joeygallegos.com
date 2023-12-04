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
		return $this->privateMailgun->get($this->domain . '/unsubscribes');
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
		return $this->privateMailgun->get($this->domain . '/events', $query);
	}

	/**
	 * Validate that an email address is a valid email.
	 * @param  Mailgun  $mailgun Mailgun instance
	 * @param  string  $email Email address to be checked
	 * @return boolean If email is valid
	 */
	public function isValidEmail(string $email = '')
	{
		$this->logger->info("EmailEngine > Checking email address: {$email}");

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
			$result = $this->publicMailgun->get('address/validate', ['address' => $email]);
			$this->logger->info("EmailEngine > API response: " . var_export($result, true));

			$valid = $result->http_response_body->is_valid > 0;
			$this->logger->info("EmailEngine > API.http_response_body.is_valid=" . ($valid ? "true" : "false") . "");

			if ($emailPreCheck && $valid) {
				$this->logger->info('EmailEngine > Email pre-check and API check both passed');
			}

			if ($emailPreCheck) {
				if (!$valid) {
					$this->logger->info('EmailEngine > Email pre-check passed but API check did not pass');
				}
			}

			return $valid;
		} catch (\Exception $e) {
			$this->logger->error("EmailEngine > Failure while checking email: {$email}");
			$this->logger->error("EmailEngine > {$e->getMessage()}");
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

		$this->logger->info("EmailEngine > Checking if disposable email: {$email}");
		try {
			$result = $this->publicMailgun->get("address/validate", ['address' => $email]);
			$this->logger->info("EmailEngine > API response: " . var_export($result, true));

			$valid = $result->http_response_body->is_disposable_address > 0;
			$this->logger->info("EmailEngine > API.http_response_body.is_disposable_address=" . ($valid ? "true" : "false") . "");
			return $valid;
		} catch (\Exception $e) {
			$this->logger->error("EmailEngine > Failure while checking email: {$email}");
			$this->logger->error("EmailEngine > {$e->getMessage()}");
			return false;
		}
	}

	/**
	 * Create and send an email to a user
	 * @param  array $parameters settings for email
	 */
	public function createEmail($parameters = [])
	{

		$messagePayload = [];
		$to = $parameters['to'];
		$from = $parameters['from'];
		$subject = $parameters['subject'];
		$html = $parameters['html'];

		// if specific from not given, supply one
		if (!isset($parameters['from'])) {
			$parameters['from'] = $_ENV['ADMINTOOLS_FROM'];
		}

		// usually by default the email has all these
		$messagePayload = [
			'from' => $from,
			'to' => $to,
			'subject' => $subject,
			'html' => $html
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
		} catch (\RuntimeException $e) {
			$this->logger->error('EmailEngine > RuntimeException thrown');
			$this->logger->error("EmailEngine > {$e->getMessage()}");
			$this->logger->error("EmailEngine > " . json_encode($parameters));
		}
		return $response;
	}
}
