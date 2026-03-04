<?php
include_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit();
}

$type = isset($_GET['type']) ? $_GET['type'] : null;

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sql = "SELECT id, name, name_th, icon, type, color FROM categories";
    
    if ($type !== null && in_array($type, ['income', 'expense'])) {
        $sql .= " WHERE type = :type";
    }

    $sql .= " ORDER BY type, name_th";

    $stmt = $conn->prepare($sql);
    
    if ($type !== null) {
        $stmt->bindParam(":type", $type);
    }
    
    $stmt->execute();

    $categories = $stmt->fetchAll();

    echo json_encode([
        "success" => true,
        "count" => count($categories),
        "data" => $categories
    ]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server Error: " . $e->getMessage()]);
}
?>