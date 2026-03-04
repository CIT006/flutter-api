<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);

if(!$input || !isset($input['text'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Text required']);
    exit();
}

$text = $input['text'];
$lowerText = mb_strtolower($text, 'UTF-8');

// AI Keywords for classification
$incomeKeywords = [
    'เงินเดือน', 'โบนัส', 'รับ', 'โอนเข้า', 'คืน', 'รายได้', 'ขายได้', 
    'ได้มา', 'ได้รับ', 'เงินได้', 'กำไร', 'ผลตอบแทน', 'ดอกเบี้ย',
    'เบิก', 'ถอน', 'เงินสด', 'รับเงิน', 'โอนมา', 'ได้เงิน'
];

$expenseKeywords = [
    'กิน', 'ซื้อ', 'จ่าย', 'ค่า', 'เติม', 'ช้อป', 'ใช้', 'เสีย',
    'ชำระ', 'โอนเงิน', 'ส่ง', 'ให้', 'บริจาค', 'ลงทุน', 'ฝาก',
    'จ่ายบิล', 'ค่าน้ำ', 'ค่าไฟ', 'ค่าเน็ต', 'ค่าโทรศัพท์'
];

$categoryKeywords = [
    'food' => ['กิน', 'อาหาร', 'ข้าว', 'กาแฟ', 'น้ำ', 'ขนม', 'ร้านอาหาร', 'มื้อ', 'หิว'],
    'transport' => ['เดินทาง', 'รถ', 'น้ำมัน', 'แท็กซี่', 'grab', 'bolt', 'ขนส่ง', 'ค่ารถ', 'มอเตอร์ไซค์'],
    'shopping' => ['ซื้อ', 'ช้อป', 'เสื้อผ้า', 'ของใช้', 'ห้าง', 'ออนไลน์', ' Lazada', 'Shopee'],
    'entertainment' => ['หนัง', 'เกม', 'เที่ยว', 'คอนเสิร์ต', 'บันเทิง', 'สนุก', 'เที่ยวเล่น'],
    'bills' => ['บิล', 'ค่าไฟ', 'ค่าน้ำ', 'ค่าเน็ต', 'ค่าโทรศัพท์', 'ประกัน', 'ค่าเช่า'],
    'health' => ['หมอ', 'ยา', 'โรงพยาบาล', 'สุขภาพ', 'คลินิก', 'ป่วย'],
    'education' => ['เรียน', 'หนังสือ', 'คอร์ส', 'การศึกษา', 'โรงเรียน', 'มหาวิทยาลัย'],
    'salary' => ['เงินเดือน', 'เงินเดือนออก', 'รายได้', 'เงินเดือนเข้า'],
    'bonus' => ['โบนัส', 'รางวัล', 'พิเศษ', 'เงินพิเศษ'],
    'investment' => ['ลงทุน', 'หุ้น', 'กองทุน', 'crypto', ' bitcoin']
];

// Extract numbers from text
preg_match_all('/(\d+(?:[.,]\d+)?)/u', $text, $matches);
$numbers = $matches[1] ?? [];

$amount = 0;
if (!empty($numbers)) {
    $amount = max(array_map(function($n) {
        return floatval(str_replace(',', '.', $n));
    }, $numbers));
}

// Determine type
$type = 'expense';
$confidence = 0;

foreach($incomeKeywords as $keyword) {
    if(mb_strpos($lowerText, mb_strtolower($keyword, 'UTF-8'), 0, 'UTF-8') !== false) {
        $type = 'income';
        $confidence += 2;
    }
}

foreach($expenseKeywords as $keyword) {
    if(mb_strpos($lowerText, mb_strtolower($keyword, 'UTF-8'), 0, 'UTF-8') !== false) {
        $type = 'expense';
        $confidence += 2;
    }
}

// Detect category
$category = 'other';
foreach($categoryKeywords as $cat => $keywords) {
    foreach($keywords as $keyword) {
        if(mb_strpos($lowerText, mb_strtolower($keyword, 'UTF-8'), 0, 'UTF-8') !== false) {
            $category = $cat;
            $confidence += 1;
            break 2;
        }
    }
}

echo json_encode([
    'success' => true,
    'data' => [
        'type' => $type,
        'amount' => $amount,
        'description' => $text,
        'category' => $category,
        'confidence' => $confidence
    ]
]);
?>