<?php
require_once __DIR__ . '/db.php';
$conn = getDB();
?>
<!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<title>Персональні дані</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<style>
.ui-autocomplete { z-index:1050; }
.highlight { background-color: yellow; }
.is-invalid { border-color: red; }
</style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">Головна</a>
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" href="analytics.php">Аналітика</a></li>
      <li class="nav-item"><a class="nav-link" href="numbers.php">Номери</a></li>
      <li class="nav-item"><a class="nav-link active" href="persons.php">Персональні дані</a></li>
    </ul>
  </div>
</nav>

<div class="container my-5">
<h1>Персональні дані</h1>

<form id="filter-form" class="row g-3 mb-4">
    <div class="col-md-3">
        <input type="text" id="filter-name" name="name" class="form-control" placeholder="ПІБ">
    </div>
    <div class="col-md-3">
        <input type="text" id="filter-phone" name="phone" class="form-control" placeholder="Телефон">
    </div>
    <div class="col-md-3">
        <input type="date" id="filter-created-from" name="created_from" class="form-control" placeholder="Створено від">
    </div>
    <div class="col-md-3">
        <input type="date" id="filter-created-to" name="created_to" class="form-control" placeholder="Створено до">
    </div>
</form>

<div class="mb-3">
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#personModal">Додати користувача</button>
</div>

<div class="table-responsive">
    <table class="table table-striped table-bordered" id="persons-table">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>ПІБ</th>
                <th>Дата народження</th>
                <th>Телефони</th>
                <th>В черзі</th>
                <th>Дата створення</th>
                <th>Дії</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<!-- Модальне вікно -->
<div class="modal fade" id="personModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="personForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Додати користувача</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="person-id">
        <div class="mb-3">
          <label class="form-label">ПІБ</label>
          <input type="text" class="form-control" name="full_name" id="person-name" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Дата народження</label>
          <input type="date" class="form-control" name="birth_date" id="person-birth">
        </div>
        <div class="mb-3">
          <label class="form-label">Телефони</label>
          <div id="phone-list">
            <div class="input-group mb-2 phone-item">
              <input type="text" class="form-control" name="phone[]" required>
              <button type="button" class="btn btn-danger remove-phone">Видалити</button>
            </div>
          </div>
          <button type="button" class="btn btn-sm btn-secondary" id="add-phone">Додати телефон</button>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Зберегти</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрити</button>
      </div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function loadData() {
    $.get('ajax_persons.php', $("#filter-form").serialize(), function(data){
        $("#persons-table tbody").html(data.html);
    }, 'json');
}

function attachPhoneAutocomplete() {
    $(".phone-item input").autocomplete({
        source: function(request, response){
            $.getJSON('autocomplete.php', {field:'phone', term:request.term}, response);
        },
        minLength: 1
    });
}

$(function(){
    loadData();
    $("#filter-name, #filter-phone, #filter-created-from, #filter-created-to").on('input change', loadData);

    $("#add-phone").click(function(){
        $("#phone-list").append(`<div class="input-group mb-2 phone-item">
            <input type="text" class="form-control" name="phone[]" required>
            <button type="button" class="btn btn-danger remove-phone">Видалити</button>
        </div>`);
        attachPhoneAutocomplete();
    });

    $("#phone-list").on('click','.remove-phone', function(){ $(this).closest('.phone-item').remove(); });

    $("#personForm").submit(function(e){
        e.preventDefault();
        $.post('save_person.php', $(this).serialize(), function(resp){
            alert(resp.message);
            if(resp.success){
                $('#personModal').modal('hide');
                loadData();
            }
        }, 'json');
    });

    $("#persons-table").on('click','.edit-person', function(){
        let id = $(this).data('id');
        $.getJSON('get_person.php', {id:id}, function(resp){
            if(resp.success){
                $("#person-id").val(resp.data.id);
                $("#person-name").val(resp.data.full_name);
                $("#person-birth").val(resp.data.birth_date);
                $("#phone-list").html('');
                resp.data.phones.forEach(function(p){
                    $("#phone-list").append(`<div class="input-group mb-2 phone-item">
                        <input type="text" class="form-control" name="phone[]" value="${p}" required>
                        <button type="button" class="btn btn-danger remove-phone">Видалити</button>
                    </div>`);
                });
                attachPhoneAutocomplete();
                $(".modal-title").text("Редагувати користувача");
                $('#personModal').modal('show');
            } else alert(resp.message);
        });
    });

    $("#persons-table").on('click','.delete-person', function(){
        if(!confirm('Видалити користувача?')) return;
        $.post('delete_person.php',{id:$(this).data('id')}, function(resp){
            alert(resp.message);
            if(resp.success) loadData();
        },'json');
    });

    $("#persons-table").on('click','.add-to-queue', function(){
        let id = $(this).data('id');
        let phones = $(this).data('phones').split(',');
        $.post('add_to_queue.php',{person_id:id, phones:phones}, function(resp){
            alert(resp.message);
            if(resp.success) loadData();
        },'json');
    });

   

});
</script>
</body>
</html>
