<?php
/**
 * Authentication Helper
 * University Result Management System
 * 
 * Include this file at the top of any protected page.
 * Validates the user session and redirects if not authenticated.
 */

session_start();

/**
 * Require student authentication.
 * Redirects to login if not authenticated.
 */
function requireStudentAuth(): void {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
        header('Location: login.php');
        exit;
    }
}

/**
 * Require admin authentication.
 * Redirects to admin login if not authenticated.
 */
function requireAdminAuth(): void {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header('Location: ../admin/index.php');
        exit;
    }
}

/**
 * Check if user is logged in (any role).
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Get the initials of the current user (for avatar).
 */
function getUserInitials(): string {
    if (!isset($_SESSION['name'])) return '?';
    $parts = explode(' ', $_SESSION['name']);
    $initials = '';
    foreach ($parts as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    return substr($initials, 0, 2);
}
