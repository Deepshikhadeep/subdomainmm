<?php

/**
 * Application API Routes (Admin API)
 * Prefix: /api/application/extensions/cfsubdomain/
 * Requires: Application API key
 */

use Illuminate\Support\Facades\Route;
use Pterodactyl\BlueprintFramework\Extensions\cfsubdomain\SubdomainApiController;

Route::get('/servers/{server}/subdomains', [SubdomainApiController::class, 'getServerSubdomains']);
Route::get('/servers/{server}/ports', [SubdomainApiController::class, 'getAvailablePorts']);
Route::post('/subdomains', [SubdomainApiController::class, 'createSubdomain']);
Route::delete('/subdomains/{id}', [SubdomainApiController::class, 'deleteSubdomain']);
Route::get('/nodes/{node}/settings', [SubdomainApiController::class, 'getNodeSettings']);
