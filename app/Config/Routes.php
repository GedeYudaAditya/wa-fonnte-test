<?php

use App\Controllers\WhatsAppAPI;
use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->group('api', function ($routes) {
    $routes->post('whatsapp', [WhatsAppAPI::class, 'index']);

    $routes->get('whatsapp/webhook', [WhatsAppAPI::class, 'checkStatusMessage']);
    $routes->post('whatsapp/webhook', [WhatsAppAPI::class, 'checkStatusMessage']);
});
