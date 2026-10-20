<?php

namespace App\Middleware\FormValidation;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class PreserveInputMiddleware
{
	private const CONTACT_FORM_PATH_PATTERN = '#^/contact/[^/]+/?$#';
	private const PRESERVED_FIELDS = [
		'name',
		'orgname',
		'phone',
		'email',
		'location',
		'what',
		'message',
	];

	protected $container;

	public function __construct($container)
	{
		$this->container = $container;
	}

	public function __invoke(Request $request, RequestHandler $handler): Response
	{
		$twig = $this->container->get('view');
		$oldFormData = $_SESSION['old_form_data'] ?? [];

		// Treat preserved values as one-request state so a later visit cannot inherit stale contact details.
		unset($_SESSION['old_form_data']);
		$twig->getEnvironment()->addGlobal('old_form_data', is_array($oldFormData) ? $oldFormData : []);

		if (
			$request->getMethod() === 'POST' &&
			preg_match(self::CONTACT_FORM_PATH_PATTERN, $request->getUri()->getPath()) === 1
		) {
			$parsedBody = $request->getParsedBody();
			$parsedBody = is_array($parsedBody) ? $parsedBody : [];

			// Only user-facing fields may survive the redirect; control and authentication fields stay transient.
			$_SESSION['old_form_data'] = array_intersect_key($parsedBody, array_flip(self::PRESERVED_FIELDS));
		}

		return $handler->handle($request);
	}
}
