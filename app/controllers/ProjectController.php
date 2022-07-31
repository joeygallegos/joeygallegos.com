<?php
namespace App\Controllers;
use App\Models\Project;
use App\Models\Service;
use Slim\Http\Request;
use Slim\Http\Response;

class ProjectController extends BaseController {
	protected $container;
	protected $logger;

	// {"metadata_errors":[{"message":"Invalid request. This video was deleted."}]}
	public function __construct($container)
	{
		$this->container = $container;
		$this->logger = $this->container->projectLogger;
	}

	public function getProjectPage(Request $request, Response $response, array $args)
	{
		$descriptor = sanitize($args['descriptor']);

		$project = Project::where('slug', '=', $descriptor)->first();
		if (is_null($project))
		{
			// TODO: 404
		}
		return $this->getResponseForProject($response, $project);
	}

	/**
	 * Gets the specific response object for a project object
	 * @return Slim\Http\Response
	 */
	protected function getResponseForProject(Response $response, Project $project)
	{
		$projectHasStats = count($project->getStats) ? $project->getStats : [];
		$projectHasScopeItems = count($project->getScopeItems) ? $project->getScopeItems->chunk($project->getScopeItems->count() / 2) : [];
		$projectHasTestimony = count($project->getTestimony) ? $project->getTestimony : [];
		$headerSpaceAfter = empty($projectHasStats) ? true : false;

		$data = [
			'project' => $project,
			'stats' => $projectHasStats,
			'scopeItems' => $projectHasScopeItems,
			'testimony' => $projectHasTestimony,
			'header_space_after' => $headerSpaceAfter,
			'page_title' => $project->title . ' | Joey Gallegos',
			'services' => Service::all()
		];

		$templateFile = '/portfolio/' . $project->slug . '.twig';
		return $this->container->view->render($response, $templateFile, $data);
	}
}