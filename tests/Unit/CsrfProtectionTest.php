<?php

declare(strict_types=1);

use App\Middleware\CsrfFailureHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Csrf\Guard;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

final class CsrfProtectionTest extends TestCase
{
	public function testContactFormRendersRequestTokenAsHiddenFields(): void
	{
		[$guard, $handler] = $this->guardAndHandler();
		$guard->process($this->request('GET'), $handler);
		$request = $handler->lastRequest;

		self::assertNotNull($request);
		$html = $this->renderContactForm([
			'name_key' => $guard->getTokenNameKey(),
			'value_key' => $guard->getTokenValueKey(),
			'name' => $request->getAttribute($guard->getTokenNameKey()),
			'value' => $request->getAttribute($guard->getTokenValueKey()),
		]);

		self::assertStringContainsString(
			sprintf('type="hidden" name="%s" value="%s"', $guard->getTokenNameKey(), $request->getAttribute($guard->getTokenNameKey())),
			$html
		);
		self::assertStringContainsString(
			sprintf('type="hidden" name="%s" value="%s"', $guard->getTokenValueKey(), $request->getAttribute($guard->getTokenValueKey())),
			$html
		);
	}

	public function testMissingTokenReturnsSafeBadRequestWithoutCallingApplication(): void
	{
		[$guard, $handler] = $this->guardAndHandler();
		$response = $guard->process($this->request('POST')->withParsedBody([]), $handler);

		self::assertSame(400, $response->getStatusCode());
		self::assertSame(0, $handler->calls);
		self::assertStringContainsString('Refresh the page and try again.', (string) $response->getBody());
	}

	public function testInvalidTokenReturnsSafeBadRequestWithoutReflectingCredentials(): void
	{
		[$guard, $handler] = $this->guardAndHandler();
		$response = $guard->process($this->request('POST')->withParsedBody([
			$guard->getTokenNameKey() => 'submitted-name-secret',
			$guard->getTokenValueKey() => 'submitted-value-secret',
		]), $handler);
		$body = (string) $response->getBody();

		self::assertSame(400, $response->getStatusCode());
		self::assertSame(0, $handler->calls);
		self::assertStringNotContainsString('submitted-name-secret', $body);
		self::assertStringNotContainsString('submitted-value-secret', $body);
	}

	public function testValidTokenCanBeUsedOnceAndReplayNeverCallsApplication(): void
	{
		[$guard, $handler] = $this->guardAndHandler();
		$guard->process($this->request('GET'), $handler);
		$tokenRequest = $handler->lastRequest;
		self::assertNotNull($tokenRequest);

		$body = [
			$guard->getTokenNameKey() => $tokenRequest->getAttribute($guard->getTokenNameKey()),
			$guard->getTokenValueKey() => $tokenRequest->getAttribute($guard->getTokenValueKey()),
		];
		$handler->reset();

		$firstResponse = $guard->process($this->request('POST')->withParsedBody($body), $handler);
		$replayResponse = $guard->process($this->request('POST')->withParsedBody($body), $handler);

		self::assertSame(204, $firstResponse->getStatusCode());
		self::assertSame(400, $replayResponse->getStatusCode());
		self::assertSame(1, $handler->calls);
	}

	public function testContactErrorsHaveAccessibleLabelsAndDescriptions(): void
	{
		$html = $this->renderContactForm([
			'name_key' => 'csrf_name',
			'value_key' => 'csrf_value',
			'name' => 'generated-name',
			'value' => 'generated-value',
		], ['email' => ['Enter a valid email address.']]);

		self::assertStringContainsString('role="alert" aria-labelledby="contact-form-errors-title"', $html);
		self::assertStringContainsString('<label for="email">Your email address</label>', $html);
		self::assertStringContainsString('id="email-error"', $html);
		self::assertStringContainsString('aria-invalid="true" aria-describedby="email-error"', $html);
		self::assertStringContainsString('type="email"', $html);
		self::assertStringContainsString('type="submit"', $html);
	}

	private function guardAndHandler(): array
	{
		$storage = [];
		$responseFactory = new ResponseFactory();
		// Persistent mode stays disabled so each successful unsafe request consumes its token.
		$guard = new Guard($responseFactory, 'csrf', $storage, new CsrfFailureHandler($responseFactory));

		return [$guard, new CsrfRecordingHandler()];
	}

	private function request(string $method): ServerRequestInterface
	{
		return (new ServerRequestFactory())->createServerRequest($method, '/contact/session-hash');
	}

	private function renderContactForm(array $csrf, array $formErrors = []): string
	{
		$twig = new Environment(new FilesystemLoader(dirname(__DIR__, 2) . '/app/views'));
		$twig->addFunction(new TwigFunction(
			'url_for',
			static fn (string $route, array $arguments = []): string => '/contact/' . ($arguments['hash'] ?? '')
		));

		return $twig->render('contact.twig', [
			'backend' => ['post_request_obfuscation_hash' => 'session-hash'],
			'csrf' => $csrf,
			'data' => [],
			'form_errors' => $formErrors,
			'old_form_data' => [],
		]);
	}
}

final class CsrfRecordingHandler implements RequestHandlerInterface
{
	public int $calls = 0;
	public ?ServerRequestInterface $lastRequest = null;

	public function handle(ServerRequestInterface $request): Response
	{
		$this->calls++;
		$this->lastRequest = $request;

		return new Response(204);
	}

	public function reset(): void
	{
		$this->calls = 0;
		$this->lastRequest = null;
	}
}
