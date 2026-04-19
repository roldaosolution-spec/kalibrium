<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use OwenIt\Auditing\AuditingServiceProvider;

return [
    AppServiceProvider::class,
    AuditingServiceProvider::class,
    FortifyServiceProvider::class,
];
