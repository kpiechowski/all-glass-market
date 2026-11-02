<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Repositories;

use App\Core\Abstracts\ModelRepository;
use App\Modules\Audit\Domain\Models\ChangesLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ChangesLogRepository extends ModelRepository
{
    protected function getModel(): string
    {
        return ChangesLog::class;
    }

    public function forModel(Model $model): Builder
    {
        return $this->query()
            ->where('loggable_type', $model::class)
            ->where('loggable_id', $model->getKey());
    }

    public function forUser(int $userId): Builder
    {
        return $this->query()->where('user_id', $userId);
    }

    public function byAction(string $action): Builder
    {
        return $this->query()->where('meta_data->action', $action);
    }
}
