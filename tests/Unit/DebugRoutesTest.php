<?php

declare(strict_types=1);

use DI\Container;
use PHPUnit\Framework\TestCase;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

final class DebugRoutesTest extends TestCase
{
	private string|false $originalEnvironment;

	protected function setUp(): void
	{
		$this->originalEnvironment = getenv('ENVIRONMENT');
	}

	protected function tearDown(): void
	{
		if ($this->originalEnvironment === false) {
			putenv('ENVIRONMENT');
		} else {
			putenv('ENVIRONMENT=' . $this->originalEnvironment);
		}
	}

	public function testTestRouteRespondsInAssembly(): void
	{
		$app = $this->buildApp('assembly');
		$request = (new ServerRequestFactory())->createServerRequest('GET', '/test/');

		$response = $app->handle($request);

		self::assertSame(200, $response->getStatusCode());
		self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
		self::assertTrue(json_decode((string)$response->getBody(), true)['success']);
	}

	public function testDebugRoutesAreRegisteredInStaging(): void
	{
		$app = $this->buildApp('staging');

		self::assertSame('/debug/', $app->getRouteCollector()->getNamedRoute('debug')->getPattern());
		self::assertSame('/test/', $app->getRouteCollector()->getNamedRoute('test')->getPattern());
	}

	public function testDebugRoutesReturnNotFoundInProduction(): void
	{
		$app = $this->buildApp('production');

		foreach (['/debug/', '/test/'] as $path) {
			$request = (new ServerRequestFactory())->createServerRequest('GET', $path);
			$response = $app->handle($request);

			self::assertSame(404, $response->getStatusCode(), $path);
		}
	}

	private function buildApp(string $environment): \Slim\App
	{
		putenv('ENVIRONMENT=' . $environment);

		$container = new Container();
		$logger = new class {
			public function info(string $message): void {}
		};
		$container->set('debugLogger', $logger);
		$container->set('contactFormLogger', $logger);
		$container->set('randomGenerator', new class {
			public function generateString(int $length, string $alphabet): string
			{
				return str_repeat($alphabet[0], $length);
			}
		});

		AppFactory::setContainer($container);
		$app = AppFactory::create();
		$app->addRoutingMiddleware();
		$app->addErrorMiddleware(false, false, false);

		require dirname(__DIR__, 2) . '/app/routes.php';

		return $app;
	}
}
