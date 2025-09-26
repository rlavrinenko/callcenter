<?php
require_once __DIR__ . '/db.php';
$conn = getDB(); // mysqli підключення
header('Content-Type: application/json; charset=utf-8');

// Фільтри
$phone    = $_GET['phone'] ?? '';
$owner    = $_GET['owner'] ?? '';
$status   = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to'] ?? '';

// SQL умови
$where = [];
$params = [];

if ($phone !== '') { $where[] = "c.phone LIKE '%".$conn->real_escape_string($phone)."%'"; }
if ($status !== '') { $where[] = "c.action='".$conn->real_escape_string($status)."'"; }
if ($dateFrom !== '') { $where[] = "DATE(c.start_ts) >= '".$conn->real_escape_string($dateFrom)."'"; }
if ($dateTo !== '') { $where[] = "DATE(c.start_ts) <= '".$conn->real_escape_string($dateTo)."'"; }
if ($owner !== '') { $where[] = "p.full_name LIKE '%".$conn->real_escape_string($owner)."%'"; }

$where_sql = $where ? "WHERE ".implode(" AND ", $where) : "";

// ---------- Дані таблиці ----------
$sql = "SELECT 
            c.id, c.phone, c.action, c.pressed_key, c.start_ts, c.end_ts, c.duration,
            p.full_name AS owner
        FROM call_log c
        LEFT JOIN person_phones pp ON pp.phone = c.phone
        LEFT JOIN persons p ON p.id = pp.person_id
        $where_sql
        ORDER BY c.start_ts DESC
        LIMIT 500";

$result = $conn->query($sql);
$calls = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $calls[] = $row;
    }
}

// ---------- Формування HTML ----------
$actionColors = ['new'=>'primary','queue'=>'warning','done'=>'success','failed'=>'danger'];
$html = '';
foreach ($calls as $c){
    $badge = "<span class='badge bg-".($actionColors[$c['action']] ?? 'secondary')."'>".htmlspecialchars($c['action'])."</span>";
    $ownerName = $c['owner'] ?: '<span class="text-muted">невідомо</span>';
    $html .= "<tr>
        <td>{$c['id']}</td>
        <td><a href='#' class='history-link' data-phone='{$c['phone']}'>".htmlspecialchars($c['phone'])."</a></td>
        <td>$ownerName</td>
        <td>$badge</td>
        <td>".htmlspecialchars($c['pressed_key'])."</td>
        <td>{$c['start_ts']}</td>
        <td>{$c['end_ts']}</td>
        <td>{$c['duration']}</td>
    </tr>";
}

// ---------- Статистика ----------
$sqlStats = "SELECT 
    COUNT(*) AS total,
    SUM(c.action='done') AS success,
    SUM(c.action='failed') AS failed,
    ROUND(AVG(c.duration)) AS avg_duration
    FROM call_log c
    LEFT JOIN person_phones pp ON pp.phone=c.phone
    LEFT JOIN persons p ON p.id = pp.person_id
    $where_sql";

$statsRes = $conn->query($sqlStats);
$stats = $statsRes ? $statsRes->fetch_assoc() : ['total'=>0,'success'=>0,'failed'=>0,'avg_duration'=>0];

// ---------- Графіки ----------
$sqlStatus = "SELECT c.action, COUNT(*) AS cnt FROM call_log c
    LEFT JOIN person_phones pp ON pp.phone=c.phone
    LEFT JOIN persons p ON p.id=pp.person_id
    $where_sql
    GROUP BY c.action";
$resStatus = $conn->query($sqlStatus);
$statusData = ['labels'=>[], 'data'=>[]];
while($r = $resStatus->fetch_assoc()){
    $statusData['labels'][] = $r['action'];
    $statusData['data'][] = (int)$r['cnt'];
}

$sqlDaily = "SELECT DATE(start_ts) AS d, COUNT(*) AS cnt FROM call_log c
    LEFT JOIN person_phones pp ON pp.phone=c.phone
    LEFT JOIN persons p ON p.id=pp.person_id
    $where_sql
    GROUP BY DATE(start_ts) ORDER BY d ASC";
$resDaily = $conn->query($sqlDaily);
$dailyData = ['labels'=>[], 'data'=>[]];
while($r = $resDaily->fetch_assoc()){
    $dailyData['labels'][] = $r['d'];
    $dailyData['data'][] = (int)$r['cnt'];
}

// ---------- Відповідь ----------
echo json_encode([
    'html' => $html,
    'stats' => [
        'total' => (int)$stats['total'],
        'success' => (int)$stats['success'],
        'failed' => (int)$stats['failed'],
        'avg' => (int)$stats['avg_duration']
    ],
    'charts' => [
        'status' => $statusData,
        'daily'  => $dailyData
    ]
], JSON_UNESCAPED_UNICODE);
