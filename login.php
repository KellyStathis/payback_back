<?php
    header("Content-Type: application/json");
 
    require 'database.php';
     
    // Use a prepared statement
    $stmt = $mysqli->prepare("SELECT COUNT(*), id, password FROM users WHERE name=?");   
    // Bind the parameter
    $stmt->bind_param('s', $user);
    $user = $_POST['username'];
    // Execute
    $stmt->execute();
    // Bind and fetch the results
    $stmt->bind_result($cnt, $user_id, $pwd_hash);
    $stmt->fetch();
     
    $pwd_guess = $_POST['password'];
    // Compare the submitted password to the actual password hash
    if ($cnt == 1 && crypt($pwd_guess, $pwd_hash)==$pwd_hash) {
        // Login succeeded!
        session_start();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $user;
        $_SESSION['token'] = substr(md5(rand()), 0, 10); // generate a 10-character random string
        echo json_encode(array(
            "success" => true,
            "token" => $_SESSION['token']
        ));
        exit;
    } else {
        echo json_encode(array(
            "success" => false,
            "message" => "Incorrect Username or Password"
        ));
        exit;
    }
?>