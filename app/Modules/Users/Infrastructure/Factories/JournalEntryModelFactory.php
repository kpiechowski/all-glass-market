<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Factories;

use App\Core\Infrastructure\Factories\ModelFactory;
use App\Modules\Users\Domain\Enums\JournalSeverity;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;

/** @extends ModelFactory<JournalEntryModel> */
final class JournalEntryModelFactory extends ModelFactory
{
    protected $model = JournalEntryModel::class;

    public function definition(): array
    {
        return [
            'user_id' => UserModel::factory(),
            'kind' => 'offer',
            'severity' => JournalSeverity::Info,
            'title' => fake()->sentence(4),
            'content' => fake()->sentence(),
            'subject_id' => null,
            'is_read' => false,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (): array => ['is_read' => true]);
    }

    public function forSubject(int $subjectId): static
    {
        return $this->state(fn (): array => ['subject_id' => $subjectId]);
    }
}
