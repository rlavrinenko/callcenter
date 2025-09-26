<?php
require_once __DIR__ . '/db.php';
$conn = getDB(); // mysqli підключення
header('Content-Type: text/html; charset=utf-8');

$phone = $_GET['phone'] ?? '';
if (!$phone) { 
    echo "Номер не вказано"; 
    exit; 
}

// Запит на історію дзвінків
$sql = "SELECT c.id, c.phone, c.action, c.pressed_key, c.start_ts, c.end_ts, c.duration,
               p.full_name AS owner
        FROM call_log c
        LEFT JOIN person_phones pp ON pp.phone = c.phone
        LEFT JOIN persons p ON p.id = pp.person_id
        WHERE c.phone = '".$conn->real_escape_string($phone)."'
        ORDER BY c.start_ts DESC
        LIMIT 100";

$result = $conn->query($sql);

echo "<table class='table table-sm table-striped'>
<thead><tr>
<th>ID</th><th>Телефон</th><th>Власник</th><th>Action</th><th>Кнопка</th><th>Початок</th><th>Кінець</th><th>Тривалість</th>
</tr></thead><tbody>";

$actionColors = ['new'=>'primary','queue'=>'warning','done'=>'success','failed'=>'danger'];

if ($result) {
    while ($c = $result->fetch_assoc()) {
        $badge = "<span class='badge bg-".($actionColors[$c['action']] ?? 'secondary')."'>".htmlspecialchars($c['action'])."</span>";
        $owner = $c['owner'] ?: '<span class="text-muted">невідомо</span>';
        echo "<tr>
            <td>{$c['id']}</td>
            <td>".htmlspecialchars($c['phone'])."</td>
            <td>$owner</td>
            <td>$badge</td>
            <td>".htmlspecialchars($c['pressed_key'])."</td>
            <td>{$c['start_ts']}</td>
            <td>{$c['end_ts']}</td>
            <td>{$c['duration']}</td>
        </tr>";
    }
}

echo "</tbody></table>";
