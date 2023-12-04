<?php

namespace App\Controllers;

use DI\Container;
use App\Models\Config;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class SpotifyController extends BaseController
{
	protected $container;
	protected $logger;
	public function __construct(Container $container)
	{
		$this->container = $container;
		$this->logger = $this->container->get('spotifyLogger');
	}

	/**
	 * Steps to authorize Spotify app to track the music playing
	 * 
	 * 1. Hit https://joeygallegos.com/spotify and login
	 * 2. Spotify will redirect you back with "code" param to act as the authorization string
	 * 3. Request the Access Token using the authorization string
	 * 4. Store the Access Token in the DB config for future requests
	 * 
	 * https://accounts.spotify.com/authorize?client_id=83e8ec93381b4bd58ba54e3fc8fb8423&redirect_uri=https%3A%2F%2Fjoeygallegos.com%2Fspotify&response_type=code&scope=user-read-email+user-modify-playback-state+user-read-currently-playing+user-read-playback-state
	 * 
	 * https://joeygallegos.com/spotify?code=AQCwAoLA-pSr3h6orD2rzr7l7hIBzCrSWqzbzwWJcsOs-brr1-gzdbNZ_FrvZHTeL19MfnY-ZNhP8Z96gxUitxoofsnWnZAT7T03oLy2JHIJVsfm7gXr8UezuuEQ-PlsbmArEdhMzJbW1QpnNOwhGwWL1klvY2wlrNWDr_E-r5yU-orCzzTyWM0eeaKyKpElraBMH971yBKmlZSi4M3rLHTtVviKvue6O4a-5OACQEGHU2Y1MF2Upe7XAKrYn8NIFdT2R7mGekuJEJoxBI5rnKFsU8dx41wMJz-GTjRqHnWpw6Ca4UFnMg
	 */
	public function getSpotifyAuth(Request $request, Response $response, array $args)
	{
		$spotify = $this->container->get('spotify');
		$session = $spotify['session'];
		$api = $spotify['api'];

		// if no param code from spotify is passed to us
		$paramCode = $request->getQueryParams()['code'] ?? null;
		if (isNullOrEmptyString($paramCode)) {
			$this->log('info', __FUNCTION__, 'Null code provided');
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
			return $this->redirectExternal($response, $url);
		}

		$result = $session->requestAccessToken($paramCode);
		$sessionAccessToken = $session->getAccessToken();
		$sessionRefreshToken = $session->getRefreshToken();

		Config::updateSection('spotify_access_token', $sessionAccessToken, $this->logger);
		Config::updateSection('spotify_refresh_token', $sessionRefreshToken, $this->logger);

		$api->setAccessToken($sessionAccessToken);

		$this->log('info', __FUNCTION__, 'Authorization String = ' . $paramCode);
		$this->log('info', __FUNCTION__, 'Code = ' . $result);
		$this->log('info', __FUNCTION__, 'SessionAccessToken = ' . $sessionAccessToken);
		return $this->responseWithJson($request, $response, ['result' => $result], 200);
	}
}
