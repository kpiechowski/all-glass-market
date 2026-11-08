<?php

declare(strict_types=1);

namespace Tests\Fixtures\Audit;

use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A referenced, soft-deleting record. */
final class ProducerModel extends ReadOnlyModel
{
    use SoftDeletes;

    protected $table = 'audit_producers';

    protected $guarded = [];
}
