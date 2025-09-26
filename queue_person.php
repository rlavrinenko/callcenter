<?php
require_once __DIR__ . '/db.php';
$conn = getDB();
header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);
if(!$id){
    echo json_encode(['success'=>false,'message'=>'ID не вказано']);
    exit;
}

$res = $conn->query("SELECT phone FROM person_phones WHERE person_id=$id");
if(!$res || $res->num_rows==0){
    echo json_encode(['success'=>false,'message'=>'Телефонів не знайдено']);
    exit;
}

$added = 0;
while($row=$res->fetch_assoc()){
    $phone = $conn->real_escape_string($row['phone']);
    $check = $conn->query("SELECT id FROM numbers WHERE phone='$phone' LIMIT 1");
    if($check && $check->num_rows>0) continue;

    $stmt = $conn->prepare("INSERT INTO numbers (phone, owner_id, status) VALUES (?, ?, 'new')");
    $stmt->bind_param("si",$phone,$id);
    if($stmt->execute()) $added++;
}

if($added>0){
    echo json_encode(['success'=>true,'message'=>"Додано $added номер(ів)"]);
} else {
    echo json_encode(['success'=>false,'message'=>"Усі номери вже у черзі"]);
}
