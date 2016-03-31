<?php
    header("Content-Type: application/json");
    require 'database.php'; 
    
    // CHECK IF USERNAME TAKEN
    // Use a prepared statement
    $stmt = $mysqli->prepare("SELECT COUNT(*), id FROM users WHERE username=?");
    // Bind the parameter
    $stmt->bind_param('s', $username);
    $username = $_POST['username'];
    // Execute
    $stmt->execute();
    // Bind the results
    $stmt->bind_result($username_cnt, $username_user_id);
    // Fetch and close
    $stmt->fetch();
    $stmt->close();
    
    // CHECK IF EMAIL TAKEN
    // Use a prepared statement
    $stmt2 = $mysqli->prepare("SELECT COUNT(*), id FROM users WHERE email=?");
    // Bind the parameter
    $stmt2->bind_param('s', $email);
    $email = $_POST['email'];
    // Execute
    $stmt2->execute();
    // Bind the results
    $stmt2->bind_result($email_cnt, $email_user_id);
    // Fetch and close
    $stmt2->fetch();
    $stmt2->close();
    
    // Check if user already exists
    if ($username_cnt == 1){
        echo json_encode(array(
            "success" => false,
            "message" => "Username already exists"
        ));
        exit;
    }
    else if ($email_cnt == 1){
        echo json_encode(array(
            "success" => false,
            "message" => "Email already registered"
        ));
        exit;
    }
    else {
        // Check username format
        if(!preg_match('/^[\w_\-]{3,15}+$/', $username) ){ // Username is not valid format
            echo json_encode(array(
                "success" => false,
                "message" => "Invalid user: Username must be 3-15 characters and include only alphanumeric characters, -, and _."
            ));
            exit;
        }
        // Check email format
        else if (!preg_match('/\S+@\S+\.\S+/', $email)) {
            echo json_encode(array(
                "success" => false,
                "message" => "Invalid email address."
            ));
            exit;
        }
        // Check password format
        else if (!preg_match('/^[\w_\-]{5,15}+$/', $_POST['password'])) { // Password invalid
            echo json_encode(array(
                "success" => false,
                "message" => "Invalid password: Password must be 3-15 characters and include only alphanumeric characters, -, and _."
            ));
            exit;
        }
        // Valid user, email, and password, add to users
        else {
            // Use a prepared statement
            $stmt3 = $mysqli->prepare("insert into users (username, email, password) values (?, ?, ?)");
            if(!$stmt3){
                printf("Query Prep Failed: %s\n", $mysqli->error);
                exit;
            }
            // Bind the parameter
            $stmt3->bind_param('sss', $username, $email, $pwd_hash);
            // Encrypt password
            $pwd_hash = crypt($_POST['password']);
            // Execute and close
            $stmt3->execute();
            $stmt3->close();
            echo json_encode(array(
                "success" => true
            ));
            exit;
        }
    }
?>