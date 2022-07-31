<?php
namespace App\Controllers;
use App\Models\ProcessStep;
use App\Models\Project;
use App\Models\QuestionAnswerItems;
use Slim\Http\Request;
use Slim\Http\Response;

class HomeController extends BaseController {
	protected $container;
	protected $logger;

	public function __construct($container)
	{
		$this->container = $container;
		$this->logger = $this->container->homeLogger;
		$this->siteName = 'Joey Gallegos';
	}

	public function getHomePage(Request $request, Response $response, array $args)
	{
		return $this->container->view->render($response, 'home.twig', [
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
		return $this->container->view->render($response, 'about.twig', [
			'header_space_after' => true,
			'page' => [
				'title' => 'About | ' . $this->siteName
			],
		]);
	}

	public function getFaqPage(Request $request, Response $response, array $args)
	{
		return $this->container->view->render($response, 'faq.twig', [
			'questionItems' => QuestionAnswerItems::all(),
			'header_space_after' => true,
			'page' => [
				'title' => 'FAQ | ' . $this->siteName
			],
		]);
	}

	public function getExperiencePage(Request $request, Response $response, array $args)
	{
		return $this->container->view->render($response, 'experience.twig', [
			'header_space_after' => true,
			'page' => [
				'title' => 'Experience | ' . $this->siteName
			],
		]);
	}

	public function getStylePage(Request $request, Response $response, array $args)
	{
		return $this->container->view->render($response, 'style.twig', [
			'page' => [
				'title' => 'Style | ' . $this->siteName
			]
		]);
	}
}