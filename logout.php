<?php
// logout.php

// 1. Start the session (necessary to access session variables)
session_start();

// 2. Unset all session variables
// This clears the data stored for the current user's session.
$_SESSION = array();

// 3. Destroy the session
// This removes the session data from the server's storage.
session_destroy();

// 4. Redirect to the login page
// Assuming your login page is named 'login.php'
header('Location: login.php');

// 5. Exit script execution
// Ensures no further code is executed after the redirection header is sent.
exit;
?>