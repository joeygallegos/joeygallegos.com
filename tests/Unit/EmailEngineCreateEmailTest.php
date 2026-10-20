<?php

declare(strict_types=1);

use App\Models\EmailEngine;
use PHPUnit\Framework\TestCase;

final class EmailEngineCreateEmailTest extends TestCase
{
	public function testCreateEmailUsesConfiguredDefaultSender(): void
	{
		$mailgun = new EmailEngineSuccessfulMailgun();
		$engine = $this->buildEmailEngine($mailgun, 'default@example.test');

		$result = $engine->createEmail([
			'to' => 'recipient@example.test',
			'subject' => 'Subject',
			'html' => '<p>Message</p>',
		]);

		self::assertSame('sent', $result);
		self::assertSame('default@example.test', $mailgun->payload['from']);
	}

	public function testCreateEmailUsesExplicitSenderWhenProvided(): void
	{
		$mailgun = new EmailEngineSuccessfulMailgun();
		$engine = $this->buildEmailEngine($mailgun, 'default@example.test');

		$engine->createEmail([
			'from' => 'specific@example.test',
			'to' => 'recipient@example.test',
			'subject' => 'Subject',
			'html' => '<p>Message</p>',
		]);

		self::assertSame('specific@example.test', $mailgun->payload['from']);
	}

	public function testCreateEmailRejectsEachMissingRequiredParameterBeforeSending(): void
	{
		$completeParameters = [
			'to' => 'recipient@example.test',
			'subject' => 'Subject',
			'html' => '<p>Message</p>',
		];

		foreach (array_keys($completeParameters) as $missingParameter) {
			$mailgun = new EmailEngineSuccessfulMailgun();
			$engine = $this->buildEmailEngine($mailgun, 'default@example.test');
			$parameters = $completeParameters;
			unset($parameters[$missingParameter]);

			try {
				$engine->createEmail($parameters);
				self::fail("Expected {$missingParameter} to be required");
			} catch (InvalidArgumentException $exception) {
				self::assertStringContainsString($missingParameter, $exception->getMessage());
				self::assertSame([], $mailgun->payload);
			}
		}
	}

	public function testCreateEmailRejectsMissingExplicitAndConfiguredSender(): void
	{
		$mailgun = new EmailEngineSuccessfulMailgun();
		$engine = $this->buildEmailEngine($mailgun, '');

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('from');

		$engine->createEmail([
			'to' => 'recipient@example.test',
			'subject' => 'Subject',
			'html' => '<p>Message</p>',
		]);
	}

	private function buildEmailEngine(EmailEngineSuccessfulMailgun $mailgun, string $fromEmail): EmailEngine
	{
		$reflection = new ReflectionClass(EmailEngine::class);
		/** @var EmailEngine $engine */
		$engine = $reflection->newInstanceWithoutConstructor();

		foreach (['privateMailgun' => $mailgun, 'domain' => 'example.test', 'fromEmail' => $fromEmail] as $propertyName => $value) {
			$property = $reflection->getProperty($propertyName);
			$property->setValue($engine, $value);
		}

		return $engine;
	}
}

final class EmailEngineSuccessfulMailgun
{
	public array $payload = [];

	public function messages(): self
	{
		return $this;
	}

	public function send(string $domain, array $payload): string
	{
		$this->payload = $payload;
		return 'sent';
	}
}
