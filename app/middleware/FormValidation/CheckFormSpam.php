<?php

namespace App\Middleware\FormValidation;

use \Delight\Cookie\Session as Session;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class CheckFormSpam
{

	protected $container;

	const FORM_READY_AT_SESSION_NAME = 'post_request_ready_at';
	const FORM_OBFUSCATION_SESSION_NAME = 'post_request_obfuscation_hash';
	const HUMAN_CHECK_TIME = 10;

	public function __construct($container)
	{
		$this->container = $container;
	}

	public function __invoke(Request $request, RequestHandler $handler): Response
	{
		$response = $handler->handle($request);
		// if doesn't have token from the contact page get request
		// but is trying to post to the contact form
		// likely a bot trying to use form without going to webpage first
		// route cannot be used without this session variable
		if (!Session::has(self::FORM_READY_AT_SESSION_NAME)) {
			// TODO: Handle the bot request
			return $response->withStatus(400);
		}

		// Session::set(self::FORM_READY_AT_SESSION_NAME, Carbon::now());
		return $response;
	}
}
