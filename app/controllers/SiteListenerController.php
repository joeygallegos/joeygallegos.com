<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use DI\Container;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class SiteListenerController extends BaseController
{
	protected $container;

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	public function getResponseFromSite(Request $request, Response $response, array $args)
	{
		$allowedTokens = $this->getAllowedTokens();
		if ($allowedTokens === []) {
			// Fail closed when deployment configuration is absent instead of accepting any caller.
			return $this->responseWithJson($request, $response, [
				'error' => [
					'code' => 503,
					'message' => 'The site listener is unavailable'
				]
			], 503);
		}

		$authorization = $request->getHeaderLine('Authorization');
		// Reject whitespace and combined credentials so exactly one opaque Bearer token is authenticated.
		if (!preg_match('/^Bearer ([^\s,]+)$/i', $authorization, $matches) || !$this->tokenIsAllowed($matches[1], $allowedTokens)) {
			// All authentication failures deliberately share one response so callers cannot probe valid credentials.
			return $this->responseWithJson($request, $response, [
				'error' => [
					'code' => 401,
					'message' => 'Authentication required'
				]
			], 401)->withHeader('WWW-Authenticate', 'Bearer');
		}

		return $this->responseWithJson($request, $response, [
			'success' => [
				'code' => 200,
				'message' => 'The site listener is available'
			]
		], 200);
	}

	private function getAllowedTokens(): array
	{
		$configuredTokens = getenv('SITE_LISTENER_TOKENS');
		if ($configuredTokens === false) {
			return [];
		}

		// Trimming supports a readable comma-separated setting; empty entries never become valid credentials.
		return array_values(array_filter(
			array_map('trim', explode(',', $configuredTokens)),
			static fn (string $token): bool => $token !== ''
		));
	}

	private function tokenIsAllowed(string $providedToken, array $allowedTokens): bool
	{
		$isAllowed = false;
		foreach ($allowedTokens as $allowedToken) {
			// Evaluate every configured token to avoid revealing its position through an early return.
			$isAllowed = hash_equals($allowedToken, $providedToken) || $isAllowed;
		}

		return $isAllowed;
	}
}
