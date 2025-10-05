<?php
require_once __DIR__ . '/db.php';
$conn = getDB();

// --- Масові дії ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['action']) && !empty($_POST['selected'])) {
        $selected = $_POST['selected'];
        foreach($selected as $person_id) {
            $person_id = intval($person_id);
            $res = $conn->query("SELECT phone FROM person_phones WHERE person_id=$person_id");
            while($ph = $res->fetch_assoc()){
                $phone = $conn->real_escape_string($ph['phone']);

                // --- Додати / повторно додати в чергу ---
                if($_POST['action'] === 'add_queue' || $_POST['action'] === 'retry_queue'){
                    $check = $conn->query("SELECT id FROM numbers WHERE phone='$phone' LIMIT 1");
                    if($check->num_rows==0){
                        $conn->query("INSERT INTO numbers (phone, owner_id, status, pressed_key, start_ts, end_ts)
                                      VALUES ('$phone',$person_id,'new','0',0,0)");
                    } elseif($_POST['action'] === 'retry_queue') {
                        $conn->query("UPDATE numbers SET status='retry' WHERE phone='$phone'");
                    }
                }

                // --- Додати / повторно додати в нагадування ---
                if($_POST['action'] === 'add_remind' || $_POST['action'] === 'retry_remind'){
                    $check = $conn->query("SELECT id FROM call_remind_1 WHERE phone='$phone' LIMIT 1");
                    if($check->num_rows==0){
                        $conn->query("INSERT INTO call_remind_1 (phone, owner_id, status, pressed_key, start_ts, end_ts)
                                      VALUES ('$phone',$person_id,'new','0',0,0)");
                    } elseif($_POST['action'] === 'retry_remind') {
                        $conn->query("UPDATE call_remind_1 SET status='retry' WHERE phone='$phone'");
                    }
                }
            }
        }
        header("Location: visitors.php");
        exit;
    }
}

// --- Додавання нового користувача з перевіркою ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_person'])) {
    $full_name = trim($_POST['full_name']);
    $birth_date = trim($_POST['birth_date']);
    $phone = trim($_POST['phone']);

    if ($full_name !== '') {
        // Перевірка на існування ПІБ
        $checkName = $conn->prepare("SELECT id FROM persons WHERE full_name=? LIMIT 1");
        $checkName->bind_param("s", $full_name);
        $checkName->execute();
        $checkName->store_result();
        if($checkName->num_rows > 0){
            $checkName->close();
            $error_message = "Користувач з таким ПІБ вже існує!";
        } else {
            $checkName->close();
            $birth_date = $birth_date === '' ? null : $birth_date;

            $stmt = $conn->prepare("INSERT INTO persons (full_name, birth_date, created_at) VALUES (?, ?, NOW())");
            $stmt->bind_param("ss", $full_name, $birth_date);
            $stmt->execute();
            $person_id = $stmt->insert_id;
            $stmt->close();

            // Додаємо телефон якщо він не пустий і ще не існує
            if ($phone !== '') {
                $checkPhone = $conn->prepare("SELECT id FROM person_phones WHERE phone=? LIMIT 1");
                $checkPhone->bind_param("s", $phone);
                $checkPhone->execute();
                $checkPhone->store_result();
                if($checkPhone->num_rows == 0){
                    $checkPhone->close();
                    $stmt2 = $conn->prepare("INSERT INTO person_phones (person_id, phone, status, pressed) VALUES (?, ?, 'new', '0')");
                    $stmt2->bind_param("is", $person_id, $phone);
                    $stmt2->execute();
                    $stmt2->close();
                } else {
                    $checkPhone->close();
                    $error_message = "Телефон вже існує!";
                }
            }
        }
    }
}

// --- Видалення користувача ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM persons WHERE id=$id");
    header("Location: visitors.php");
    exit;
}
// ---- Вказати статус отримання ---
if (isset($_GET['received_id'])) {
    $id = intval($_GET['received_id']);
    $conn->query("UPDATE persons SET received = 'так' WHERE id = $id");
    header("Location: visitors.php");
    exit;
}


// --- Пошук / фільтр ---
$search = trim($_GET['search'] ?? '');
////сортування



// --- Отримання телефонів для підсвітки ---
$numbersPhones = [];
$res = $conn->query("SELECT phone FROM numbers");
while($r = $res->fetch_assoc()) $numbersPhones[trim($r['phone'])] = true;

$remindPhones = [];
$res = $conn->query("SELECT phone FROM call_remind_1");
while($r = $res->fetch_assoc()) $remindPhones[trim($r['phone'])] = true;

// --- Отримання успішних дзвінків ---
$completedPhones = [];
$res = $conn->query("SELECT phone FROM completed_calls");
while($r = $res->fetch_assoc()) $completedPhones[trim($r['phone'])] = true;

// --- Отримання користувачів ---
$sql = "SELECT p.*, GROUP_CONCAT(ph.phone SEPARATOR ',') as phones
        FROM persons p
        LEFT JOIN person_phones ph ON ph.person_id = p.id ";

if($search !== '') {
    $s = $conn->real_escape_string($search);
    $sql .= "WHERE p.full_name LIKE '%$s%' OR ph.phone LIKE '%$s%' ";
}

$sql .= "GROUP BY p.id
         ORDER BY created_at DESC";
$result = $conn->query($sql);

// --- Масив з користувачами + визначення статусу ---
$rows = [];
while($row = $result->fetch_assoc()) {
    $phones = $row['phones'] ? explode(',', $row['phones']) : [];
    $row['call_status'] = 0;
    foreach($phones as $ph) {
        if(isset($completedPhones[trim($ph)])) {
            $row['call_status'] = 1;
            break;
        }
    }
    $rows[] = $row;
}


?>
<!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<title>Відвідувачі</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    .phone-queue { background-color: #fff3cd; }    /* жовтий */
    .phone-remind { background-color: #d4edda; }   /* зелений */
    .phone-both  { background-color: #f8d7da; }    /* червоний */
    .phone-span { display:inline-block; padding:2px 4px; margin:1px; border-radius:4px; }
</style>
</head>
<body>
<!-- Меню -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-3">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">Головна</a>
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" href="analytics.php">Аналітика</a></li>
      <li class="nav-item"><a class="nav-link" href="numbers.php">Черга</a></li>
      <li class="nav-item"><a class="nav-link" href="reminder.php">Черга Нагадування</a></li>
      <li class="nav-item"><a class="nav-link active" href="visitors.php">Персональні дані</a></li>
    </ul>
  </div>
</nav>

<div class="container py-4">
<h2 class="mb-4">Відвідувачі</h2>

<?php if(!empty($error_message)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
<?php endif; ?>

<!-- Форма додавання -->
<form method="POST" class="row g-3 mb-4">
    <div class="col-md-3">
        <input type="text" name="full_name" class="form-control" placeholder="ПІБ" required>
    </div>
    <div class="col-md-3">
        <input type="date" name="birth_date" class="form-control" placeholder="Дата народження">
    </div>
    <div class="col-md-3">
        <input type="text" name="phone" class="form-control" placeholder="Телефон">
    </div>
    <div class="col-md-3">
        <button type="submit" name="add_person" class="btn btn-success w-100">➕ Додати</button>
    </div>
</form>

<!-- Пошук -->
<form method="GET" class="mb-2">
    <div class="input-group">
        <input type="text" name="search" class="form-control" placeholder="Пошук по ПІБ або телефону" value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-primary" type="submit">Пошук</button>
    </div>
</form>

<!-- Масові дії -->
<form method="POST">
<div class="mb-2">
    <button type="submit" name="action" value="add_queue" class="btn btn-warning btn-sm me-2">Додати в чергу</button>
    <button type="submit" name="action" value="retry_queue" class="btn btn-warning btn-sm me-2">Повторно в чергу</button>
    <button type="submit" name="action" value="add_remind" class="btn btn-success btn-sm me-2">Додати в нагадування</button>
    <button type="submit" name="action" value="retry_remind" class="btn btn-success btn-sm">Повторно в нагадування</button>
</div>

<!-- Таблиця -->
<table class="table table-bordered table-hover align-middle">
    <thead class="table-light">
        <tr>
            <th><input type="checkbox" id="selectAll"></th>
            <th>ID</th>
            <th>ПІБ</th>
            <th>Дата народження</th>
            <th>Телефони</th>
            <th>Статус дзвінка</th>
            <th>Отримано</th>
            <th>Дія</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($rows as $row): ?>
        <?php $phones = $row['phones'] ? explode(',', $row['phones']) : []; ?>
        <tr>
            <td><input type="checkbox" name="selected[]" value="<?= $row['id'] ?>"></td>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= $row['birth_date'] ?></td>
            <td>
                <?php foreach($phones as $ph):
                    $ph = trim($ph);
                    $color_class = '';
                    if(isset($numbersPhones[$ph]) && isset($remindPhones[$ph])) $color_class = 'phone-both';
                    elseif(isset($numbersPhones[$ph])) $color_class = 'phone-queue';
                    elseif(isset($remindPhones[$ph])) $color_class = 'phone-remind';
                ?>
                <span class="phone-span <?= $color_class ?>"><?= htmlspecialchars($ph) ?></span><br>
                <?php endforeach; ?>
            </td>
            <td>
                <?php if($row['call_status'] == 1): ?>
                    <span style="color:green;">&#10004;</span>
                <?php else: ?>
                    <span style="color:red;">&#10008;</span>
                <?php endif; ?>
            </td>
	    <td>
    		<?php if (trim($row['received']) === 'так'): ?>
        	<span class="text-success fw-bold">✅ Так</span>
    		<?php else: ?>
        	<span class="text-danger fw-bold">❌ Ні</span>
    		<?php endif; ?>
	    </td>
            <td>
                <a href="?received_id=<?= $row['id'] ?>" class="btn btn-sm btn-success">📥 Отримано</a>
                <a href="profile.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">👤 Профіль</a>
                <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Видалити відвідувача?');">🗑️ Видалити</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Виділення всіх чекбоксів
document.getElementById('selectAll').addEventListener('change', function(){
    let checked = this.checked;
    document.querySelectorAll('input[name="selected[]"]').forEach(cb => cb.checked = checked);
});
</script>
</body>
</html>

