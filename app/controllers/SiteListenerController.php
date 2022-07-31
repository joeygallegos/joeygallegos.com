<?php

namespace App\Controllers;

use Slim\Container;
use App\Models\Config;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Controllers\BaseController;

class SiteListenerController extends BaseController
{
	protected $container;
	protected $logger;
	public function __construct(Container $container)
	{
		$this->container = $container;
		$this->logger = $this->container->debugLogger;
	}

	public function getResponseFromSite(Request $request, Response $response, array $args)
	{
		$siteToken = $request->getParam('token');

		if (isNullOrEmptyString($siteToken)) {
			$this->log('info', __FUNCTION__, sprintf('Failed attempt from siteToken %s', $siteToken));
			return $response->withJson([
				'error' => [
					'code' => 400,
					'message' => 'The site token was not provided'
				]
			], 400);
		}

		if (!in_array($siteToken, ['db8de3bd-3b51-447d-b099-44ae0c111e7f', 'f32eb393-8390-4d21-8b3a-9105eb8adc3b', 'f2ac2149-a975-4ea4-b71a-469fb70212ea'])) {
			return $response->withJson([
				'error' => [
					'code' => 400,
					'message' => 'The site token is not registered in the listener'
				]
			], 400);
		}

		$payload = [
			'success' => [
				'code' => 200,
				'message' => sprintf('You are using %s', $siteToken)
			]
		];
		return $response->withJson($payload, 200);
	}
}
