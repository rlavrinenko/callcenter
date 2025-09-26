<?php
require_once __DIR__.'/db.php';
$conn = getDB();
header('Content-Type: application/json');

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if(!$id){
    echo json_encode(['success'=>false,'message'=>'Не вказано ID']);
    exit;
}

$conn->query("DELETE FROM persons WHERE id=$id");
$conn->query("DELETE FROM person_phones WHERE person_id=$id");

echo json_encode(['success'=>true,'message'=>'Користувача видалено']);
