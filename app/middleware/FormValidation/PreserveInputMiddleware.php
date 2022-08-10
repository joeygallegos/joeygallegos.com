<?php

namespace App\Middleware\FormValidation;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class PreserveInputMiddleware
{
	protected $container;

	public function __construct($container)
	{
		$this->container = $container;
	}

	public function __invoke(Request $request, RequestHandler $handler): Response
	{
		$response = $handler->handle($request);
		$twig = $this->container->get('view');

		if (isset($_SESSION['old_form_data'])) {
			$twig->getEnvironment()->addGlobal('old_form_data', $_SESSION['old_form_data']);
		}

		$_SESSION['old_form_data'] = $request->getQueryParams();
		return $response;
	}
}
