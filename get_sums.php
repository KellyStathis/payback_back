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
    //
    
    $response_array = array();
    $response_array["success"] = true;
    
    $stmt = $mysqli->prepare("select friend_id FROM friends WHERE user_id=?");
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
    $stmt->bind_result($friend_id);
    while ($stmt->fetch()) {
        $sum_stmt = $mysqli->prepare("select sum(amount_owed) from owed_and_paid JOIN expenses on (expenses.id=owed_and_paid.expense_id) WHERE ower_id=? AND expenses.buyer_id=?");
        if(!$sum_stmt){
            printf("Query Prep Failed: %s\n", $mysqli->error);
            $response_array["success"] = false;
            json_encode($response_array);
            exit;  
        }
        $sum_stmt->bind_param('ii', $user_id, $friend_id);
        $sum_stmt->execute();
        $sum_stmt->store_result();
        $sum_stmt->bind_result($total_owed);
        $sum_stmt->fetch();
        $sum_stmt->close();
    }
    $stmt->close();

    // FIXME encode friend_ids and amounts owed in response array

?>