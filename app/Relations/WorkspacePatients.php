<?php

namespace App\Relations;

use App\Models\Beneficiary;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Resolves workspace patients across the mysql and main_sql connections.
 *
 * @extends Relation<Beneficiary, Workspace, Collection<int, Beneficiary>>
 */
final class WorkspacePatients extends Relation
{
    /** @var array<int|string, list<int>> */
    private array $beneficiaryIdsByWorkspace = [];

    public function addConstraints(): void
    {
        if (! self::$constraints) {
            return;
        }

        $workspaceId = $this->parent->getKey();

        if ($workspaceId === null) {
            $this->query->whereKey([]);

            return;
        }

        $this->beneficiaryIdsByWorkspace = $this->loadBeneficiaryIds([$workspaceId]);
        $this->query->whereKey($this->beneficiaryIdsByWorkspace[$workspaceId] ?? []);
    }

    /**
     * @param  array<int, Workspace>  $models
     */
    public function addEagerConstraints(array $models): void
    {
        $workspaceIds = array_values(array_filter(
            array_map(
                static fn (Workspace $workspace): mixed => $workspace->getKey(),
                $models,
            ),
            static fn (mixed $workspaceId): bool => $workspaceId !== null,
        ));

        $this->beneficiaryIdsByWorkspace = $this->loadBeneficiaryIds($workspaceIds);
        $beneficiaryIds = array_values(array_unique(array_merge(
            [],
            ...array_values($this->beneficiaryIdsByWorkspace),
        )));

        if ($beneficiaryIds === []) {
            $this->eagerKeysWereEmpty = true;

            return;
        }

        $this->query->whereKey($beneficiaryIds);
    }

    /**
     * @param  array<int, Workspace>  $models
     * @return array<int, Workspace>
     */
    public function initRelation(array $models, $relation): array
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->related->newCollection());
        }

        return $models;
    }

    /**
     * @param  array<int, Workspace>  $models
     * @param  Collection<int, Beneficiary>  $results
     * @return array<int, Workspace>
     */
    public function match(array $models, Collection $results, $relation): array
    {
        $patientsById = $results->keyBy(
            static fn (Beneficiary $beneficiary): int|string => $beneficiary->getKey(),
        );

        foreach ($models as $workspace) {
            $patientIds = $this->beneficiaryIdsByWorkspace[$workspace->getKey()] ?? [];
            $patients = array_values(array_filter(array_map(
                static fn (int $beneficiaryId): ?Beneficiary => $patientsById->get($beneficiaryId),
                $patientIds,
            )));

            $workspace->setRelation($relation, $this->related->newCollection($patients));
        }

        return $models;
    }

    /** @return Collection<int, Beneficiary> */
    public function getResults(): Collection
    {
        if ($this->parent->getKey() === null) {
            return $this->related->newCollection();
        }

        return $this->query->get();
    }

    /**
     * @param  list<int|string>  $workspaceIds
     * @return array<int|string, list<int>>
     */
    private function loadBeneficiaryIds(array $workspaceIds): array
    {
        if ($workspaceIds === []) {
            return [];
        }

        $idsByWorkspace = [];
        $links = WorkspaceBeneficiary::query()
            ->whereIn('workspace_id', $workspaceIds)
            ->whereNotNull('beneficiary_id')
            ->orderBy('id')
            ->get(['workspace_id', 'beneficiary_id']);

        foreach ($links as $link) {
            $workspaceId = $link->workspace_id;
            $beneficiaryId = $link->beneficiary_id;

            if ($beneficiaryId === null) {
                continue;
            }

            $idsByWorkspace[$workspaceId] ??= [];

            if (! in_array($beneficiaryId, $idsByWorkspace[$workspaceId], true)) {
                $idsByWorkspace[$workspaceId][] = $beneficiaryId;
            }
        }

        return $idsByWorkspace;
    }
}
