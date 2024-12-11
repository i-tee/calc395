function validateAndCollect() {

    var formData = {}; // Массив для данных
    var allFilled = true; // Флаг, чтобы проверить, все ли поля заполнены

    $('input.-js-mdata').each(function(){

        var inputValue = $(this).val().trim(); // Получаем значение и убираем пробелы
        var inputName = $(this).attr('name'); // Имя инпута

        // Проверка на пустое значение
        if (inputValue === '') {
            allFilled = false;
        } else {
            formData[inputName] = inputValue; // Собираем данные
        }

    });

    $('input.-js-noempty').each(function(){

        var inputValue = $(this).val().trim();

        // Проверка на пустое значение
        if (inputValue === '') {
            $(this).addClass('is-invalid'); // Подсвечиваем поле
            allFilled = false;
        } else {
            $(this).removeClass('is-invalid'); // Убираем подсветку
        }

    });

    var changes = {};

    $('.-js-body').each(function(){

        var type = $(this).attr('data-type');

        var i = 0;

        if (!changes[type]) {
            changes[type] = [];  // Создаем массив для этого типа, если еще не существует
        }

        $(this).find('.row').each(function(){

            // Инициализируем changes[type][i], если оно не существует
            if (!changes[type][i]) {
                changes[type][i] = {};  // Создаем объект для этого индекса, если еще не существует
            }

            const count = $(this).find('input[type="date"]').length;
            if(count == 2){ //Если 2 поля с типом date

                var date1 = $(this).find('input[type="date"]').first().val();
                var date2 = $(this).find('input[type="date"]').last().val();

                changes[type][i]['date1'] = date1;
                changes[type][i]['date2'] = date2;

            }else{ // Иначе ищем date и summ

                var date = $(this).find('input[type="date"]').val();
                var summ = $(this).find('input[type="text"]').val();

                changes[type][i]['date'] = date;
                changes[type][i]['summ'] = summ;

            }

            i++;

        });

    });

    formData['changes'] = changes;  

    // Если все поля заполнены, запускаем startCalc
    if (allFilled) {
        startCalc(formData);
    }

}

$('.-js-body button.btn-close').on('click', function(){

    $(this).parent('.-js-row-remove').remove();

});

$('.-js-block button').on('click', function(){

    // Создание элементов HTML
    const row = document.createElement('div');
    row.classList.add('row', '-js-row-remove', 'add-pay-body');

    const col1 = document.createElement('div');
    col1.classList.add('col');
    const p1 = document.createElement('p');

    if($(this).attr('class').includes('-js-type-ignore')){
        p1.textContent = calcLangData.at;
    }else{
        p1.textContent = calcLangData.date;
    }

    const input1 = document.createElement('input');
    input1.type = 'date';
    input1.classList.add('form-control', '-js-noempty');
    col1.appendChild(p1);
    col1.appendChild(input1);

    const col2 = document.createElement('div');
    col2.classList.add('col');
    const p2 = document.createElement('p');
    const input2 = document.createElement('input');

    if($(this).attr('class').includes('-js-type-ignore')){
        p2.textContent = calcLangData.to;
        input2.type = 'date';
    }else{
        p2.textContent = calcLangData.summa;
        input2.type = 'text';
    }

    input2.classList.add('form-control', '-js-noempty');
    col2.appendChild(p2);
    col2.appendChild(input2);

    const col3 = document.createElement('div');
    col3.classList.add('col', 'text-end');
    const button = document.createElement('button');
    button.type = 'button';
    button.classList.add('btn-close');
    button.setAttribute('aria-label', 'Close');
    button.setAttribute('onclick', 'this.closest(\'.-js-row-remove\').remove()');
    col3.appendChild(button);

    // Добавление колонок в строку
    row.appendChild(col1);
    row.appendChild(col2);
    row.appendChild(col3);

    // Вставка строки в элемент с классом "-js-body"
    var parentBlock = $(this).closest('.-js-block');
    var bodyBlock = parentBlock.find('.-js-body');

    if (bodyBlock) {
        bodyBlock.append(row);
    }

});

function reformatDate(dateStr) {
    // Разделяем дату на части
    const [year, month, day] = dateStr.split('-');
    // Возвращаем в новом формате
    return `${day}.${month}.${year}`;
}

function startCalc(array){

    console.log(array);

    $.ajax({

        url: window.location.href,
        method: 'GET',
        data: {
            ajax: true,
            data: array
        },
        dataType: 'json',
        success: function(response) {

            createResults(response);

            console.log(response);

            createTable('cacl395table');
            
            response.intervals.forEach((item, index) => {
                
                var td = '<tr>';
                td += '<td>' + reformatDate(item['from']) + ' - ' + reformatDate(item['to']) + '</td>';
                td += '<td>' + item['days'] + '</td>';
                td += '<td>' + item['dy'] + '</td>';
                td += '<td>' + item['rate'] + '</td>';
                td += '<td>' + formatCurrency(item['penalty']) + '</td>';
                td += '<td>' + formatCurrency(item['debt']) + '</td>'; 
                td += '</tr>';

                $('#cacl395table').find('tbody').append(td);

            });

        },
        error: function(xhr) {
            console.error(xhr.responseText);
        }

    });

}

function createResults(response){

    $('#calc_penalty').html('');
    $('#calc_debt').html('');
    $('#calc_result').html('');
    $('#chr').remove();

    var p = $('<p></p>');
    p.text(calcLangData.penalty);
    $('#calc_penalty').append(p);

    var h2 = $('<h2></h2>');
    h2.text(formatCurrency(response.penalty_summ));
    $('#calc_penalty').append(h2);

    var p = $('<p></p>');
    p.text(calcLangData.total);
    $('#calc_debt').append(p);

    var h2 = $('<h2></h2>');
    h2.text(formatCurrency(response.debt));
    $('#calc_debt').append(h2);

    var li = $('<li></li>');
    li.text(calcLangData.calc_result_period_date + ' с ' + reformatDate(response.startDate) + ' по ' + reformatDate(response.endtDate));
    $('#calc_result').append(li);

    var li = $('<li></li>');
    li.text(calcLangData.calc_result + ' ' + formatCurrency(response.amount));
    $('#calc_result').append(li);

    var li = $('<li></li>');
    li.text(calcLangData.calc_result_period + ' ' + response.penalty_days);
    $('#calc_result').append(li);

    var hr = $('<hr id="chr">');
    $('#calc_result').before(hr);

    blockPrint('cacl395table');

}

function blockPrint(id){

    $('.-js-container-remove').remove();

    // Создание нового элемента
    const container = document.createElement('div');
    container.className = 'container-print container mt-5 text-end -js-container-remove hide-print';

    const button = document.createElement('button');
    button.className = 'btn btn-primary';
    button.setAttribute('onclick', 'window.print()');

    const icon = document.createElement('i');
    icon.className = 'bi bi-printer';

    // Вложение иконки и текста в кнопку
    button.append(icon);
    button.append(document.createTextNode(' ' + calcLangData.print));

    // Вложение кнопки в контейнер
    container.append(button);

    // Вставка контейнера
    const targetElement = document.getElementById(id);
    if (targetElement) {
        targetElement.after(container);
    }

}

function createTable(containerId) {

    // Проверяем, существует ли контейнер
    const $container = $("#" + containerId);
    if ($container.length === 0) {
      console.error(`container ID "${containerId}" no found.`);
      return;
    }
  
    // Создаём таблицу с jQuery
    const $table = $("<table>").addClass("table");
    const $thead = $("<thead>");
    const $tbody = $("<tbody>");
  
    // Создаём строку заголовка
    const $headerRow = $("<tr>");
    const headers = [
        calcLangData.period,
        calcLangData.days,
        calcLangData.dy,
        calcLangData.rate,
        calcLangData.penalty,
        calcLangData.debt
    ];
  
    headers.forEach((headerText) => {
      $("<td>")
        .attr("scope", "col")
        .text(headerText)
        .appendTo($headerRow);
    });
  
    // Добавляем элементы в таблицу
    $thead.append($headerRow);
    $table.append($thead).append($tbody);
  
    // Добавляем таблицу в указанный контейнер
    $container.html($table);

    return $table;

}

function formatCurrency(number) {

    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB'
    }).format(number);

}