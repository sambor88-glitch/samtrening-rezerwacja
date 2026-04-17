<?php

return [
    'name'            => env('APP_NAME', 'SAMtrening'),
    'env'             => env('APP_ENV', 'production'),
    'debug'           => (bool) env('APP_DEBUG', false),
    'url'             => env('APP_URL', 'http://localhost'),
    'timezone'        => 'Europe/Warsaw',
    'locale'          => 'pl',
    'fallback_locale' => 'en',
    'faker_locale'    => 'pl_PL',
    'cipher'          => 'AES-256-CBC',
    'key'             => env('APP_KEY'),
    'providers'       => \Illuminate\Support\ServiceProvider::defaultProviders()->toArray(),
    'aliases'         => \Illuminate\Foundation\AliasLoader::getInstance()->getAliases(),
];
