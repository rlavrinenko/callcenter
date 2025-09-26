<?php
require_once __DIR__ . '/db.php';
$conn = getDB();

$data = $_POST;

$response = ['success'=>false,'message'=>''];

if(isset($data['action']) && $data['action'] == 'delete'){
    $id = intval($data['id']);
    // Видаляємо телефони
    $conn->query("DELETE FROM person_phones WHERE person_id = $id");
    // Видаляємо користувача
    $conn->query("DELETE FROM persons WHERE id = $id");
    $response = ['success'=>true,'message'=>'Користувача видалено'];
    echo json_encode($response);
    exit;
}

// Додавання або редагування
$full_name = trim($data['full_name']);
$birth_date = $data['birth_date'];
$phones = $data['phone'] ?? [];

if(!$full_name){
    $response['message'] = 'Не вказано ПІБ';
    echo json_encode($response);
    exit;
}

$id = isset($data['id']) && $data['id'] ? intval($data['id']) : 0;

if($id){
    // Оновлення користувача
    $stmt = $conn->prepare("UPDATE persons SET full_name=?, birth_date=? WHERE id=?");
    $stmt->bind_param("ssi", $full_name, $birth_date, $id);
    $stmt->execute();
    // Видаляємо старі телефони
    $conn->query("DELETE FROM person_phones WHERE person_id=$id");
}else{
    // Додавання нового користувача
    $stmt = $conn->prepare("INSERT INTO persons(full_name,birth_date) VALUES(?,?)");
    $stmt->bind_param("ss", $full_name, $birth_date);
    $stmt->execute();
    $id = $conn->insert_id;
}

// Додаємо телефони
foreach($phones as $phone){
    $phone = trim($phone);
    if($phone){
        $stmt = $conn->prepare("INSERT INTO person_phones(person_id, phone) VALUES(?,?)");
        $stmt->bind_param("is",$id,$phone);
        $stmt->execute();
    }
}

$response = ['success'=>true,'message'=>'Користувача збережено'];
echo json_encode($response);
