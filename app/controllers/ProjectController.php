<?php
namespace App\Controllers;

use App\Models\Project;
use App\Models\Service;
use DI\Container;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Slim\Exception\HttpNotFoundException;

class ProjectController extends BaseController {
	protected $container;
	protected $logger;

	// {"metadata_errors":[{"message":"Invalid request. This video was deleted."}]}
	public function __construct(Container $container)
	{
		$this->container = $container;
		$this->logger = $this->container->get('projectLogger');
	}

	public function getProjectPage(Request $request, Response $response, array $args)
	{
		$descriptor = sanitize($args['descriptor']);

		$project = Project::where('slug', '=', $descriptor)->first();
		if (is_null($project))
		{
			throw new HttpNotFoundException($request, 'Project was not found');
		}
		return $this->getResponseForProject($response, $project);
	}

	/**
	 * Gets the specific response object for a project object
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	protected function getResponseForProject(Response $response, Project $project)
	{
		$relations = $this->getProjectRelations($project);

		$data = [
			'project' => $project,
			'stats' => $relations['stats'],
			'scopeItems' => $relations['scopeItems'],
			'testimony' => $relations['testimony'],
			'header_space_after' => empty($relations['stats']),
			'page_title' => $project->title . ' | Joey Gallegos',
			'services' => Service::all()
		];

		$templateFile = '/portfolio/' . $project->slug . '.twig';
		return $this->container->view->render($response, $templateFile, $data);
	}

	protected function getProjectRelations(Project $project): array
	{
		$stats = $project->getStats;
		$scopeItems = $project->getScopeItems;
		$scopeItemCount = $scopeItems->count();

		return [
			'stats' => $stats->isNotEmpty() ? $stats : [],
			// Round up so odd totals remain balanced, and never pass Collection::chunk() a zero or fractional size.
			'scopeItems' => $scopeItems->isNotEmpty()
				? $scopeItems->chunk(max(1, (int) ceil($scopeItemCount / 2)))
				: [],
			'testimony' => $project->getTestimony ?? [],
		];
	}
}
