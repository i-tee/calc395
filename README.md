# Калькулятор процентов по 395 ГК РФ
## PHP-Библиотека для расчета процентов по правилам ст. 395 ГК РФ
1. Есть демо -> [Калькулятор процентов по 395 ГК РФ](https://tee.su/calc395)
2. Есть готовый HTML-шаблон и JS-скрипт для моментального запуска

### Установка:
`composer require i-tee/calc395`

### Состав пакета:
- `Calc395.php` - Библиотека. Одной её достаточно для полноценной работы. Остальные файлы для быстрого запуска
- `calccontainer.html` - html шаблон калькулятора. Использует bootstrap 5
- `lang_ru.ini` - языковой пакет
- `rates.json` - список ключевых ставок с 2016 до 2025 гг
- `script.js` - скрипт для обмена данными между шаблоном calccontainer.html и библиотекой Calc395.php
- `style.css` - стили

### Как пользоваться:
После подключения библиотеки к своему проекту

```php
use Calc395\Calc395;
$calc = new Calc395();
$calc->add($data);
```

Метод `$calc->add($data)` принимает данные в таком формате:

```JSON
// В объекте тестовые демо-данные
// $data =
{
    "amount": "100000",  // string — Сумма долга в виде строки (так как сумма может быть очень большой и требовать точности)
    "startDate": "2023-11-03",  // string (формат даты) — Дата начала периода в формате строки (ГГГГ-ММ-ДД)
    "endDate": "2024-02-15",  // string (формат даты) — Дата окончания периода в формате строки (ГГГГ-ММ-ДД)
    "changes": {  // object — Объект, содержащий изменения в сумме долга
        "plus": [  // array — Массив изменений, уменьшающих сумму, массив частичных оплат
            {
                "date": "2024-12-03",  // string (формат даты) — Дата изменения
                "summ": "1000"  // string — Сумма добавления в виде строки
            }
        ],
        "minus": [  // array — Массив изменений, увеличивающих сумму долга
            {
                "date": "2024-11-27",  // string (формат даты) — Дата изменения
                "summ": "1500"  // string — Сумма уменьшения в виде строки
            }
        ]
    }
}
```


Получаем результат:
После того как скормили данные методу `$calc->add($data)`, можно получить полный расчёт методом `$calc->getResult()`

```php

$result = $calc->getResult();

```

Переменная `$result` будет содержать данные в таком формате:

```JSON

// В объекте тестовые демо-данные
// $result =
{
    "intervals": [  // array — Массив интервалов времени, на которые разбивается расчёт
        {
            "from": "2023-11-03",  // string (формат даты) — Начало интервала
            "to": "2023-12-17",  // string (формат даты) — Конец интервала
            "days": 45,  // integer — Количество дней в интервале
            "debt": 100000,  // integer — Основная сумма долга на начало интервала
            "dy": 365,  // integer — Количество дней в году, для расчёта дневной ставки
            "rate": "15",  // string — Процентная ставка в процентах
            "penalty": 1849.32  // float — Сумма неустойки, рассчитанная для этого интервала
        },
        {
            "from": "2023-12-18",  // string (формат даты) — Начало интервала
            "to": "2023-12-31",  // string (формат даты) — Конец интервала
            "days": 14,  // integer — Количество дней в интервале
            "debt": 100000,  // integer — Основная сумма долга на начало интервала
            "dy": 365,  // integer — Количество дней в году
            "rate": "16",  // string — Процентная ставка в процентах
            "penalty": 613.7  // float — Сумма неустойки для этого интервала
        },
        {
            "from": "2024-01-01",  // string (формат даты) — Начало интервала
            "to": "2024-02-15",  // string (формат даты) — Конец интервала
            "days": 46,  // integer — Количество дней в интервале
            "debt": 100000,  // integer — Основная сумма долга на начало интервала
            "dy": 366,  // integer — Количество дней в году (високосный год)
            "rate": "16",  // string — Процентная ставка в процентах
            "penalty": 2010.93  // float — Сумма неустойки для этого интервала
        }
    ],
    "amount": 100000,  // integer — Основная сумма долга
    "final_amount": 100000,  // integer — Итоговая сумма долга (если не было изменений)
    "penalty_days": 105,  // integer — Общее количество дней, на которые начислены проценты
    "penalty_summ": 4473.95,  // float — Общая сумма неустойки за все периоды
    "mean_rate": 15.57,  // float — Средняя ставка процента по всем периодам
    "debt": 104473.95,  // float — Итоговая сумма долга с учётом неустойки
    "startDate": "2023-11-03",  // string (формат даты) — Дата начала расчёта
    "endtDate": "2024-02-15"  // string (формат даты) — Дата окончания расчёта
}

```

### Использование готового шаблона:

Готовый шаблон и js-скрипт из пакета с библиотекой, выведет html форму и script на странице, будет обращаться при помощи Ajax к текущему url и с помощью JavaScript показывать результаты расчета. По этому, в php-контроллере/роутере, для разделения запросов, от калькулятора будет приходить GET параметр `ajax` = `true`. Пример гипотетического `index.php`:

!!! Не забудь подключить `jQuery`

```php

use Calc395\Calc395;
$calc = new Calc395();

if(isset($_GET['ajax']) and $_GET['ajax'] == true){

    $calc->add($_GET['data']);
    $data = $calc->getResult();
    
    header('Content-Type: application/json');
    echo json_encode($data);

}else{

    // <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    $calc->::getCalcStyle();    // Выведет содержимое style.css
    $calc->::getCalcTmplHtml(); // Выведет содержимое calccontainer.html
    $calc->::getLangJSON();     // Создаст глобальную переменную в js calcLangData с языковыми переменными
    $calc->::getScriptTag();    // Выведет содержимое script.js

}

```
