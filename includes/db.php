<?php
// includes/db.php

$host = 'localhost';
$dbname = 'haven_db';
$username = 'root'; // Default XAMPP username
$password = '';     // Default XAMPP password is blank

try {
    // Connect to the database using PDO for security
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Start a session here so every page remembers if the user is logged in
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}
?>