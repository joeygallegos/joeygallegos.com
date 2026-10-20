<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CsrfFailureHandler
{
	public function __construct(private ResponseFactoryInterface $responseFactory) {}

	public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$response = $this->responseFactory->createResponse(400);
		// Keep this response independent of submitted values so rejected credentials can never be reflected.
		$response->getBody()->write(
			'<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Session expired</title></head>' .
			'<body><main><h1>Session expired</h1><p role="alert">Refresh the page and try again.</p></main></body></html>'
		);

		return $response->withHeader('Content-Type', 'text/html; charset=UTF-8');
	}
}
