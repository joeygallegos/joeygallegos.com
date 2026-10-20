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

	public function __invoke(Request $request, RequestHandler $handler): Response
	{
		$formErrors = $_SESSION['form_errors'] ?? [];

		// Errors belong only to the next request, and Twig must receive them before a route renders.
		unset($_SESSION['form_errors']);
		$this->container->get('view')->getEnvironment()->addGlobal('form_errors', is_array($formErrors) ? $formErrors : []);

		return $handler->handle($request);
	}
}
