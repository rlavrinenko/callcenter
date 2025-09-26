<?php
require_once __DIR__ . '/db.php';
$conn = getDB();

header('Content-Type: application/json');

$person_id = isset($_POST['person_id']) ? intval($_POST['person_id']) : 0;
$phones = isset($_POST['phones']) ? $_POST['phones'] : [];

if(!$person_id || empty($phones)){
    echo json_encode(['success'=>false,'message'=>'Не вказано номер телефону чи власника.']);
    exit;
}

$added = 0;

foreach($phones as $phone){
    $phone = trim($phone);
    if(!$phone) continue;

    // Додаємо тільки унікальні телефони
    $phone_esc = $conn->real_escape_string($phone);
    $conn->query("INSERT IGNORE INTO numbers (phone, owner_id, status) VALUES ('$phone_esc', $person_id, 'new')");
    if($conn->affected_rows > 0) $added++;
}

echo json_encode(['success'=>true,'message'=>"Додано $added номерів до черги."]);
