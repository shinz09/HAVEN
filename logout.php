<?php
session_start();
// Empty the session variables
$_SESSION = array();
// Destroy the session cookie
session_destroy();
// Send them back to the login page securely
header("Location: login.php");
exit;
?>