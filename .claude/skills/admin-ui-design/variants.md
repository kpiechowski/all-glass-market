# Variants

A variant is one of our classes set on a schema component with `->extraAttributes(['class' => '…'])`. It is rung 2 of the styling ladder: use it when the native look does not fit the place. Its CSS lives in the component's own file (`resources/css/admin/filament/<area>/<component>.css`).

- Only the classes below go into `extraAttributes`. A new variant is added only when a real module needs one, and goes into this file once the user accepts it on the Styleguide.
- One variant per component. The Styleguide ("Sekcje i układ", "Karta rekordu") shows each one next to the default.

## Sections

| Variant | Use for | Not for |
|---|---|---|
| default | every section; a section inside a section is already tighter | — |
| `section--compact` | a nested section that must be denser than the default nested one: repeated blocks, sections side by side | a top-level form or record section |
| `section--flat` | grouping fields inside another section (one address, one block) with a title and a hairline, no frame | a top-level section; a block that should read as its own card |

```php
Section::make('Magazyny')->schema([
    Section::make('Magazyn Wrocław')
        ->extraAttributes(['class' => 'section--compact'])
        ->columns(['default' => 1, 'md' => 2])
        ->schema([…]),
]);

Section::make('Dane firmy')->schema([
    Section::make('Adres siedziby')
        ->extraAttributes(['class' => 'section--flat'])
        ->columns(['default' => 1, 'md' => 12])
        ->schema([…]),
    Section::make('Adres do faktur')
        ->extraAttributes(['class' => 'section--flat'])
        ->schema([…]),
]);
```

## Tabs

| Variant | Use for | Not for |
|---|---|---|
| default | tabs inside a form or a section: plain underline | — |
| `tabs--underline` | page and record tabs | — |
| `tabs--pills` | a switcher between views inside a section ("Dziś / 7 dni / 30 dni") | page or record tabs; groups of form fields |

- Page and record tabs (uncontained, outside any section) get the underline look **without** the class. Set `tabs--underline` on them anyway to name the intent; it also forces that look where the default would not reach (inside a section or a contained card).
- `tabs--pills` works only on horizontal, uncontained Tabs: `->contained(false)`, never `->vertical()`.

```php
Tabs::make('Zakładki rekordu')
    ->contained(false)
    ->extraAttributes(['class' => 'tabs--underline'])
    ->tabs([…]);

Section::make('Sprzedaż')->schema([
    Tabs::make('Okres')
        ->contained(false)
        ->extraAttributes(['class' => 'tabs--pills'])
        ->tabs([Tab::make('Dziś')->schema([…]), Tab::make('7 dni')->schema([…])]),
]);
```

## Infolist

Both live in `filament/infolists/entry.css`; the full record view is [template C](templates/C-record-view.md).

| Variant | Use for | Not for |
|---|---|---|
| `infolist--sheet` | every infolist section of a record view: label left, value right, hairline rows, stacked on mobile | form sections; with `->inlineLabel()` (the theme does the layout) |
| `infolist--identity` | the heading-less section that opens a record: avatar, name, main contact | anything but the first block of a record view |

- A sheet section with short values runs two sheet columns from xl: `->columns(['default' => 1, 'xl' => 2])`; long values take `->columnSpanFull()`.
- The identity section has no heading; its entries hide their labels with `->hiddenLabel()`.

```php
Section::make(__('customers::resources.customer.sections.company'))
    ->extraAttributes(['class' => 'infolist--sheet'])
    ->columns(['default' => 1, 'xl' => 2])
    ->schema([…]);

Section::make()
    ->extraAttributes(['class' => 'infolist--identity'])
    ->schema([
        Flex::make([
            ImageEntry::make('avatar')->hiddenLabel()->circular()->imageSize(56)->grow(false),
            Group::make([
                TextEntry::make('name')->hiddenLabel()->size(TextSize::Large)->weight(FontWeight::Bold),
                TextEntry::make('email')->hiddenLabel()->copyable(),
            ]),
        ])->verticallyAlignCenter(),
    ]);
```
