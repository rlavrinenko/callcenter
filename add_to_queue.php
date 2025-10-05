<?php
require_once __DIR__.'/db.php';
$conn = getDB();
header('Content-Type: application/json');

$person_id = isset($_POST['person_id']) ? intval($_POST['person_id']) : 0;
$phones = $_POST['phones'] ?? [];

if(!$person_id || empty($phones)){
    echo json_encode(['success'=>false,'message'=>'Не вказано номер телефону чи власника.']);
    exit;
}

$added = 0;
$stmt_check = $conn->prepare("SELECT id FROM numbers WHERE phone=?");
$stmt_insert = $conn->prepare("INSERT INTO numbers (phone, owner_id, status) VALUES (?,?, 'new')");

foreach($phones as $phone){
    $phone = trim($phone);
    if(!$phone) continue;

    $stmt_check->bind_param("s",$phone);
    $stmt_check->execute();
    $res = $stmt_check->get_result();
    if($res->num_rows>0) continue;

    $stmt_insert->bind_param("si",$phone,$person_id);
    if($stmt_insert->execute()) $added++;
}

echo json_encode(['success'=>true,'message'=>"Додано $added номерів до черги."]);
