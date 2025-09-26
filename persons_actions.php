<?php
require_once __DIR__ . '/db.php';
$conn = getDB();
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if($action=='delete'){
    $id = intval($_POST['id'] ?? 0);
    if($id){
        $conn->query("DELETE FROM person_phones WHERE person_id=$id");
        $conn->query("DELETE FROM persons WHERE id=$id");
        echo json_encode(['success'=>true,'message'=>'Користувача видалено']);
    } else echo json_encode(['success'=>false,'message'=>'Помилка']);
    exit;
}

if($action=='get'){
    $id=intval($_GET['id'] ?? 0);
    if($id){
        $res = $conn->query("SELECT * FROM persons WHERE id=$id");
        $person = $res->fetch_assoc();
        $phones=[];
        $res2=$conn->query("SELECT phone FROM person_phones WHERE person_id=$id");
        while($p=$res2->fetch_assoc()) $phones[]=$p['phone'];
        $person['phones']=$phones;
        echo json_encode(['success'=>true,'data'=>$person]);
    } else echo json_encode(['success'=>false,'message'=>'Помилка']);
    exit;
}

$full_name = trim($_POST['full_name'] ?? '');
$birth_date = $_POST['birth_date'] ?? '';
$phones = $_POST['phone'] ?? [];
$id = intval($_POST['id'] ?? 0);

if(!$full_name){ echo json_encode(['success'=>false,'message'=>'Введіть ПІБ']); exit; }

if($id){ // редагування
    $conn->query("UPDATE persons SET full_name='".$conn->real_escape_string($full_name)."', birth_date='".$conn->real_escape_string($birth_date)."' WHERE id=$id");
    $conn->query("DELETE FROM person_phones WHERE person_id=$id");
} else { // додавання
    $conn->query("INSERT INTO persons (full_name,birth_date) VALUES ('".$conn->real_escape_string($full_name)."','".$conn->real_escape_string($birth_date)."')");
    $id = $conn->insert_id;
}

foreach($phones as $p){
    $p = trim($p);
    if(!$p) continue;
    $conn->query("INSERT INTO person_phones (person_id, phone) VALUES ($id,'".$conn->real_escape_string($p)."')");
}

echo json_encode(['success'=>true,'message'=>'Зміни збережено']);
