<?php
require_once __DIR__ . '/../includes/admin-auth.php';
unset($_SESSION['admin_id']);
flash('success', 'Admin logged out.');
redirect('/admin/login.php');
