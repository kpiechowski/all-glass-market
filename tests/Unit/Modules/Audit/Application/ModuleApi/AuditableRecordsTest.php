<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Audit\Application\ModuleApi;

use App\Modules\Audit\Application\ModuleApi\AuditableRecords;
use App\Modules\Audit\Application\ModuleApi\AuditableRecordType;
use PHPUnit\Framework\TestCase;

class AuditableRecordsTest extends TestCase
{
    public function test_a_registered_type_is_found_and_an_unregistered_one_is_ignored(): void
    {
        $records = new AuditableRecords;
        $type = new AuditableRecordType('things.thing', 'App\\Thing', 'Rzecz', fn (): string => 'x', fn (): ?string => null, excludedFields: ['secret']);

        $records->register($type);

        $this->assertSame($type, $records->find('App\\Thing'));
        $this->assertTrue($records->isRegistered('App\\Thing'));
        $this->assertSame($type, $records->findByKey('things.thing'));
        $this->assertNull($records->findByKey('App\\Thing'));
        $this->assertNull($records->find('App\\Other'));
        $this->assertFalse($records->isRegistered('App\\Other'));
    }

    public function test_every_field_is_audited_except_the_excluded_ones(): void
    {
        $type = new AuditableRecordType('things.thing', 'App\\Thing', 'Rzecz', fn (): string => 'x', fn (): ?string => null, excludedFields: ['secret']);

        $this->assertFalse($type->isAudited('secret'));
        $this->assertTrue($type->isAudited('price'));

        foreach (['id', 'created_at', 'updated_at', 'deleted_at'] as $technical) {
            $this->assertFalse($type->isAudited($technical));
        }
    }
}
