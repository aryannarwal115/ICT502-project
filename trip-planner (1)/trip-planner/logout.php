<?php
require_once __DIR__ . '/includes/auth.php';
$_SESSION = [];
session_unset();
session_destroy();
session_start();
flash('success', 'You have been logged out.');
redirect('/index.php');
