<?php

namespace App\Middleware\FormValidation;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class ValidationErrorsMiddleware
{

	protected $container;
	public function __construct($container)
	{
		$this->container = $container;
	}

	// if errors is set, pass to view container
	public function __invoke(Request $request, RequestHandler $handler): Response
	{
		$response = $handler->handle($request);
		if (isset($_SESSION['form_errors'])) {
			$this->container->view->getEnvironment()->addGlobal('form_errors', $_SESSION['form_errors']);
		}

		unset($_SESSION['form_errors']);
		return $response;
	}
}
