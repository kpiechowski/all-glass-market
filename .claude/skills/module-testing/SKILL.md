---
name: module-testing
description: "Testing and finishing work in a module. Use when writing or running tests, building an entity, value object, policy, mapper, repository method or handler test-first, or checking that a task is done with composer check."
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
| Filament resource | one Livewire test each for create, edit and delete, proving the write lands through the command | after | Feature |

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

The refusal is the rule, so it always gets its own test. Use `Event::fake([OfferActivated::class])` when the event is part of the intent. Arrange state with model factories (the Core base factory opens the write scope); resolve ports, not adapters, so the binding is tested too.

## Fakes

Default to real adapters and the in-memory database. Substitute a fake for an external integration, to force a failure that is hard to arrange, or when proving a handler is agnostic to its implementation.

## Running

```bash
dartisan test --compact --filter=ActivateOffer   # while working
composer check                                   # pint, deptrac, test presence, full phpunit
```

## Before you finish

- Every class in the "first" rows got its test before its code, and you saw that test fail first.
- Every handler test covers each refusal, and every query test proves exclusion.
- `composer check` passes. Report its result, including failures, as it is.
