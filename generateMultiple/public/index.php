<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../resources/v1/PasswordResource.php';

$router           = new Router('v1', '');
$passwordResource = new PasswordResource();

$router->addRoute('GET',  '/password',          [$passwordResource, 'generate']);
$router->addRoute('POST', '/passwords',         [$passwordResource, 'generateMultiple']);
$router->addRoute('POST', '/password/validate', [$passwordResource, 'validate']);

$router->dispatch();
