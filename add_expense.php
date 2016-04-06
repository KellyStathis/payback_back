<?php
    header("Content-Type: application/json");
    ini_set("session.cookie_httponly", 1);
    session_start();
    require 'database.php';
    
    $json_data = json_decode('php://input');
    $expense_name = $json_data['expense_name'];
    $buyer_id = $json_data['buyer_id'];
    $total_amount = $json_data['total_amount'];
    $date_added = $json_data['date_added']; //'yyyy-mm-dd'
    $owers = $json_data['owers'];
    $num_owers = count($json_data['owers']);

    // CSRF token
    if ($_SESSION['token'] !== $json_data['token']) {
         die("Request forgery detected");
    }
    
    /* TEST EXAMPLE
    $expense_name = "ice cream";
    $buyer_id = 15;
    $total_amount = 6.80;
    $date_added = "2016-04-05";
    $owers[0] = 19;
    $num_owers = count($owers);
    */
    
    if ($total_amount <= 0) { // Check that expense is postive
        echo json_encode(array(
            "success" => false,
            "message" => "Cannot add a negative expense"
        ));
        exit;
    }
    else if (!preg_match('/\d{4}-\d{2}-\d{2}/', $date_added)) { // Check date format
        echo json_encode(array(
            "success" => false,
            "message" => "Invalid date format"
        ));
        exit;
    }
    else { // Check date values
        list($year, $month, $day) = split('-', $date_added);
        echo "month: " + $month + "\n";
        echo "day: " + $day + "\n";
        echo "year: " + $year + "\n";
        if (!checkdate($month, $day, $year)) {
            echo json_encode(array(
                "success" => false,
                "message" => "Invalid date"
            ));
            exit;
        }
    }
    
    // INSERT INTO EXPENSES
    // Use a prepared statement
    $stmt = $mysqli->prepare("insert into expenses (expense_name, buyer_id, total_amount, date_added) values (?, ?, ?, ?)");
    if(!$stmt){
        printf("Query Prep Failed: %s\n", $mysqli->error);
        exit;
    }
    // Bind the parameter
    $stmt->bind_param('sids', $expense_name, $buyer_id, $total_amount, $date_added);
    // Execute and close
    $stmt->execute();
    $stmt->close();
   
    $expense_id = $mysqli->insert_id;
    
    // INSERT INTO OWED_AND_PAID FOR EACH OWER
    for ($i = 0; $i < count($owers); $i++) {
        // Use a prepared statement
        $stmt = $mysqli->prepare("insert into owed_and_paid (expense_id, ower_id, amount_owed, amount_paid) values (?, ?, ?, ?)");
        if(!$stmt){
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
        }
        // Bind the parameter
        $stmt->bind_param('iidd', $expense_id, $ower_id, $amount_owed, $amount_paid);
        $ower_id = $owers[$i];
        $amount_owed = $total_amount / (count($owers) + 1);
        $amount_paid = 0;
        // Execute and close
        $stmt->execute();
        $stmt->close();
    }
    echo jsonify_new_expense($expense_id, $expense_name, $buyer_id, $total_amount, $date_added, $owers);
    exit;
    
    function jsonify_new_expense($expense_id, $expense_name, $buyer_id, $total_amount, $date_added, $owers) {
        $response_array['expense_id'] = $expense_id;
        $response_array['expense_name'] = $expense_name;
        $response_array['buyer_id'] = $buyer_id;
        $response_array['total'] = $total_amount;
        $response_array['date_added'] = $date_added;
        for ($i = 0; $i < count($owers); $i++) {
             $response_array['owers'][$i]['friend_id'] = $owers[$i];
             $response_array['owers'][$i]['owed'] = $total_amount / (count($owers) + 1);
             $response_array['owers'][$i]['paid'] = 0;
             
        }
        return json_encode($response_array);
    }
    

?>