<?php
require_once __DIR__ . '/db.php';
$conn = getDB();
header('Content-Type: application/json; charset=utf-8');

$id = $_POST['id'] ?? null;
if(!$id){
    echo json_encode(['success'=>false,'message'=>'ID не вказано']);
    exit;
}

$sql = "UPDATE numbers SET status='removed' WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
if($stmt->execute()){
    echo json_encode(['success'=>true,'message'=>'Статус оновлено на removed']);
}else{
    echo json_encode(['success'=>false,'message'=>'Помилка оновлення']);
}
