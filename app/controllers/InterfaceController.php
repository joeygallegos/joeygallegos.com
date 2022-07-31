<?php

namespace App\Controllers;

use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use App\Models\Project;
use Slim\Http\Response;
use App\Controllers\BaseController;
use SpotifyWebAPI\SpotifyWebAPIException;

class InterfaceController extends BaseController
{
	protected $container;
	protected $logger;

	public function __construct($container)
	{
		$this->container = $container;
		$this->logger = $this->container->debugLogger;
	}

	public function getDebugPage(Request $request, Response $response, array $args)
	{
		$spotify = $this->container->spotify;
		$session = $spotify['session'];
		$api = $spotify['api'];

		$nowPlaying = null;
		try {
			$nowPlaying = $api->getMyCurrentTrack();
		} catch (SpotifyWebAPIException $ex) {
			$nowPlaying = null;
			$this->log('info', __FUNCTION__, 'Exception from the Spotify API');
			$this->log('info', __FUNCTION__, $ex->getMessage());
		}

		$debugData = [
			'environment' => getenv('ENVIRONMENT'),
			'host' => getenv('HOST'),
			'database' => getenv('DB_NAME'),
			'request-addr' => getRequestAddress(),
			'time' => [
				'current' => time()
			],
			'new-uuid' => Uuid::uuid4(),
			'flashes' => $this->container->flash->getFirstMessage('data'),
		];

		$debugData['now-playing'] = null;
		if (!is_null($nowPlaying)) {
			$debugData['now-playing'] = [
				'artist_name' => $nowPlaying->item->album->artists,
				'album' => [
					'image' => $nowPlaying->item->images,
					'name' => $nowPlaying->item->name
				]
			];
		}
		return $response->withHeader('Content-Type', 'application/json')->withJson($debugData, 200, JSON_PRETTY_PRINT);
	}

	public function getAPI(Request $request, Response $response, array $args)
	{
		$data = [];

		$projects = Project::where('active', 1)->get();
		foreach ($projects as $project) {

			// current project to array
			$projectArr = $project->toProjectArray();

			// push project array to API data array
			array_push($data, $projectArr);
		}
		return $response->withHeader('Content-Type', 'application/json')->withJson($data, 200, JSON_PRETTY_PRINT);
	}

	public function getTestPage(Request $request, Response $response, array $args)
	{
		// Config::updateRaw('profile_pic', '/assets/img/joey.jpg', $this->container->debugLogger);
		$data = [
			'success' => true,
			'flashes' => [
				[
					'type' => 'success',
					'message' => 'test flash message'
				]
			]
		];

		$this->log('info', __FUNCTION__, 'Test page request pinged');
		return $response->withHeader('Content-Type', 'application/json')->withJson($data, 200, JSON_PRETTY_PRINT);
	}
}
