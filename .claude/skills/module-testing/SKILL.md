---
name: module-testing
description: "Testing and finishing work in a module. Use when writing or running tests, building an entity, value object, policy, mapper, repository method or handler test-first, or checking that a task is done with composer check or composer check:module."
---

# Testing a module

Tests are how you prove your work. Rules and write paths are built **test-first**: write the test, watch it go **red** for the right reason, write the code until it goes **green**, then refactor. For the loop itself, load `mattpocock-skills:tdd`.

## What gets a test, and when

| Class | Test | Written | Suite |
|---|---|---|---|
| Entity | each behaviour method: state change and each refusal | first | Unit |
| Value object | valid input and each rejected format | first | Unit |
| Domain policy | each branch of each rule | first | Unit |
| Mapper | round-trip: `toEntity(model built from toRow(x))` equals `x` | first | Feature |
| Repository domain method | returns what matches **and excludes** what does not | first | Feature |
| `{X}Queries` method | same: includes and excludes | first | Feature |
| Command handler | through the bus: happy path, every refusal, event emitted, unauthorized actor rejected | first | Feature |
| Listener | dispatch the event, assert the reaction | first | Feature |
| Observer | a change through the repository records one `RecordChanged` with the expected kind and fields; a save without change records none | first | Feature |
| Filament resource | one Livewire test each for create, edit and delete, proving the write lands through the command; create once with every optional field empty | after | Feature |

Leave out tests that restate declarations: columns existing, enum cases, getters, casts, framework behaviour.

## Where tests live

```
tests/Unit/Modules/{Name}/...       no database, no container
tests/Feature/Modules/{Name}/...    in-memory SQLite, RefreshDatabase
```

Mirror the class path and name the file `{Class}Test.php`. The test-presence check in `composer check` matches on that name.

## Handlers

Send through the real bus with real adapters against the in-memory database. That exercises the binding, the transaction, the guard and the events.

```php
public function test_it_activates_a_draft_offer(): void
{
    $offer = OfferModelFactory::new()->draft()->create();

    app(CommandBus::class)->send(new ActivateOfferCommand(Actor::system('test'), $offer->id));

    $this->assertSame(OfferStatus::Active, $offer->fresh()->status);
}

public function test_it_refuses_an_offer_without_a_price(): void
{
    $offer = OfferModelFactory::new()->draft()->withoutPrice()->create();

    $this->expectException(OfferCannotBeActivated::class);

    app(CommandBus::class)->send(new ActivateOfferCommand(Actor::system('test'), $offer->id));
}
```

The refusal is the rule, so it always gets its own test, and the test names **which** refusal: one exception class often carries many reasons, so assert its `translationKey` along with the class.

```php
try {
    app(CommandBus::class)->send(new RemoveEurocodeCommand(Actor::system('test'), $model->id, $mainCode));
    $this->fail('Expected a refusal.');
} catch (GlassModelRefused $refusal) {
    $this->assertSame('catalogue::exceptions.glass_model.main_eurocode', $refusal->translationKey);
}
```

A Livewire test of a refusal asserts the notification's title, not only that one appeared. Use `Event::fake([OfferActivated::class])` when the event is part of the intent. Arrange state with model factories (the Core base factory opens the write scope); resolve ports, not adapters, so the binding is tested too.

## Fakes

Default to real adapters and the in-memory database. Substitute a fake for an external integration, to force a failure that is hard to arrange, or when proving a handler is agnostic to its implementation.

## Running

```bash
dartisan test --compact --filter=ActivateOffer   # while working: one class or test
composer check:module offers                     # closes a task: pint + phpstan + phpunit for the module(s), deptrac + architecture tests for all
composer check:module offers,catalogue           # several modules, comma-separated
composer check                                   # milestones: pint, deptrac, phpstan, test presence, full phpunit
```

**Which check closes the work.** Neither command has a time limit. The full run takes 5–6 minutes; `check:module` takes a fraction of that (Offers: about 1.5 min).

- **A task or an issue** closes with `composer check:module` over **every module its diff touches**: changed and untracked files under `app/Modules/<X>/` or `tests/*/Modules/<X>/`.
- **The full `composer check`** closes it instead when one of these holds:
  - the diff touches anything outside those module folders: `app/Core`, `app/Filament`, `app/Integrations`, `config`, `routes`, `lang`, `database`, `composer.json`, `deptrac.yaml`, `phpunit.xml`, or `tests/` outside `Modules`;
  - it is a **milestone**:
    - the last issue of a delivery run, or of a whole module;
    - a manager's handoff before `/clear`;
    - a run of a single issue;
    - a merge of a delivery branch into `main`.
- **A full check that fails at a milestone:** find the commit that broke it (`git bisect` over the run's commits, using `check:module` of the failing test's module), and fix it as a new fix round or issue before the milestone counts as done.

Never run two checks at once in the same directory: `ScaffoldCommandsTest` writes a temporary module into `app/Modules`, and the other run fails on it. Separate worktrees are fine.

## Before you finish

- Every class in the "first" rows got its test before its code, and you saw that test fail first.
- Every handler test covers each refusal and asserts its translation key, and every query test proves exclusion.
- The check that "Which check closes the work" requires passes: `check:module` over the touched modules, or the full `composer check` when the diff leaves the module folders or at a milestone. Report which one you ran and its result, including failures, as it is.
