<?php
    header("Content-Type: application/json");
    require 'database.php'; 
    
    // Use a prepared statement
    $stmt = $mysqli->prepare("SELECT COUNT(*), id, password FROM users WHERE name=?");
    // Bind the parameter
    $stmt->bind_param('s', $user);
    $user = $_POST['new_username'];
    // Execute
    $stmt->execute();
    // Bind the results
    $stmt->bind_result($cnt, $user_id, $pwd_hash);
    // Fetch and close
    $stmt->fetch();
    $stmt->close();
    
    
    // Check if user already exists
    if($cnt == 1){
        echo json_encode(array(
            "success" => false,
            "message" => "User already exists"
        ));
        exit;
    }  
    else {
        // Check username format
        if(!preg_match('/^[\w_\-]{3,15}+$/', $user) ){ // Username is not valid format
            echo json_encode(array(
                "success" => false,
                "message" => "Invalid user: Username must be 3-15 characters and include only alphanumeric characters, -, and _."
            ));
            exit;
        }
        // Check password format
        else if (!preg_match('/^[\w_\-]{5,15}+$/', $_POST['new_password'])) { // Password invalid
            echo json_encode(array(
                "success" => false,
                "message" => "Invalid password: Password must be 3-15 characters and include only alphanumeric characters, -, and _."
            ));
            exit;
        }
        // Valid user and password, add to users
        else {
            // Use a prepared statement
            $stmt = $mysqli->prepare("insert into users (name, password) values (?, ?)");
            if(!$stmt){
                printf("Query Prep Failed: %s\n", $mysqli->error);
                exit;
            }
            // Bind the parameter
            $stmt->bind_param('ss', $user, $pwd_hash);
            $user = $_POST['new_username'];
            // Encrypt password
            $pwd_hash = crypt($_POST['new_password']);
            // Execute and close
            $stmt->execute();
            $stmt->close();
            echo json_encode(array(
                "success" => true
            ));
            exit;
        }
    }
?>