<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HomeControllerLoggingTest extends TestCase
{
	public function testSpotifyLoggingDoesNotReferenceServiceStateOrExceptionDetails(): void
	{
		$sourceLines = file(dirname(__DIR__, 2) . '/app/controllers/HomeController.php');
		self::assertNotFalse($sourceLines);

		$loggingLines = array_filter($sourceLines, static fn (string $line): bool => str_contains($line, '->logger->'));
		$loggingSource = implode('', $loggingLines);

		// The service array contains an authenticated Spotify session; exceptions may echo request details.
		foreach (['$spotify', 'json_encode(', 'getMessage()'] as $unsafeExpression) {
			self::assertStringNotContainsString($unsafeExpression, $loggingSource);
		}
		self::assertStringContainsString('Spotify current-track data unavailable', $loggingSource);
	}
}
