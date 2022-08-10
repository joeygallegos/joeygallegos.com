<?php

namespace App\Controllers;

use DI\Container;
use App\Models\Project;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use App\Models\ProcessStep;
use App\Models\QuestionAnswerItems;

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
