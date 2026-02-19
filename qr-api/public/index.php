<?php
// Entry point - API Generadora de Códigos QR
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../resources/v1/QrResource.php';

$router     = new Router('v1', '');
$qrResource = new QrResource();

// ============================================================
// Rutas de la API
// ============================================================

// POST /api/v1/qr/text  -> QR de texto plano
$router->addRoute('POST', '/qr/text', [$qrResource, 'text']);

// POST /api/v1/qr/url   -> QR de URL
$router->addRoute('POST', '/qr/url', [$qrResource, 'url']);

// POST /api/v1/qr/wifi  -> QR de red WiFi
$router->addRoute('POST', '/qr/wifi', [$qrResource, 'wifi']);

// POST /api/v1/qr/geo   -> QR de geolocalización
$router->addRoute('POST', '/qr/geo', [$qrResource, 'geo']);

$router->dispatch();
?>
