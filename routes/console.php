<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('gestion:estado', function () {
    $this->info('Gestion Docente está disponible. Revise README.md para la instalación en Laragon.');
});
