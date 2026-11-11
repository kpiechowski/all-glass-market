<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Catalogue\Domain\Entities;

use App\Modules\Catalogue\Domain\Entities\GlassModel;
use App\Modules\Catalogue\Domain\Exceptions\GlassModelRefused;
use App\Modules\Catalogue\Domain\ValueObjects\Eurocode;
use App\Modules\Catalogue\Domain\ValueObjects\EurocodeHolder;
use App\Modules\Catalogue\Domain\ValueObjects\OemCode;
use PHPUnit\Framework\TestCase;

/** RULE-01, RULE-02, RULE-04, RULE-05, WF-01, WF-03, WF-04. */
class GlassModelTest extends TestCase
{
    private function model(): GlassModel
    {
        return GlassModel::create(Eurocode::from('8613AGS'), 1, null, [], [], null);
    }

    public function test_a_new_model_has_its_main_eurocode_as_its_only_eurocode_and_is_active(): void
    {
        $model = $this->model();

        $this->assertNull($model->id());
        $this->assertSame('8613AGS', $model->mainEurocode()->value);
        $this->assertCount(1, $model->eurocodes());
        $this->assertFalse($model->isWithdrawn());
    }

    public function test_a_eurocode_held_by_another_model_is_refused_naming_it(): void
    {
        try {
            GlassModel::create(Eurocode::from('8613AGS'), 1, null, [], [], new EurocodeHolder(7, '8613XYZ'));
            $this->fail('A taken Eurocode was accepted');
        } catch (GlassModelRefused $e) {
            $this->assertSame('8613XYZ', $e->parameters['holder']);
        }
    }

    public function test_a_model_without_a_position_is_refused(): void
    {
        $this->expectException(GlassModelRefused::class);

        GlassModel::create(Eurocode::from('8613AGS'), null, null, [], [], null);
    }

    public function test_oem_codes_equal_by_comparison_are_kept_once(): void
    {
        $model = GlassModel::create(Eurocode::from('8613AGS'), 1, null, [3, 3], [
            OemCode::tryFrom('1k0-845'),
            OemCode::tryFrom('1K0 845'),
            OemCode::tryFrom('2K0845'),
        ], null);

        $this->assertSame(['1K0-845', '2K0845'], array_map(fn (OemCode $c): string => $c->value, $model->oemCodes()));
        $this->assertSame([3], $model->equipmentIds());
    }

    public function test_changing_parameters_reports_a_change(): void
    {
        $model = $this->model();

        $this->assertTrue($model->changeParameters(2, 5, [3, 4], [OemCode::tryFrom('A1')]));
        $this->assertSame(2, $model->positionId());
        $this->assertSame(5, $model->colourId());
        $this->assertSame([3, 4], $model->equipmentIds());
    }

    public function test_saving_the_same_parameters_is_no_change(): void
    {
        $model = GlassModel::create(Eurocode::from('8613AGS'), 1, 5, [3, 4], [OemCode::tryFrom('A1')], null);

        $this->assertFalse($model->changeParameters(1, 5, [4, 3], [OemCode::tryFrom('a1')]));
    }

    public function test_changing_parameters_without_a_position_is_refused(): void
    {
        $this->expectException(GlassModelRefused::class);

        $this->model()->changeParameters(null, null, [], []);
    }

    public function test_a_withdrawn_model_can_still_be_changed(): void
    {
        $model = $this->model();
        $model->withdraw();

        $this->assertTrue($model->changeParameters(2, null, [], []));
        $this->assertTrue($model->isWithdrawn());
    }

    public function test_withdraw_and_restore_flip_the_flag(): void
    {
        $model = $this->model();

        $model->withdraw();
        $this->assertTrue($model->isWithdrawn());

        $model->restore();
        $this->assertFalse($model->isWithdrawn());
    }

    public function test_withdrawing_twice_is_refused(): void
    {
        $model = $this->model();
        $model->withdraw();

        $this->expectException(GlassModelRefused::class);

        $model->withdraw();
    }

    public function test_restoring_an_active_model_is_refused(): void
    {
        $this->expectException(GlassModelRefused::class);

        $this->model()->restore();
    }

    public function test_an_unused_model_can_be_deleted(): void
    {
        $this->model()->ensureCanBeDeleted([]);

        $this->addToAssertionCount(1);
    }

    private function modelWithTwo(): GlassModel
    {
        return GlassModel::reconstitute(1, [Eurocode::from('8613AGS'), Eurocode::from('8613BGS')], Eurocode::from('8613AGS'), 1, null, [], [], false);
    }

    private function codes(GlassModel $model): array
    {
        return array_map(fn (Eurocode $e): string => $e->value, $model->eurocodes());
    }

    public function test_a_free_eurocode_is_added_as_non_main(): void
    {
        $model = $this->model();

        $this->assertTrue($model->addEurocode(Eurocode::from('8613 bgs'), null));

        $this->assertSame(['8613AGS', '8613BGS'], $this->codes($model));
        $this->assertSame('8613AGS', $model->mainEurocode()->value);
    }

    public function test_adding_a_eurocode_the_model_already_holds_changes_nothing(): void
    {
        $model = $this->model();

        $this->assertFalse($model->addEurocode(Eurocode::from('8613AGS'), null));
        $this->assertCount(1, $model->eurocodes());
    }

    public function test_adding_a_eurocode_held_by_another_model_is_refused_naming_it(): void
    {
        $model = $this->model();

        try {
            $model->addEurocode(Eurocode::from('8613BGS'), new EurocodeHolder(7, '8613XYZ'));
            $this->fail('A taken Eurocode was accepted');
        } catch (GlassModelRefused $e) {
            $this->assertSame('8613XYZ', $e->parameters['holder']);
            $this->assertCount(1, $model->eurocodes());
        }
    }

    public function test_a_non_main_eurocode_is_removed(): void
    {
        $model = $this->modelWithTwo();

        $model->removeEurocode(Eurocode::from('8613BGS'));

        $this->assertSame(['8613AGS'], $this->codes($model));
    }

    public function test_the_last_eurocode_cannot_be_removed(): void
    {
        $this->expectException(GlassModelRefused::class);

        $this->model()->removeEurocode(Eurocode::from('8613AGS'));
    }

    public function test_the_main_eurocode_cannot_be_removed_before_another_is_main(): void
    {
        $model = $this->modelWithTwo();

        try {
            $model->removeEurocode(Eurocode::from('8613AGS'));
            $this->fail('The main Eurocode was removed');
        } catch (GlassModelRefused) {
            $this->assertCount(2, $model->eurocodes());
        }

        $model->changeMainEurocode(Eurocode::from('8613BGS'));
        $model->removeEurocode(Eurocode::from('8613AGS'));

        $this->assertSame(['8613BGS'], $this->codes($model));
    }

    public function test_a_eurocode_the_model_does_not_hold_cannot_be_removed_or_made_main(): void
    {
        $model = $this->modelWithTwo();

        try {
            $model->removeEurocode(Eurocode::from('NOPE1'));
            $this->fail('Removed a code the model does not hold');
        } catch (GlassModelRefused) {
        }

        $this->expectException(GlassModelRefused::class);
        $model->changeMainEurocode(Eurocode::from('NOPE1'));
    }

    public function test_the_main_eurocode_changes_to_another_held_one(): void
    {
        $model = $this->modelWithTwo();

        $this->assertTrue($model->changeMainEurocode(Eurocode::from('8613BGS')));
        $this->assertSame('8613BGS', $model->mainEurocode()->value);
        $this->assertFalse($model->changeMainEurocode(Eurocode::from('8613BGS')));
    }

    public function test_a_received_eurocode_joins_as_non_main(): void
    {
        $model = $this->model();

        $model->receiveEurocode(Eurocode::from('8613BGS'));

        $this->assertSame(['8613AGS', '8613BGS'], $this->codes($model));
        $this->assertSame('8613AGS', $model->mainEurocode()->value);
    }

    public function test_a_model_cannot_receive_a_eurocode_it_holds(): void
    {
        $this->expectException(GlassModelRefused::class);

        $this->model()->receiveEurocode(Eurocode::from('8613AGS'));
    }
}
