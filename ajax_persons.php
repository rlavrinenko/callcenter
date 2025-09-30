<?php
require_once __DIR__.'/db.php';
$conn = getDB();

header('Content-Type: application/json');

$name = $_GET['name'] ?? '';
$phone = $_GET['phone'] ?? '';
$created_from = $_GET['created_from'] ?? '';
$created_to = $_GET['created_to'] ?? '';

$sql = "SELECT p.id, p.full_name, p.birth_date, p.created_at,
        GROUP_CONCAT(pp.phone) AS phones,
        SUM(CASE WHEN n.status IS NOT NULL THEN 1 ELSE 0 END) AS in_queue
        FROM persons p
        LEFT JOIN person_phones pp ON pp.person_id = p.id
        LEFT JOIN numbers n ON n.phone = pp.phone
        WHERE 1=1";

$params = [];
$types = '';

if($name){
    $sql .= " AND p.full_name LIKE ?";
    $params[] = "%$name%";
    $types .= 's';
}

if($phone){
    $sql .= " AND pp.phone LIKE ?";
    $params[] = "%$phone%";
    $types .= 's';
}

if($created_from){
    $sql .= " AND p.created_at >= ?";
    $params[] = $created_from;
    $types .= 's';
}

if($created_to){
    $sql .= " AND p.created_at <= ?";
    $params[] = $created_to;
    $types .= 's';
}

$sql .= " GROUP BY p.id ORDER BY p.full_name ASC";

$stmt = $conn->prepare($sql);
if($params){
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

$html = '';
while($row = $res->fetch_assoc()){
    $phones = $row['phones'] ?? '';
    $in_queue = $row['in_queue']>0 ? 'Так' : 'Ні';
    $html .= "<tr>
        <td>{$row['id']}</td>
        <td>{$row['full_name']}</td>
        <td>{$row['birth_date']}</td>
        <td>{$phones}</td>
        <td>{$in_queue}</td>
        <td>{$row['created_at']}</td>
        <td>
            <button class='btn btn-sm btn-primary edit-person' data-id='{$row['id']}'>Редагувати</button>
            <button class='btn btn-sm btn-danger delete-person' data-id='{$row['id']}'>Видалити</button>
            <button class='btn btn-sm btn-success add-to-queue' data-id='{$row['id']}' data-phones='{$phones}'>Додати в чергу</button>
            <button class='btn btn-sm btn-warning remind-btn' data-person-id='{$row['id']}'>Нагадати</button>
        </td>
    </tr>";
}

echo json_encode(['html'=>$html]);
