<?php

    header("Content-Type: application/json");
    ini_set("session.cookie_httponly", 1);
    session_start();
    require 'database.php';
    
    // COMMENT THIS OUT TO TEST STAND-ALONE
    // CSRF token
    if ($_SESSION['token'] !== $_POST['token']) {
         die("Request forgery detected");
    }
    
    // get sums for each friend


?>