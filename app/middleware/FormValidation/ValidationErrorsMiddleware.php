<?php
namespace App\Middleware\FormValidation;

use Slim\Http\Request;
use Slim\Http\Response;

class ValidationErrorsMiddleware {

	protected $container;
	public function __construct($container) {
		$this->container = $container;
	}

	// if errors is set, pass to view container
	public function __invoke(Request $request, Response $response, callable $next) {
		if (isset($_SESSION['errors'])) {
			$this->container->view->getEnvironment()->addGlobal('errors', $_SESSION['errors']);
		}

		unset($_SESSION['errors']);
		return $next($request, $response);
	}
}