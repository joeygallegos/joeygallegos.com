<?php

namespace App\Middleware\FormValidation;

use Carbon\Carbon;
use Delight\Cookie\Session;


use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;


class SetFormSpam implements \Psr\Http\Server\MiddlewareInterface
{

	protected $container;
	protected $logger;
	private $obfuscationHash;

	const FORM_READY_AT_SESSION_NAME = 'post_request_ready_at';
	const FORM_OBFUSCATION_SESSION_NAME = 'post_request_obfuscation_hash';
	const HUMAN_CHECK_TIME = 10;

	public function __construct($container)
	{
		$this->container = $container;
		$this->logger = $this->container->get('contactFormLogger');

		// strtolower because of middleware which changes to all lowercase
		$this->obfuscationHash = strtolower($this->container->get('randomGenerator')->generateString(64, 'abcdefghijklmnopqrstuvwxyz'));
	}

	public function process(Request $request, RequestHandler $handler): Response
	{
		// if no target time set
		if (!Session::has(self::FORM_READY_AT_SESSION_NAME)) {

			// set one, with the `ready time` being 10 seconds from now
			Session::set(self::FORM_READY_AT_SESSION_NAME, Carbon::now()->addSeconds(self::HUMAN_CHECK_TIME));
		}

		// if session `ready time` is setup
		// and now is greater than `ready time`
		// setup again with now + 10 seconds
		if ((Carbon::now()->gt(Session::get(self::FORM_READY_AT_SESSION_NAME)))) {
			Session::set(self::FORM_READY_AT_SESSION_NAME, Carbon::now()->addSeconds(self::HUMAN_CHECK_TIME));
		}

		// set a random form obfuscation hash each request
		// then write it to the twig view
		// which redirects to the route
		// which expects some random value and will check if exists in session
		Session::set(self::FORM_OBFUSCATION_SESSION_NAME, $this->obfuscationHash);
		$this->logger->info(sprintf('SetFormSpam %s set = %s', self::FORM_OBFUSCATION_SESSION_NAME, $this->obfuscationHash));

		// to execute this middleware before the route code
		// all logic must be BEFORE this handle method is called
		// because handle calls upon the next queued middleware or the request
		$response = $handler->handle($request);
		return $response;
	}
}
