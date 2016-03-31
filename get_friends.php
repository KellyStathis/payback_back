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
    $stmt = $mysqli->prepare("select friend_id, users.username FROM friends JOIN users on (friends.friend_id=users.id) WHERE user_id=?");
    if(!$stmt){
        printf("Query Prep Failed: %s\n", $mysqli->error);
        exit;
    }
    // Bind the parameter
    $stmt->bind_param('i', $user_id);
    $user_id = $_SESSION['user_id'] ;
    // Execute and close
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($friend_id, $friend_username);
    $friends_array = array();
    $friends_array['success'] = true;
    $cnt = 0;
    while ($stmt->fetch()) {
        $friends_array['friend' + $cnt]['friend_id'] = htmlentities($friend_id);
        $friends_array['friend' + $cnt]['friend_username'] = htmlentities($friend_username);
        $cnt++;       
    }
    $friends_array['count'] = $cnt;
    $stmt->close();

    echo json_encode($friends_array);
    exit;  
    
?>