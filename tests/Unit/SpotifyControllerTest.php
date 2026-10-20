<?php

declare(strict_types=1);

use App\Controllers\SpotifyController;
use DI\Container;
use PHPUnit\Framework\TestCase;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

final class SpotifyControllerTest extends TestCase
{
	private string|false $originalEnvironment;
	private string|false $originalEnabled;
	private string|false $originalUsername;
	private string|false $originalPasswordHash;

	protected function setUp(): void
	{
		$this->originalEnvironment = getenv('ENVIRONMENT');
		$this->originalEnabled = getenv('SPOTIFY_AUTH_ENABLED');
		$this->originalUsername = getenv('SPOTIFY_AUTH_USERNAME');
		$this->originalPasswordHash = getenv('SPOTIFY_AUTH_PASSWORD_HASH');
		putenv('SPOTIFY_AUTH_USERNAME=operator');
		putenv('SPOTIFY_AUTH_PASSWORD_HASH=' . password_hash('correct password', PASSWORD_DEFAULT));
		unset($_SESSION['spotify_oauth_state']);
	}

	protected function tearDown(): void
	{
		$this->restoreEnvironment('ENVIRONMENT', $this->originalEnvironment);
		$this->restoreEnvironment('SPOTIFY_AUTH_ENABLED', $this->originalEnabled);
		$this->restoreEnvironment('SPOTIFY_AUTH_USERNAME', $this->originalUsername);
		$this->restoreEnvironment('SPOTIFY_AUTH_PASSWORD_HASH', $this->originalPasswordHash);
		unset($_SESSION['spotify_oauth_state']);
	}

	public function testSpotifyRouteIsNotRegisteredInProduction(): void
	{
		$app = $this->buildApp('production', 'true');

		$response = $app->handle((new ServerRequestFactory())->createServerRequest('GET', '/spotify'));

		self::assertSame(404, $response->getStatusCode());
	}

	public function testSpotifyRouteIsNotRegisteredWithoutExplicitOptIn(): void
	{
		$app = $this->buildApp('staging', 'false');

		$response = $app->handle((new ServerRequestFactory())->createServerRequest('GET', '/spotify'));

		self::assertSame(404, $response->getStatusCode());
	}

	public function testCallbackRejectsMismatchedStateAndConsumesStoredState(): void
	{
		$_SESSION['spotify_oauth_state'] = $this->pendingState('expected-state');
		$controller = $this->buildController(new SpotifySessionDouble());
		$request = (new ServerRequestFactory())
			->createServerRequest('GET', 'https://example.test/spotify')
			->withQueryParams(['code' => 'authorization-code', 'state' => 'wrong-state']);

		$response = $controller->getSpotifyAuth($request, (new ResponseFactory())->createResponse(), []);

		self::assertSame(400, $response->getStatusCode());
		self::assertStringContainsString('Restart the authorization process', (string)$response->getBody());
		self::assertArrayNotHasKey('spotify_oauth_state', $_SESSION);
	}

	public function testCallbackRejectsMissingState(): void
	{
		$_SESSION['spotify_oauth_state'] = $this->pendingState('expected-state');
		$controller = $this->buildController(new SpotifySessionDouble());
		$request = (new ServerRequestFactory())
			->createServerRequest('GET', 'https://example.test/spotify')
			->withQueryParams(['code' => 'authorization-code']);

		$response = $controller->getSpotifyAuth($request, (new ResponseFactory())->createResponse(), []);

		self::assertSame(400, $response->getStatusCode());
		self::assertStringContainsString('Restart the authorization process', (string)$response->getBody());
		self::assertArrayNotHasKey('spotify_oauth_state', $_SESSION);
	}

	public function testCallbackRejectsMissingCode(): void
	{
		$_SESSION['spotify_oauth_state'] = $this->pendingState('expected-state');
		$controller = $this->buildController(new SpotifySessionDouble());
		$request = (new ServerRequestFactory())
			->createServerRequest('GET', 'https://example.test/spotify')
			->withQueryParams(['state' => 'expected-state']);

		$response = $controller->getSpotifyAuth($request, (new ResponseFactory())->createResponse(), []);

		self::assertSame(400, $response->getStatusCode());
		self::assertStringContainsString('Restart the authorization process', (string)$response->getBody());
		self::assertArrayNotHasKey('spotify_oauth_state', $_SESSION);
	}

	public function testInitiationRequiresBasicCredentials(): void
	{
		$controller = $this->buildController(new SpotifySessionDouble());
		$request = (new ServerRequestFactory())->createServerRequest('GET', 'https://example.test/spotify');

		$response = $controller->getSpotifyAuth($request, (new ResponseFactory())->createResponse(), []);

		self::assertSame(401, $response->getStatusCode());
		self::assertNotSame('', $response->getHeaderLine('WWW-Authenticate'));
	}

	public function testInitiationRejectsInvalidBasicCredentials(): void
	{
		$controller = $this->buildController(new SpotifySessionDouble());
		$request = (new ServerRequestFactory())
			->createServerRequest('GET', 'https://example.test/spotify')
			->withHeader('Authorization', 'Basic ' . base64_encode('operator:wrong password'));

		$response = $controller->getSpotifyAuth($request, (new ResponseFactory())->createResponse(), []);

		self::assertSame(401, $response->getStatusCode());
	}

	public function testInitiationAcceptsValidBasicCredentialsAndCreatesStrongState(): void
	{
		$session = new SpotifySessionDouble();
		$controller = $this->buildController($session);
		$request = (new ServerRequestFactory())
			->createServerRequest('GET', 'https://example.test/spotify')
			->withHeader('Authorization', 'Basic ' . base64_encode('operator:correct password'));

		$response = $controller->getSpotifyAuth($request, (new ResponseFactory())->createResponse(), []);

		self::assertSame(302, $response->getStatusCode());
		self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $_SESSION['spotify_oauth_state']['value']);
		self::assertIsInt($_SESSION['spotify_oauth_state']['created_at']);
		self::assertSame($_SESSION['spotify_oauth_state']['value'], $session->authorizeOptions['state']);
	}

	public function testCallbackRejectsExpiredStateAndConsumesIt(): void
	{
		$_SESSION['spotify_oauth_state'] = $this->pendingState('expected-state', time() - 601);
		$controller = $this->buildController(new SpotifySessionDouble());
		$request = (new ServerRequestFactory())
			->createServerRequest('GET', 'https://example.test/spotify')
			->withQueryParams(['code' => 'authorization-code', 'state' => 'expected-state']);

		$response = $controller->getSpotifyAuth($request, (new ResponseFactory())->createResponse(), []);

		self::assertSame(400, $response->getStatusCode());
		self::assertArrayNotHasKey('spotify_oauth_state', $_SESSION);
	}

	public function testSuccessfulCallbackReturnsOnlyGenericResponse(): void
	{
		$_SESSION['spotify_oauth_state'] = $this->pendingState('expected-state');
		$session = new SpotifySessionDouble();
		$controller = $this->buildController($session);
		$request = (new ServerRequestFactory())
			->createServerRequest('GET', 'https://example.test/spotify')
			->withQueryParams(['code' => 'authorization-code', 'state' => 'expected-state']);

		$response = $controller->getSpotifyAuth($request, (new ResponseFactory())->createResponse(), []);
		$body = (string)$response->getBody();

		self::assertSame(200, $response->getStatusCode());
		self::assertSame(['message' => 'Spotify authorization completed.'], json_decode($body, true));
		self::assertStringNotContainsString('authorization-code', $body);
		self::assertStringNotContainsString($session->accessToken, $body);
		self::assertStringNotContainsString($session->refreshToken, $body);
		self::assertSame([$session->accessToken, $session->refreshToken], $controller->persistedTokens);
		self::assertArrayNotHasKey('spotify_oauth_state', $_SESSION);
	}

	private function buildApp(string $environment, string $enabled): \Slim\App
	{
		putenv('ENVIRONMENT=' . $environment);
		putenv('SPOTIFY_AUTH_ENABLED=' . $enabled);

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

	private function pendingState(string $value, ?int $createdAt = null): array
	{
		return [
			'value' => $value,
			'created_at' => $createdAt ?? time(),
		];
	}

	private function buildController(SpotifySessionDouble $session): TestableSpotifyController
	{
		$container = new Container();
		$container->set('spotifyLogger', new class {
			public function info(string $message): void {}
		});
		$container->set('spotify', [
			'session' => $session,
			'api' => new class {
				public function setAccessToken(string $accessToken): void {}
			},
		]);

		return new TestableSpotifyController($container);
	}

	private function restoreEnvironment(string $name, string|false $value): void
	{
		if ($value === false) {
			putenv($name);
			return;
		}

		putenv($name . '=' . $value);
	}
}

final class TestableSpotifyController extends SpotifyController
{
	public array $persistedTokens = [];

	protected function persistTokens(string $accessToken, string $refreshToken): void
	{
		$this->persistedTokens = [$accessToken, $refreshToken];
	}
}

final class SpotifySessionDouble
{
	public string $accessToken = 'secret-access-token';
	public string $refreshToken = 'secret-refresh-token';
	public array $authorizeOptions = [];

	public function getAuthorizeUrl(array $options): string
	{
		$this->authorizeOptions = $options;
		return 'https://accounts.example.test/authorize';
	}

	public function requestAccessToken(string $code): bool
	{
		return true;
	}

	public function getAccessToken(): string
	{
		return $this->accessToken;
	}

	public function getRefreshToken(): string
	{
		return $this->refreshToken;
	}
}
