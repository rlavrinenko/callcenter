<?php
require_once __DIR__ . '/db.php';
$conn = getDB();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if($action == 'get' && isset($_GET['id'])){
    $id = intval($_GET['id']);
    $res = $conn->query("SELECT * FROM persons WHERE id=$id");
    if($res && $res->num_rows){
        $person = $res->fetch_assoc();
        $phones_res = $conn->query("SELECT phone FROM person_phones WHERE person_id=$id");
        $phones = [];
        while($p = $phones_res->fetch_assoc()) $phones[] = $p['phone'];
        echo json_encode(['success'=>true,'data'=>['id'=>$person['id'],'full_name'=>$person['full_name'],'birth_date'=>$person['birth_date'],'phones'=>$phones]]);
    } else echo json_encode(['success'=>false,'message'=>'Користувача не знайдено']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$full_name = trim($_POST['full_name'] ?? '');
$birth_date = $_POST['birth_date'] ?? '';
$phones = $_POST['phone'] ?? [];

if(!$full_name){
    echo json_encode(['success'=>false,'message'=>'Введіть ПІБ']); exit;
}

// Додати або редагувати
if($id){
    $stmt = $conn->prepare("UPDATE persons SET full_name=?, birth_date=? WHERE id=?");
    $stmt->bind_param("ssi",$full_name,$birth_date,$id);
    $stmt->execute();
    $conn->query("DELETE FROM person_phones WHERE person_id=$id");
} else {
    $stmt = $conn->prepare("INSERT INTO persons (full_name,birth_date) VALUES (?,?)");
    $stmt->bind_param("ss",$full_name,$birth_date);
    $stmt->execute();
    $id = $stmt->insert_id;
}

// Додати телефони
foreach($phones as $ph){
    $ph = trim($ph);
    if($ph) $conn->query("INSERT INTO person_phones (person_id,phone) VALUES ($id,'".$conn->real_escape_string($ph)."')");
}

echo json_encode(['success'=>true,'message'=>'Збережено']);
exit;

// Видалення
if($action=='delete' && isset($_POST['id'])){
    $id = intval($_POST['id']);
    $conn->query("DELETE FROM person_phones WHERE person_id=$id");
    $conn->query("DELETE FROM persons WHERE id=$id");
    echo json_encode(['success'=>true,'message'=>'Користувача видалено']); exit;
}
