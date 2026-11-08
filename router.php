<?php
// Router for PHP's built-in server: never serve the database folder over HTTP.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
if (preg_match('#^/(data|includes)(/|$)#', $path)) {
    http_response_code(403);
    exit('Forbidden');
}
return false;   // serve everything else normally
