<?php
// Every page starts with: require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';

// Load a class file from /classes automatically the first time the class is used.
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/classes/' . $class . '.php';
    if (is_file($file)) {
        require $file;
    }
});

Auth::start();

// Make any text safe to print inside HTML (stops XSS).
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// Show a saved date like "Oct 9, 2026 1:30 PM".
function show_date($datetime): string
{
    return date('M j, Y g:i A', strtotime((string)$datetime));
}

// Read a text value from $_POST or $_GET safely (trimmed). Returns '' if it is missing or not text.
function input_str(array $source, string $key): string
{
    return (isset($source[$key]) && is_string($source[$key])) ? trim($source[$key]) : '';
}

// Same, but not trimmed (used for passwords, where spaces can be part of the password).
function input_raw(array $source, string $key): string
{
    return (isset($source[$key]) && is_string($source[$key])) ? $source[$key] : '';
}