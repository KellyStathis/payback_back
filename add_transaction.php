<?php
    header("Content-Type: application/json");
    ini_set("session.cookie_httponly", 1);
    session_start();
    require 'database.php';
    
    // Note: This version assumes that amount_paid is the total amount owed across all expenses to a user
    
    // CSRF token
    if ($_SESSION['token'] !== $_POST['token']) {
         die("Request forgery detected");
    }
    $payer_id = $_POST['user_id'];
    $payee_id = $_POST['payee_id'];
    $amount_paid = $_POST['amount_paid'];
    $type = $_POST['type']; // "all" or "partial"
    
    /*
    $payer_id = 19;
    $payee_id = 15;
    $amount_paid = 99.99;
    */
    
    $response_array = array();
    $response_array["success"] = true;
    
    // Add transaction
    $stmt = $mysqli->prepare("insert into transactions (payer_id, payee_id, amount_paid) values (?, ?, ?)");
    if(!$stmt){
        query_error();
    }
    $stmt->bind_param('iid', $payer_id, $payee_id, $amount_paid);
    $stmt->execute();
    $stmt->close();
    
    // FIXME eliminate type all/partial -- only execute partial code (will also work for "all")
    
    if ($type == "all") {
        // For each expense, clear out amount owed and change it to amount paid
        $stmt = $mysqli->prepare("select id, amount_owed from owed_and_paid where ower_id=?");
        if(!$stmt){
            query_error();
        }
        $stmt->bind_param('i', $payer_id);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id, $expense_amount_owed);
        while($stmt->fetch()) {
            $stmt_update = $mysqli->prepare("update owed_and_paid set amount_owed=?, amount_paid=? where id=?");
            if(!$stmt_update){
                query_error();
            }
            $expense_amount_paid = 0;
            $stmt_update->bind_param('ddi', $expense_amount_paid, $expense_amount_owed, $id);
            $stmt_update->execute();
            $stmt_update->close();
        }
        $stmt->close();
    }
    else if ($type == "partial") {
        $sum_stmt = $mysqli->prepare("select sum(amount_owed) from owed_and_paid WHERE ower_id=?");
        if(!$sum_stmt){
            query_error();
        }
        $sum_stmt->bind_param('i', $payer_id);
        $sum_stmt->execute();
        $sum_stmt->store_result();
        $sum_stmt->bind_result($total_owed);
        $sum_stmt->fetch();
        $sum_stmt->close();
        
        if ($amount_paid > $total_owed) {
            $response_array["success"] = false;
            $response_array["message"] = "Cannot pay more than total amount owed";
            json_encode($response_array);
            exit; 
        }
        
        $stmt = $mysqli->prepare("select owed_and_paid.id, owed_and_paid.amount_owed, owed_and_paid.amount_paid FROM owed_and_paid
            JOIN expenses on (expenses.id=owed_and_paid.expense_id) WHERE ower_id=? ORDER BY expenses.date_added");
        if(!$stmt_update){
            query_error();
        }
        $money_left = $amount_paid;
        $stmt->bind_param('i', $payer_id);
        $stmt->execute();
        $stmt->bind_result();
        $stmt->bind_result($id, $expense_amount_owed, $expense_amount_paid);
        while($stmt->fetch() && $money_left > 0) {
            $stmt_update = $mysqli->prepare("update owed_and_paid set amount_owed=?, amount_paid=? where id=?");
            if(!$stmt_update){
                query_error();
            }
            if ($money_left >= $expense_amount_owed) {
                $expense_amount_paid = $expense_amount_owed;
                $expense_amount_owed = 0;
                $money_left = $money_left - $expense_amount_paid;
            }
            else {
                $expense_amount_paid = $money_left;
                $expense_amount_owed = $expense_amount_owed - $expense_amount_paid;
                $money_left = 0;
            }
            $stmt_update->bind_param('ddi', $expense_amount_owed, $expense_amount_paid, $id);
            $stmt_update->execute();
            $stmt_update->close();
        }
    }
    
    
    
    // FIXME send expense ids and altered amounts owed and paid back -- will need this later
    json_encode($response_array);
    exit;
    
    function query_error() {
        printf("Query Prep Failed: %s\n", $mysqli->error);
        $response_array["success"] = false;
        json_encode($response_array);
        exit;  
    }
    
    
?>