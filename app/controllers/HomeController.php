<?php

namespace App\Controllers;

use PageLink;
use DI\Container;
use App\Models\Config;
use Slim\Psr7\Request;
use App\Models\Project;
use Slim\Psr7\Response;
use App\Models\ProcessStep;
use SpotifyWebAPI\SpotifyWebAPI;
use App\Controllers\BaseController;
use App\Models\QuestionAnswerItems;
use SpotifyWebAPI\SpotifyWebAPIException;

class HomeController extends BaseController
{
	protected $container;
	protected $logger;
	protected $siteName;

	public function __construct(Container $container)
	{
		$this->container = $container;
		$this->logger = $this->container->get('homeLogger');
		$this->siteName = 'Joey Gallegos';
	}

	public function getHomePage(Request $request, Response $response, array $args)
	{
		return $this->container->get('view')->render($response, 'home.twig', [
			'header_space_after' => true,
			'page' => [
				'title' => 'Home | ' . $this->siteName
			],
			'steps' => ProcessStep::where('active', 1)->orderBy('sequence', 'asc')->get(),
			'projects' => $projects = Project::where('active', 1)->get(),
			'env' => [
				'profile_pic' => getenv('PROFILE_PIC')
			]
		]);
	}

	// SOURCE: https://developer.spotify.com/documentation/web-api/tutorials/refreshing-tokens
	public function getLinksPage(Request $request, Response $response, array $args)
	{
		// this returns a class - not array, so use accessors
		// artists may contain more than one, so start at 0
		// die('reached');
		$spotify = null;
		$currentTrack = null;
		try {
			$spotify = $this->container->get('spotify');
			$currentTrack = $spotify['api']->getMyCurrentTrack();
			// TODO: Exception when no music is playing. Null value comes forward, but function throws exception because not expected
		} catch (\Exception) {
			// Spotify exceptions may include request or account details, so record only the failed operation.
			$this->logger->error('Spotify current-track request failed');
		}

		$spotifyData = null;
		if (is_null($spotify) || is_null($currentTrack)) {
			// The service array contains the authenticated API session and must not be serialized into logs.
			$this->logger->info('Spotify current-track data unavailable');
		} else {
			$spotifyData = [
				'song' => $currentTrack->item->name,
				'artist' => $currentTrack->item->artists[0]->name,
				'link' => $currentTrack->item->external_urls->spotify
			];
		}

		// get link objects
		$pageLinks = PageLink::orderBy('order')->get();

		return $this->container->get('view')->render($response, 'links.twig', [
			'header_space_after' => true,
			'page' => [
				'title' => 'Links | ' . $this->siteName
			],
			'pageLinks' => $pageLinks,
			'env' => [
				'profile_pic' => getenv('PROFILE_PIC')
			],
			'currentSongSpotify' => $spotifyData,
		]);
	}

	public function getAboutPage(Request $request, Response $response, array $args)
	{
		return $this->container->get('view')->render($response, 'about.twig', [
			'header_space_after' => true,
			'page' => [
				'title' => 'About | ' . $this->siteName
			],
		]);
	}

	public function getFaqPage(Request $request, Response $response, array $args)
	{
		return $this->container->get('view')->render($response, 'faq.twig', [
			'questionItems' => QuestionAnswerItems::all(),
			'header_space_after' => true,
			'page' => [
				'title' => 'FAQ | ' . $this->siteName
			],
		]);
	}

	public function getExperiencePage(Request $request, Response $response, array $args)
	{
		return $this->container->get('view')->render($response, 'experience.twig', [
			'header_space_after' => true,
			'page' => [
				'title' => 'Experience | ' . $this->siteName
			],
		]);
	}

	public function getStylePage(Request $request, Response $response, array $args)
	{
		return $this->container->get('view')->render($response, 'style.twig', [
			'page' => [
				'title' => 'Style | ' . $this->siteName
			]
		]);
	}
}
