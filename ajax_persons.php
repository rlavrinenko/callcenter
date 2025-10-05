<?php
require_once __DIR__ . '/db.php';
$conn = getDB();
$term = isset($_GET['term']) ? $conn->real_escape_string($_GET['term']) : '';
$q = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : "";

$sql = "SELECT p.id, p.full_name, p.birth_date, p.created_at,
        GROUP_CONCAT(pp.phone ORDER BY pp.id SEPARATOR ',') AS phones,
        SUM(CASE WHEN n.status='new' OR n.status='queue' THEN 1 ELSE 0 END) AS in_queue
        FROM persons p
        LEFT JOIN person_phones pp ON p.id=pp.person_id
        LEFT JOIN numbers n ON pp.phone=n.phone
        WHERE p.full_name LIKE '%$term%' OR pp.phone LIKE '%$term%'
        GROUP BY p.id
        ORDER BY p.full_name ASC";

$res = $conn->query($sql);
$html = "";
while($row = $res->fetch_assoc()){
    $id = $row['id'];
    $full_name = htmlspecialchars($row['full_name']);
    $birth_date = htmlspecialchars($row['birth_date']);
    $phones = htmlspecialchars($row['phones']);
    $created_at = htmlspecialchars($row['created_at']);
    // перевірка чи є хоч один телефон у черзі
    $in_queue = "Ні";
    $phonesArr = explode(", ", $row['phones']);
    foreach($phonesArr as $ph){
        $ph = trim($ph);
        if(!$ph) continue;
        $check = $conn->query("SELECT id FROM numbers WHERE phone='$ph' LIMIT 1");
        if($check && $check->num_rows>0){
            $in_queue = "Так";
            break;
        }
    }

    $html .= "<tr>
        <td>{$id}</td>
        <td>{$full_name}</td>
        <td>{$birth_date}</td>
	<td>{$created_at}</td>
        <td>{$phones}</td>
        <td>{$in_queue}</td>
        <td>
<<<<<<< Updated upstream
            <button class='btn btn-sm btn-primary edit-person' data-id='{$id}'>Редагувати</button>
            <button class='btn btn-sm btn-danger delete-person' data-id='{$id}'>Видалити</button>
            <button class='btn btn-sm btn-warning queue-person' data-id='{$id}'>В чергу</button>
=======
            <button class='btn btn-sm btn-primary edit-person' data-id='{$row['id']}'>Редагувати</button>
            <button class='btn btn-sm btn-danger delete-person' data-id='{$row['id']}'>Видалити</button>
            <button class='btn btn-sm btn-success add-to-queue' data-id='{$row['id']}' data-phones='{$phones}'>Додати в чергу</button>
            <button class='btn btn-sm btn-warning remind-btn' data-id='{$row['id']}'>Нагадати</button>
>>>>>>> Stashed changes
        </td>
    </tr>";
}
echo $html ?: "<tr><td colspan='6'>Нічого не знайдено</td></tr>";
