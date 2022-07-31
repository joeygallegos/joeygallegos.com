<?php

namespace App\Middleware;

use Slim\Container;
use Slim\Handlers\NotFound;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class NotFoundHandler extends NotFound
{
	private $container;

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{
		return $this->container->view->render($response, '/errors/404.twig', [
			'backend' => [
				'environment' => getenv('ENVIRONMENT')
			],
			'page' => [
				'title' => 'Page not found!',
			]
		]);
	}
}
