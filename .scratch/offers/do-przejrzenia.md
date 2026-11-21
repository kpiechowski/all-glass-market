# Do przejrzenia

Rzeczy, do których mam wrócić i sprawdzić sam.

## Obrazki ofert: jak są obsługiwane

Zdjęcia doszły w Offers 02 (`8ea14a9`), szczegóły w Comments `issues/02-photos.md`. Do sprawdzenia:

- **Przechowywanie:** zdjęcia to listy ścieżek w kolumnach json `public_photos` i `private_photos` na tabeli `offers`, bez osobnej tabeli i bez biblioteki mediów. Czy tak zostaje, także na potrzeby kanałów sprzedaży (ogłoszenia, WooCommerce, OLX)?
- **Dyski:** publiczne leżą na dysku `public` (`storage/app/public/offers/public/{userId}/`), prywatne na `local` (`storage/app/private/offers/private/{userId}/`). Folder należy do tego, kto wgrał plik, nie do oferty. Jeśli admin wgra zdjęcie do cudzej oferty, plik trafia do folderu admina.
- **Serwowanie prywatnych:** tylko przez trasę `admin/offers/{offer}/private-photos/{position}`, właścicielowi i adminom, każdy inny dostaje 404. W formularzu edycji podgląd prywatnych idzie przez podpisany, czasowy URL Filamentu (dysk `local` z `serve: true`).
- **Osierocone pliki:** usunięte albo podmienione zdjęcie zostaje na dysku. Pliki znikną dopiero przy trwałym usunięciu oferty (Offers 06, FR-26) albo przez przyszłe zadanie sprzątające, którego jeszcze nie ma.
- **Brak obróbki:** nie ma miniatur, zmiany rozmiaru, kompresji, znaku wodnego ani limitu wagi pliku. Jest tylko `->image()` i najwyżej 10 sztuk na rodzaj.
- **Historia zmian:** pokazuje zdjęcia jako liczbę („3 pliki”), więc sama zmiana kolejności wygląda jak „3 pliki → 3 pliki”.
- **Ponowne zatwierdzanie:** dodanie, usunięcie albo zmiana kolejności zdjęć publicznych przez sprzedawcę cofa ofertę Aktywną do Oczekujących. Zdjęcia prywatne nie zmieniają statusu.
- **Migracja z V1:** jak przejdą zdjęcia starych ofert? Sprawdzić w `.scratch/data-migration/offers/`.
- **Ręcznie w panelu:** wgrywanie, przeciąganie kolejności, odznaka „Główne” na pierwszym zdjęciu, galeria prywatna widoczna tylko dla właściciela i admina.
