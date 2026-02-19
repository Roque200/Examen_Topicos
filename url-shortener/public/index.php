<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../resources/v1/UrlResource.php';

$router      = new Router('v1', '');
$urlResource = new UrlResource();


$router->addRoute('POST', '/shorten', [$urlResource, 'shorten']);
$router->addRoute('GET', '/redirect/{code}', [$urlResource, 'redirect']);
$router->addRoute('GET', '/stats/{code}', [$urlResource, 'stats']);
$router->dispatch();
?>
