<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(array("status" => "ok"));
    exit();
}

include 'connn.php';

$xcase = isset($_POST['xcase']) ? $_POST['xcase'] : '';
$id = isset($_POST['id']) ? $_POST['id'] : '';

$evaluate_date = isset($_POST['evaluate_date']) ? $_POST['evaluate_date'] : '';
$company_id = isset($_POST['company_id']) ? $_POST['company_id'] : '';
$std_id = isset($_POST['std_id']) ? $_POST['std_id'] : '';
$term = isset($_POST['term']) ? $_POST['term'] : '';
$status = isset($_POST['status']) ? $_POST['status'] : '1';
$detail = isset($_POST['detail']) ? $_POST['detail'] : '';
$prog_language = isset($_POST['prog_language']) ? $_POST['prog_language'] : '';
$price = isset($_POST['price']) ? $_POST['price'] : '0';
$cost = isset($_POST['cost']) ? $_POST['cost'] : '0';
$transport = isset($_POST['transport']) ? $_POST['transport'] : '0';

if ($xcase == 1) {
    $sql = "INSERT INTO intern_evaluate
    (evaluate_date, company_id, std_id, term, status, detail, prog_language, price, cost, transport)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(array("status" => "error", "message" => "Prepare failed: " . $conn->error));
        exit();
    }
    
    $stmt->bind_param(
        "ssssisssss",
        $evaluate_date, $company_id, $std_id, $term, $status, $detail, $prog_language, $price, $cost, $transport
    );
    
    if ($stmt->execute()) {
        echo json_encode(array("status" => "success"));
    } else {
        echo json_encode(array("status" => "error", "message" => $stmt->error));
    }
    $stmt->close();
    
} elseif ($xcase == 2) {
    $sql = "UPDATE intern_evaluate SET
    evaluate_date = ?,
    company_id = ?,
    std_id = ?,
    term = ?,
    status = ?,
    detail = ?,
    prog_language = ?,
    price = ?,
    cost = ?,
    transport = ?
    WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(array("status" => "error", "message" => "Prepare failed: " . $conn->error));
        exit();
    }
    
    $stmt->bind_param(
        "ssssisssssi",
        $evaluate_date, $company_id, $std_id, $term, $status, $detail, $prog_language, $price, $cost, $transport, $id
    );
    
    if ($stmt->execute()) {
        echo json_encode(array("status" => "success"));
    } else {
        echo json_encode(array("status" => "error", "message" => $stmt->error));
    }
    $stmt->close();
    
} elseif ($xcase == 3) {
    $sql = "DELETE FROM intern_evaluate WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(array("status" => "error", "message" => "Prepare failed: " . $conn->error));
        exit();
    }
    
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(array("status" => "success"));
    } else {
        echo json_encode(array("status" => "error", "message" => $stmt->error));
    }
    $stmt->close();
    
} else {
    echo json_encode(array("status" => "error", "message" => "xcase not valid"));
    exit();
}

$conn->close();
?>