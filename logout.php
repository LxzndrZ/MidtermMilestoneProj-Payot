<?php
require __DIR__ . '/bootstrap.php';
Auth::logout();
// The session was destroyed, so start a fresh one just to carry the flash message.
Auth::start();
Auth::flash('You have been logged out.');
redirect('login.php');