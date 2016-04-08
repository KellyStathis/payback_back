<?php

    header("Content-Type: application/json");
    ini_set("session.cookie_httponly", 1);
    session_start();
    require 'database.php';
    
    /*
    // CSRF token
    if ($_SESSION['token'] !== $_POST['token']) {
         die("Request forgery detected");
    }
    
    $user_id = $_POST['user_id'];
    */
    
    $user_id = 15;
    $response_array = array();
    
    
    // QUERY EXPENSES - where user is buyer
    $stmt = $mysqli->prepare("select id, expense_name, buyer_id, total_amount, date_added from expenses WHERE buyer_id=?");
    if(!$stmt){
        printf("Query Prep Failed: %s\n", $mysqli->error);
        exit;
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($expense_id, $expense_name, $buyer_id, $total_amount, $date_added);
    while($stmt->fetch()) {
        // QUERY OWED AND PAID - where user is buyer
        $stmt_owers = $mysqli->prepare("select ower_id, amount_owed, amount_paid from owed_and_paid WHERE expense_id=?");
        if(!$stmt_owers) {
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
        }
        $stmt_owers->bind_param('i', $expense_id);
        $stmt_owers->execute();
        $stmt_owers->store_result();
        $stmt_owers->bind_result($ower_id, $amount_owed, $amount_paid);
        $ower_index = 0;
        $owers = array();
        while ($stmt_owers->fetch()) {
            $owers[$ower_index]['ower_id'] = $ower_id;
            $owers[$ower_index]['owed'] = $amount_owed;
            $owers[$ower_index]['paid'] = $amount_paid;
            $ower_index++;
        }
        $stmt_owers->close();
        
        $response_array = existing_expense_toArray($expense_id, $expense_name, $buyer_id, $total_amount, $date_added, $owers, $response_array, $user_id);
        echo "response array after function return: \n";
        print_r($response_array);
    }
    $stmt->close();
                             
                              
    // QUERY OWED AND PAID - where user is ower
    $stmt = $mysqli->prepare("select expense_id, amount_owed, amount_paid from owed_and_paid WHERE ower_id=?");
    if(!$stmt){
        printf("Query Prep Failed: %s\n", $mysqli->error);
        exit;
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($expense_id, $amount_owed, $amount_paid);
    $owers = array();
    $owers[0]['ower_id'] = $user_id;
    $owers[0]['owed'] = $amount_owed;
    $owers[0]['paid'] = $amount_paid;
    while($stmt->fetch()) {
        // QUERY EXPENSES - where user is ower
        $stmt_expense = $mysqli->prepare("select expense_name, buyer_id, total_amount, date_added from expenses WHERE id=?");
        if(!$stmt_expense)  {
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
        }
        $stmt_expense->bind_param('i', $expense_id);
        $stmt_expense->execute();
        $stmt_expense->store_result();
        $stmt_expense->bind_result($expense_name, $buyer_id, $total_amount, $date_added);
        $stmt_expense->close();
        $response_array = existing_expense_toArray($expense_id, $expense_name, $buyer_id, $total_amount, $date_added, $owers, $response_array, $user_id);
        echo "response array after function return: \n";
        print_r($response_array);
    }
    $stmt->close();

    function existing_expense_toArray($expense_id, $expense_name, $buyer_id, $total_amount, $date_added, $owers, $response_array, $user_id) {
        echo "expense id found: " . $expense_id . "\n";
        $response_array['expenses'][$expense_id]['expense_id'] = $expense_id;
        $response_array['expenses'][$expense_id]['expense_name'] = $expense_name;
        $response_array['expenses'][$expense_id]['buyer_id'] = $buyer_id;
        $response_array['expenses'][$expense_id]['total'] = $total_amount;
        $response_array['expenses'][$expense_id]['date_added'] = $date_added;
        for ($i = 0; $i < count($owers); $i++) {
            $ower_id = $owers[$i]['ower_id'];
            $response_array['expenses'][$expense_id]['owers'][$i]['ower_id'] = $ower_id;
            $response_array['expenses'][$expense_id]['owers'][$i]['owed'] = $owers[$i]['owed'];
            $response_array['expenses'][$expense_id]['owers'][$i]['paid'] = $owers[$i]['paid'];
            if ($buyer_id == $user_id) { // User is buyer
                // FIXME Add expense to ower friends's list - curently overwrites
                $response_array['friends'][$ower_id]['expense_id'] = $expense_id;
            }
            else { // User is ower
                // FIXME Add expense to buyer friend's list - curently overwrites
                $response_array['friends'][$buyer_id]['expense_id'] = $expense_id;      
            }
        }
        echo "response array before function return: \n";
        print_r($response_array);
        return $response_array;
     }
    
    //return json_encode($response_array);
    echo "response array at end: \n";
    print_r($response_array);
    echo "JSON: \n";
    echo json_encode($response_array);

?>