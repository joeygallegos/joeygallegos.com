<?php

declare(strict_types=1);

use App\Middleware\FormValidation\SetFormSpam;
use DI\Container;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class SetFormSpamLoggingTest extends TestCase
{
	protected function setUp(): void
	{
		$_SESSION = [];
	}

	public function testObfuscationHashIsStoredButNotLogged(): void
	{
		$logger = new SetFormSpamRecordingLogger();
		$container = new Container();
		$container->set('contactFormLogger', $logger);
		$container->set('randomGenerator', new class {
			public function generateString(int $length, string $alphabet): string
			{
				return 'PRIVATE-FORM-HASH';
			}
		});
		$middleware = new SetFormSpam($container);
		$request = (new ServerRequestFactory())->createServerRequest('GET', '/contact');
		$handler = new class implements RequestHandlerInterface {
			public function handle(ServerRequestInterface $request): ResponseInterface
			{
				return new Response();
			}
		};

		$response = $middleware->process($request, $handler);

		self::assertSame(200, $response->getStatusCode());
		self::assertSame('private-form-hash', $_SESSION[SetFormSpam::FORM_OBFUSCATION_SESSION_NAME]);
		self::assertStringContainsString('initialized form obfuscation state', $logger->messagesAsText());
		self::assertStringNotContainsString('private-form-hash', strtolower($logger->messagesAsText()));
	}
}

final class SetFormSpamRecordingLogger
{
	public array $messages = [];

	public function info(string $message): void
	{
		$this->messages[] = $message;
	}

	public function messagesAsText(): string
	{
		return implode("\n", $this->messages);
	}
}
