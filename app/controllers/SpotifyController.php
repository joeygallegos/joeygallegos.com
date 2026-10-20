<?php

namespace App\Controllers;

use DI\Container;
use App\Models\Config;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class SpotifyController extends BaseController
{
	private const OAUTH_STATE_TTL_SECONDS = 600;

	protected $container;
	protected $logger;
	public function __construct(Container $container)
	{
		$this->container = $container;
		$this->logger = $this->container->get('spotifyLogger');
	}

	/**
	 * Start Spotify authorization or process its callback.
	 *
	 * The one-time state is bound to the current PHP session so another browser cannot
	 * submit an authorization code to this callback on the operator's behalf.
	 */
	public function getSpotifyAuth(Request $request, Response $response, array $args)
	{
		$queryParams = $request->getQueryParams();
		$paramCode = $queryParams['code'] ?? null;
		$returnedState = $queryParams['state'] ?? null;
		$hasCode = is_string($paramCode) && trim($paramCode) !== '';
		$hasCallbackParams = array_key_exists('code', $queryParams) ||
			array_key_exists('state', $queryParams) ||
			array_key_exists('error', $queryParams);

		if (strtolower($request->getUri()->getScheme()) !== 'https') {
			unset($_SESSION['spotify_oauth_state']);
			return $this->authorizationFailure($request, $response);
		}

		if (!$hasCallbackParams) {
			if (!$this->hasValidOperatorCredentials($request)) {
				return $this->authenticationRequired($request, $response);
			}

			$spotify = $this->container->get('spotify');
			$session = $spotify['session'];
			$state = bin2hex(random_bytes(32));
			$_SESSION['spotify_oauth_state'] = [
				'value' => $state,
				'created_at' => time(),
			];
			$options = [
				'scope' => [
					'user-read-email',
					'user-modify-playback-state',
					'user-read-currently-playing',
					'user-read-playback-state'
				],
				'state' => $state,
			];
			$url = $session->getAuthorizeUrl($options);
			return $this->redirectExternal($response, $url)->withHeader('Cache-Control', 'no-store');
		}

		// A callback-shaped request must not silently start a replacement authorization flow.
		if (!$hasCode) {
			unset($_SESSION['spotify_oauth_state']);
			return $this->authorizationFailure($request, $response);
		}

		// Delete state before validation so every callback attempt consumes it exactly once.
		$pendingState = $_SESSION['spotify_oauth_state'] ?? null;
		unset($_SESSION['spotify_oauth_state']);
		$expectedState = is_array($pendingState) ? ($pendingState['value'] ?? null) : null;
		$stateCreatedAt = is_array($pendingState) ? ($pendingState['created_at'] ?? null) : null;
		if (
			!is_string($expectedState) ||
			$expectedState === '' ||
			!is_int($stateCreatedAt) ||
			$stateCreatedAt > time() ||
			(time() - $stateCreatedAt) > self::OAUTH_STATE_TTL_SECONDS ||
			!is_string($returnedState) ||
			$returnedState === '' ||
			!hash_equals($expectedState, $returnedState)
		) {
			return $this->authorizationFailure($request, $response);
		}

		$spotify = $this->container->get('spotify');
		$session = $spotify['session'];
		$api = $spotify['api'];
		try {
			$session->requestAccessToken($paramCode);
			$sessionAccessToken = $session->getAccessToken();
			$sessionRefreshToken = $session->getRefreshToken();

			$this->persistTokens($sessionAccessToken, $sessionRefreshToken);

			$api->setAccessToken($sessionAccessToken);
		} catch (\Throwable $exception) {
			// Debug environments must not render provider exceptions that could include OAuth data.
			$this->log('error', __FUNCTION__, 'Spotify authorization processing failed');
			return $this->authorizationProcessingFailure($request, $response);
		}

		return $this->responseWithJson($request, $response, [
			'message' => 'Spotify authorization completed.',
		], 200)->withHeader('Cache-Control', 'no-store');
	}

	private function hasValidOperatorCredentials(Request $request): bool
	{
		$authorization = $request->getHeaderLine('Authorization');
		if (!str_starts_with($authorization, 'Basic ')) {
			return false;
		}

		$decoded = base64_decode(substr($authorization, 6), true);
		if ($decoded === false || !str_contains($decoded, ':')) {
			return false;
		}

		[$username, $password] = explode(':', $decoded, 2);
		$expectedUsername = getenv('SPOTIFY_AUTH_USERNAME');
		$passwordHash = getenv('SPOTIFY_AUTH_PASSWORD_HASH');
		// Missing configuration disables initiation instead of creating a default credential.
		if (!is_string($expectedUsername) || $expectedUsername === '' || !is_string($passwordHash) || $passwordHash === '') {
			return false;
		}

		return hash_equals($expectedUsername, $username) && password_verify($password, $passwordHash);
	}

	private function authenticationRequired(Request $request, Response $response): Response
	{
		return $this->responseWithJson($request, $response, [
			'message' => 'Spotify authorization requires operator authentication.',
		], 401)
			->withHeader('WWW-Authenticate', 'Basic realm="Spotify authorization", charset="UTF-8"')
			->withHeader('Cache-Control', 'no-store');
	}

	protected function persistTokens(string $accessToken, string $refreshToken): void
	{
		// These values are credentials, so never pass a logger into the generic config updater.
		Config::updateSection('spotify_access_token', $accessToken);
		Config::updateSection('spotify_refresh_token', $refreshToken);
	}

	private function authorizationFailure(Request $request, Response $response): Response
	{
		return $this->responseWithJson($request, $response, [
			'message' => 'Spotify authorization could not be completed. Restart the authorization process.',
		], 400)->withHeader('Cache-Control', 'no-store');
	}

	private function authorizationProcessingFailure(Request $request, Response $response): Response
	{
		return $this->responseWithJson($request, $response, [
			'message' => 'Spotify authorization could not be completed. Restart the authorization process.',
		], 502)->withHeader('Cache-Control', 'no-store');
	}
}
