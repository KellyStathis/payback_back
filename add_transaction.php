<?php
    header("Content-Type: application/json");
    ini_set("session.cookie_httponly", 1);
    session_start();
    require 'database.php';
    
    // COMMENT OUT TO TEST STAND-ALONE
    // CSRF token
    if ($_SESSION['token'] !== $_POST['token']) {
         die("Request forgery detected");
    }
    $payer_id = $_POST['user_id'];
    $payee_id = $_POST['payee_id'];
    $amount_paid = $_POST['amount_paid'];
    //
    
    /*// UNCOMMENT TO TEST STAND-ALONE
    $payer_id = 19;
    $payee_id = 15;
    $amount_paid = 6.40;
    //*/
    
    $response_array = array();
    $response_array["success"] = true;
    
    // Check to make sure user is not paying off more than total amount owed
    $sum_stmt = $mysqli->prepare("select sum(amount_owed) from owed_and_paid JOIN expenses on (expenses.id=owed_and_paid.expense_id) WHERE ower_id=? AND expenses.buyer_id=?");
    if(!$sum_stmt){
        printf("Query Prep Failed: %s\n", $mysqli->error);
        $response_array["success"] = false;
        json_encode($response_array);
        exit;  
    }
    $sum_stmt->bind_param('ii', $payer_id, $payee_id);
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
    
    // Add transaction
    $stmt = $mysqli->prepare("insert into transactions (payer_id, payee_id, amount_paid) values (?, ?, ?)");
    if(!$stmt){
        printf("Query Prep Failed: %s\n", $mysqli->error);
        $response_array["success"] = false;
        json_encode($response_array);
        exit;  
    }
    $stmt->bind_param('iid', $payer_id, $payee_id, $amount_paid);
    $stmt->execute();
    $stmt->close();
    
    // Updaet owed_and_paid entries
    $stmt = $mysqli->prepare("select owed_and_paid.id, owed_and_paid.amount_owed, owed_and_paid.amount_paid, owed_and_paid.expense_id FROM owed_and_paid
        JOIN expenses on (expenses.id=owed_and_paid.expense_id) WHERE ower_id=? ORDER BY expenses.date_added");
    if(!$stmt){
        printf("Query Prep Failed: %s\n", $mysqli->error);
        $response_array["success"] = false;
        json_encode($response_array);
        exit;  
    }
    $money_left = $amount_paid;
    $stmt->bind_param('i', $payer_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $expense_amount_owed, $expense_amount_paid, $expense_id);
    while($stmt->fetch() && $money_left > 0) {
        $stmt_update = $mysqli->prepare("update owed_and_paid set amount_owed=?, amount_paid=? where id=?");
        if(!$stmt_update){
            printf("Query Prep Failed: %s\n", $mysqli->error);
            $response_array["success"] = false;
            json_encode($response_array);
            exit;  
        }
        $prev_expense_amount_paid = $expense_amount_paid;
        $prev_expense_amount_owed = $expense_amount_owed;
        if ($money_left >= $expense_amount_owed) {
            $expense_amount_paid = $prev_expense_amount_paid + $prev_expense_amount_owed;
            $expense_amount_owed = 0;
            $money_left = $money_left - $prev_expense_amount_owed; // money left minus amount just paid off
        }
        else {
            $expense_amount_paid = $money_left + $prev_expense_amount_paid;
            $expense_amount_owed = $prev_expense_amount_owed - $money_left;
            $money_left = 0;
        }
        $stmt_update->bind_param('ddi', $expense_amount_owed, $expense_amount_paid, $id);
        $stmt_update->execute();
        $stmt_update->close();
    }
    $stmt->close();

    //echo json_encode($response_array, JSON_NUMERIC_CHECK);
    
    print_r(json_encode($response_array, JSON_NUMERIC_CHECK));
    exit; // COMMENT OUT TO TEST STAND-ALONE
    
    
?>