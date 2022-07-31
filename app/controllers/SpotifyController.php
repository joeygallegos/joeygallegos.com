<?php

namespace App\Controllers;

use Slim\Container;
use App\Models\Config;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Controllers\BaseController;

class SpotifyController extends BaseController
{
	protected $container;
	protected $logger;
	public function __construct(Container $container)
	{
		$this->container = $container;
		$this->logger = $this->container->spotifyLogger;
	}

	/**
	 * Steps to authorize Spotify app to track the music playing
	 * 
	 * 1. Hit https://joeygallegos.com/spotify and login
	 * 2. Spotify will redirect you back with "code" param to act as the authorization string
	 * 3. Request the Access Token using the authorization string
	 * 4. Store the Access Token in the DB config for future requests
	 */
	public function getSpotifyAuth(Request $request, Response $response, array $args)
	{
		$spotify = $this->container->spotify;
		$session = $spotify['session'];
		$api = $spotify['api'];

		$paramCode = $request->getParam('code');
		if (isNullOrEmptyString($paramCode)) {

			$this->log('info', __FUNCTION__, 'Code? = null');
			$session = $spotify['session'];
			$options = [
				'scope' => [
					'user-read-email',
					'user-modify-playback-state',
					'user-read-currently-playing',
					'user-read-playback-state'
				],
			];
			$url = $session->getAuthorizeUrl($options);
			$this->log('info', __FUNCTION__, 'URL? = ' . $url);
			return $response->withRedirect($url);
		}

		$result = $session->requestAccessToken($paramCode);
		$sessionAccessToken = $session->getAccessToken();

		Config::updateSection('spotify_access_token', $sessionAccessToken, $this->logger);
		$api->setAccessToken($sessionAccessToken);

		$this->log('info', __FUNCTION__, 'Authorization String = ' . $paramCode);
		$this->log('info', __FUNCTION__, 'Code = ' . $result);
		$this->log('info', __FUNCTION__, 'SessionAccessToken = ' . $sessionAccessToken);
		return $response->withJson(['result' => $result], 200);
	}
}
