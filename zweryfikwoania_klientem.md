### Rzeczy do zweryfikwoania z klientem

## 1. Co robimy z fomrami wysyłki, jaki chcemy osiagnąć ich kształt i uzycie.

## 2. Powłoki wśród kolorów szyby (katalog, D-46)
W V1 słownik „Kolor” zawiera dwie wartości, które są powłokami, a nie barwą: **SOLAR** (53 oferty) i **POWŁOKA GRZEWCZA** (18 ofert). W migracji zostają kolorami, jak w V1. Pytanie: czy klient chce je przenieść do wyposażenia (i wtedy uzupełnić prawdziwą barwę tych modeli ręcznie), czy zostają kolorami?

## 3. Lista przeglądu przed migracją katalogu (D-13, D-47, D-59–D-64)
Plik: `.scratch/data-migration/catalogue/review-list.md` (wygenerowany z bazy produkcyjnej 2026-11-11; przed migracją wygenerować ponownie). Klient poprawia, co się da, **bezpośrednio w V1** (panel albo szybki update w bazie), żeby nie budować jednorazowych narzędzi:
- **A.** 27 grup eurokodów, które różnią się nie tylko prefiksem pojazdu — czy to na pewno ta sama szyba? Jeśli nie: usunąć zły eurokod z oferty w V1.
- **B.** 17 grup ze sprzecznymi parametrami (kolor, wyposażenie, pozycja) — poprawić oferty w V1.
- **C.** 45 eurokodów bez żadnej oferty — podać pozycję (V1 nie ma na to pola; odpowiedź idzie do pliku mapowania importu). Bez odpowiedzi: podpowiedź z kodu albo kod nie przechodzi.
- **D.** 70 kodów o nietypowym kształcie, część wygląda na numery OEM (np. `1K4845202B`, `1-05019706AA`) — zostawić jako eurokod, przenieść do OEM konkretnego modelu, czy pominąć?
- **E.** Producenci: duplikaty `BYD OEM`/`OEM BYD`, `LAMESON`/`LEMSON`; nieużywane `LEMSON`, `OEM BYD`, `PS`.
- **F.** Nadwozia pojazdów: literówki i różne zapisy tego samego (np. `HATCHBACK 5D` = `5 DRZWI HATCHBACK`) oraz wartości, które nie są nadwoziem (`1995-2003 W210`, `JL`…). Poprawić w V1.
- **H.** 3 złączenia eurokodów wynikające tylko z usuniętych ofert — sprawdzić.

## 4. Osobny model akcesoriów (ADD-A01, D-27)
Uszczelki, listwy, ramki są dziś „modelami szyb” z pozycją akcesorium. Czy klient chce osobnego rodzaju pozycji w katalogu (np. jako osobny typ produktu w sklepie)? Koszt dodania później: niski–średni.

## 5. Kanał informacyjny o zmianach sprzedawców (D-20)
W V1 admin czytał zgłoszenia (tickety) jako changelog zmian sprzedawców. W V2 jest tylko zakładka „oczekujące”. Czy admin panelu potrzebuje dodatkowo takiego strumienia informacji?

## 6. Które zmiany sprzedawcy wymagają ponownej akceptacji (Oferty, D-31)
Propozycja V2: **zmiana ceny działa od razu, bez akceptacji** (pojedynczo i masowo). Każda inna zmiana sprzedawcy (model szyby lub wskazówka, zdjęcia, opisy, producent, rok, stan, ocena) wraca do „Oczekuje na zatwierdzenie” i oferta znika z kanałów do czasu akceptacji. W V1 każda edycja (także ceny) wymagała akceptacji, ale masowa zmiana ceny ją omijała. Czy klient się zgadza?

## 7. Automatyczne wygasanie ofert (Oferty, D-36)
W V2 na razie brak: ofertę zdejmuje się ze sprzedaży ręcznie („Wycofana”). Czy klient chce reguły typu „niesprzedana po N dniach → wycofana, z powiadomieniem sprzedawcy”? Jeśli tak, po ilu dniach i kogo powiadomić?

## 8. Osobny ekran rewizji oferty dla admina (Oferty, ADD-04)
W V2 admin widzi na ofercie zakładkę „Historia” i blok „Zmiany od ostatniego zatwierdzenia”. Czy potrzebny jest osobny ekran przeglądu oferty przed akceptacją, inny niż widok oferty?

## 9. Oferty z ilością większą niż 1 (Oferty, D-02, D-68)
W V2 jedna oferta = jedna fizyczna szyba. W V1 16 ofert ma wpisaną ilość > 1 (11 „Nowa”, do 13 sztuk; 5 „Używana”, do 4 sztuk), wszystkie Aktywne, żadna nie miała sprzedaży ani rezerwacji. Prośba: sprawdzić w V1, ile sztuk naprawdę jest, i poprawić ilość (np. na 1, jeśli to pomyłka). Migracja podzieli to, co zostanie, na osobne oferty (każda sztuka z własnym numerem i kopią zdjęć). Lista ofert: zapytanie `select id, sku, quantity from offers where quantity > 1` (wygenerować przed migracją).
