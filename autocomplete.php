<?php
require_once __DIR__ . '/db.php';
$conn = getDB();
$field = $_GET['field'] ?? '';
$term  = $conn->real_escape_string($_GET['term'] ?? '');
$data = [];

if ($field === 'phone' && $term) {
    $res = $conn->query("SELECT DISTINCT phone FROM person_phones WHERE phone LIKE '%$term%' LIMIT 10");
    while($r=$res->fetch_assoc()) $data[]=$r['phone'];
}
echo json_encode($data);
