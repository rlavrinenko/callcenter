<?php
require_once __DIR__.'/db.php';
$conn = getDB();

$person_id = $_POST['person_id'] ?? 0;
$phones = $_POST['phones'] ?? [];

header('Content-Type: application/json');

if($person_id && !empty($phones)){
    $stmt = $conn->prepare("INSERT INTO call_remind_1 
        (phone, owner_id, status, pressed_key, start_ts, end_ts, duration) 
        VALUES (?, ?, 'new', '', NOW(), NULL, 0)");
    $stmt->bind_param("si", $phone, $person_id);

    $count = 0;
    foreach($phones as $phone){
        $phone = trim($phone);
        if($phone && $stmt->execute()) $count++;
    }
    $stmt->close();

    echo json_encode(['status'=>'success','message'=>"Користувач доданий у call_remind_1 для {$count} номерів"]);
} else {
    echo json_encode(['status'=>'error','message'=>'Некоректні дані']);
}
