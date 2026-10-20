<?php

declare(strict_types=1);

use App\Middleware\FormValidation\CheckFormSpam;
use Delight\Cookie\Session;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class CheckFormSpamMiddlewareTest extends TestCase
{
	protected function setUp(): void
	{
		$_SESSION = [];
	}

	public function testReturns400WhenSpamSessionStateIsMissing(): void
	{
		$middleware = new CheckFormSpam(new stdClass());
		$request = (new ServerRequestFactory())->createServerRequest('POST', '/contact/abc');
		$handler = new class implements RequestHandlerInterface {
			public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
			{
				return (new Response())->withStatus(204);
			}
		};

		$result = $middleware($request, $handler);

		self::assertSame(400, $result->getStatusCode());
	}

	public function testDelegatesToHandlerWhenSessionStateExists(): void
	{
		Session::set(CheckFormSpam::FORM_READY_AT_SESSION_NAME, 'ready');
		Session::set(CheckFormSpam::FORM_OBFUSCATION_SESSION_NAME, 'hash');

		$middleware = new CheckFormSpam(new stdClass());
		$request = (new ServerRequestFactory())->createServerRequest('POST', '/contact/abc');
		$handler = new class implements RequestHandlerInterface {
			public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
			{
				return (new Response())->withStatus(204);
			}
		};

		$result = $middleware($request, $handler);

		self::assertSame(204, $result->getStatusCode());
	}
}
