<?php
require_once __DIR__ . '/db.php';
$conn = getDB();
?>
<!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<title>Аналітика дзвінків</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.10.0/dist/css/bootstrap-datepicker.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">Адмінка</a>
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" href="index.php">Головна</a></li>
      <li class="nav-item"><a class="nav-link active" href="analytics.php">Аналітика</a></li>
      <li class="nav-item"><a class="nav-link" href="numbers.php">Номери</a></li>
      <li class="nav-item"><a class="nav-link" href="visitors.php">Персональні дані</a></li>
    </ul>
  </div>
</nav>

<div class="container my-5">

<h1 class="mb-4">Аналітика дзвінків</h1>

<!-- Картки статистики -->
<div class="row text-center mb-4" id="stats-cards">
  <div class="col-md-3 mb-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Всього дзвінків</h5>
        <p class="fs-4 fw-bold" id="total-calls">0</p>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Успішні</h5>
        <p class="fs-4 fw-bold text-success" id="successful-calls">0</p>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Помилки</h5>
        <p class="fs-4 fw-bold text-danger" id="failed-calls">0</p>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Середня тривалість</h5>
        <p class="fs-4 fw-bold text-info" id="avg-duration">0 сек</p>
      </div>
    </div>
  </div>
</div>

<!-- Фільтри -->
<div class="card mb-4">
  <div class="card-body">
    <form id="filter-form" class="row g-3">
      <div class="col-md-2">
        <input type="text" name="phone" id="filter-phone" class="form-control" placeholder="Телефон">
      </div>
      <div class="col-md-3">
        <input type="text" name="owner" id="filter-owner" class="form-control" placeholder="Власник (ПІБ)">
      </div>
      <div class="col-md-2">
        <select name="status" id="filter-status" class="form-select">
          <option value="">Усі статуси</option>
          <option value="new">Нові</option>
          <option value="queue">В черзі</option>
          <option value="done">Виконані</option>
          <option value="failed">Помилки</option>
        </select>
      </div>
      <div class="col-md-2">
        <input type="text" name="date_from" id="filter-date-from" class="form-control datepicker" placeholder="Від дати">
      </div>
      <div class="col-md-2">
        <input type="text" name="date_to" id="filter-date-to" class="form-control datepicker" placeholder="До дати">
      </div>
      <div class="col-md-1 d-grid">
        <button type="button" class="btn btn-secondary" id="reset-filters">Скинути</button>
      </div>
    </form>
  </div>
</div>

<!-- Таблиця -->
<div class="table-responsive mb-5">
  <table class="table table-striped table-bordered" id="analytics-table">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Телефон</th>
        <th>Власник</th>
        <th>Action</th>
        <th>Кнопка</th>
        <th>Початок</th>
        <th>Кінець</th>
        <th>Тривалість</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- Графіки -->
<h3>Графіки дзвінків</h3>
<div class="row">
  <div class="col-md-6 mb-4">
    <canvas id="statusChart"></canvas>
  </div>
  <div class="col-md-6 mb-4">
    <canvas id="dailyChart"></canvas>
  </div>
</div>

</div>

<!-- Modal -->
<div class="modal fade" id="historyModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Історія дзвінків</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="history-content">Завантаження...</div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.10.0/dist/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.10.0/dist/locales/bootstrap-datepicker.uk.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let table;

function loadAnalytics() {
  $.get('ajax_analytics.php', $("#filter-form").serialize(), function(data){
    table.clear().rows.add($(data.html)).draw();

    $("#total-calls").text(data.stats.total);
    $("#successful-calls").text(data.stats.success);
    $("#failed-calls").text(data.stats.failed);
    $("#avg-duration").text(data.stats.avg + ' сек');

    updateCharts(data.charts);
  }, 'json');
}

function updateCharts(chartsData){
  const ctx1 = document.getElementById('statusChart').getContext('2d');
  if(window.statusChartInstance) window.statusChartInstance.destroy();
  window.statusChartInstance = new Chart(ctx1, {
    type: 'pie',
    data: {
      labels: chartsData.status.labels,
      datasets: [{
        label: 'Кількість дзвінків',
        data: chartsData.status.data,
        backgroundColor: ['#007bff','#28a745','#ffc107','#dc3545']
      }]
    },
    options: { responsive: true }
  });

  const ctx2 = document.getElementById('dailyChart').getContext('2d');
  if(window.dailyChartInstance) window.dailyChartInstance.destroy();
  window.dailyChartInstance = new Chart(ctx2, {
    type: 'bar',
    data: {
      labels: chartsData.daily.labels,
      datasets: [{
        label: 'Кількість дзвінків',
        data: chartsData.daily.data,
        backgroundColor: '#17a2b8'
      }]
    },
    options: { responsive: true }
  });
}

$(function(){
  $('.datepicker').datepicker({format: 'yyyy-mm-dd', language: 'uk', autoclose: true});

  table = $('#analytics-table').DataTable({
    dom: 'Bfrtip',
    buttons: ['excelHtml5','csvHtml5'],
    ordering: true,
    searching: false,
    paging: true
  });

  $("#filter-phone, #filter-owner, #filter-status, #filter-date-from, #filter-date-to").on('change input', loadAnalytics);
  $("#reset-filters").click(function(){
    $("#filter-form")[0].reset();
    loadAnalytics();
  });

  $('#analytics-table').on('click', '.history-link', function(e){
    e.preventDefault();
    let phone = $(this).data('phone');
    $("#history-content").html("Завантаження...");
    $("#historyModal").modal('show');
    $.get('ajax_history.php', {phone: phone}, function(html){
      $("#history-content").html(html);
    });
  });

  loadAnalytics();
});
</script>
</body>
</html>
