<?php

declare(strict_types=1);

use App\Middleware\FormValidation\PreserveInputMiddleware;
use App\Middleware\FormValidation\ValidationErrorsMiddleware;
use DI\Container;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class FormStateMiddlewareTest extends TestCase
{
	protected function setUp(): void
	{
		$_SESSION = [];
	}

	public function testContactPostPreservesOnlyUserFacingFields(): void
	{
		$view = new FormStateRecordingView();
		$middleware = new PreserveInputMiddleware($this->containerWithView($view));
		$request = (new ServerRequestFactory())
			->createServerRequest('POST', '/contact/route-secret')
			->withParsedBody([
				'name' => 'Taylor',
				'orgname' => 'Example Co',
				'phone' => '555-0100',
				'email' => 'taylor@example.test',
				'location' => 'Austin',
				'what' => 'A website',
				'message' => 'Please contact me.',
				'_honey' => 'bot-value',
				'csrf_name' => 'csrf-name-secret',
				'csrf_value' => 'csrf-value-secret',
				'session' => 'session-secret',
				'hash' => 'route-secret',
			]);

		$middleware($request, new FormStateRecordingHandler($view));

		self::assertSame([
			'name' => 'Taylor',
			'orgname' => 'Example Co',
			'phone' => '555-0100',
			'email' => 'taylor@example.test',
			'location' => 'Austin',
			'what' => 'A website',
			'message' => 'Please contact me.',
		], $_SESSION['old_form_data']);
	}

	public function testPreservedInputIsConsumedOnceBeforeRendering(): void
	{
		$_SESSION['old_form_data'] = ['name' => 'Taylor'];
		$view = new FormStateRecordingView();
		$middleware = new PreserveInputMiddleware($this->containerWithView($view));
		$request = (new ServerRequestFactory())->createServerRequest('GET', '/contact-me');

		$firstHandler = new FormStateRecordingHandler($view);
		$middleware($request, $firstHandler);

		self::assertSame(['name' => 'Taylor'], $firstHandler->globalsAtHandle['old_form_data']);
		self::assertArrayNotHasKey('old_form_data', $_SESSION);

		$secondHandler = new FormStateRecordingHandler($view);
		$middleware($request, $secondHandler);

		self::assertSame([], $secondHandler->globalsAtHandle['old_form_data']);
	}

	public function testValidationErrorsAreConsumedOnceBeforeRendering(): void
	{
		$_SESSION['form_errors'] = ['name' => ['Name must not be empty']];
		$view = new FormStateRecordingView();
		$middleware = new ValidationErrorsMiddleware($this->containerWithView($view));
		$request = (new ServerRequestFactory())->createServerRequest('GET', '/contact-me');

		$firstHandler = new FormStateRecordingHandler($view);
		$middleware($request, $firstHandler);

		self::assertSame(
			['name' => ['Name must not be empty']],
			$firstHandler->globalsAtHandle['form_errors']
		);
		self::assertArrayNotHasKey('form_errors', $_SESSION);

		$secondHandler = new FormStateRecordingHandler($view);
		$middleware($request, $secondHandler);

		self::assertSame([], $secondHandler->globalsAtHandle['form_errors']);
	}

	private function containerWithView(FormStateRecordingView $view): Container
	{
		$container = new Container();
		$container->set('view', $view);

		return $container;
	}
}

final class FormStateRecordingView
{
	public array $globals = [];

	public function getEnvironment(): self
	{
		return $this;
	}

	public function addGlobal(string $name, mixed $value): void
	{
		$this->globals[$name] = $value;
	}
}

final class FormStateRecordingHandler implements RequestHandlerInterface
{
	public array $globalsAtHandle = [];

	public function __construct(private FormStateRecordingView $view) {}

	public function handle(ServerRequestInterface $request): Response
	{
		$this->globalsAtHandle = $this->view->globals;

		return new Response();
	}
}
