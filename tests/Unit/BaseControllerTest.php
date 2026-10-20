<?php

declare(strict_types=1);

use App\Controllers\BaseController;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class BaseControllerTest extends TestCase
{
	protected function setUp(): void
	{
		$_SESSION = [];
	}

	public function testResponseWithJsonWritesBodyAndStatus(): void
	{
		$controller = new class extends BaseController {
			public function __construct()
			{
				$this->container = new DI\Container();
			}
		};

		$request = (new ServerRequestFactory())->createServerRequest('GET', '/');
		$response = new Response();
		$result = $controller->responseWithJson($request, $response, ['ok' => true], 201);

		self::assertSame(201, $result->getStatusCode());
		self::assertSame('application/json', $result->getHeaderLine('Content-Type'));
		self::assertStringContainsString('"ok": true', (string)$result->getBody());
	}

	public function testRedirectExternalSetsLocationHeader(): void
	{
		$controller = new class extends BaseController {
			public function __construct()
			{
				$this->container = new DI\Container();
			}
		};

		$response = new Response();
		$result = $controller->redirectExternal($response, 'https://example.com');

		self::assertSame(302, $result->getStatusCode());
		self::assertSame('https://example.com', $result->getHeaderLine('Location'));
	}

	public function testGetCookieValueReturnsExpectedCookie(): void
	{
		$controller = new class extends BaseController {
			public function __construct()
			{
				$this->container = new DI\Container();
			}
		};

		$request = (new ServerRequestFactory())
			->createServerRequest('GET', '/')
			->withCookieParams(['token' => 'abc123']);

		self::assertSame('abc123', $controller->getCookieValue($request, 'token'));
		self::assertNull($controller->getCookieValue($request, 'missing'));
	}
}
