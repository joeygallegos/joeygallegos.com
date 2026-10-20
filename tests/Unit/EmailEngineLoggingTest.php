<?php

declare(strict_types=1);

use App\Models\EmailEngine;
use PHPUnit\Framework\TestCase;

final class EmailEngineLoggingTest extends TestCase
{
	public function testDeliveryFailureDoesNotLogPayloadOrProviderException(): void
	{
		$logger = new EmailEngineRecordingLogger();
		$mailgun = new EmailEngineFailingMailgun();
		$engine = $this->buildEmailEngine($logger, $mailgun);
		$parameters = [
			'from' => 'sender-secret@example.test',
			'to' => 'recipient-secret@example.test',
			'bcc' => 'bcc-secret@example.test',
			'subject' => 'Private subject',
			'html' => '<p>Private message body</p>',
			'text' => 'Private message body',
		];

		$result = $engine->createEmail($parameters);

		self::assertFalse($result);
		self::assertSame($parameters['to'], $mailgun->payload['to']);
		self::assertSame($parameters['html'], $mailgun->payload['html']);
		self::assertStringContainsString('Email delivery request failed', $logger->messagesAsText());

		foreach (array_values($parameters) as $sensitiveValue) {
			self::assertStringNotContainsString($sensitiveValue, $logger->messagesAsText());
		}
		self::assertStringNotContainsString(EmailEngineFailingMailgun::PRIVATE_PROVIDER_DETAIL, $logger->messagesAsText());
	}

	public function testEmailEngineLoggingCallsDoNotReferenceSensitiveValues(): void
	{
		$sourceLines = file(dirname(__DIR__, 2) . '/app/models/EmailEngine.php');
		self::assertNotFalse($sourceLines);

		$loggingLines = array_filter($sourceLines, static fn (string $line): bool => str_contains($line, '->logger->'));
		$loggingSource = implode('', $loggingLines);

		// Provider responses and exceptions can echo addresses, payloads, credentials, or remote diagnostics.
		foreach (['$email', 'var_export(', 'getMessage()', 'json_encode($parameters)'] as $unsafeExpression) {
			self::assertStringNotContainsString($unsafeExpression, $loggingSource);
		}
	}

	private function buildEmailEngine(EmailEngineRecordingLogger $logger, EmailEngineFailingMailgun $mailgun): EmailEngine
	{
		$reflection = new ReflectionClass(EmailEngine::class);
		/** @var EmailEngine $engine */
		$engine = $reflection->newInstanceWithoutConstructor();

		foreach (['logger' => $logger, 'privateMailgun' => $mailgun, 'domain' => 'mailgun-secret.example.test'] as $propertyName => $value) {
			$property = $reflection->getProperty($propertyName);
			$property->setValue($engine, $value);
		}

		return $engine;
	}
}

final class EmailEngineRecordingLogger
{
	public array $messages = [];

	public function error(string $message): void
	{
		$this->messages[] = $message;
	}

	public function messagesAsText(): string
	{
		return implode("\n", $this->messages);
	}
}

final class EmailEngineFailingMailgun
{
	public const PRIVATE_PROVIDER_DETAIL = 'provider diagnostic with api-token-secret';
	public array $payload = [];

	public function messages(): self
	{
		return $this;
	}

	public function send(string $domain, array $payload): void
	{
		$this->payload = $payload;
		throw new RuntimeException(self::PRIVATE_PROVIDER_DETAIL);
	}
}
