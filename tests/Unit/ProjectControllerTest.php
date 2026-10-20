<?php

declare(strict_types=1);

use App\Controllers\ProjectController;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

final class ProjectControllerTest extends TestCase
{
	public function testEmptyRelationsAreHandledWithoutCountingNull(): void
	{
		$relations = $this->normalizeRelations([], [], null);

		self::assertSame([], $relations['stats']);
		self::assertSame([], $relations['scopeItems']);
		self::assertSame([], $relations['testimony']);
	}

	public function testSingleScopeItemUsesAPositiveChunkSize(): void
	{
		$relations = $this->normalizeRelations([], ['one'], null);

		self::assertSame([1], $relations['scopeItems']->map->count()->values()->all());
	}

	public function testOddScopeItemCountIsSplitIntoTwoBalancedChunks(): void
	{
		$relations = $this->normalizeRelations([], ['one', 'two', 'three'], null);

		self::assertSame([2, 1], $relations['scopeItems']->map->count()->values()->all());
	}

	public function testNonEmptyStatsAndTestimonyArePreserved(): void
	{
		$testimony = new stdClass();
		$relations = $this->normalizeRelations(['stat'], [], $testimony);

		self::assertSame(['stat'], $relations['stats']->all());
		self::assertSame($testimony, $relations['testimony']);
	}

	private function normalizeRelations(array $stats, array $scopeItems, ?object $testimony): array
	{
		$project = new Project();
		$project->setRelation('getStats', new Collection($stats));
		$project->setRelation('getScopeItems', new Collection($scopeItems));
		$project->setRelation('getTestimony', $testimony);

		$controller = new class extends ProjectController {
			public function __construct()
			{
			}

			public function normalize(Project $project): array
			{
				return $this->getProjectRelations($project);
			}
		};

		return $controller->normalize($project);
	}
}
