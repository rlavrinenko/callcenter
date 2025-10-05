<?php
require_once __DIR__.'/db.php';
$conn = getDB();
header('Content-Type: application/json');

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$full_name = trim($_POST['full_name'] ?? '');
$birth_date = $_POST['birth_date'] ?? '';
$phones = $_POST['phone'] ?? [];

if(!$full_name){
    echo json_encode(['success'=>false,'message'=>'ПІБ не може бути порожнім']);
    exit;
}

if($id){ 
    // Оновлення
    $stmt = $conn->prepare("UPDATE persons SET full_name=?, birth_date=? WHERE id=?");
    $stmt->bind_param("ssi",$full_name,$birth_date,$id);
    $stmt->execute();
} else { 
    // Додавання нового
    $stmt = $conn->prepare("INSERT INTO persons (full_name, birth_date, created_at) VALUES (?,?,NOW())");
    $stmt->bind_param("ss",$full_name,$birth_date);
    $stmt->execute();
    $id = $stmt->insert_id;
}

// Оновлення телефонів
$conn->query("DELETE FROM person_phones WHERE person_id=$id");
$stmt = $conn->prepare("INSERT INTO person_phones (person_id, phone) VALUES (?,?)");
foreach($phones as $phone){
    $phone = trim($phone);
    if($phone){
        $stmt->bind_param("is",$id,$phone);
        $stmt->execute();
    }
}

echo json_encode(['success'=>true,'message'=>'Дані збережено']);
