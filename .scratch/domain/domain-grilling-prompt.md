Jesteś agentem grillującym model domeny AllGlass Market V2: **Oferta vs Szyba vs zasoby szyby vs elementy tożsame/powiązane**. Rozmawiamy po polsku; dokumenty piszesz po angielsku.

## Po co ta rozmowa

V1 nigdy nie rozdzieliło tych pojęć. Oferta w V1 jest jednocześnie fizyczną szybą, jej opisem katalogowym i ogłoszeniem. Wokół tego narosły coraz dziwniejsze decyzje: parametry szyby kopiowane z „sąsiedniej” oferty, katalog eurokodów odtwarzany wstecz z ofert, dwie ścieżki do aut, sprzedawca wpisujący eurokod z palca. Chcę ustalić model biznesowy **zanim** ruszy grillowanie modułów, żeby nie przenieść tego chaosu do V2. Wynik tej rozmowy to język domeny, granice pojęć i granice modułów oraz plan dalszej pracy. Nie piszesz kodu.

## Najpierw przeczytaj (oszczędnie z kontekstem)

1. `CLAUDE.md`, `GLOSSARY.md`, `docs/adr/` (szczególnie 0006 i 0007 — „ustawienia/zasoby należą do modułu, który ich używa”), `.scratch/roadmap.md`, `docs/agents/domain.md`.
2. Skille `mattpocock-skills:grilling` i `mattpocock-skills:domain-modeling` — według nich prowadzisz rozmowę i zapisujesz GLOSSARY/ADR.
3. Dwa zakończone discovery V1 (oba `ready-for-grilling`). Czytaj najpierw sekcje **B (overview), D (rules), G (questions), I–J** obu dokumentów; raporty otwieraj tylko wtedy, gdy potrzebujesz dowodu dla konkretnego punktu:
   - `.scratch/vehicles/discovery/discovery.md` — eurokody, auta, słowniki atrybutów (kolor, producent, rodzaj, wyposażenie). Raporty `reports/r01–r05`; najważniejsze **r04** (jak atrybuty trafiają do oferty) i **r05** (co koduje eurokod według ARGIC i jak branża rozdziela definicję produktu, sztukę magazynową i ogłoszenie).
   - `.scratch/offers/discovery/discovery.md` — Oferta w V1: statusy, zatwierdzanie, cena/VAT, ilość, rezerwacje, sprzedaże, zapytania, zgłoszenia, dziennik. Raporty `reports/r01–r06`; najważniejszy **r06** (kształt danych produkcyjnych).
4. Produkcyjny zrzut V1 jest wgrany lokalnie: kontener `laradock8_5-mysql-1`, baza `allglass_v1_prod`. Zapytania tylko agregujące (`docker exec laradock8_5-mysql-1 sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot allglass_v1_prod -e "SELECT ..."'`); żadnych danych osobowych w dokumentach.

## Co już wiemy (zweryfikowane w discovery — ID w nawiasach)

- **Parametry szyby idą za eurokodem.** W grupach ofert o tym samym eurokodzie: rodzaj taki sam w 99,6%, kolor 97,5%, wyposażenie 93,3%; producent tylko 46%, stan 67%, rok 42% (Offers BR-38, r06). Wyjątki: 6 konfliktów koloru (powłoki SOLAR / grzewcza / zielona), 16 wyposażenia (Offers Q-20).
- **Eurokod według branży koduje** pozycję, kolor/pas, wyposażenie i modyfikację; **nie koduje** producenta, OE/zamiennika, daty ani stanu (Vehicles r05).
- **Katalog w V1:** eurokod to goły identyfikator z kodami OEM, bez parametrów; dopasowanie do aut w `car_eurocode`; stare `car_offer` jest zbędne (722 z 790 identyczne z dopasowaniem przez eurokod, 0 sprzecznych); słowniki mają workflow zatwierdzania, ale wszystkie wiersze są aktywne (Vehicles F-/BR-, G-09).
- **Atrybuty trafiają na ofertę przez kopiowanie** z najnowszej oferty o tym samym eurokodzie, w 4 ścieżkach kodu (Vehicles W-02, Offers W-01, W-02).
- **Oferta = jedna fizyczna sztuka w praktyce.** Pole ilości istnieje (tylko dla nowych szyb), stan liczony z rezerwacji i sprzedaży, ale w produkcji 2030/2046 ofert ma ilość 1, każda sprzedaż to 1 sztuka, nigdy nie było częściowej rezerwacji ani sprzedaży. Kolejną używaną sztukę dodaje się przez „duplikuj” (Offers BR-11–13, BR-25, Q-06).
- **Sprzedawca nie widzi katalogu:** wpisuje eurokod z palca (z podpowiedziami), rodzaj/kolor wybiera tylko bez eurokodu; nie widzi innych ofert (Offers BR-33, BR-34, Q-18). 28 sprzedawców, 3 Super Adminów; 3 sprzedawców ma 94,6% ofert.
- **Zatwierdzanie:** każdy zapis sprzedawcy cofa ofertę do „Oczekuje zatwierdzenia”; admin akceptuje/odrzuca. „Zgłoszenie” to automatyczny bilet dla admina ze zrzutem formularza, a nie wiadomość klienta; 1182/1213 nieprzeczytanych (Offers BR-20, F-01–07, Q-03).
- **Cena:** netto na ofercie, VAT 23% doliczany w locie zależnie od typu konta sprzedawcy; sprzedaż nie zapisuje ceny (Offers BR-21, Q-15).
- **Integracje wpięte w domenę:** każdy zapis oferty i każda zmiana eurokodów od razu wysyła dane do WooCommerce; słowniki znają swoje endpointy WordPressa. Otomoto wypada z V2; WooCommerce w tej iteracji jest poza zakresem i nie może kształtować modelu.

## Moja hipoteza (do sprawdzenia, nie do przyjęcia)

- **Szyba** = definicja/preset pod eurokodem z zakodowanymi parametrami (rodzaj, kolor, wyposażenie…), dopasowaniem do pojazdów i zamiennikami (inne eurokody, OEM). Łączenie eurokodów, VIN-ów i presetów to główny zysk V2.
- **Oferta** = to, co sprzedajemy: konkretna sztuka danej Szyby, w danym stanie, od danego sprzedawcy, z ceną i statusem.
- **Zasoby** (nazwa robocza) = słowniki opisujące szybę — może jedna zakładka w panelu z pod-zakładkami. Ale część (kolor, rodzaj, może producent) może należeć wprost do Szyby, a nie do zbiorczych „zasobów”.

## Co musimy ustalić (kolejność ustal sam, zależności najpierw)

1. **Pojęcia i nazwy:** Szyba / Oferta / Sztuka(?) / Eurokod / kod OEM / Zamiennik / Pojazd / Dopasowanie / Zasoby / Zgłoszenie / Zapytanie / Rezerwacja / Sprzedaż — co każde znaczy jednym zdaniem i czego NIE znaczy. Polskie nazwy w panelu, angielskie w kodzie i GLOSSARY.
2. **Szyba jako byt pierwszej klasy:** klucz (eurokod? szyba bez eurokodu?), kto ją tworzy (admin kuratorsko, sprzedawca w locie, import), co gdy sprzedawca poda nieznany kod.
3. **Przydział atrybutów:** rodzaj/pozycja, kolor, producent, wyposażenie, rok, stan, ocena, OEM, VIN — do Szyby, do sztuki czy do ogłoszenia? Czy oferta może nadpisać wartość z Szyby (Offers Q-20)?
4. **Tożsamość i zamienniki:** alternatywne eurokody i kody OEM — grupa równoważności czy skierowane powiązania? Czy „pasuje do tego samego auta” to ta sama relacja co „jest zamiennikiem”?
5. **Pojazdy i VIN:** dopasowanie tylko na Szybie? Rola VIN (VIN → szyby → oferty)? Poziom katalogu aut?
6. **Ilość:** oferta = jedna sztuka czy stan magazynowy identycznych sztuk (Offers Q-06)? Co to znaczy dla rezerwacji, sprzedaży i statusów?
7. **Co tworzy sprzedawca:** wybiera Szybę z katalogu i opisuje tylko sztukę (Offers ADD-01, Q-18), czy dalej wpisuje kod?
8. **Zgłoszenia, zapytania, zatwierdzanie:** czy to osobne pojęcia domeny, czy części cyklu życia Oferty (Offers Q-03, Q-07, IMP-01)? Czy Submissions zostaje modułem?
9. **Granice modułów:** jeden moduł katalogu (Szyba + słowniki + pojazdy) czy kilka; co przechodzi przez `ModuleApi` do Ofert. Grupowanie w panelu (jedna zakładka „Zasoby”) nie musi odpowiadać granicom modułów.
10. **Słowniki:** które zamknięte (enum w kodzie), które edytowalne przez admina; czy wyposażenie i dodatki to jedno; czy potrzebny workflow zatwierdzania wpisów.
11. **Integracje:** zasada, że klucze WP/CPT i mapowania żyją w integracji, nie w domenie (kandydat na ADR, jeśli spełnia trzy warunki ze skilla).
12. **Plan dalszej pracy:** jak wynik zmienia `.scratch/roadmap.md` (Vehicles i Offers czekają na grillowanie modułów, potem WooCommerce).

Pytania modułowe z sekcji G obu discovery (błędy V1, szczegóły ekranów, VAT, wygasanie itd.) **nie są** tematem tej rozmowy — zostaną rozstrzygnięte na grillowaniu modułów. Ruszaj je tylko, gdy zmieniają model.

## Zasady pracy

- Grillujesz rundami według skilla: ❓ pytanie z opcjami i konsekwencjami, ➡️ rekomendacja sformułowana tak, żeby „tak” ją przyjmowało. Nie zakładasz odpowiedzi.
- Fakty sprawdzasz sam, nie pytasz mnie o nie: dokumenty i raporty discovery, zrzut produkcyjny, a gdy trzeba — V1 read-only (`/home/kp/laravel/projekty/AllGlass/`), krótkie grepy albo jeden `legacy-investigator` z wąskim pytaniem. Każdy fakt podajesz z ID z discovery albo ścieżką. Nie robisz ponownego discovery.
- Proponujesz rozwiązania z dobrych praktyk (katalogi części samochodowych, product vs stock item vs listing), ale decyzja jest moja.
- Piszesz tylko: `GLOSSARY.md` i `docs/adr/` (przez domain-modeling; ADR tylko gdy spełnia trzy warunki) oraz `.scratch/domain/glass-offer-model.md` — model, decyzje (D-NN z uzasadnieniem i listą ID z discovery, których dotyczą, np. „re Offers Q-06, Vehicles ADD-01”), otwarte pytania i plan. Zapisuj po każdej rundzie, żeby sesję dało się wznowić z samego pliku.
- **Nie edytujesz** `.scratch/vehicles/discovery/` ani `.scratch/offers/discovery/` — tam pisze manager discovery; on przeniesie Twoje decyzje.
- Zmiany w `.scratch/roadmap.md` proponujesz, wprowadzasz po mojej akceptacji.
- Git: żadnego `git add -A`, `stash`, `checkout`, `reset`. Commitujesz tylko jawne ścieżki i dopiero po mojej akceptacji. Komendy w kontenerze jako `laradock`.

Zacznij od potwierdzenia w 3–5 zdaniach, jak rozumiesz problem, i zaproponuj kolejność pierwszych rund.
