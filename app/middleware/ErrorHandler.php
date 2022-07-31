<?php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ErrorHandler
{
	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{
		return $this->container->view->render($response, '/errors/500.twig', [
			'backend' => [
				'environment' => getenv('ENVIRONMENT'),
				'base' => '/'
			],
			'page' => [
				'title' => 'Error!',
			]
		]);

		include getBaseDirectory() . '/app/bootstrap.php';
		include getBaseDirectory() . '/app/views/errors/error.php';
		return $response->withStatus(500)->withHeader('Content-Type', 'text/html');
	}
}
