<?php
    header("Content-Type: application/json");
    ini_set("session.cookie_httponly", 1);
    session_start();
    require 'database.php'; 
    
     // CSRF token
    if ($_SESSION['token'] !== $_POST['token']) {
         die("Request forgery detected");
    }
    
    // Use a prepared statement
    $stmt = $mysqli->prepare("SELECT COUNT(*), id, username FROM users WHERE email=?");
    // Bind the parameter
    $stmt->bind_param('s', $email);
    $email = $_POST['friend_email'];
    // Execute
    $stmt->execute();
    // Bind the results
    $stmt->bind_result($cnt, $friend_id, $friend_username);
    // Fetch and close
    $stmt->fetch();
    $stmt->close();
    
    // FIXME: check if user is already friends with the user
    
    // Check if email valid
    if(!preg_match('/\S+@\S+\.\S+/', $email) ){
        echo json_encode(array(
            "success" => false,
            "message" => "Invalid email address."
        ));
        exit;
    }
    // Check if user does not exist
    else if ($cnt == 0){
        echo json_encode(array(
            "success" => false,
            "message" => "Friend does not exist: have them make an account!"
        ));
        exit;
    }
    else if ($_SESSION['user_id'] == $friend_id) {
        echo json_encode(array(
            "success" => false,
            "message" => "Cannot add yourself as a friend!"
        ));
        exit;
    }
    else { // Friend user exists, add to friend lists
        // Use a prepared statement
        $stmt = $mysqli->prepare("insert into friends (user_id, friend_id) values (?, ?)");
        if(!$stmt){
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
        }
        // Bind the parameter
        $stmt->bind_param('ii', $user_id, $friend_id);
        $user_id = $_SESSION['user_id'];
        // Execute and close
        $stmt->execute();
        $stmt->close();
        
        // Use a prepared statement
        $stmt2 = $mysqli->prepare("insert into friends (user_id, friend_id) values (?, ?)");
        if(!$stmt2){
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
        }
        // Bind the parameter
        $stmt2->bind_param('ii', $friend_id, $user_id);
        // Execute and close
        $stmt2->execute();
        $stmt2->close();
        
        echo json_encode(array(
            "success" => true
        ));
        exit;
    }
?>