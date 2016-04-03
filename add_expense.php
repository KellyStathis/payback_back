<?php
    header("Content-Type: application/json");
    ini_set("session.cookie_httponly", 1);
    session_start();
    require 'database.php';
    
    $json_data = json_decode('php://input');
    $expense_name = $json_data['expense_name'];
    $buyer_id = $json_data['buyer_id'];
    $total_amount = $json_data['total_amount'];
    $date_added = $json_data['date_added'];
    $owers = $json_data['owers'];
    $num_owers = $json_data['owers'].length;
    
    
    //'yyyy-mm-dd'
    
    
    // CSRF token
    if ($_SESSION['token'] !== $json_data['token']) {
         die("Request forgery detected");
    }
    
    if ($total_amount <= 0) {
        echo json_encode(array(
            "success" => false,
            "message" => "Cannot add a negative expense"
        ));
        exit;
    }
    // FIXME date regex
    else if (!preg_match('', $date_added)) {
        echo json_encode(array(
            "success" => false,
            "message" => "Invalid date"
        ));
        exit;
    }
    
    // Formatted correctly
    // Use a prepared statement
    $stmt = $mysqli->prepare("insert into expenses (expense_name, buyer_id, total_amount, date_added) values (?, ?, ?, ?)");
    if(!$stmt){
        printf("Query Prep Failed: %s\n", $mysqli->error);
        exit;
    }
   
    
    // For each ower:
    // Use a prepared statement
    $stmt2 = $mysqli->prepare("insert into owed_and_paid (expense_id, ower_id, amount_owed, amount_paid) values (?, ?, ?, ?)");
    if(!$stmt2){
        printf("Query Prep Failed: %s\n", $mysqli->error);
        exit;
    }   
     

?>