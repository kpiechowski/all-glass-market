# Archiwum: setup agentowy przed ticketowaniem (czerwiec–sierpień 2026)

Ten folder to **dokumentacja historyczna**. Opisuje, jak agenci pracowali w tym repo, zanim przeszliśmy na skille Matta Pococka (`.scratch/` + tickety). Nic tutaj nie jest instrukcją do wykonania. To materiał wejściowy do grillowania i rozpisywania ticketów.

## Po co jest to repo

AllGlass Market V2 to przepisanie od nowa monolitu V1 (`/home/kp/laravel/projekty/AllGlass/`) na Laravel 13 + Filament 5 + Ecotone (CQRS), z podziałem na moduły DDD. Zasada od początku: **nie kopiować kodu V1, tylko go zrozumieć i napisać od nowa.**

## Jak zmieniał się setup — oś czasu

| Data | Commit | Co się zmieniło |
|---|---|---|
| 2026-06-09 | `eacee9e` | Start z szablonu `filament-ddd-template`. Reguły w `.ai/guidelines/architecture.md`, skille Copilota w `.github/skills/`, `AGENTS.md`. |
| 2026-06-26 | `97c0fcb` | `AGENTS.md` → `Claude.md`. Powstaje `.ai/skills/refactor-plan/refactor-plan.md`: jeden plan na ~390 linii, ładowany w każdej sesji. |
| 2026-06-29 | `d5b3e92` | Plan rozbity na moduły: pięciokrokowy workflow i pliki `scan.md` / `plan.md` per moduł w `.ai/modules/`. |
| 2026-08-23 | `efff90e` | Przebudowa wokół skilli w `.claude/skills/`. Wyszło na jaw, że `Claude.md` (mała litera) **nigdy nie był ładowany** przez Claude Code, więc ani guidelines Boosta, ani reguły projektu nie trafiały do agenta. Usunięte `.github/skills` i `architecture.md`. `boost.json` przestawiony z Copilota na `claude_code`. Pierwszy szkic `docs/agents/` od Matta. |
| 2026-08-23 | `6b57dc4` | `make:module` i stuby dopasowane do ports & adapters. |
| 2026-10-31 | — | Tę sesję opisuje sekcja „Sprzątanie 2026-10-31” na dole. |

## Stary sposób pracy: pięciokrokowy workflow na moduł

Każdy moduł przechodził tę samą sekwencję, a każdy krok mógł robić osobny agent:

1. **SCAN.** Głęboki skan V1, wynik w `modules/{name}/scan.md` wg szablonu `modules/_template/scan.md`. Obejmował modele, UI Filamenta, serwisy/joby/listenery, komendy konsolowe, eventy i polityki, migracje i przecieki między modułami.
2. **HEALTH REVIEW.** Checklista na końcu skanu (niedokończone feature'y, mieszane odpowiedzialności, abstrakcyjne hierarchie, `booted()` wołające serwisy, TODO). Każde ⚠️ oznaczało **stop i pytanie do użytkownika** przed planowaniem.
3. **PLAN.** Projekt V2 w `modules/{name}/plan.md`: modele, enumy, eventy, komendy i query, API repozytorium, UI oraz checklista „done”. Na górze zapisywane były decyzje z health review.
4. **IMPLEMENT.** Agent czytał tylko `architecture.md` + `plan.md`, bez całego planu refaktoru, żeby oszczędzać kontekst.
5. **VERIFY.** Zestaw grepów pod antywzorce, `dartisan migrate`, `pint --dirty`, a na koniec status w tabeli faz.

Zasady wokół workflow:

- Jeden moduł to jeden branch i jeden PR. Pracy z różnych modułów się nie mieszało.
- Faza 2 startuje dopiero, gdy cała Faza 1 ma ✅. Faza 3 dopiero, gdy istnieją eventy `OfferUpdated/Deleted/Sold/Archived`.

**Co działało:** skan V1 przed projektowaniem oraz bramka „health review → pytanie do człowieka”. Dzięki temu nie przenoszono złych wzorców z V1 (przykład: decyzje w Audit). **Co nie działało:** plan był jednym wielkim dokumentem statusowym zamiast małych ticketów. Workflow siedział w pliku, którego agent nie ładował automatycznie. Reguły architektury żyły w trzech miejscach naraz (`architecture.md`, `Claude.md`, plan). Statusy rozjeżdżały się z rzeczywistością, np. pamięć agenta do dziś twierdziła, że Audit „nie jest zaczęty”.

## Decyzje, które nadal obowiązują (do zweryfikowania przy grillowaniu)

### Kolejność faz

| Faza | Moduł | Stan |
|---|---|---|
| 1 | Users | ✅ (przed ports & adapters, do migracji) |
| 1a | Audit | ✅ (przed ports & adapters, do migracji) |
| 1b | Settings | skan gotowy, brak planu |
| 1c | Submissions | skan gotowy, brak planu |
| 2a | Vehicles | tylko zakres skanu |
| 2b | Offers | tylko zakres skanu (największy moduł) |
| 3a | WooCommerce (integracja) | tylko zakres skanu |
| 3b | Otomoto (integracja) | tylko zakres skanu |

### Granice modułów

- Otomoto i WooCommerce to `app/Integrations/`, nie moduły. Usunięcie integracji nie może dotykać domeny.
- Vehicles (Car, Eurocode) to osobny moduł, bo istnieją niezależnie od ofert.
- Reservations, Sales i OfferInquiry żyją w Offers.
- ChangesLog z V1 trafia do modułu Audit (cross-cutting).
- Settings to płaski moduł klucz-wartość. Submissions to osobny moduł.

### Decyzje z health review

- **Audit:** hierarchia `JournalAction` z V1 (dziennik + notyfikacja w jednej klasie) została zastąpiona dwoma listenerami na eventach Offers: `OfferJournalListener` i `OfferPanelNotificationListener`. Na razie to stuby, do wypełnienia w fazie Offers. Model `UserNotification` usunięty, zamiast niego `HasChangesLog` na `User`.
- **Settings:** `spatie/activitylog` nie istnieje w V2, więc zostaje `HasChangesLog` albo brak logowania. `RoleResource` (Shield) siedzi w klastrze Settings w V1, a należy do Users.
- **Submissions:** `offer_id` zostaje surowym FK bez relacji, dopóki nie powstanie Offers.
- **Schematy DB** nie muszą być 1:1 z V1, ale muszą dać się zmigrować z danych V1.

### Zagadnienia przekrojowe ze starego planu (usunięte przy odchudzaniu planu w `d5b3e92`)

- Polityki żyją w module, którego dotyczą. V1 ma je zdublowane.
- `spatie/medialibrary`, `overtrue/laravel-versionable` i `binarycats/laravel-sku` na `Offer` zostają, bo to infrastruktura, nie logika. Żadna z tych paczek nie jest jeszcze zainstalowana w V2.
- `spatie/laravel-permission` nie jest zainstalowany, więc `UserPolicy` jest zablokowana.

### Typowe pułapki portowania

Pełna lista jest w `v1-port/refactor-plan.md`. Najważniejsze: strony Filamenta tylko wysyłają komendy na bus. Hooki `booted()` / `static::updated` z V1 zastępujemy eventem i listenerem. Jedna komenda to jedna intencja użytkownika, a nie każdy podkrok. Każdy moduł ma pliki tłumaczeń.

## Co jest w tym folderze

| Ścieżka | Zawartość | Wartość dziś |
|---|---|---|
| `v1-port/refactor-plan.md` | Odchudzony plan: workflow, tabela faz, granice, pułapki | Fazy i granice: tak. Workflow: historia. |
| `v1-port/modules/_template/` | Szablony `scan.md` i `plan.md` | Wzór, co sprawdzać przy skanie V1 |
| `v1-port/modules/audit/` | Skan i plan, wdrożone | Historia oraz uzasadnienie decyzji |
| `v1-port/modules/settings/`, `submissions/` | Pełne skany V1 | **Gotowy materiał do ticketów** |
| `v1-port/modules/vehicles/`, `offers/`, `woocommerce/`, `otomoto/` | Tylko listy plików V1 do przeskanowania | Zakres researchu |

Uwaga: plany i skany opisują układ sprzed ports & adapters (np. `Domain/Observers`, repozytoria bez portu, `ModelRepository`). Tam, gdzie kłócą się ze skillami w `.claude/skills/`, rację mają skille.

## Co obowiązuje teraz (poza archiwum)

- **`.claude/skills/`** zawiera 6 skilli architektury: `module-layout`, `module-cqrs`, `module-persistence`, `module-events`, `filament-module-ui` i `module-testing`. Opisują docelową architekturę ports & adapters. Ich treść i założenia trzeba zweryfikować w osobnej sesji.
- **`CLAUDE.md`** zawiera reguły warstw, mapę „krok → skill”, sekcję skilli Matta i blok Laravel Boost.
- **`docs/agents/`** to konfiguracja skilli Matta Pococka (plugin `mattpocock-skills`): tickety jako markdown w `.scratch/<feature>/`, triage przez linię `Status:`, glosariusz `GLOSSARY.md` i `docs/adr/` tworzone leniwie.

## Sprzątanie 2026-10-31

- `.ai/` przeniesione tutaj. Laravel Boost traktuje ten folder jako swój: czyta `.ai/guidelines` i `.ai/skills`, a od wersji 2.10 także `.ai/rules`. Stary plan mógłby więc wrócić do agenta jako aktywna reguła.
- Paczki composer podbite do najnowszych wersji: Laravel 13.8 → 13.39, Filament 5.6 → 5.11, Ecotone 1.313 → 1.328, Boost 2.4 → 2.10, PHPUnit 12 → 13.4 i reszta. Laravel 13 i Filament 5 to już najnowsze wersje major. `ecotone/laravel` miał constraint `*`, teraz ma `^1.328.3`.
- `boost:update` odświeżył blok `<laravel-boost-guidelines>` w `CLAUDE.md` (wcześniej podawał np. PHPUnit v12).
