---
name: module-testing
description: "How to test a module: what deserves a test and what does not, where test files live, and how to test command handlers, queries, domain policies, value objects, repositories, listeners and Filament pages. Use when writing tests for module code, adding tests to an existing module, or deciding whether something needs a test at all."
---

# Testing a module

Test the rules and the wiring. Don't test declarations.

The layers make this concrete: Domain holds rules worth testing in isolation, Application holds behaviour worth testing end to end, UserInterface mostly holds descriptions that a test would only restate.

## What to test

| Test it | Because |
|---|---|
| Command handlers | the write path — rules enforced, state changed, events fired |
| Query handlers | reads feeding decisions or calculations |
| Domain policies | pure business rules; cheapest and highest-value tests here |
| Value objects | validation and invariants |
| Repository methods beyond CRUD | a domain query returning the wrong set is a silent bug |
| Listeners | the reaction actually happens, and does one thing |
| Filament CRUD paths | that a page or action dispatches a command instead of writing directly |

## What not to test

- **Schema, table, form and infolist definitions.** Asserting a column exists restates the definition and breaks on every cosmetic change.
- **Plain CRUD passing straight through a repository**, with no rule in between.
- **Framework behaviour** — that Eloquent saves, that Ecotone routes, that a cast casts.
- **Getters, enum cases, constructor assignment.**

A test that fails only when you rename something is noise. A test that fails when behaviour changes is the point.

## Where tests live

```
tests/
  Unit/Modules/{Name}/        no database: policies, value objects, pure logic
  Feature/Modules/{Name}/     database: handlers, repositories, listeners, Filament
```

Matches the existing `phpunit.xml` — two suites, nothing to configure. PHPUnit 12, SQLite in memory, so `RefreshDatabase` is cheap.

Run with `dartisan test`, never `php artisan`. Narrow while working: `dartisan test --filter=ActivateOffer`.

## Handlers

Test through the bus with a real repository against the in-memory database. That exercises the actual query, the real binding and the events — which is what you want to know works.

```php
class ActivateOfferCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_activates_a_draft_offer(): void
    {
        $offer = Offer::factory()->draft()->create();

        app(CommandBus::class)->send(new ActivateOfferCommand($offer->id));

        $this->assertSame(OfferStatus::Active, $offer->fresh()->status);
    }

    public function test_it_refuses_an_offer_without_a_price(): void
    {
        $offer = Offer::factory()->draft()->withoutPrice()->create();

        $this->expectException(OfferCannotBeActivatedException::class);

        app(CommandBus::class)->send(new ActivateOfferCommand($offer->id));
    }
}
```

Cover the refusal, not only the happy path — the refusal is the rule. Assert events with `Event::fake()` where the event is the point of the command.

Every model needs a factory (`Infrastructure/Factories/`), with states for the cases worth testing. Factory states are what keep handler tests readable.

## Policies and value objects

Pure, so test them pure — no `RefreshDatabase`, no container, in `tests/Unit/`:

```php
public function test_a_draft_offer_without_a_price_cannot_be_activated(): void
{
    $offer = new Offer(['status' => OfferStatus::Draft, 'price' => null]);

    $this->assertFalse((new OfferDomainPolicy())->canBeActivated($offer));
}
```

These are the fastest tests you will write and they cover the rules that matter most. When a policy needs the database to answer, it is doing orchestration and belongs in `Application/Services/` instead.

## Repositories

Test the Eloquent adapter against the database, and only the methods carrying domain meaning:

```php
public function test_it_returns_only_active_offers_for_the_vehicle(): void
{
    $vehicle = Vehicle::factory()->create();
    Offer::factory()->active()->for($vehicle)->create();
    Offer::factory()->draft()->for($vehicle)->create();
    Offer::factory()->active()->create();

    $this->assertCount(1, app(OfferRepository::class)->activeForVehicle($vehicle->id));
}
```

Note the third row: prove it *excludes* what it should. A query test that only creates matching rows passes with a missing `where`.

Resolve the port (`OfferRepository`), not the adapter — that also verifies the binding.

## Fakes

Default to the real adapter and the in-memory database. Writing a fake per repository means reimplementing `create`, `update`, `delete` and every query, which becomes a second Eloquent to maintain and mostly tests itself.

Substitute a fake when the real thing is genuinely in the way:

- an **external integration** — no test should call Otomoto or WooCommerce
- **forcing a failure** that is hard to arrange for real
- proving a handler is **agnostic to its implementation**, when that is the point of the test

This is where the port pays off in tests. Its main value is still design — the domain depends on nothing.

## Listeners and Filament

A listener test asserts the reaction happened: dispatch the event, assert the effect. If a listener does two unrelated things, that is the signal to split it, not to write a longer test.

For Filament, test that the write path goes through the bus — which is the rule most easily broken (`filament-module-ui`):

```php
Livewire::test(CreateUser::class)
    ->fillForm([...])
    ->call('create')
    ->assertHasNoFormErrors();

$this->assertDatabaseHas('users', ['email' => '…']);
```

One such test per resource is enough. Don't test each field; test that creating, updating and deleting work through their commands.

## Migration status

- **Users and Audit have no tests.** `tests/` holds `TestCase.php` and two `ExampleTest` stubs. New work is tested; the two existing modules get tests when they are reworked.
- **Only `UserFactory` exists.** Audit's models need factories before their handlers can be tested.
- **`TestCase` is empty.** Shared helpers (authenticating an admin, a Filament panel helper) belong there once a second test needs them — not before.
