<?php
require_once __DIR__ . '/db.php';
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Персони</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
</nav>
<body >
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">Адмінка</a>
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" href="index.php">Головна</a></li>
      <li class="nav-item"><a class="nav-link" href="analytics.php">Аналітика</a></li>
      <li class="nav-item"><a class="nav-link" href="numbers.php">Номери</a></li>
      <li class="nav-item"><a class="nav-link active" href="persons.php">Персональні дані</a></li>
    </ul>
  </div>
</nav>
<div class="container">


    <h1 class="mb-4">Персони</h1>
    <div class="mb-3 d-flex">
        <input type="text" id="search" class="form-control me-2" placeholder="Пошук по ПІБ або телефону">
        <button id="add-person" class="btn btn-success">Додати</button>
    </div>
    <table class="table table-bordered" id="persons-table">
        <thead>
        <tr>
            <th>ID</th>
            <th>ПІБ</th>
            <th>Дата народження</th>
		<th>Дата створення</th>
            <th>Телефони</th>
            <th>В черзі</th>
            <th>Дії</th>
        </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<!-- Модальне вікно -->
<div class="modal fade" id="personModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="person-form" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Персона</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="person-id">
                <div class="mb-3">
                    <label class="form-label">ПІБ</label>
                    <input type="text" name="full_name" id="person-full_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Дата народження</label>
                    <input type="date" name="birth_date" id="person-birth_date" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Телефони (через кому)</label>
                    <input type="text" name="phones" id="person-phones" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Зберегти</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрити</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function loadPersons(query="") {
    $.get("ajax_persons.php", {q: query}, function (html) {
        $("#persons-table tbody").html(html);
    });
}

$(function(){
    loadPersons();

    $("#search").on("keyup", function(){
        loadPersons($(this).val());
    });

    $("#add-person").click(function(){
        $("#person-id").val("");
        $("#person-full_name").val("");
        $("#person-birth_date").val("");
        $("#person-phones").val("");
        new bootstrap.Modal(document.getElementById('personModal')).show();
    });

    $("#person-form").submit(function(e){
        e.preventDefault();
        $.post("save_person.php", $(this).serialize(), function(resp){
            alert(resp.message);
            if(resp.success){
                loadPersons();
                bootstrap.Modal.getInstance(document.getElementById('personModal')).hide();
            }
        }, "json");
    });

    $("#persons-table").on("click", ".edit-person", function(){
        var id = $(this).data("id");
        $.getJSON("save_person.php", {id:id}, function(data){
            $("#person-id").val(data.id);
            $("#person-full_name").val(data.full_name);
            $("#person-birth_date").val(data.birth_date);
            $("#person-phones").val(data.phones);
            new bootstrap.Modal(document.getElementById('personModal')).show();
        });
    });

    $("#persons-table").on("click", ".delete-person", function(){
        if(!confirm("Видалити цю персону?")) return;
        var id = $(this).data("id");
        $.post("delete_person.php",{id:id}, function(resp){
            alert(resp.message);
            loadPersons();
        }, "json");
    });

    $("#persons-table").on("click", ".queue-person", function(){
        var id = $(this).data("id");
        $.post("queue_person.php",{id:id}, function(resp){
            alert(resp.message);
            loadPersons();
        }, "json");
    });
});
</script>
</body>
</html>
