<?php
require_once __DIR__ . '/db.php';
$conn = getDB();

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD']==='GET' && isset($_GET['id'])){
    $id = intval($_GET['id']);
    $res = $conn->query("SELECT * FROM persons WHERE id=$id");
    $person = $res->fetch_assoc();

    $phones = [];
    $res2 = $conn->query("SELECT phone FROM person_phones WHERE person_id=$id");
    while($r=$res2->fetch_assoc()) $phones[]=$r['phone'];

    echo json_encode([
        'id'=>$person['id'],
        'full_name'=>$person['full_name'],
        'birth_date'=>$person['birth_date'],
        'phones'=>implode(", ",$phones)
    ]);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$full_name = trim($_POST['full_name'] ?? "");
$birth_date = $_POST['birth_date'] ?? "";
$phones = explode(",", $_POST['phones'] ?? "");

if(!$full_name){
    echo json_encode(['success'=>false,'message'=>'Не вказано ПІБ']);
    exit;
}

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

foreach($phones as $ph){
    $ph = trim($ph);
    if(!$ph) continue;
    $stmt = $conn->prepare("INSERT INTO person_phones (person_id,phone) VALUES (?,?)");
    $stmt->bind_param("is",$id,$ph);
    $stmt->execute();
}

echo json_encode(['success'=>true,'message'=>'Збережено успішно']);
