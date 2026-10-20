<?php

declare(strict_types=1);

use App\Models\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
	protected function setUp(): void
	{
		ConfigTestDouble::$entry = null;
	}

	public function testUpdateSectionThrowsForMissingEntryWithoutLogger(): void
	{
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('No entry in the database found');

		ConfigTestDouble::updateSection('missing', 'value');
	}

	public function testUpdateRawThrowsForMissingEntryWithoutLogger(): void
	{
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('No entry in the database found');

		ConfigTestDouble::updateRaw('missing', 'value');
	}

	public function testGetRawReturnsDefaultWhenEntryIsMissingOrEmpty(): void
	{
		self::assertSame('fallback', ConfigTestDouble::getRaw('missing', 'fallback'));

		ConfigTestDouble::$entry = new ConfigTestRecord('setting', '');
		self::assertSame('fallback', ConfigTestDouble::getRaw('setting', 'fallback'));

		ConfigTestDouble::$entry = new ConfigTestRecord('setting', 'configured');
		self::assertSame('configured', ConfigTestDouble::getRaw('setting', 'fallback'));
	}

	public function testUpdateLogsOnlyTheConfigurationKey(): void
	{
		$logger = new ConfigRecordingLogger();
		$entry = new ConfigTestRecord('spotify_access_token', 'old-secret-token');
		ConfigTestDouble::$entry = $entry;

		self::assertTrue(ConfigTestDouble::updateSection('spotify_access_token', 'new-secret-token', $logger));
		self::assertSame('new-secret-token', $entry->configuration_value);
		self::assertTrue($entry->saved);
		self::assertStringContainsString('spotify_access_token', $logger->messagesAsText());
		self::assertStringNotContainsString('old-secret-token', $logger->messagesAsText());
		self::assertStringNotContainsString('new-secret-token', $logger->messagesAsText());
	}

	public function testUpdateRawAlsoKeepsValuesOutOfTheLog(): void
	{
		$logger = new ConfigRecordingLogger();
		ConfigTestDouble::$entry = new ConfigTestRecord('spotify_refresh_token', 'old-refresh-secret');

		self::assertTrue(ConfigTestDouble::updateRaw('spotify_refresh_token', 'new-refresh-secret', $logger));
		self::assertStringContainsString('spotify_refresh_token', $logger->messagesAsText());
		self::assertStringNotContainsString('old-refresh-secret', $logger->messagesAsText());
		self::assertStringNotContainsString('new-refresh-secret', $logger->messagesAsText());
	}
}

final class ConfigTestDouble extends Config
{
	public static ?ConfigTestRecord $entry = null;

	protected static function findByKey($key)
	{
		return self::$entry;
	}
}

final class ConfigTestRecord
{
	public bool $saved = false;

	public function __construct(
		public string $configuration_key,
		public mixed $configuration_value
	) {
	}

	public function save(): bool
	{
		$this->saved = true;
		return true;
	}
}

final class ConfigRecordingLogger
{
	public array $messages = [];

	public function info(string $message): void
	{
		$this->messages[] = $message;
	}

	public function error(string $message): void
	{
		$this->messages[] = $message;
	}

	public function messagesAsText(): string
	{
		return implode("\n", $this->messages);
	}
}
