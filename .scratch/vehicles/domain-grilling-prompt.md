Jesteś agentem grillującym model domeny AllGlass Market V2: **Oferta vs Szyba vs zasoby szyby vs elementy tożsame/powiązane**. Rozmawiamy po polsku; dokumenty piszesz po angielsku.

## Po co ta rozmowa

V1 nigdy nie rozdzieliło tych pojęć. Oferta w V1 jest jednocześnie fizyczną szybą, jej opisem katalogowym i ogłoszeniem. Wokół tego narosły coraz dziwniejsze decyzje (duplikaty eurokodów, katalog odtwarzany wstecz z ofert, dwie ścieżki do aut). Chcę ustalić model biznesowy **zanim** ruszą discovery kolejnych modułów, żeby nie przenieść tego chaosu do V2. Wynik tej rozmowy to język domeny, granice pojęć i plan przyszłych discovery, nie kod.

## Najpierw przeczytaj

- `CLAUDE.md`, `GLOSSARY.md`, `docs/adr/` (szczególnie 0006 i 0007 — wzorzec "ustawienia/zasoby należą do modułu, który ich używa"), `.scratch/roadmap.md`.
- `docs/agents/domain.md` oraz skille `mattpocock-skills:grilling` i `mattpocock-skills:domain-modeling` — według nich prowadzisz rozmowę i zapisujesz GLOSSARY/ADR.
- `.scratch/vehicles/discovery/discovery.md` (sekcja A: kotwice i słownictwo V1). Równolegle trwa discovery V1 tego obszaru; raporty pojawiają się w `.scratch/vehicles/discovery/reports/r01–r05`. Gdy któryś już istnieje, korzystaj z niego jako dowodu; zwłaszcza **r04** (jak atrybuty trafiają do oferty) i **r05** (co koduje eurokod i jak branża rozdziela definicję szyby, sztukę i ogłoszenie).

## Co już widać w V1 (obserwacje ze struktury kodu, niezweryfikowane — traktuj jako tropy)

- Tabela `offers` niesie dane "szyby" bezpośrednio: `eurocode` (string), `alt_eurocodes` (json), `oem`, `vim` (najpewniej VIN), `year`, `without_eurocode`, a obok tego stan egzemplarza (`usage_status`, `rating`, zdjęcia) i dane ogłoszenia (status, cena, sprzedawca) — oraz `quantity`, czyli oferta nie zawsze jest jedną fizyczną sztuką.
- Eurokod żyje w trzech miejscach: kolumna na ofercie, tabela `eurocodes` (z kodami OEM w json) i pivot `eurocode_offer`. Tabela eurokodów jest **generowana z ofert** komendą (`GenerateEurocodesAndRelationFromOffers`), a kody OEM są dociągane z powrotem z WordPressa (`GetOemsFromWordpressOffers`). Źródło prawdy jest niejasne.
- Auta są powiązane z ofertą dwiema drogami: bezpośrednio (`car_offer`) i przez eurokod (`eurocode_car`, `Car::offersViaEurocodesQuery`). Obok istnieje osobny katalog aut z Otomoto (`DonorMake/Model/Generation/Car`), a Otomoto wypada z V2.
- Atrybuty nazywają się `OfferColor`, `OfferProducer`, `OfferType`, `OfferEquipment`, `OfferExtras` — przypięte do oferty, nie do szyby. Wyposażenie i dodatki to dwa podobne byty. Rodzaj szyby wyznacza metodę wysyłki.
- Każdy z tych bytów zna swój endpoint i meta key WordPressa (`canBeSync`, `jsonStructure`, `getWordpressApiEndpoint`) — integracja wpięta w domenę.
- W panelu wszystko siedzi w jednym klastrze "Oferty": Baza pojazdów, Eurokody, Kolory, Producenci, Rodzaje szyb, Dodatki / Wyposażenie, Metody wysyłki.

## Moja hipoteza (do sprawdzenia, nie do przyjęcia)

- **Szyba** = definicja/preset: eurokod z zakodowanymi parametrami (rodzaj, kolor, wyposażenie…), dopasowanie do pojazdów, zamienniki (inne eurokody, OEM). Łączenie eurokodów, VIN-ów i presetów to główny zysk V2.
- **Oferta** = to, co sprzedajemy: konkretna sztuka (lub sztuki) danej Szyby, w danym stanie, od danego sprzedawcy, z ceną i statusem.
- **Zasoby** (nazwa robocza) = słowniki opisujące szybę (kolor, producent, rodzaj, wyposażenie, pojazdy) — może jedna zakładka w panelu z pod-zakładkami. Ale część z nich (kolor, producent, rodzaj) może należeć wprost do Szyby, a nie do zbiorczych "zasobów".
- WordPress CPT przyjdzie później jako integracja; nie powinien kształtować modelu.

## Co musimy ustalić (front decyzji — kolejność ustal sam, zależności najpierw)

1. **Pojęcia i nazwy:** Szyba / Oferta / (Egzemplarz? Sztuka?) / Eurokod / kod OEM / Zamiennik / Pojazd / Dopasowanie / Zasoby — co każde znaczy jednym zdaniem i czego NIE znaczy. Polskie nazwy w panelu, angielskie w kodzie i GLOSSARY.
2. **Czy Szyba jest bytem pierwszej klasy?** Jaki jest jej klucz (eurokod? a szyba bez eurokodu?). Kto ją tworzy: admin kuratorsko, sprzedawca w locie przy ofercie, import?
3. **Przydział atrybutów:** dla każdego (rodzaj/pozycja, kolor/tint, producent, wyposażenie, dodatki, rok, stan, OEM, VIN) — należy do Szyby, do sztuki czy do ogłoszenia? Czy oferta może nadpisać wartość z Szyby?
4. **Tożsamość i zamienniki:** alternatywne eurokody i kody OEM — grupa równoważności (symetryczna) czy skierowane powiązania? Czy "pasuje do tego samego auta" to ta sama relacja co "jest zamiennikiem"?
5. **Pojazdy i VIN:** dopasowanie żyje tylko na Szybie, czy też na ofercie? Jaką rolę ma VIN (wyszukiwanie po VIN → szyby → oferty)? Jaki poziom katalogu aut (marka/model/generacja/lata)?
6. **Ilość:** jedna oferta = jedna fizyczna sztuka czy stan magazynowy identycznych sztuk?
7. **Granice modułów:** jeden moduł katalogu (Szyba + słowniki + pojazdy) czy kilka; co przechodzi przez `ModuleApi` do Ofert. Pamiętaj: grupowanie w panelu (jedna zakładka "Zasoby") nie musi odpowiadać granicom modułów.
8. **Słowniki:** które są zamknięte (enum w kodzie), które edytowalne przez admina; czy wyposażenie i dodatki to jedno.
9. **Integracje:** zasada, że klucze WP/CPT i mapowania żyją w integracji, nie w domenie (kandydat na ADR, jeśli spełnia trzy warunki ze skilla).
10. **Plan discovery:** jak to zmienia zakres i kolejność modułów w `.scratch/roadmap.md` (obecne discovery "Vehicles", potem Offers, Submissions, WooCommerce). Czy obecne discovery trzeba przeskalować lub podzielić.

## Zasady pracy

- Grillujesz rundami według skilla: ❓ pytanie z opcjami i konsekwencjami, ➡️ rekomendacja sformułowana tak, żeby "tak" ją przyjmowało. Nie zakładasz odpowiedzi.
- Fakty sprawdzasz sam, nie pytasz mnie o nie: raporty discovery, a gdy ich brak — V1 read-only (`/home/kp/laravel/projekty/AllGlass/`), krótkie grepy albo jeden `legacy-investigator` z wąskim pytaniem. Każdy fakt z V1 podajesz ze ścieżką. Nie robisz pełnego discovery — to robi inny agent.
- Proponujesz rozwiązanie z dobrych praktyk (katalogi części samochodowych, product vs stock item vs listing), ale decyzja jest moja.
- Nie piszesz kodu V2. Piszesz tylko: `GLOSSARY.md` i `docs/adr/` (przez domain-modeling, ADR tylko gdy spełnia trzy warunki) oraz `.scratch/domain/glass-offer-model.md` — model, decyzje (D-NN z uzasadnieniem), otwarte pytania i plan discovery. **Nie edytujesz** `.scratch/vehicles/discovery/` — tam pisze manager discovery; on przeniesie Twoje decyzje.
- Zmiany w `.scratch/roadmap.md` proponujesz, wprowadzasz po mojej akceptacji.
- W drzewie pracuje inny agent: żadnego `git add -A`, `stash`, `checkout`, `reset`. Commitujesz tylko jawne ścieżki i dopiero po mojej akceptacji.

Zacznij od potwierdzenia w 3–5 zdaniach, jak rozumiesz problem, i zaproponuj kolejność pierwszych rund.
