<?php
require_once __DIR__ . '/db.php';
$conn = getDB();

header('Content-Type: application/json');

// Отримуємо параметри фільтра
$name = isset($_GET['name']) ? trim($_GET['name']) : '';
$phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';
$created_from = isset($_GET['created_from']) ? trim($_GET['created_from']) : '';
$created_to = isset($_GET['created_to']) ? trim($_GET['created_to']) : '';

// Формуємо WHERE умови
$where = [];
$params = [];
$types = '';

if($name){
    $where[] = 'p.full_name LIKE ?';
    $params[] = "%$name%";
    $types .= 's';
}
if($phone){
    $where[] = 'pp.phone LIKE ?';
    $params[] = "%$phone%";
    $types .= 's';
}
if($created_from){
    $where[] = 'p.created_at >= ?';
    $params[] = $created_from;
    $types .= 's';
}
if($created_to){
    $where[] = 'p.created_at <= ?';
    $params[] = $created_to;
    $types .= 's';
}

$whereSQL = $where ? 'WHERE '.implode(' AND ', $where) : '';

// Основний SQL
$sql = "SELECT p.id, p.full_name, p.birth_date, p.created_at,
               GROUP_CONCAT(pp.phone SEPARATOR ', ') AS phones,
               SUM(CASE WHEN n.status IN ('new','queue') THEN 1 ELSE 0 END) AS in_queue
        FROM persons p
        LEFT JOIN person_phones pp ON pp.person_id = p.id
        LEFT JOIN numbers n ON n.phone = pp.phone
        $whereSQL
        GROUP BY p.id
        ORDER BY p.full_name ASC";

$stmt = $conn->prepare($sql);
if($params){
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$html = '';
while($row = $result->fetch_assoc()){
    $inQueue = $row['in_queue'] > 0 ? 'Так' : 'Ні';
    $phones_display = htmlspecialchars($row['phones']);
    $full_name = htmlspecialchars($row['full_name']);
    // Підсвічування пошуку
    if($name) $full_name = str_ireplace($name, "<span class='highlight'>$name</span>", $full_name);
    if($phone && $phones_display) $phones_display = str_ireplace($phone, "<span class='highlight'>$phone</span>", $phones_display);

    $html .= "<tr>
        <td>{$row['id']}</td>
        <td>{$full_name}</td>
        <td>{$row['birth_date']}</td>
        <td>{$phones_display}</td>
        <td>{$inQueue}</td>
        <td>{$row['created_at']}</td>
        <td>
            <button class='btn btn-sm btn-primary edit-person' data-id='{$row['id']}'>Редагувати</button>
            <button class='btn btn-sm btn-danger delete-person' data-id='{$row['id']}'>Видалити</button>
            <button class='btn btn-sm btn-success add-to-queue' data-id='{$row['id']}' data-phones='{$row['phones']}'>Додати в чергу</button>
        </td>
    </tr>";
}

echo json_encode(['html'=>$html]);
