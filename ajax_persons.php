<?php
require_once __DIR__.'/db.php';
$conn = getDB();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$conditions = [];
$params = [];
$types = '';

if($search !== ''){
    $conditions[] = "(p.full_name LIKE ? OR pp.phone LIKE ? OR p.created_at LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}

$where = count($conditions) ? 'WHERE '.implode(' AND ', $conditions) : '';

$sql = "SELECT p.id, p.full_name, p.birth_date, p.created_at,
        GROUP_CONCAT(pp.phone SEPARATOR ', ') as phones,
        SUM(CASE WHEN n.status IN ('new','queue') THEN 1 ELSE 0 END) as in_queue
        FROM persons p
        LEFT JOIN person_phones pp ON pp.person_id=p.id
        LEFT JOIN numbers 
		LEFT JOIN numbers n ON n.phone = pp.phone 
		$where
		GROUP BY p.id
		ORDER BY p.full_name ASC";

$stmt = $conn->prepare($sql);

if($types){
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$html = '';
while($row = $result->fetch_assoc()){
    $phones = $row['phones'] ?? '';
    $inQueue = $row['in_queue'] > 0 ? 'Так' : 'Ні';

    $html .= '<tr>';
    $html .= '<td>'.$row['id'].'</td>';
    $html .= '<td>'.$row['full_name'].'</td>';
    $html .= '<td>'.$row['birth_date'].'</td>';
    $html .= '<td>'.$row['created_at'].'</td>';
    $html .= '<td>'.$phones.'</td>';
    $html .= '<td>'.$inQueue.'</td>';
    $html .= '<td>
        <button class="btn btn-sm btn-primary edit-person" data-id="'.$row['id'].'" data-name="'.htmlspecialchars($row['full_name']).'" data-birth="'.$row['birth_date'].'" data-phones="'.htmlspecialchars($phones).'">Редагувати</button>
        <button class="btn btn-sm btn-danger delete-person" data-id="'.$row['id'].'">Видалити</button>
        <button class="btn btn-sm btn-success add-to-queue" data-id="'.$row['id'].'" data-phones="'.htmlspecialchars($phones).'">Додати в чергу</button>
    </td>';
    $html .= '</tr>';
}

echo json_encode(['html'=>$html]);
