<?php
/**
 * University Result Management System
 * Landing Page — Redirects to student login
 */
session_start();

// If already logged in, redirect appropriately
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: student/dashboard.php');
    }
    exit;
}

// Default: redirect to student login
header('Location: student/login.php');
exit;
