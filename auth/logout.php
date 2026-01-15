<?php

require_once '../includes/db.php';
// LOGOUT
function logoutUser($conn)
{
    if (isset($_GET['logout']) && $_GET['logout'] == 1) {
        // Update the logged_in status to 0 in the database
        $uname = $_SESSION['username']; // Assuming 'username' is the column where the username is stored in your users table
        $sql = "UPDATE users SET logged_in='0' WHERE uname='$uname'";
        mysqli_query($conn, $sql); // <-- Added semicolon here
        
        // Destroy the session and redirect to the login page
        session_destroy();
        header("Location: /");
        exit; // Prevent further execution
    }
}

// END LOGOUT
?>