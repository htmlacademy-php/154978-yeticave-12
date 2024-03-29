<?php
/**
 * Проверяет переданную дату на соответствие формату 'ГГГГ-ММ-ДД'
 *
 * Примеры использования:
 * is_date_valid('2019-01-01'); // true
 * is_date_valid('2016-02-29'); // true
 * is_date_valid('2019-04-31'); // false
 * is_date_valid('10.10.2010'); // false
 * is_date_valid('10/10/2010'); // false
 *
 * @param string $date Дата в виде строки
 *
 * @return bool true при совпадении с форматом 'ГГГГ-ММ-ДД', иначе false
 */
function is_date_valid(string $date): bool
{
    $format_to_check = 'Y-m-d';
    $dateTimeObj = date_create_from_format($format_to_check, $date);

    return $dateTimeObj !== false && array_sum(date_get_last_errors()) === 0;
}

/**
 * Создает подготовленное выражение на основе готового SQL запроса и переданных данных
 *
 * @param $db
 * @param $sql string SQL запрос с плейсхолдерами вместо значений
 * @param $params
 * @param null $types
 * @return mixed $stmt Подготовленное выражение
 */
function db_get_prepare_stmt($db, $sql, $params, $types = null)
{
    if (!$types) {
        foreach ($params as $value) {
            $types .= 's';
        }
    }
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    return $stmt;
}

/**
 * Создает подготовленное выражение на основе готового SQL запроса и переданных данных.
 * Выбирает все строки из результирующего набора и помещает их в ассоциативный массив, обычный массив или в оба
 *
 * @param $db
 * @param $sql
 * @param $params
 * @param null $types
 * @return mixed
 */
function db_get_assoc($db, $sql, $params, $types = null)
{
    $stmt = db_get_prepare_stmt($db, $sql, $params, $types);
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

/**
 * Создает подготовленное выражение на основе готового SQL запроса и переданных данных.
 * Извлекает результирующий ряд в виде ассоциативного массива
 *
 * @param $db
 * @param $sql
 * @param $params
 * @param null $types
 * @return mixed
 */
function db_get_all($db, $sql, $params, $types = null)
{
    $stmt = db_get_prepare_stmt($db, $sql, $params, $types);
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}


/**
 * Возвращает корректную форму множественного числа
 * Ограничения: только для целых чисел
 *
 * Пример использования:
 * $remaining_minutes = 5;
 * echo "Я поставил таймер на {$remaining_minutes} " .
 *     get_noun_plural_form(
 *         $remaining_minutes,
 *         'минута',
 *         'минуты',
 *         'минут'
 *     );
 * Результат: "Я поставил таймер на 5 минут"
 *
 * @param int $number Число, по которому вычисляем форму множественного числа
 * @param string $one Форма единственного числа: яблоко, час, минута
 * @param string $two Форма множественного числа для 2, 3, 4: яблока, часа, минуты
 * @param string $many Форма множественного числа для остальных чисел
 *
 * @return string Рассчитанная форма множественнго числа
 */
function get_noun_plural_form(int $number, string $one, string $two, string $many): string
{
    $number = (int)$number;
    $mod10 = $number % 10;
    $mod100 = $number % 100;

    switch (true) {
        case ($mod100 >= 11 && $mod100 <= 20):
            return $many;

        case ($mod10 > 5):
            return $many;

        case ($mod10 === 1):
            return $one;

        case ($mod10 >= 2 && $mod10 <= 4):
            return $two;

        default:
            return $many;
    }
}

/**
 * Подключает шаблон, передает туда данные и возвращает итоговый HTML контент
 * @param string $name Путь к файлу шаблона относительно папки templates
 * @param array $data Ассоциативный массив с данными для шаблона
 * @return string Итоговый HTML
 */
function include_template($name, array $data = [])
{
    $name = __DIR__ . '/templates/' . $name;

    ob_start();
    extract($data);
    require $name;

    return ob_get_clean();
}


/**
 * Принимает один аргумент — число. Возвращает результат — отформатированную сумму вместе со знаком рубля.
 * @param $value
 * @return string
 */
function price_formatting($value)
{
    $price = ceil($value);
    $price = number_format($price, '0', '', ' ');
    $price .= " ₽";
    return $price;
}


/**
 * Принимает строку. Возвращет отформатированную строку - заменяет HTML-теги и кавычки на HTML-мнемоники.
 * @param $str
 * @return string
 */
function esc($str)
{
    $text = htmlspecialchars($str, ENT_QUOTES);
    return $text;
}


/**
 * Принимает дату в формате ГГГГ-ММ-ДД
 * Возвращает массив, где первый элемент — целое количество часов до даты, а второй — остаток в минутах
 * @param $date
 * @return array
 */
function dt_remaining($date)
{
    $ts = time();
    $end_ts = strtotime($date);
    $ts_diff = $end_ts - $ts;

    $hours = str_pad(floor($ts_diff / 3600), "2", '0', STR_PAD_LEFT);
    $minutes = str_pad(floor(($ts_diff % 3600) / 60), "2", '0', STR_PAD_LEFT);
    $seconds = str_pad(floor(($ts_diff % 3600) % 60), "1", '0', STR_PAD_LEFT);
    $hours_minutes = [$hours, $minutes, $seconds];

    return $hours_minutes;
}

/** Проверяет переданную "начальную цену" на соответствие значению - число больше нуля.
 * @param $value
 * @return string|null
 */
function validate_price($value)
{
    if (!is_double($value) || $value <= 0) {
        return "Значение должно быть числом больше 0";
    }
}

/** Проверяет переданную "дату завершения" на соответствие значению - указанная дата больше текущей даты на один день.
 * @param $date
 * @return string
 */
function validate_current_date($date)
{
    if (is_date_valid($date) && dt_remaining($date)[0] < "24") {
        return "Дата должна быть больше текущей даты хотя бы на 1 день.";
    }
}

/** Проверяет переданный "шаг ставки" на соответствие значению - целое число больше 0.
 * @param $value
 * @return string
 */
function validate_step_rate($value)
{
    if (!is_int($value) || $value <= 0) {
        return "Значение должно быть целым числом больше 0";
    }
}

/** Проверяет выбрана ли категория лота из списка.
 * @param $id
 * @param $category_list
 * @return string
 */
function validate_category_id($id, $category_list)
{
    if (!in_array($id, $category_list)) {
        return "Выберите категорию из списка";
    }
}

/** Проверяет расширение загруженного файла.
 * @param $file
 * @return string
 */
function validate_file($file)
{
    $file_type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (($file_type !== "jpeg") && ($file_type !== "jpg") && ($file_type !== "png")) {
        return $errors['lot-img'] = 'Загрузите картинку в формате JPG, JPEG или PNG';
    }
}

/** Сохраняет значение полей формы после валидации.
 * @param $name
 * @return mixed
 */
function getPostVal($name)
{
    return filter_input(INPUT_POST, $name);
}

/** Валидация формы
 * @param $form
 * @param $rules
 * @param $required
 * @return array
 */
function form_validation($form, $rules, $required)
{
    $errors = [];
    foreach ($form as $key => $value) {
        if (in_array($key, $required) && empty($value)) {
            $errors[$key] = "Заполните это поле";
        } elseif (isset($rules[$key])) {
            $rule = $rules[$key];
            $validationResult = $rule($value);
            if ($validationResult) {
                $errors[$key] = $validationResult;
            }
        }
    }

    return $errors;
}

/** Отображает дату создания ставки для лота.
 * @param $date
 * @return false|string
 */
function rate_dt_add($date)
{
    $ts = time();
    $dt_add = strtotime($date);
    $ts_diff = $ts - $dt_add;

    if ($ts_diff >= 3600 && $ts_diff < 3600 * 24) {
        $hours = floor($ts_diff / 3600);
        $noun = get_noun_plural_form($hours, 'час', 'часа', 'часов');
        return $hours . ' ' . $noun . ' назад';
    } elseif ($ts_diff >= 60 && $ts_diff < 3600) {
        $minutes = str_pad(floor(($ts_diff % 3600) / 60), "1", '0', STR_PAD_LEFT);
        $noun = get_noun_plural_form($minutes, 'минута', 'минуты', 'минут');
        return $minutes . ' ' . $noun . ' назад';
    } elseif ($ts_diff < 60) {
        $noun = get_noun_plural_form($ts_diff, 'секунда', 'секунды', 'секунд');
        return $ts_diff . ' ' . $noun . ' назад';
    }

    return date('d.m.y в H:i', $dt_add);
}
