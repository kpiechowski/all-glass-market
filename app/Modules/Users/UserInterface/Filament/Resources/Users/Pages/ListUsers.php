<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Pages;

use App\Modules\Users\Infrastructure\Persistence\Queries\UserQueries;
use App\Modules\Users\UserInterface\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

/** Template A (list): one primary action, the active/inactive split as list tabs. */
class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /** "Aktywni" is the working set; "Wszyscy" lets an Admin find a person whatever their state. */
    public function getTabs(): array
    {
        return [
            'active' => Tab::make(__('users::resources.user.tabs.active'))
                ->modifyQueryUsing(fn (Builder $query): Builder => app(UserQueries::class)->onlyActive($query)),
            'inactive' => Tab::make(__('users::resources.user.tabs.inactive'))
                ->modifyQueryUsing(fn (Builder $query): Builder => app(UserQueries::class)->onlyInactive($query)),
            'all' => Tab::make(__('users::resources.user.tabs.all')),
        ];
    }
}
