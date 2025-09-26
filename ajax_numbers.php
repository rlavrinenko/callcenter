<?php
require_once __DIR__ . '/dbn.php';
$conn = getDB();

$phone = $_GET['phone'] ?? '';
$owner = $_GET['owner'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "SELECT n.id, n.phone, n.status, n.pressed_key, n.start_ts, n.end_ts, n.duration,
               COALESCE(p.full_name,'') AS owner
        FROM numbers n
        LEFT JOIN persons p ON n.owner_id = p.id
        WHERE 1=1";

$params = [];
$types = '';

if($phone){
    $sql .= " AND n.phone LIKE ?";
    $params[] = "%$phone%";
    $types .= 's';
}
if($owner){
    $sql .= " AND p.full_name LIKE ?";
    $params[] = "%$owner%";
    $types .= 's';
}
if($status){
    $sql .= " AND n.status = ?";
    $params[] = $status;
    $types .= 's';
}

$sql .= " ORDER BY n.start_ts DESC";

$stmt = $conn->prepare($sql);
if($params){
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while($row = $result->fetch_assoc()){
    $rows[] = $row;
}

echo json_encode($rows);
