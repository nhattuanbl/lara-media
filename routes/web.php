<?php

use Nhattuanbl\LaraMedia\Livewire\MediaTable;

Route::get('/', function () {
    return view('lara-media::welcome');
})->name('index');

Route::get('datatable', [MediaTable::class, 'datatable'])->name('datatable');
