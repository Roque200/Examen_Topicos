<?php
if (file_exists(__DIR__ . '/' . $_SERVER['REQUEST_URI'])) {
    return false;
}
include __DIR__ . '/index.php';
