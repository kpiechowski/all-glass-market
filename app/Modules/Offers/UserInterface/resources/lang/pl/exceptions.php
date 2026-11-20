<?php

declare(strict_types=1);

return [
    'glass_model_and_hint_together' => 'Oferta ma model szyby albo podpowiedź identyfikacyjną, nie oba naraz. Usuń jedno z nich.',
    'hint_is_too_long' => 'Podpowiedź identyfikacyjna może mieć najwyżej :max znaków.',
    'rating_is_invalid' => 'Ocena to liczba gwiazdek od 1 do 5.',
    'rating_not_for_new' => 'Oferta nowej szyby nie ma oceny. Usuń ocenę albo zmień stan.',
    'rating_needs_condition' => 'Ocenę można podać dopiero po wybraniu stanu: używana albo outlet.',
    'price_is_invalid' => 'Cena nie może być ujemna.',
    'year_is_invalid' => 'Podaj rok z przedziału od 1900 do przyszłego roku.',
    'number_is_invalid' => 'Numer oferty to AG i osiem cyfr.',
    'condition_is_unknown' => 'Wybierz stan: nowa, używana albo outlet.',
    'glass_model_cannot_be_picked' => 'Tego modelu szyby nie można wybrać, bo nie istnieje albo został wycofany. Wybierz inny model.',
    'producer_cannot_be_picked' => 'Tego producenta nie można wybrać, bo nie ma go na liście albo został wycofany. Wybierz innego producenta.',
    'too_many_public_photos' => 'Oferta może mieć najwyżej :max zdjęć publicznych.',
    'too_many_private_photos' => 'Oferta może mieć najwyżej :max zdjęć prywatnych.',
    'photo_is_invalid' => 'Nie można użyć tego zdjęcia. Dodaj je ponownie.',
    'not_yours' => 'Nie masz dostępu do tej oferty.',
    'missing_for_sending' => 'Nie można wysłać oferty do zatwierdzenia. Uzupełnij: :fields.',
    'sending_fields' => [
        'glass' => 'Szyba (model albo podpowiedź identyfikacyjna)',
        'price' => 'Cena (co najmniej 1 zł)',
        'condition' => 'Stan',
        'rating' => 'Ocena',
        'public_photos' => 'Zdjęcia publiczne (co najmniej jedno)',
    ],
    'cannot_be_sent' => 'Tej oferty nie można wysłać do zatwierdzenia w jej obecnym statusie.',
    'cannot_be_approved' => 'Zatwierdzić można tylko ofertę oczekującą na zatwierdzenie albo odrzuconą.',
    'glass_model_required' => 'Oferta bez modelu szyby nie może zostać zatwierdzona. Wybierz albo utwórz model szyby.',
    'cannot_be_rejected' => 'Odrzucić można tylko ofertę oczekującą na zatwierdzenie albo aktywną.',
    'rejection_reason_required' => 'Podaj powód odrzucenia oferty.',
];
