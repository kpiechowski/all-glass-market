<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;

/** FR-08: the site root and the plain sign-in address lead to the panel sign-in page. */
Route::get('/', fn () => redirect(Filament::getPanel('admin')->getLoginUrl()));
Route::get('/login', fn () => redirect(Filament::getPanel('admin')->getLoginUrl()))->name('login');
