<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Interfaces\ErrorHandlerInterface;
use Slim\Views\Twig;
use Throwable;

final class ErrorHandler implements ErrorHandlerInterface
{
	private const CONTENT_TYPE = 'text/html; charset=UTF-8';

	public function __construct(
		private Twig $view,
		private ResponseFactoryInterface $responseFactory
	) {}

	public function __invoke(
		ServerRequestInterface $request,
		Throwable $exception,
		bool $displayErrorDetails,
		bool $logErrors,
		bool $logErrorDetails
	): ResponseInterface {
		$statusCode = $this->determineStatusCode($exception);
		$templateStatus = in_array($statusCode, [404, 405], true) ? $statusCode : 500;
		$response = $this->responseFactory
			->createResponse($statusCode)
			->withHeader('Content-Type', self::CONTENT_TYPE);

		if ($exception instanceof HttpMethodNotAllowedException) {
			$response = $response->withHeader('Allow', implode(', ', $exception->getAllowedMethods()));
		}

		try {
			// Error details are deliberately excluded even when development diagnostics are enabled.
			// They can contain credentials, request data, file paths, and stack traces.
			return $this->view->render($response, "error/{$templateStatus}.twig", [
				'page' => ['title' => $this->pageTitle($templateStatus)],
			]);
		} catch (Throwable) {
			// Rendering can fail because the original exception left Twig or its dependencies unusable.
			// Start with a fresh response so no partial template output or headers can escape.
			$fallback = $this->responseFactory
				->createResponse(500)
				->withHeader('Content-Type', self::CONTENT_TYPE);
			$fallback->getBody()->write(
				'<!doctype html><html lang="en"><head><meta charset="utf-8">'
				. '<title>Something went wrong</title></head><body><main>'
				. '<h1>Something went wrong</h1><p>Please try again later.</p>'
				. '<p><a href="/">Go to homepage</a></p></main></body></html>'
			);

			return $fallback;
		}
	}

	private function determineStatusCode(Throwable $exception): int
	{
		if ($exception instanceof HttpException && $exception->getCode() >= 400 && $exception->getCode() <= 599) {
			return $exception->getCode();
		}

		return 500;
	}

	private function pageTitle(int $statusCode): string
	{
		return match ($statusCode) {
			404 => 'Page not found',
			405 => 'Method not allowed',
			default => 'Something went wrong',
		};
	}
}
