<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Transaction.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $transaction = new Transaction($db);

    $method = $_SERVER['REQUEST_METHOD'];
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 1;

    switch($method) {
        case 'GET':
            if(isset($_GET['id'])) {
                $result = $transaction->readOne($_GET['id'], $user_id);
                if($result) {
                    echo json_encode(['success' => true, 'data' => $result]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Transaction not found']);
                }
            } else {
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
                $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
                $result = $transaction->readAll($user_id, $limit, $offset);
                echo json_encode(['success' => true, 'data' => $result]);
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents("php://input"), true);
            
            if(!$input) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
                exit();
            }
            
            $data = [
                'user_id' => $user_id,
                'type' => $input['type'],
                'category_id' => intval($input['category_id']),
                'amount' => floatval($input['amount']),
                'description' => $input['description'],
                'transaction_date' => $input['transaction_date']
            ];
            
            $result = $transaction->create($data);
            if($result['success']) {
                http_response_code(201);
                echo json_encode($result);
            } else {
                http_response_code(500);
                echo json_encode($result);
            }
            break;

        case 'PUT':
            $input = json_decode(file_get_contents("php://input"), true);
            $input['user_id'] = $user_id;
            $result = $transaction->update($input);
            echo json_encode($result);
            break;

        case 'DELETE':
            if(isset($_GET['id'])) {
                $result = $transaction->delete($_GET['id'], $user_id);
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID required']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'line' => $e->getLine()
    ]);
}
?>