<?php

namespace App\Controllers;

use Slim\Routing\RouteContext;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// No entry or class found for 'router'
abstract class BaseController
{
	protected $container;
	protected $logger;

	/**
	 * Build the path for a named route including the base path
	 *
	 * @param string $name        Route name
	 * @param array  $data        Named argument replacement data
	 * @param array  $queryParams Optional query string parameters
	 */
	public function responseWithFlash(Request $request, Response $response, string $routeName, array $data, array $queryParams = [])
	{
		// get flash handler and add a message to the display queue
		// the added message gets plucked from the queue on next Twig layout trigger
		$this->container->get('flash')->addMessage('data', $data);

		// get route context object
		$routeContext = RouteContext::fromRequest($request);
		$routeParser = $routeContext->getRouteParser();

		// get URL with route name, push the data and query params to it
		// TODO: Should make null or provide data here???
		$destination = $routeParser->urlFor($routeName, $data, $queryParams);

		// return response with redirect to destination
		return $response->withHeader('Location', $destination)->withStatus(302);
	}

	/**
	 * Build the path for a named route including the base path
	 *
	 * @param string $name        Route name
	 * @param array  $payload     Named argument replacement data
	 * @param array  $queryParams Optional query string parameters
	 */
	public function responseWithJson(Request $request, Response $response, array $payload, $statusCode = 200)
	{
		$response->getBody()->write((string)json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		return $response
			->withHeader('Content-Type', 'application/json')
			->withStatus($statusCode);
	}

	/**
	 * Redirect to a named route
	 *
	 * @param string $name        Route name
	 * @param array  $data        Named argument replacement data
	 * @param array  $queryParams Optional query string parameters
	 */
	public function redirect(Request $request, Response $response, string $routeName = '', array $data = [], array $queryParams = []): Response
	{
		// get route context object
		$routeContext = RouteContext::fromRequest($request);
		$routeParser = $routeContext->getRouteParser();

		// get URL with route name, push the data and query params to it
		// TODO: Should make null or provide data here???
		$destination = $routeParser->urlFor($routeName, $data, $queryParams);

		// return response with redirect to destination
		return $response->withHeader('Location', $destination)->withStatus(302);
	}

	/**
	 * Redirect to an external destination
	 *
	 * @param string $destination External destination
	 */
	public function redirectExternal(Response $response, string $destination = ''): Response
	{
		// return response with redirect to external destination
		return $response->withHeader('Location', $destination)->withStatus(302);
	}

	/**
	 * Get base directory from the request object
	 * @param  Request $request current request
	 * @return String|Null base directory
	 */
	public function getBaseDirectory(Request $request = null)
	{
		return !is_null($request) ? (string)$request->getUri()->withPath('')->withQuery('')->withFragment('') : null;
	}

	/**
	 * Log message for this controller
	 * @param  [type] $logger   [description]
	 * @param  string $type     [description]
	 * @param  [type] $function [description]
	 * @param  string $msg   [description]
	 * @return [type]           [description]
	 */
	public function log(string $type, string $function, string $msg)
	{
		if ($this->logger != null) {
			$this->logger->{$type}($function . ": " . $msg);
		} else throw new \Exception('Logger instance is null', 1);
	}

	/**
	 * @param Request $request
	 * @param string $cookieName
	 * @return string
	 */
	public function getCookieValue(Request $request, string $cookieName = '')
	{
		$cookies = $request->getCookieParams();
		return isset($cookies[$cookieName]) ? $cookies[$cookieName] : null;
	}
}
