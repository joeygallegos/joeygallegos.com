<?php
namespace App\Middleware\FormValidation;

use Carbon\Carbon;
use Slim\Container;
use App\Models\SuperUserLogin;
use Psr\Http\Message\ResponseInterface;
use \Delight\Cookie\Session as Session;
use Psr\Http\Message\ServerRequestInterface;

class SetFormSpam {

	protected $container;
	private $obfuscationHash;

	const FORM_READY_AT_SESSION_NAME = 'post_request_ready_at';
	const FORM_OBFUSCATION_SESSON_NAME = 'post_request_obfuscation_hash';
	const HUMAN_CHECK_TIME = 10;

	public function __construct(Container $container)
	{
		$this->container = $container;

		// strtolower because of middleware which changes to all lowercase
		$this->obfuscationHash = strtolower($this->container->randomGenerator->generateString(64, 'abcdefghijklmnopqrstuvwxyz'));
	}

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
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
		Session::set(self::FORM_OBFUSCATION_SESSON_NAME, $this->obfuscationHash);

		// handle next middleware or request
		return $next($request, $response);
	}
}