<?php
require_once __DIR__ . '/includes/auth.php';

$current = current_user();
if ($current) {
    redirect($current['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php');
}

flash('info', 'Student accounts are created by an administrator. Ask your administrator for your email and password.');
redirect('login.php');
