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
    
    $user_id = $_POST['user_id'];
    //

    //$user_id = 19; // UNCOMMENT TO TEST STAND-ALONE
    $response_array = array();
    $response_array["success"] = true;
    
    
    // QUERY EXPENSES - where user is buyer
    $stmt_buyer = $mysqli->prepare("select id, expense_name, buyer_id, total_amount, date_added from expenses WHERE buyer_id=?");
    if(!$stmt_buyer){
        printf("Query Prep Failed: %s\n", $mysqli->error);
        exit;
    }
    $stmt_buyer->bind_param('i', $user_id);
    $stmt_buyer->execute();
    $stmt_buyer->store_result();
    $stmt_buyer->bind_result($expense_id, $expense_name, $buyer_id, $total_amount, $date_added);
    while($stmt_buyer->fetch()) {
        // QUERY OWED AND PAID - where user is buyer
        $stmt_owers = $mysqli->prepare("select ower_id, amount_owed, amount_paid from owed_and_paid WHERE expense_id=?");
        if(!$stmt_owers) {
            printf("Query Prep Failed: %s\n", $mysqli->error);
            $response_array['success'] = false;
            $response_array['message'] = "Query prep failed";
            echo json_encode($response_array);
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
    }
    $stmt_buyer->close();
                             
                              
    // QUERY OWED AND PAID - where user is ower
    $stmt_ower = $mysqli->prepare("select expense_id, amount_owed, amount_paid from owed_and_paid WHERE ower_id=?");
    if(!$stmt_ower){
        printf("Query Prep Failed: %s\n", $mysqli->error);
        $response_array['success'] = false;
        $response_array['message'] = "Query prep failed";
        echo json_encode($response_array);
        exit;
    }
    $stmt_ower->bind_param('i', $user_id);
    $stmt_ower->execute();
    $stmt_ower->store_result();
    $stmt_ower->bind_result($expense_id, $amount_owed, $amount_paid);
    $owers = array();
    while($stmt_ower->fetch()) {
        $owers[0]['ower_id'] = $user_id;
        $owers[0]['owed'] = $amount_owed;
        $owers[0]['paid'] = $amount_paid;
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
        $stmt_expense->fetch();
        $stmt_expense->close();
        $response_array = existing_expense_toArray($expense_id, $expense_name, $buyer_id, $total_amount, $date_added, $owers, $response_array, $user_id);
    }
    $stmt_ower->close();

    function existing_expense_toArray($expense_id, $expense_name, $buyer_id, $total_amount, $date_added, $owers, $response_array, $user_id) {
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
                if (isset($response_array['friends'][$ower_id])) {
                    array_push($response_array['friends'][$ower_id], $expense_id);
                }
                else {
                    $response_array['friends'][$ower_id] = array();
                    array_push($response_array['friends'][$ower_id], $expense_id);
                }
                
            }
            else { // User is ower
                if (isset($response_array['friends'][$buyer_id])) {
                    array_push($response_array['friends'][$buyer_id], $expense_id);
                }
                else {
                    $response_array['friends'][$buyer_id] = array();
                    array_push($response_array['friends'][$buyer_id], $expense_id);
                }   
            }
        }
        return $response_array;
     }
    
    //print_r($response_array); // UNCOMMENT TO TEST STAND-ALONE
    echo json_encode($response_array, JSON_NUMERIC_CHECK);
    exit; // COMMENT THIS OUT TO TEST STAND-ALONE

?>