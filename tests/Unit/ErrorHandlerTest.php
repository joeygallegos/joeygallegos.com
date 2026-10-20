<?php

declare(strict_types=1);

use App\Middleware\ErrorHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Views\Twig;

final class ErrorHandlerTest extends TestCase
{
	public function testUnknownRouteUsesBrandedNotFoundPage(): void
	{
		$response = $this->buildApp()->handle($this->request('GET', '/missing'));

		self::assertSame(404, $response->getStatusCode());
		self::assertSame('text/html; charset=UTF-8', $response->getHeaderLine('Content-Type'));
		self::assertStringContainsString('<h1>Page not found</h1>', (string)$response->getBody());
		self::assertStringContainsString('Go to homepage', (string)$response->getBody());
	}

	public function testUnsupportedMethodPreservesStatusAndAllowHeader(): void
	{
		$app = $this->buildApp();
		$app->get('/resource', fn ($request, ResponseInterface $response): ResponseInterface => $response);

		$response = $app->handle($this->request('POST', '/resource'));

		self::assertSame(405, $response->getStatusCode());
		self::assertStringContainsString('GET', $response->getHeaderLine('Allow'));
		self::assertStringContainsString("<h1>That action isn't available</h1>", (string)$response->getBody());
	}

	public function testUnhandledExceptionUsesSafeServerErrorPage(): void
	{
		$app = $this->buildApp();
		$app->get('/failure', function (): never {
			throw new RuntimeException('secret-database-password');
		});

		$response = $app->handle($this->request('GET', '/failure'));
		$body = (string)$response->getBody();

		self::assertSame(500, $response->getStatusCode());
		self::assertStringContainsString('<h1>Something went wrong</h1>', $body);
		self::assertStringNotContainsString('secret-database-password', $body);
		self::assertStringNotContainsString('RuntimeException', $body);
	}

	public function testTwigFailureReturnsStaticSafeServerError(): void
	{
		// The directory exists but intentionally contains none of the error templates.
		$app = $this->buildApp(dirname(__DIR__));
		$app->get('/failure', function (): never {
			throw new RuntimeException('secret-template-context');
		});

		$response = $app->handle($this->request('GET', '/failure'));
		$body = (string)$response->getBody();

		self::assertSame(500, $response->getStatusCode());
		self::assertSame('text/html; charset=UTF-8', $response->getHeaderLine('Content-Type'));
		self::assertStringContainsString('<h1>Something went wrong</h1>', $body);
		self::assertStringContainsString('Go to homepage', $body);
		self::assertStringNotContainsString('secret-template-context', $body);
	}

	private function buildApp(?string $templatePath = null): \Slim\App
	{
		$app = AppFactory::create();
		$app->addRoutingMiddleware();
		$errorMiddleware = $app->addErrorMiddleware(false, false, false);
		$errorMiddleware->setDefaultErrorHandler(new ErrorHandler(
			Twig::create($templatePath ?? dirname(__DIR__, 2) . '/app/views'),
			$app->getResponseFactory()
		));

		return $app;
	}

	private function request(string $method, string $path): \Psr\Http\Message\ServerRequestInterface
	{
		return (new ServerRequestFactory())->createServerRequest($method, $path);
	}
}
