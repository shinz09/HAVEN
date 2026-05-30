<?php
// includes/auth.php

// Make sure the session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to kick out anyone who isn't logged in
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

// Function to kick out anyone who isn't a specific role
function requireRole($requiredRole) {
    requireLogin();
    if ($_SESSION['role'] !== $requiredRole) {
        // If a Hotel manager tries to sneak into the Admin page, kick them to home
        header("Location: index.php"); 
        exit;
    }
}
?>