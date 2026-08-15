<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use SocialiteProviders\Manager\ServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    ServiceProvider::class,
];
