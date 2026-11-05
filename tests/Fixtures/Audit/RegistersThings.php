<?php

declare(strict_types=1);

namespace Tests\Fixtures\Audit;

use App\Core\Domain\Actor;
use App\Core\Infrastructure\Bus\ActorStack;
use App\Core\Infrastructure\Bus\HoldCommandActor;
use App\Core\Infrastructure\Bus\WrapCommandInTransaction;
use App\Modules\Audit\Application\ModuleApi\AuditableRecords;
use App\Modules\Audit\Application\ModuleApi\AuditableRecordType;
use App\Modules\Audit\Application\ModuleApi\TrackedRelation;
use App\Modules\Audit\Application\ModuleApi\ValuePresenter;
use Ecotone\Lite\EcotoneLite;
use Ecotone\Modelling\CommandBus;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Test-only record type "Rzecz" registered with Audit, and a bus that writes it as a command. */
trait RegistersThings
{
    private CommandBus $thingBus;

    protected function setUpThings(bool $register = true): void
    {
        Schema::create('audit_producers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->softDeletes();
        });
        Schema::create('audit_things', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedInteger('price')->nullable();
            $table->string('status')->nullable();
            $table->string('secret')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('audit_thing_producers', function (Blueprint $table): void {
            $table->unsignedBigInteger('thing_id');
            $table->unsignedBigInteger('producer_id');
        });

        if ($register) {
            app(AuditableRecords::class)->register(new AuditableRecordType(
                key: 'things.thing',
                modelClass: ThingModel::class,
                name: 'Rzecz',
                title: fn (ThingModel $thing): string => 'Rzecz '.$thing->name,
                url: fn (ThingModel $thing): ?string => '/things/'.$thing->id,
                excludedFields: ['secret', 'synced_at'],
                fieldLabels: ['name' => 'Nazwa', 'price' => 'Cena', 'status' => 'Status'],
                valuePresenters: ['price' => ValuePresenter::money(), 'status' => ValuePresenter::enum(ThingStatus::class)],
                trackedRelations: ['producers' => new TrackedRelation('Producenci', ValuePresenter::reference(ProducerModel::class))],
                restorableFields: ['price', 'status'],
            ));
        }

        $this->thingBus = EcotoneLite::bootstrapFlowTesting(
            [ChangeThingHandler::class, HoldCommandActor::class, WrapCommandInTransaction::class],
            [new ChangeThingHandler, new HoldCommandActor(app(ActorStack::class)), new WrapCommandInTransaction],
        )->getGateway(CommandBus::class);
    }

    /** @param array<string, mixed> $attributes */
    protected function createThing(Actor $actor, array $attributes = ['name' => 'szyba'], array $producerIds = []): int
    {
        return $this->thingBus->send(new ChangeThingCommand($actor, 'create', attributes: $attributes, producerIds: $producerIds));
    }
}
