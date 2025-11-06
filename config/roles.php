<?php
// Role constants
define('ROLE_ADMIN', 'admin');
define('ROLE_DOCTOR', 'doctor');
define('ROLE_NURSE', 'nurse');
define('ROLE_PHARMACY', 'pharmacy');
define('ROLE_PATIENT', 'patient');

/**
 * Check if current session user has the exact role.
 */
function isRole(string $role): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Check if current session user has any of the provided roles.
 */
function userHasAnyRole(array $roles): bool {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $roles, true);
}
?>