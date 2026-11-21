<?php

declare(strict_types=1);

/** Known cross-module imports of private classes, and own-module imports of ModuleApi classes (grouped by module). */
return [
    // Audit
    'app/Modules/Audit/Application/Commands/RecordChange/RecordChangeCommandHandler.php => App\\Modules\\Audit\\Application\\ModuleApi\\AuditableRecords',
    'app/Modules/Audit/Application/Commands/RestoreChange/RestoreChangeCommandHandler.php => App\\Modules\\Audit\\Application\\ModuleApi\\AuditableRecords',
    'app/Modules/Audit/Application/Listeners/RecordChangeInHistory.php => App\\Modules\\Audit\\Application\\ModuleApi\\AuditableRecords',
    'app/Modules/Audit/Application/Queries/PresentChange/ChangePresenter.php => App\\Modules\\Audit\\Application\\ModuleApi\\AuditableRecords',
    'app/Modules/Audit/Application/Queries/PresentChange/ChangePresenter.php => App\\Modules\\Audit\\Application\\ModuleApi\\PresentedLine',
    'app/Modules/Audit/Application/Queries/PresentChange/ChangePresenter.php => App\\Modules\\Audit\\Application\\ModuleApi\\PresentedValue',
    'app/Modules/Audit/Application/Queries/PresentChange/ChangePresenter.php => App\\Modules\\Audit\\Application\\ModuleApi\\ValuePresenter',
    'app/Modules/Audit/UserInterface/Filament/Actions/RestoreChangeAction.php => App\\Modules\\Audit\\Application\\ModuleApi\\AuditableRecords',
    'app/Modules/Audit/UserInterface/Filament/Tables/AuditLogTable.php => App\\Modules\\Audit\\Application\\ModuleApi\\AuditableRecords',
    'app/Modules/Audit/UserInterface/Filament/Tables/AuditLogTableFilters.php => App\\Modules\\Audit\\Application\\ModuleApi\\AuditableRecords',
    // Catalogue
    'app/Modules/Catalogue/Application/Commands/DeleteDictionaryValue/DeleteDictionaryValueCommandHandler.php => App\\Modules\\Catalogue\\Application\\ModuleApi\\DictionaryValueUsages',
    'app/Modules/Catalogue/Application/Commands/DeleteGlassModel/DeleteGlassModelCommandHandler.php => App\\Modules\\Catalogue\\Application\\ModuleApi\\GlassModelUsages',
    'app/Modules/Catalogue/UserInterface/Console/SeedCatalogueConsoleCommand.php => App\\Modules\\Catalogue\\Application\\ModuleApi\\CatalogueTestData',
    'app/Modules/Catalogue/UserInterface/Filament/GlassModelPicker/Actions/CreateGlassModelInPlaceAction.php => App\\Modules\\Catalogue\\Application\\ModuleApi\\EurocodeSuggestions',
    'app/Modules/Catalogue/UserInterface/Filament/Pages/CatalogueSettings/Schemas/CatalogueSettingsForm.php => App\\Modules\\Catalogue\\Application\\ModuleApi\\GlassModelThumbnails',
    'app/Modules/Catalogue/UserInterface/Filament/Resources/GlassModels/Tables/GlassModelsTable.php => App\\Modules\\Catalogue\\Application\\ModuleApi\\GlassModelCardLink',
    'app/Modules/Catalogue/UserInterface/Filament/Resources/GlassModels/Tables/GlassModelsTable.php => App\\Modules\\Catalogue\\Application\\ModuleApi\\GlassModelLinks',
    'app/Modules/Catalogue/UserInterface/Filament/Resources/GlassModels/Tables/GlassModelsTable.php => App\\Modules\\Catalogue\\Application\\ModuleApi\\GlassModelThumbnails',
    // Offers
    'app/Modules/Offers/UserInterface/Filament/Pages/OfferSettings/OfferSettings.php => App\\Modules\\Offers\\Application\\ModuleApi\\BuyersPrices',
    'app/Modules/Offers/UserInterface/Filament/Resources/Offers/Actions/SellOfferAction.php => App\\Modules\\Offers\\Application\\ModuleApi\\BuyersPrices',
    'app/Modules/Offers/UserInterface/Filament/Resources/Offers/Pages/ListOffers.php => App\\Modules\\Offers\\Application\\ModuleApi\\BuyersPrices',
    'app/Modules/Offers/UserInterface/Filament/Resources/Offers/Schemas/OfferInfolist.php => App\\Modules\\Offers\\Application\\ModuleApi\\BuyersPrices',
    'app/Modules/Offers/UserInterface/Filament/Resources/Offers/Tables/OffersTable.php => App\\Modules\\Offers\\Application\\ModuleApi\\BuyersPrices',
    'app/Modules/Offers/UserInterface/Filament/Resources/Offers/Tables/OffersTableFilters.php => App\\Modules\\Offers\\Application\\ModuleApi\\BuyersPrices',
    // Preferences
    'app/Modules/Preferences/UserInterface/Filament/Widgets/QuickAccessWidget.php => App\\Modules\\Preferences\\Application\\ModuleApi\\QuickAccess',
    // Users
    'app/Modules/Users/Application/Commands/CreateUser/CreateUserCommandHandler.php => App\\Modules\\Users\\Application\\ModuleApi\\EmailSending',
    'app/Modules/Users/Application/Commands/DeleteJournalEntries/DeleteJournalEntriesCommandHandler.php => App\\Modules\\Users\\Application\\ModuleApi\\JournalEntryUrls',
    'app/Modules/Users/Application/Commands/RequestPasswordReset/RequestPasswordResetCommandHandler.php => App\\Modules\\Users\\Application\\ModuleApi\\EmailSending',
    'app/Modules/Users/Application/Commands/SendPasswordLink/SendPasswordLinkCommandHandler.php => App\\Modules\\Users\\Application\\ModuleApi\\EmailSending',
    'app/Modules/Users/Application/Listeners/SendJournalEntryNotification.php => App\\Modules\\Users\\Application\\ModuleApi\\JournalEntryUrls',
    'app/Modules/Users/Application/Listeners/SendJournalEntryNotification.php => App\\Modules\\Users\\Application\\ModuleApi\\JournalSubjects',
    'app/Modules/Users/Application/Listeners/WriteAccountTypeChangeJournalEntries.php => App\\Modules\\Users\\Application\\ModuleApi\\AdminRecipients',
    'app/Modules/Users/Application/Listeners/WriteAccountTypeChangeJournalEntries.php => App\\Modules\\Users\\Application\\ModuleApi\\JournalWriter',
    'app/Modules/Users/Application/Services/PasswordLinkEligibility.php => App\\Modules\\Users\\Application\\ModuleApi\\RoleRegistry',
    'app/Modules/Users/Application/Services/UserAuthorizer.php => App\\Modules\\Users\\Application\\ModuleApi\\RoleRegistry',
    'app/Modules/Users/Application/Services/UserJournalSubject.php => App\\Modules\\Users\\Application\\ModuleApi\\UserUrls',
    'app/Modules/Users/Application/Services/UserManagementGuard.php => App\\Modules\\Users\\Application\\ModuleApi\\RoleRegistry',
    'app/Modules/Users/UserInterface/Filament/Auth/Login.php => App\\Modules\\Users\\Application\\ModuleApi\\RoleRegistry',
    'app/Modules/Users/UserInterface/Filament/Resources/JournalEntries/Actions/GoToSubjectAction.php => App\\Modules\\Users\\Application\\ModuleApi\\JournalSubjects',
    'app/Modules/Users/UserInterface/Filament/Resources/JournalEntries/Support/FilamentJournalEntryUrls.php => App\\Modules\\Users\\Application\\ModuleApi\\JournalEntryUrls',
    'app/Modules/Users/UserInterface/Filament/Resources/Users/Actions/SendPasswordLinkAction.php => App\\Modules\\Users\\Application\\ModuleApi\\EmailSending',
    'app/Modules/Users/UserInterface/Filament/Resources/Users/Schemas/UserForm.php => App\\Modules\\Users\\Application\\ModuleApi\\EmailSending',
    'app/Modules/Users/UserInterface/Filament/Resources/Users/Support/FilamentUserUrls.php => App\\Modules\\Users\\Application\\ModuleApi\\UserUrls',
    'app/Modules/Users/UserInterface/Filament/Resources/Users/Support/RoleDisplay.php => App\\Modules\\Users\\Application\\ModuleApi\\RoleRegistry',
    'app/Modules/Users/UserInterface/Http/Middleware/AuthenticatePanel.php => App\\Modules\\Users\\Application\\ModuleApi\\RoleRegistry',
];
