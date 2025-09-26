<?php
require_once __DIR__.'/db.php';
$conn = getDB();
header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if(!$id){
    echo json_encode(['success'=>false,'message'=>'Не вказано ID']);
    exit;
}

$res = $conn->query("SELECT id, full_name, birth_date FROM persons WHERE id=$id");
if($row = $res->fetch_assoc()){
    $phones_res = $conn->query("SELECT phone FROM person_phones WHERE person_id=$id");
    $phones = [];
    while($p = $phones_res->fetch_assoc()) $phones[] = $p['phone'];
    $row['phones'] = $phones;
    echo json_encode(['success'=>true,'data'=>$row]);
} else {
    echo json_encode(['success'=>false,'message'=>'Користувача не знайдено']);
}
