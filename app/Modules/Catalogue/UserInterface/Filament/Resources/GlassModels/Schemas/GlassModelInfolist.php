<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Schemas;

use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions\AddEurocodeAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions\ChangeMainEurocodeAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions\MoveEurocodeAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions\RemoveEurocodeAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Support\GlassModelViewSections;
use Filament\Actions\ActionGroup;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * Template C, layout (b): the state and dates stay in view beside the data. FR-25: sections other
 * modules register follow under both columns.
 */
class GlassModelInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(['default' => 1, 'lg' => 3])->schema([
                Group::make([self::identitySection(), self::codesSection()])
                    ->columnSpan(['default' => 'full', 'lg' => 2])
                    ->columnOrder(['default' => 2, 'lg' => 1]),
                Group::make([self::statusSection()])
                    ->columnSpan(['default' => 'full', 'lg' => 1])
                    ->columnOrder(['default' => 1, 'lg' => 2]),
            ]),
            self::extensionSections(),
        ]);
    }

    private static function identitySection(): Section
    {
        return Section::make(__('catalogue::resources.glass_model.sections.identity'))
            ->key('identity')
            ->extraAttributes(['class' => 'infolist--sheet'])
            ->headerActions([self::eurocodeActions()])
            ->schema([
                TextEntry::make('mainEurocode.code')
                    ->label(__('catalogue::resources.glass_model.fields.main_eurocode'))
                    ->formatStateUsing(fn (?string $state): HtmlString => self::mainEurocodeBadge((string) $state))
                    ->copyable(),
                TextEntry::make('eurocodes.code')
                    ->label(__('catalogue::resources.glass_model.fields.eurocodes'))
                    ->badge()
                    ->color('gray')
                    ->copyable(),
                TextEntry::make('position.name')
                    ->label(__('catalogue::resources.glass_model.fields.position'))
                    ->badge()
                    ->color('gray'),
                TextEntry::make('colour.name')
                    ->label(__('catalogue::resources.glass_model.fields.colour'))
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
                TextEntry::make('equipment.name')
                    ->label(__('catalogue::resources.glass_model.fields.equipment'))
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
            ]);
    }

    private static function codesSection(): Section
    {
        return Section::make(__('catalogue::resources.glass_model.sections.codes'))
            ->extraAttributes(['class' => 'infolist--sheet'])
            ->schema([
                TextEntry::make('oemCodes.code')
                    ->label(__('catalogue::resources.glass_model.fields.oem_codes'))
                    ->hiddenLabel()
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
            ]);
    }

    /** The Eurocode actions concern the codes of this section only, so they sit in its header. */
    private static function eurocodeActions(): ActionGroup
    {
        return ActionGroup::make([
            AddEurocodeAction::make(),
            ChangeMainEurocodeAction::make(),
            MoveEurocodeAction::make(),
            RemoveEurocodeAction::make(),
        ])->label(__('catalogue::resources.glass_model.actions.eurocodes.label'))
            ->icon('heroicon-o-hashtag')
            ->button()
            ->color('gray');
    }

    /** The same soft primary badge as the main Eurocode on the catalogue card. */
    private static function mainEurocodeBadge(string $code): HtmlString
    {
        return new HtmlString('<span class="glass-card__code glass-card__code--main">'.e($code).'</span>');
    }

    private static function statusSection(): Section
    {
        return Section::make(__('catalogue::resources.glass_model.sections.status'))
            ->extraAttributes(['class' => 'infolist--sheet'])
            ->schema([
                TextEntry::make('withdrawn')
                    ->label(__('catalogue::resources.glass_model.fields.state'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => self::stateLabel($state))
                    ->color(fn (bool $state): string => $state ? 'gray' : 'success'),
                TextEntry::make('created_at')
                    ->label(__('catalogue::resources.glass_model.fields.created_at'))
                    ->date('d.m.Y'),
                TextEntry::make('updated_at')
                    ->label(__('catalogue::resources.glass_model.fields.updated_at'))
                    ->date('d.m.Y'),
            ]);
    }

    /** FR-25: renders nothing until a module registers a section. */
    private static function extensionSections(): Group
    {
        return Group::make()
            ->schema(fn (GlassModelModel $record): array => app(GlassModelViewSections::class)->for($record));
    }

    public static function stateLabel(bool $withdrawn): string
    {
        return __('catalogue::resources.glass_model.state.'.($withdrawn ? 'withdrawn' : 'active'));
    }
}
