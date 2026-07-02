<?php

/**
 * Client API Routes (User-facing API)
 * Prefix: /api/client/extensions/cfsubdomain/
 * Requires: Client API key (user session)
 */

use Illuminate\Support\Facades\Route;
use Pterodactyl\BlueprintFramework\Extensions\cfsubdomain\SubdomainApiController;

Route::get('/servers/{server}/subdomains', [SubdomainApiController::class, 'getServerSubdomains']);
Route::get('/servers/{server}/ports', [SubdomainApiController::class, 'getAvailablePorts']);
Route::post('/subdomains', [SubdomainApiController::class, 'createSubdomain']);
Route::delete('/subdomains/{id}', [SubdomainApiController::class, 'deleteSubdomain']);
