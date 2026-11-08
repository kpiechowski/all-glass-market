---
name: module-events
description: "Domain events, listeners and observers. Use when emitting an event, writing a listener, reacting in one module to something that happened in another, or touching audit and change logging."
---

# Events

An event says what happened without knowing who cares. That is how modules react to each other without importing each other's internals.

## Business events

```php
final readonly class OfferActivated   // Domain/Events/
{
    use Dispatchable;

    public function __construct(
        public int $offerId,
        public CarbonImmutable $activatedAt,
    ) {}
}
```

- Plain Laravel events, `final readonly`, named as a past-tense business fact (`OfferSold`, `ReservationExpired`).
- Carry **ids and values**. A listener needing more loads it through its own port or the owner's `ModuleApi`.
- The **command handler** dispatches it after persisting: the handler knows the intent, so the fact is named correctly. Since every change of state is a command, every change gets its event.
- The module where the fact occurred owns the event, even when only other modules listen.

## Listeners

`Application/Listeners/`, registered in the module's `{Name}EventServiceProvider` (`$listen`), which is listed in `{Name}ModuleServiceProvider::$eventProviders`.

- One listener, one reaction. Two unrelated reactions to one event are two listeners.
- A listener that changes state sends a command, with `Actor::system('<listener reason>')` unless the event carries the original actor.
- A listener with an external side effect (mail, notification, integration API call) implements `ShouldHandleEventsAfterCommit`, so a rolled-back transaction sends nothing.

Choose the channel by what the caller needs:

| Need | Use |
|---|---|
| Something happened; whoever cares may react | event |
| A result back, or failure must stop the caller | the other module's `ModuleApi` |

**When the emitting module does not exist yet**, leave the `$listen` entry commented with a note naming the module it waits for. Use no `class_exists()` guard, since that turns a typo into silence.

## Observers and audit

The repository adapter saves through Eloquent inside the write scope, so model observers fire. An observer is **thin**: it calls Core's `ChangeRecorder` from `created`, `updated`, `deleted` and (for soft-deleting models) `restored`, and does nothing else. `ChangeRecorder` emits the generic Core event `RecordChanged`: model class, id, **kind** (`ChangeKind`: Created, Changed, Deleted, Restored), field changes (raw old and new values), relation changes (raw ids added and removed per relation) and the **Actor** of the command being handled. The Audit module listens to `RecordChanged` to build the change log, without knowing which modules exist.

**The Actor** comes from the command bus, which holds the current command's `Actor` (`public Actor $actor` on the command) for the length of the command. A nested command applies its own Actor and the outer one returns after it. A change recorded with no command being handled throws `NoCurrentActorException`; there is no fallback System actor. In tests, wrap writes in `app(ActorStack::class)->run($actor, ...)`. Impersonation needs nothing special: the Actor is recorded as the command carries it.

**Recording is buffered per command and synchronous.** `ChangeRecorder` does not dispatch at once: it buffers in the current command's frame on the `ActorStack`. When the command finishes successfully the frame coalesces its changes to **one `RecordChanged` per record** (a Changed folds into an earlier Created or Changed of the same record: first `old`, last `new`, relation diffs united, a net-empty Changed dropped; Deleted and Restored are never folded) and dispatches them with that command's Actor, **before the transaction commits** (`HoldCommandActor` runs inside `WrapCommandInTransaction`). A command that throws dispatches nothing. A nested command flushes its own frame with its own Actor. Never defer recording with `ShouldDispatchAfterCommit` or a queued listener: if the entry cannot be stored the command rolls back.

**Relations:** observers cannot see pivot syncs. An adapter syncs an audited many-to-many relation only through `EloquentRepository::syncRelation($id, $relation, $ids)`, which buffers the diff as a Changed carrying only relation changes; the frame folds it into the record's other changes, so a listener sees one event with fields and relations together (nothing is buffered when the relation did not change).

Observers live in `Infrastructure/Persistence/Observers/` and are registered in the event provider's `$observers`. Business events are named by the handler; an observer never reads dirty fields to decide which business event happened.

## Before you finish

- Every business event is emitted by a command handler after `add()` or `save()`.
- Every event payload holds ids and values only.
- Every listener does one thing, and external side effects run after commit.
- Every observer only calls `ChangeRecorder`, and no audited relation is synced outside `syncRelation()`.
- Every listener has a test: dispatch the event, assert the reaction (`module-testing`).
