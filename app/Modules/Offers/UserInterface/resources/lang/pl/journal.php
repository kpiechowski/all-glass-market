<?php

declare(strict_types=1);

return [
    'approved' => [
        'title' => 'Oferta :number została zatwierdzona',
        'content' => 'Administrator zatwierdził Twoją ofertę :number. Jest teraz aktywna.',
    ],
    'rejected' => [
        'title' => 'Oferta :number została odrzucona',
        'content' => "Administrator odrzucił Twoją ofertę :number.\nPowód: :reason",
    ],
    'changed_by_admin' => [
        'title' => 'Administrator zmienił ofertę :number',
        'content' => 'Administrator zmienił dane Twojej oferty :number. Status oferty pozostał bez zmian.',
    ],
    'withdrawn' => [
        'title' => 'Oferta :number została wycofana',
        'content' => 'Administrator wycofał Twoją ofertę :number. Możesz ją wznowić, jeśli nie zmieniano jej po zatwierdzeniu.',
    ],
    'deleted' => [
        'title' => 'Oferta :number została usunięta',
        'content' => 'Administrator usunął Twoją ofertę :number. Nie widzisz jej już na liście.',
    ],
];
