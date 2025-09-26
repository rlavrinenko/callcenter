<?php
require_once __DIR__ . '/db.php';
$conn = getDB();

$id = intval($_GET['id'] ?? 0);
if (!$id) die("Користувач не знайдений");

// Отримуємо користувача
$res = $conn->query("SELECT * FROM persons WHERE id=$id");
if (!$res || $res->num_rows == 0) die("Користувач не знайдений");
$person = $res->fetch_assoc();

// Отримуємо телефони користувача
$phones = [];
$res2 = $conn->query("SELECT phone FROM person_phones WHERE person_id=$id");
while ($p = $res2->fetch_assoc()) $phones[] = $p['phone'];

// Отримуємо всі дзвінки для цих телефонів
$call_logs = [];
if (count($phones) > 0) {
    $phone_list = "'" . implode("','", array_map([$conn, 'real_escape_string'], $phones)) . "'";
    $res3 = $conn->query("SELECT * FROM call_logs WHERE phone IN ($phone_list) ORDER BY created_at DESC");
    while ($r = $res3->fetch_assoc()) {
        $call_logs[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<title>Деталі користувача</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<style>
.highlight { background-color: #ffff99; }
</style>
</head>
<body class="bg-light">
<div class="container my-5">
    <h1>Деталі користувача: <?=htmlspecialchars($person['full_name'])?></h1>
    <p><strong>ID:</strong> <?=$person['id']?></p>
    <p><strong>Дата народження:</strong> <?=$person['birth_date']?></p>

    <h3>Телефони користувача</h3>
    <ul>
        <?php foreach ($phones as $phone): ?>
            <li><?=$phone?></li>
        <?php endforeach; ?>
    </ul>

    <h3>Детальні дзвінки</h3>

    <form id="filter-form" class="row g-3 mb-3">
        <div class="col-md-3">
            <input type="text" id="filter-phone" class="form-control" placeholder="Телефон">
        </div>
        <div class="col-md-3">
            <input type="date" id="filter-date" class="form-control">
        </div>
        <div class="col-md-3">
            <select id="filter-status" class="form-select">
                <option value="">Всі статуси</option>
                <option value="new">new</option>
                <option value="called">called</option>
                <option value="removed">removed</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="button" id="clear-filters" class="btn btn-secondary">Скинути фільтри</button>
        </div>
    </form>

    <table class="table table-bordered table-striped" id="calls-table">
        <thead class="table-dark">
            <tr>
                <th>Телефон</th>
                <th>Дата дзвінка</th>
                <th>Статус</th>
                <th>Натиснута кнопка</th>
                <th>Час розмови (сек)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($call_logs as $log): ?>
            <tr>
                <td><?=$log['phone']?></td>
                <td><?=$log['created_at']?></td>
                <td><?=$log['status'] ?? ''?></td>
                <td><?=$log['pressed'] ?? ''?></td>
                <td><?=$log['duration'] ?? 0?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <a href="persons.php" class="btn btn-primary">Назад</a>
</div>

<script>
$(function(){
    function filterTable(){
        var phone = $('#filter-phone').val().trim().toLowerCase();
        var date = $('#filter-date').val();
        var status = $('#filter-status').val();

        $('#calls-table tbody tr').each(function(){
            var $tr = $(this);
            var trPhone = $tr.find('td:eq(0)').text().toLowerCase();
            var trDate = $tr.find('td:eq(1)').text().substr(0,10);
            var trStatus = $tr.find('td:eq(2)').text();

            var show = true;
            if(phone && !trPhone.includes(phone)) show = false;
            if(date && trDate !== date) show = false;
            if(status && trStatus !== status) show = false;

            $tr.toggle(show);
            if(show && phone && trPhone.includes(phone)){
                var regex = new RegExp('('+phone+')', 'ig');
                $tr.find('td:eq(0)').html($tr.find('td:eq(0)').text().replace(regex,'<span class="highlight">$1</span>'));
            } else {
                $tr.find('td:eq(0)').text(trPhone);
            }
        });
    }

    $('#filter-phone, #filter-date, #filter-status').on('input change', filterTable);
    $('#clear-filters').click(function(){
        $('#filter-phone').val('');
        $('#filter-date').val('');
        $('#filter-status').val('');
        filterTable();
    });
});
</script>
</body>
</html>
