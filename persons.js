function loadData() {
    $.get('ajax_persons.php', $("#filter-form").serialize(), function(data){
        $("#persons-table tbody").html(data.html);
    }, 'json');
}

function isValidUAphone(phone) {
    return /^(\+380\d{9}|0\d{9})$/.test(phone.trim());
}

$(function(){
    loadData();

    $("#filter-name, #filter-phone").on('input', loadData);

    $("#add-person").click(function(){
        $("#personForm")[0].reset();
        $("#person-id").val('');
        $("#phone-list").html('<div class="input-group mb-2 phone-item"><input type="text" class="form-control" name="phone[]" required><button type="button" class="btn btn-danger remove-phone">Видалити</button></div>');
        $(".modal-title").text("Додати користувача");
    });

    $("#add-phone").click(function(){
        $("#phone-list").append('<div class="input-group mb-2 phone-item"><input type="text" class="form-control" name="phone[]" required><button type="button" class="btn btn-danger remove-phone">Видалити</button></div>');
    });

    $("#phone-list").on('click', '.remove-phone', function(){ $(this).closest('.phone-item').remove(); });

    $("#persons-table").on('click', '.edit-person', function(){
        var id = $(this).data('id');
        var name = $(this).data('name');
        var birth = $(this).data('birth');
        var phones = $(this).data('phones') ? $(this).data('phones').split(',') : [];

        $("#person-id").val(id);
        $("#person-name").val(name);
        $("#person-birth").val(birth);
        $("#phone-list").html('');
        phones.forEach(function(p){
            $("#phone-list").append('<div class="input-group mb-2 phone-item"><input type="text" class="form-control" name="phone[]" value="'+p+'" required><button type="button" class="btn btn-danger remove-phone">Видалити</button></div>');
        });
        $(".modal-title").text("Редагувати користувача");
        $('#personModal').modal('show');
    });

    $("#persons-table").on('click', '.remove-from-queue', function(){
        if(!confirm('Видалити номер з черги?')) return;
        var phones = $(this).data('phones').split(',');
        $.post('remove_from_queue.php', {phones: phones}, function(resp){ alert(resp.message); loadData(); }, 'json');
    });
});
	
