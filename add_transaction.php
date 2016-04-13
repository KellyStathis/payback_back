<?php
    header("Content-Type: application/json");
    ini_set("session.cookie_httponly", 1);
    session_start();
    require 'database.php';
    
    // Note: This version assumes that amount_paid is the total amount owed across all expenses to a user
    
    /*
    // CSRF token
    if ($_SESSION['token'] !== $_POST['token']) {
         die("Request forgery detected");
    }
    $payer_id = $_POST['user_id'];
    $payee_id = $_POST['payee_id'];
    $amount_paid = $_POST['amount_paid'];
    */
    $payer_id = 19;
    $payee_id = 15;
    $amount_paid = 99.99;
    
    $response_array = array();
    $response_array["success"] = true;
    
    // Add transaction
    $stmt = $mysqli->prepare("insert into transactions (payer_id, payee_id, amount_paid) values (?, ?, ?)");
    if(!$stmt){
        printf("Query Prep Failed: %s\n", $mysqli->error);
        exit;
    }
    $stmt->bind_param('iid', $payer_id, $payee_id, $amount_paid);
    $stmt->execute();
    $stmt->close();
    
    // For each expense, clear out amount owed and change it to amount paid
    $stmt = $mysqli->prepare("select id, amount_owed from owed_and_paid where ower_id=?");
    if(!$stmt){
        printf("Query Prep Failed: %s\n", $mysqli->error);
        $response_array["success"] = false;
        json_encode($response_array);
        exit;
    }
    $stmt->bind_param('i', $payer_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $amount_owed);
    while($stmt->fetch()) {
        $stmt_update = $mysqli->prepare("update owed_and_paid set amount_owed=?, amount_paid=? where id=?");
        if(!$stmt_update){
            printf("Query Prep Failed: %s\n", $mysqli->error);
            $response_array["success"] = false;
            json_encode($response_array);
            exit;
        }
        $amount_paid = 0;
        $stmt_update->bind_param('ddi', $amount_paid, $amount_owed, $id);
        $stmt_update->execute();
        $stmt_update->close();
    }
    $stmt->close();
    
    // FIXME send expense ids and altered amounts owed and paid back -- will need this later
    json_encode($response_array);
    exit;
    
    
    
?>