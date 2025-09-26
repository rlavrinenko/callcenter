<?php
require_once __DIR__ . '/db.php';
$conn = getDB();

$id = intval($_POST['id'] ?? 0);
if($id <= 0){
    echo json_encode(['success'=>false,'message'=>'Невірний ID']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM numbers WHERE id=?");
$stmt->bind_param("i", $id);
$success = $stmt->execute();
$stmt->close();

if($success){
    echo json_encode(['success'=>true,'message'=>'Номер видалено з черги']);
}else{
    echo json_encode(['success'=>false,'message'=>'Помилка при видаленні']);
}
