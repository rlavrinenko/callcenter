<?php
require_once __DIR__ . '/db.php';
$conn = getDB();

$id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("SELECT * FROM persons WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$person = $res->fetch_assoc();
if(!$person){
    die("Користувач не знайдений");
}

// Телефони користувача
$phonesRes = $conn->prepare("SELECT phone FROM person_phones WHERE person_id=?");
$phonesRes->bind_param("i", $id);
$phonesRes->execute();
$phones = $phonesRes->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<title>Деталі користувача</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
<h2>Деталі користувача</h2>
<a href="personal.php" class="btn btn-secondary mb-3">Назад</a>

<table class="table table-bordered">
    <tr><th>ID</th><td><?= $person['id'] ?></td></tr>
    <tr><th>ПІБ</th><td><?= htmlspecialchars($person['full_name']) ?></td></tr>
    <tr><th>Дата народження</th><td><?= $person['birth_date'] ?></td></tr>
    <tr><th>Телефони</th>
        <td>
            <?php foreach($phones as $p) echo htmlspecialchars($p['phone'])."<br>"; ?>
        </td>
    </tr>
</table>
</div>
</body>
</html>
