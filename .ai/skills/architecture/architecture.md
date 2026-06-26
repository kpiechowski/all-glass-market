# Architecture Guidelines

This document defines how to structure, expand, and reason about this codebase. It covers module anatomy, DDD boundaries, CQRS flow, and the rules for where each concern belongs.

---

## Module anatomy

Every module lives under `app/Modules/{Name}/` and follows this exact layout:

```
{Name}/
  {Name}ModuleServiceProvider.php       ← module entry point
  Application/
    Commands/
      {Action}{Name}/
        {Action}{Name}Command.php       ← readonly DTO
        {Action}{Name}CommandHandler.php
    Queries/
      {Action}{Name}/
        {Action}{Name}Query.php
        {Action}{Name}QueryHandler.php
    Providers/
      {Name}EventServiceProvider.php
    Services/                           ← only for logic that doesn't fit a command
  Domain/
    Dto/
    Enums/
    Events/
    Listeners/
    Models/
    Observers/
    Repositories/
    Traits/
    ValueObjects/
    *and similar*
  Infrastructure/
    config/
    Factories/
    migrations/
    Seeders/
    *apis for external integration*
    ...
  UserInterface/
    Filament/
      Clusters/
      Pages/
      Resources/
        {Name}/
          Pages/
          Schemas/
          Tables/
          {Name}Resource.php
      Widgets/
    resources/views/
    resources/scripts/
    resources/styles/
```

Scaffold a new module with:

```bash
dartisan make:module {Name}
```

---

## DDD layer rules

| Layer              | Allowed to know about                                        | Must NOT know about                                |
| ------------------ | ------------------------------------------------------------ | -------------------------------------------------- |
| **Domain**         | Models, ValueObjects, Enums, Repository interfaces           | Application, UserInterface, Infrastructure details |
| **Application**    | Domain (models, repositories, events), Ecotone buses         | UserInterface, HTTP, Filament                      |
| **Infrastructure** | Domain models                                                | Application, UserInterface                         |
| **UserInterface**  | Application (commands/queries via bus), Domain models (read) | Direct repository access, direct model mutation    |

**Never call a repository from a Filament page.** Dispatch a command or query through the bus instead.

**Never put business logic in a Filament page, schema, or table class.** Those are pure UI descriptors.

---

## CQRS with Ecotone

Ecotone is auto-discovered — no manual registration needed. Any class with `#[CommandHandler]` or `#[QueryHandler]` on a method is picked up automatically as long as it lives under `app/`.

### Command flow

```
Filament Page
  → $this->commandBus->send(new DoSomethingCommand(...))
    → DoSomethingCommandHandler::handle(DoSomethingCommand $command)
      → injects Repository via constructor
        → calls $this->repository->create/update/delete(...)
```

### Query flow

```
Filament Page / Resource
  → $this->queryBus->send(new GetSomethingQuery(...))
    → GetSomethingQueryHandler::handle(GetSomethingQuery $query)
      → returns typed result
```

### Command class rules

- Always `readonly`.
- Named `{Verb}{Subject}Command` / `{Verb}{Subject}Query`.

```php
readonly class CreateUserCommand
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}
```

### Handler class rules

- One public method named `handle`, annotated with `#[CommandHandler]` or `#[QueryHandler]`.
- Injects dependencies through the constructor (repository, services).
- Returns a typed value (the created/updated model, a DTO, a collection).

```php
class CreateUserCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    #[CommandHandler]
    public function handle(CreateUserCommand $command): User
    {
        return $this->userRepository->create([...]);
    }
}
```

### When to use a Service instead of a Command

Use `Application/Services/` only when an operation does not map to a single user intent (e.g. a background process, a multi-step orchestration called from multiple commands). A service is injected into command handlers, never into Filament pages directly.

---

## Repository pattern

Every module with a model gets a repository in `Domain/Repositories/` that extends `App\Core\Abstracts\ModelRepository`.

```php
class UserRepository extends ModelRepository
{
    protected function getModel(): string
    {
        return User::class;
    }
}
```

`ModelRepository` provides: `find`, `findOrFail`, `all`, `create`, `update`, `delete`, `query()` (for custom queries). Add domain-specific query methods to the concrete repository, not to the model.

---

## Buses in Filament pages

Use the Core traits on any Filament page or Livewire component that needs to dispatch:

```php
use App\Core\Concerns\HasCommandBus;
use App\Core\Concerns\HasQueryBus;

class CreateUser extends CreateRecord
{
    use HasCommandBus;

    protected function handleRecordCreation(array $data): Model
    {
        return $this->commandBus->send(new CreateUserCommand(...));
    }
}
```

Livewire calls `boot{TraitName}()` automatically — `$this->commandBus` and `$this->queryBus` are ready without constructor injection.

---

## Module service provider

Every module has a `{Name}ModuleServiceProvider` that extends `App\Core\Providers\ModuleServiceProvider`. Configure it with class properties only:

```php
class UsersModuleServiceProvider extends ModuleServiceProvider
{
    protected array $eventProviders = [UsersEventServiceProvider::class];
    protected array $containerBindings = [];
    protected bool $loadsMigrations = true;
}
```

`ModuleServiceProvider` handles automatically:

- Loading migrations from `Infrastructure/migrations/`
- Merging config files from `Infrastructure/config/`
- Loading views from `UserInterface/resources/views/` (namespace = snake_case module name)
- Building the Eloquent morph map from `Domain/Models/`
- Booting event providers listed in `$eventProviders`

Do not override `register()` or `boot()` unless the module needs something genuinely custom.

---

## Event providers

Module events live in `Application/Providers/{Name}EventServiceProvider` extending `CoreEventServiceProvider`. Wire observers and listeners via class properties:

```php
class UsersEventServiceProvider extends CoreEventServiceProvider
{
    protected $listen = [
        UserCreated::class => [SendWelcomeEmail::class],
    ];

    protected $observers = [
        User::class => UserObserver::class,
    ];
}
```

---

## Filament as UI only

Filament classes are strictly presentation:

| Class                         | Responsibility                                        |
| ----------------------------- | ----------------------------------------------------- |
| `{Name}Resource.php`          | Declares model, navigation, pages — no logic          |
| `Tables/{Name}Table.php`      | Column, filter, action definitions only               |
| `Schemas/{Name}Form.php`      | Form field definitions only                           |
| `Schemas/{Name}Infolist.php`  | Infolist entry definitions only                       |
| `Schemas/{Name/}Filters/.php` | Filters or other schema like classes                  |
| `Pages/Create{Name}.php`      | Overrides `handleRecordCreation` → dispatches command |
| `Pages/Edit{Name}.php`        | Overrides `handleRecordUpdate` → dispatches command   |

Filament auto-discovery is handled by the `DiscoverModuleFilament` trait on `AdminPanelProvider`. Any Resource/Page/Widget/Cluster placed in `UserInterface/Filament/` is registered automatically — no manual panel configuration required per module.

---

## Adding a new module — checklist

1. `dartisan make:module {Name}` — scaffolds all directories and the service provider.
2. Create Domain model + migration + factory.
3. Create `Domain/Repositories/{Name}Repository` extending `ModelRepository`.
4. For each user intent: create `Application/Commands/{Action}{Name}/` with Command + CommandHandler.
5. Create Filament Resource under `UserInterface/Filament/Resources/{Name}/`.
6. Pages call `$this->commandBus->send(...)` via `HasCommandBus`.
7. Register event provider in the module service provider if needed.
8. Run `dartisan migrate` and `vendor/bin/pint --dirty`.

---

## Cross-module communication

Modules must not import from each other's namespaces directly. Cross-module interaction goes through:

- **Domain Events** — emit from one module's domain, listen in another module's event provider.
- **Application Services** — only if the operation genuinely spans modules and event-driven flow is not appropriate.

---

## Core abstracts — when to extend vs. copy

| Abstract                   | Extend when                              |
| -------------------------- | ---------------------------------------- |
| `ModelRepository`          | Every model that needs repository access |
| `CoreEventServiceProvider` | Every module event provider              |
| `ModuleServiceProvider`    | Every module entry point                 |

Do not modify Core abstracts to fix a module-specific problem. Add to the concrete class or open a discussion about changing the abstract contract.
