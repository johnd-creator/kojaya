<?php

session_start();
echo '<pre>';
echo 'Session ID: '.session_id()."\n";
echo 'Session Data: '.print_r($_SESSION, true)."\n";
echo 'Cookies: '.print_r($_COOKIE, true)."\n";
echo '</pre>';
if (auth()->check()) {
    echo 'User is authenticated: '.auth()->user()->name."\n";
} else {
    echo "User is NOT authenticated\n";
}
