<?php

declare(strict_types=1);

use App\Controllers\ContactController;
use DI\Container;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class ContactControllerTest extends TestCase
{
	protected function setUp(): void
	{
		$_SESSION = [];
	}

	public function testHashMismatchRedirectsWithErrorFlash(): void
	{
		$logger = new ContactControllerRecordingLogger();
		$controller = $this->buildController($logger);
		$_SESSION[ContactController::FORM_OBFUSCATION_SESSION_NAME] = 'session-hash';

		$request = (new ServerRequestFactory())
			->createServerRequest('POST', '/contact/path-hash')
			->withParsedBody([]);
		$response = new Response();

		$result = $controller->postContactPage($request, $response, ['hash' => 'path-hash']);

		self::assertSame(302, $result->getStatusCode());
		self::assertSame('contact-get', $controller->capturedRouteName);
		self::assertStringContainsString('session occurred', $controller->capturedFlashData['flashes'][0]['message']);
		self::assertStringNotContainsString('session-hash', $logger->messagesAsText());
		self::assertStringNotContainsString('path-hash', $logger->messagesAsText());
	}

	public function testContactNotificationKeepsSubmittedDataOutOfLogs(): void
	{
		$logger = new ContactControllerRecordingLogger();
		$emailEngine = new ContactControllerRecordingEmailEngine(true);
		$controller = $this->buildController($logger, $emailEngine);
		$_SESSION[ContactController::FORM_OBFUSCATION_SESSION_NAME] = 'matching-session-secret';
		$_SESSION[ContactController::FORM_READY_AT_SESSION_NAME] = date(DATE_ATOM, strtotime('-20 seconds'));
		$_SESSION['old_form_data'] = ['name' => 'Sensitive Person'];

		$environment = [
			'MAILGUN_FROM' => 'sender-secret@example.test',
			'CONTACT_FORM_TO' => 'recipient-secret@example.test',
			'CONTACT_FORM_BCC' => 'bcc-secret@example.test',
		];
		$previousEnvironment = [];
		foreach ($environment as $name => $value) {
			$previousEnvironment[$name] = getenv($name);
			putenv($name . '=' . $value);
		}

		$submittedData = [
			'name' => 'Sensitive Person',
			'orgname' => 'Private Organization',
			'phone' => '512-555-0199',
			'email' => 'contact-secret@example.test',
			'location' => 'Private Location',
			'what' => 'Confidential Request',
			'message' => 'Message body must remain private.',
			ContactController::SPAM_CHECK_ATTRIBUTE => '',
		];

		try {
			$request = (new ServerRequestFactory())
				->createServerRequest('POST', '/contact/matching-session-secret')
				->withParsedBody($submittedData);
			$result = $controller->postContactPage($request, new Response(), ['hash' => 'matching-session-secret']);
		} finally {
			foreach ($previousEnvironment as $name => $value) {
				$value === false ? putenv($name) : putenv($name . '=' . $value);
			}
		}

		self::assertSame(302, $result->getStatusCode());
		self::assertStringContainsString($submittedData['message'], $emailEngine->payload['html']);
		self::assertSame($environment['CONTACT_FORM_TO'], $emailEngine->payload['to']);
		self::assertStringContainsString('Email notification sent without error', $logger->messagesAsText());
		self::assertArrayNotHasKey('old_form_data', $_SESSION);

		$logText = $logger->messagesAsText();
		foreach (array_merge(array_values($submittedData), array_values($environment), ['matching-session-secret']) as $sensitiveValue) {
			if ($sensitiveValue !== '') {
				self::assertStringNotContainsString($sensitiveValue, $logText);
			}
		}
	}

	public function testContactLoggingCallsDoNotReferenceSensitiveVariables(): void
	{
		$sourceLines = file(dirname(__DIR__, 2) . '/app/controllers/ContactController.php');
		self::assertNotFalse($sourceLines);

		$loggingLines = array_filter($sourceLines, static fn (string $line): bool => str_contains($line, '->log(') || str_contains($line, '->logger->'));
		$loggingSource = implode('', $loggingLines);

		// These values contain session tokens, contact fields, recipients, message bodies, or visitor addresses.
		foreach (['$pathHash', '$sessionHash', '$paramValue', '$emailArr', 'getRequestAddress()', 'json_encode($validation->errors())'] as $unsafeExpression) {
			self::assertStringNotContainsString($unsafeExpression, $loggingSource);
		}
	}

	public function testContactPageDoesNotExposeConfigurationRecordsToTwig(): void
	{
		$view = new ContactControllerRecordingView();
		$controller = $this->buildController(view: $view);

		$controller->getContactPage(
			(new ServerRequestFactory())->createServerRequest('GET', '/contact'),
			new Response(),
			[]
		);

		self::assertSame('contact.twig', $view->template);
		self::assertArrayNotHasKey('config', $view->payload);
		self::assertArrayHasKey('backend', $view->payload);
	}

	public function testMissingReadyAtRedirectsWithSessionError(): void
	{
		$controller = $this->buildController();
		$_SESSION[ContactController::FORM_OBFUSCATION_SESSION_NAME] = 'abc';

		$request = (new ServerRequestFactory())
			->createServerRequest('POST', '/contact/abc')
			->withParsedBody([]);
		$response = new Response();

		$result = $controller->postContactPage($request, $response, ['hash' => 'abc']);

		self::assertSame(302, $result->getStatusCode());
		self::assertSame('contact-get', $controller->capturedRouteName);
		self::assertStringContainsString('session occurred', $controller->capturedFlashData['flashes'][0]['message']);
	}

	public function testTooFastSubmissionRedirectsWithThrottleMessage(): void
	{
		$controller = $this->buildController();
		$_SESSION[ContactController::FORM_OBFUSCATION_SESSION_NAME] = 'abc';
		$_SESSION[ContactController::FORM_READY_AT_SESSION_NAME] = date(DATE_ATOM, strtotime('+20 seconds'));

		$request = (new ServerRequestFactory())
			->createServerRequest('POST', '/contact/abc')
			->withParsedBody([]);
		$response = new Response();

		$result = $controller->postContactPage($request, $response, ['hash' => 'abc']);

		self::assertSame(302, $result->getStatusCode());
		self::assertStringContainsString('way too quickly', $controller->capturedFlashData['flashes'][0]['message']);
	}

	private function buildController(
		?ContactControllerRecordingLogger $logger = null,
		?ContactControllerRecordingEmailEngine $emailEngine = null,
		?ContactControllerRecordingView $view = null
	): TestableContactController
	{
		$container = new Container();
		$container->set('contactFormLogger', $logger ?? new ContactControllerRecordingLogger());
		$container->set('validator', new class {
			public function validate($request, $rules) { return $this; }
			public function failed(): bool { return false; }
			public function errors(): array { return []; }
		});
		$container->set('emailEngine', $emailEngine ?? new ContactControllerRecordingEmailEngine(false));
		$container->set('flash', new class {
			public function addMessage(string $key, $value): void {}
			public function getFirstMessage(string $key): array { return []; }
		});
		$container->set('view', $view ?? new ContactControllerRecordingView());

		return new TestableContactController($container);
	}
}

final class ContactControllerRecordingView
{
	public string $template = '';
	public array $payload = [];

	public function render($response, string $template, array $payload)
	{
		$this->template = $template;
		$this->payload = $payload;
		return $response;
	}
}

final class ContactControllerRecordingLogger
{
	public array $messages = [];

	public function info(string $message): void
	{
		$this->messages[] = $message;
	}

	public function error(string $message): void
	{
		$this->messages[] = $message;
	}

	public function messagesAsText(): string
	{
		return implode("\n", $this->messages);
	}
}

final class ContactControllerRecordingEmailEngine
{
	public array $payload = [];

	public function __construct(private bool $result) {}

	public function createEmail(array $payload): bool
	{
		$this->payload = $payload;
		return $this->result;
	}
}

final class TestableContactController extends ContactController
{
	public string $capturedRouteName = '';
	public array $capturedFlashData = [];

	public function responseWithFlash(
		\Psr\Http\Message\ServerRequestInterface $request,
		\Psr\Http\Message\ResponseInterface $response,
		string $routeName,
		array $data,
		array $queryParams = []
	) {
		$this->capturedRouteName = $routeName;
		$this->capturedFlashData = $data;
		return $response->withStatus(302)->withHeader('Location', '/contact-me');
	}
}
