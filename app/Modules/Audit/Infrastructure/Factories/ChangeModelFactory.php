<?php

declare(strict_types=1);

namespace App\Modules\Audit\Infrastructure\Factories;

use App\Core\Domain\Events\ChangeKind;
use App\Core\Infrastructure\Factories\ModelFactory;
use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;

/** @extends ModelFactory<ChangeModel> */
final class ChangeModelFactory extends ModelFactory
{
    protected $model = ChangeModel::class;

    public function definition(): array
    {
        return [
            'record_type' => 'App\\Modules\\Example\\Infrastructure\\Persistence\\Models\\ExampleModel',
            'record_id' => fake()->numberBetween(1, 1000),
            'kind' => ChangeKind::Changed,
            'title' => null,
            'changes' => ['name' => ['old' => 'a', 'new' => 'b']],
            'relation_changes' => [],
            'actor_id' => fn (): int => UserModel::withoutEvents(fn (): int => UserModel::factory()->create()->id), // the actor's own creation is not part of the history under test
            'actor_reason' => null,
            'occurred_at' => now(),
        ];
    }

    public function bySystem(string $reason = 'Harmonogram'): static
    {
        return $this->state(fn (): array => ['actor_id' => null, 'actor_reason' => $reason]);
    }

    public function forRecord(string $recordType, int $recordId): static
    {
        return $this->state(fn (): array => ['record_type' => $recordType, 'record_id' => $recordId]);
    }

    public function restoredFrom(ChangeModel $source): static
    {
        return $this->state(fn (): array => ['restored_from_id' => $source->id, 'restored_from_at' => $source->occurred_at]);
    }

    public function deleted(?string $title = null): static
    {
        return $this->state(fn (): array => [
            'kind' => ChangeKind::Deleted, 'title' => $title, 'changes' => [], 'relation_changes' => [],
        ]);
    }
}
