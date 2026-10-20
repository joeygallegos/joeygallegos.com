<?php

declare(strict_types=1);

use App\Controllers\SiteListenerController;
use DI\Container;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class SiteListenerControllerTest extends TestCase
{
	private const VALID_TOKEN = 'configured-listener-secret';

	private string|false $originalTokens;

	protected function setUp(): void
	{
		$this->originalTokens = getenv('SITE_LISTENER_TOKENS');
		putenv('SITE_LISTENER_TOKENS=' . self::VALID_TOKEN . ', secondary-secret ');
	}

	protected function tearDown(): void
	{
		$this->originalTokens === false
			? putenv('SITE_LISTENER_TOKENS')
			: putenv('SITE_LISTENER_TOKENS=' . $this->originalTokens);
	}

	#[DataProvider('unauthorizedRequestProvider')]
	public function testReturnsGeneric401ForAuthenticationFailures(?string $authorization, array $queryParams = []): void
	{
		$request = (new ServerRequestFactory())
			->createServerRequest('GET', '/api/listen')
			->withQueryParams($queryParams);
		if ($authorization !== null) {
			$request = $request->withHeader('Authorization', $authorization);
		}

		$result = (new SiteListenerController(new Container()))
			->getResponseFromSite($request, new Response(), []);

		self::assertSame(401, $result->getStatusCode());
		self::assertSame('application/json', $result->getHeaderLine('Content-Type'));
		self::assertSame('Bearer', $result->getHeaderLine('WWW-Authenticate'));
		self::assertSame([
			'error' => [
				'code' => 401,
				'message' => 'Authentication required',
			],
		], json_decode((string)$result->getBody(), true));
	}

	public static function unauthorizedRequestProvider(): array
	{
		return [
			'missing header' => [null],
			'malformed scheme' => ['Basic ' . self::VALID_TOKEN],
			'missing credential' => ['Bearer'],
			'multiple credentials' => ['Bearer ' . self::VALID_TOKEN . ',other'],
			'invalid credential' => ['Bearer invalid-listener-secret'],
			'query credential is ignored' => [null, ['token' => self::VALID_TOKEN]],
		];
	}

	public function testReturns503WhenTokenConfigurationIsMissingOrEmpty(): void
	{
		$controller = new SiteListenerController(new Container());
		$request = (new ServerRequestFactory())
			->createServerRequest('GET', '/api/listen')
			->withHeader('Authorization', 'Bearer ' . self::VALID_TOKEN);

		foreach ([false, '', ' , '] as $configuration) {
			$configuration === false
				? putenv('SITE_LISTENER_TOKENS')
				: putenv('SITE_LISTENER_TOKENS=' . $configuration);

			$result = $controller->getResponseFromSite($request, new Response(), []);

			self::assertSame(503, $result->getStatusCode());
			self::assertSame('application/json', $result->getHeaderLine('Content-Type'));
			self::assertSame([
				'error' => [
					'code' => 503,
					'message' => 'The site listener is unavailable',
				],
			], json_decode((string)$result->getBody(), true));
		}
	}

	public function testReturnsGenericSuccessWithoutExposingConfiguredCredentials(): void
	{
		$request = (new ServerRequestFactory())
			->createServerRequest('GET', '/api/listen')
			->withHeader('Authorization', 'Bearer secondary-secret');

		$result = (new SiteListenerController(new Container()))
			->getResponseFromSite($request, new Response(), []);
		$body = (string)$result->getBody();

		self::assertSame(200, $result->getStatusCode());
		self::assertSame('application/json', $result->getHeaderLine('Content-Type'));
		self::assertSame([
			'success' => [
				'code' => 200,
				'message' => 'The site listener is available',
			],
		], json_decode($body, true));
		self::assertStringNotContainsString(self::VALID_TOKEN, $body);
		self::assertStringNotContainsString('secondary-secret', $body);
	}
}
