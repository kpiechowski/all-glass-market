# Oferty: lista do przeklikania

Ręczne sprawdzenie całego modułu Oferty w panelu (tickety 01–14). Przy każdym punkcie jest oczekiwany wynik. Jeśli coś wygląda inaczej, zanotuj to pod punktem.

## Przygotowanie

- [ ] Uruchom `dartisan migrate` (migracje Offers 01–08) i raz `dartisan storage:link` (publiczne zdjęcia).
- [ ] Przygotuj konta:
  - **SA**: Super Admin;
  - **A1** i **A2**: dwóch adminów;
  - **SP**: sprzedający z kontem Prywatnym;
  - **SF**: sprzedający z kontem Firmowym;
  - **S2**: drugi sprzedający.
- [ ] W Katalogu przygotuj:
  - 2–3 modele szyby;
  - jeden model, który potem wycofasz;
  - kilku producentów.
- [ ] Przy każdym punkcie, który mówi o dzienniku, sprawdzaj dzwonek i Dziennik osoby, która ma dostać wpis. Za własną akcję nikt nie dostaje wpisu.

## 1. Tworzenie i edycja (01, 02)

- [ ] **SP → Moje oferty → Utwórz.** Jeden formularz bez kreatora: sekcja „Szyba” na górze, pod nią sztuka (producent, rok, stan, ocena, opis), zdjęcia, prywatne notatki, prywatne zdjęcia i cena.
- [ ] **„Wybierz model szyby”:**
  - otwiera się slide-over z kartami Katalogu, wyszukiwarką i filtrami;
  - po zaznaczeniu karty i potwierdzeniu sekcja pokazuje kartę modelu;
  - wycofanego modelu nie da się wybrać.
- [ ] **„Nie znam modelu”:** zamiast karty pojawia się pole na wskazówkę (kod, OEM, pojazd, VIN jako tekst).
- [ ] **Producent:**
  - wybiera się z listy Katalogu;
  - sprzedający nie może dodać nowego;
  - rok jest opcjonalny.
- [ ] **Ocena:**
  - pole oceny jest tylko dla stanu Używana i Outlet;
  - przy stanie Nowa pola nie ma, a oferta pokazuje 5 gwiazdek.
- [ ] **Zdjęcia publiczne:**
  - najwyżej 10;
  - kolejność zmienia się przeciąganiem;
  - pierwsze ma odznakę „Główne”.
- [ ] **Zdjęcia prywatne:** najwyżej 10.
- [ ] **Szkic:** zapisuje się z dowolną (nawet pustą) treścią.
- [ ] **Wysłanie do zatwierdzenia jest odrzucane** z czytelnym błędem, gdy brakuje któregoś z warunków:
  - modelu albo wskazówki;
  - ceny ≥ 1;
  - stanu;
  - oceny dla stanu Używana albo Outlet;
  - co najmniej 1 zdjęcia publicznego.
- [ ] **Wysłanie z kompletem danych:** status Oczekuje.
- [ ] **Prywatne zdjęcia:**
  - widzi je właściciel i admin;
  - **S2** po wejściu w bezpośredni adres zdjęcia dostaje 404.
- [ ] **A1 tworzy własną ofertę:**
  - z modelem szyby → od razu Aktywna;
  - bez modelu → Oczekuje.

## 2. Zatwierdzanie (03, 04)

- [ ] **A1 → Wszystkie oferty → zakładka „Oczekujące”:** ma licznik, a oferta SP jest na liście.
- [ ] **Zatwierdzenie oferty z modelem:** status Aktywna, SP dostaje wpis w dzienniku.
- [ ] **Zatwierdzenie oferty z samą wskazówką:**
  - otwiera się slide-over wypełniony treścią wskazówki;
  - można wybrać model albo utworzyć nowy model w miejscu, z głównym Eurokodem podpowiedzianym ze wskazówki;
  - nowy model zostaje przypisany do oferty.
- [ ] **Odrzucenie oferty Oczekującej albo Aktywnej:**
  - powód jest wymagany;
  - status Odrzucona;
  - SP dostaje wpis z powodem.
- [ ] **Odrzucona oferta:** A1 może ją zatwierdzić i wtedy wraca do Aktywnych.
- [ ] **Zbiorcze zatwierdzanie** 5 ofert, z których 2 nie mają modelu: komunikat „zatwierdzono 3, pominięto 2”.
- [ ] **„Zmiany od ostatniego zatwierdzenia”:**
  - SP zmienia opis zatwierdzonej oferty i ta wraca do Oczekujących;
  - A1 widzi na niej zmienione pola, każde ze starą i nową wartością;
  - oferta nigdy niezatwierdzona nie pokazuje tej sekcji.
- [ ] **Zakładka Historia:**
  - widzi ją tylko admin;
  - sprzedający nie widzi historii.

## 3. Edycja zatwierdzonej oferty (01, 04)

- [ ] **SP zmienia sam opis aktywnej oferty:** status Oczekuje.
- [ ] **SP zmienia tylko cenę aktywnej oferty:** zostaje Aktywna.
- [ ] **SP zmienia tylko prywatne notatki albo prywatne zdjęcia:** zostaje Aktywna.
- [ ] **SP zmienia cenę i zdjęcie publiczne naraz:** status Oczekuje.
- [ ] **SP nie może edytować** oferty Zarezerwowanej ani Sprzedanej.
- [ ] **A1 edytuje ofertę SP w dowolnym statusie,** także Sprzedaną:
  - status się nie zmienia;
  - SP dostaje wpis „zmieniona przez administratora”.

## 4. Model szyby na ofercie (09)

- [ ] **A1 → „Zmień model szyby” na jednej ofercie:**
  - status zostaje;
  - właściciel dostaje wpis w dzienniku.
- [ ] **Zbiorczo „Zmień model szyby” na kilku ofertach:**
  - komunikat z liczbą zmienionych i pominiętych;
  - oferty, które już mają ten model, liczą się jako pominięte.
- [ ] **Zmiana modelu w Katalogu** (np. dopisanie Eurokodu): oferta od razu pokazuje nowe dane, nic nie jest kopiowane na ofertę.
- [ ] **Wycofanie modelu w Katalogu:**
  - oferta dalej go pokazuje;
  - modelu nie da się wybrać do nowej oferty.

## 5. Rezerwacje (07)

- [ ] **SP rezerwuje swoją aktywną ofertę:**
  - notatka „dla kogo” jest wymagana;
  - status Zarezerwowana;
  - widać autora i datę.
- [ ] **Druga rezerwacja tej samej oferty** jest niemożliwa (akcja ukryta).
- [ ] **SP zwalnia własną rezerwację:** oferta wraca do Aktywnych.
- [ ] **A1 rezerwuje ofertę SP:**
  - SP widzi „Zarezerwowana przez A1, data”;
  - SP nie ma przycisków „Zwolnij” ani „Sprzedaj”;
  - SP dostaje wpis w dzienniku.
- [ ] **A1 zwalnia albo sprzedaje dowolną rezerwację.**
- [ ] **Strona „Rezerwacje” u SP:**
  - widać tylko rezerwacje na własnych ofertach;
  - przy każdej jest autor.

## 6. Sprzedaż i zwrot (07)

- [ ] **SP oznacza aktywną ofertę jako sprzedaną:**
  - podaje kanał i cenę;
  - cena jest wstępnie wypełniona ceną dla kupującego i da się ją edytować;
  - status Sprzedana.
- [ ] **Sprzedaż z rezerwacji:**
  - SP może sprzedać z własnej rezerwacji;
  - z rezerwacji A1 nie może.
- [ ] **Zakładka „Sprzedane” i widok oferty:** SP widzi datę, kanał, cenę i informację, czy był zwrot.
- [ ] **Zwrot:**
  - zwrócić sprzedaż może tylko A1, z wymaganym powodem;
  - oferta wraca do Aktywnych;
  - sprzedaż zostaje w historii z oznaczeniem „zwrócona”;
  - SP dostaje wpis.
- [ ] **SP nie ma akcji zwrotu.**

## 7. Zapytania (08)

- [ ] **A1 wysyła zapytanie o aktywną ofertę SP:**
  - kanał jest opcjonalny, notatka wymagana;
  - przed wysłaniem A1 widzi, ile zapytań jest już otwartych i od kogo;
  - SP dostaje wpis.
- [ ] **Zapytanie o ofertę, która nie jest Aktywna,** jest niemożliwe (akcja ukryta).
- [ ] **SP → „Zapytania”:** otwarcie zapytania oznacza je jako przeczytane.
- [ ] **SP akceptuje zapytanie:**
  - powstaje rezerwacja na A1 z notatką z zapytania;
  - SP nie może jej zwolnić ani sprzedać;
  - A1 dostaje wpis z odpowiedzią.
- [ ] **SP odrzuca zapytanie:** A1 dostaje wpis.
- [ ] **A1 i A2 wysyłają zapytania o tę samą ofertę, SP akceptuje drugie:**
  - pierwsze staje się Nieaktualne;
  - A1 dostaje wpis.
- [ ] **Zapytania nieaktualnieją, gdy oferta przestaje być Aktywna.** Sprawdź po kolei: rezerwacja, sprzedaż, wycofanie, kosz, odrzucenie i powrót do Oczekujących po edycji SP.
- [ ] **Admin widzi listę wszystkich zapytań:** oferta, nadawca, stan i daty.

## 8. Wycofanie, wznowienie, kosz (06)

- [ ] **SP wycofuje ofertę Oczekującą, Odrzuconą albo Aktywną:** status Wycofana.
- [ ] **Szkicu nie da się wycofać.**
- [ ] **„Wznów sprzedaż” na wycofanej ofercie, która była zatwierdzona i od tamtej pory się nie zmieniła:** wraca do Aktywnych.
- [ ] **Wznowienie po zmianie opisu:** oferta trzeba edytować i trafia do Oczekujących, nie od razu do Aktywnych.
- [ ] **Odrzucona → wycofana → wznowiona** nie może trafić prosto do Aktywnych.
- [ ] **Usuwanie:**
  - usunąć może tylko A1;
  - nie da się usunąć oferty Zarezerwowanej;
  - usunięta znika z list i pojawia się w zakładce „Kosz”;
  - SP dostaje wpis.
- [ ] **W koszu nie ma akcji:** zatwierdź, odrzuć, edytuj, zbiorcze.
- [ ] **„Przywróć” z kosza:** oferta wraca do statusu, który miała.
- [ ] **„Usuń trwale”** (tylko z kosza): oferta znika razem ze zdjęciami (sprawdź, czy pliki znikły ze storage), rezerwacjami, sprzedażami i zapytaniami.
- [ ] **Sprzedający nigdy nie widzi oferty z kosza.**

## 9. Duplikowanie (09)

- [ ] **SP duplikuje ofertę.** Powstaje nowy Szkic SP:
  - z tym samym modelem albo wskazówką, opisem i ceną;
  - bez zdjęć, prywatnych danych, producenta, roku, stanu i oceny.
- [ ] **A1 duplikuje ofertę SP:** właścicielem kopii jest A1.
- [ ] **Duplikat oferty z koszem albo z wycofanym modelem** jest odrzucany.

## 10. Ceny i VAT (05, 09)

- [ ] **Cena dla kupującego:**
  - u SF (Firma) równa się cenie wpisanej;
  - u SP (Prywatny) to cena wpisana + VAT 23%, zaokrąglona do grosza.
- [ ] **Zmiana typu konta SP z Prywatnego na Firmowy:**
  - ceny dla kupującego jego ofert spadają od razu;
  - zapisane sprzedaże się nie zmieniają.
- [ ] **SA → ustawienia Ofert → stawka VAT** zmieniona np. na 8%:
  - zmieniają się ceny dla kupującego ofert prywatnych sprzedających;
  - sprzedaże zachowują swoją stawkę;
  - zwykły admin nie ma tego ustawienia.
- [ ] **Zbiorcza zmiana ceny:**
  - działają trzy tryby: ± zł, ± % i cena stała ≥ 1;
  - oferty Zarezerwowane i Sprzedane są pominięte;
  - −50% na ofercie za 1 zł jest pominięte i policzone w komunikacie;
  - SP zmienia tylko swoje oferty;
  - nic nie wraca do ponownego zatwierdzenia.

## 11. Zbiorcze wycofanie i usuwanie (09)

- [ ] **Zbiorcze wycofanie:** oferty, których nie da się wycofać, liczą się jako pominięte, a zapytania aktywnych ofert nieaktualnieją.
- [ ] **Zbiorcze usuwanie** (tylko A1): oferty Zarezerwowane są pominięte.

## 12. Listy, zakładki, filtry, karty (10)

- [ ] **A1 → „Wszystkie oferty”, zakładki:**
  - Wszystkie, Oczekujące (z licznikiem), Aktywne, Zarezerwowane, Sprzedane, Wycofane, Odrzucone, Szkice i Kosz;
  - każda zakładka pokazuje tylko swój status, a żadna poza Koszem nie pokazuje usuniętych.
- [ ] **SP → „Moje oferty”:**
  - te same zakładki bez Kosza i bez licznika;
  - wyłącznie własne oferty.
- [ ] **Filtry tekst, pozycja i kolor:** filtr tekstowy wymaga co najmniej 3 znaków i szuka po Eurokodzie, OEM i pojeździe.
- [ ] **Pozostałe filtry:** „Bez modelu szyby”, sprzedający (tylko u admina), producent, stan i ocena. Przy ocenie 5 gwiazdek łapią się też oferty Nowe.
- [ ] **Zakres ceny:** działa po cenie dla kupującego, nie po wpisanej.
- [ ] **Kolumna „Cena dla kupującego”:** sortuje się poprawnie przy mieszance ofert prywatnych i firmowych.
- [ ] **Kolumna szyby:** miniatura, główny Eurokod, pozycja i kolor albo wskazówka. Przy pustym modelu wyświetla „Bez modelu szyby”.
- [ ] **Kolumny ukryte domyślnie:** producent, stan i ocena da się włączyć przełącznikiem kolumn.
- [ ] **Zbiorcze akcje** pokazują się tylko na zakładkach, gdzie mają sens. Na przykład na „Sprzedanych” nie ma zbiorczej zmiany ceny, a w Koszu nie ma żadnej.
- [ ] **Karty statystyk** (tylko SP, nad „Moje oferty”):
  - Aktywne, Oczekujące, Zarezerwowane i „Sprzedane w tym miesiącu” zgadzają się ze stanem;
  - zwrot sprzedaży zmniejsza „sprzedane”;
  - kliknięcie karty przenosi na jej zakładkę.

## 13. Katalog: „w użyciu” i „N ofert” (11)

- [ ] **Usunięcie modelu szyby albo producenta, którego używa oferta** (także w koszu): Katalog odmawia z informacją o ofertach. Wycofanie jest możliwe.
- [ ] **Karta modelu w Katalogu:**
  - link „1 oferta / 3 oferty / 5 ofert”, bez ofert z kosza;
  - kliknięcie otwiera listę ofert odfiltrowaną po modelu, a kliknięcie nie łapie się na wiersz;
  - przy modelu bez ofert nie ma linku.
- [ ] **Licznik w zależności od roli:**
  - admin widzi liczbę wszystkich ofert;
  - sprzedający, jeśli widzi Katalog, tylko swoje.
- [ ] **Slide-over wyboru modelu** nie pokazuje linków „N ofert”.

## 14. Dezaktywacja sprzedającego (12)

- [ ] **SP ma oferty** w statusach: Szkic, Oczekuje, Aktywna z otwartym zapytaniem, Zarezerwowana, Sprzedana, Odrzucona i jedna w koszu.
- [ ] **A1 dezaktywuje SP:**
  - Szkic, Oczekująca i Aktywna stają się Wycofane;
  - reszta zostaje bez zmian;
  - zapytanie staje się Nieaktualne i A1 dostaje wpis.
- [ ] **Dziennik SP:** dokładnie jeden wpis „Oferty zostały wycofane” z liczbą (tu 3).
- [ ] **W Historii zmian** wycofania mają aktora System i powód „seller deactivated”.
- [ ] **Ponowna aktywacja SP** niczego nie przywraca.

## 15. Wykres aktywności (14)

- [ ] **SP → „Moje oferty”:**
  - wykres jest pod kartami i da się go zwinąć;
  - dwie linie w różnych kolorach, „Dodane do sprzedaży” i „Sprzedane”, za 12 miesięcy z polskimi skrótami miesięcy;
  - tylko własne dane.
- [ ] **A1 → „Wszystkie oferty”:** wykres na górze z filtrem sprzedającego. Po zmianie sprzedającego linie się przeliczają.
- [ ] **Zwrot sprzedaży** znika z linii „Sprzedane”.
- [ ] **Oferta w koszu** nie liczy się w żadnej linii.
- [ ] **Konto bez historii:** 12 zer, bez błędów.

## 16. Ogólne

- [ ] **Teksty:** wszystkie po polsku, bez kluczy tłumaczeń typu `offers::...`.
- [ ] **Uprawnienia:** sprzedający nie widzi cudzych ofert, także po wpisaniu adresu cudzej oferty (powinien być błąd albo 404).
- [ ] **Historia zmian (global):**
  - rezerwacje, sprzedaże i zapytania są widoczne jako osobne typy;
  - zdjęcia pokazują się jako liczba plików.
- [ ] **Widok na telefonie:** lista i formularz oferty wyglądają poprawnie na wąskim ekranie.
