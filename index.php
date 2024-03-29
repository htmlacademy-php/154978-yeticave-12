<?php
require 'init.php';
require 'getwinner.php';

$sql = 'SELECT l.id, 
       l.title,
       l.price_add,
       img,
       dt_finish,
       price_add current_price,
       c.title category_title
FROM lots l 
         JOIN categories c ON l.category_id = c.id
WHERE dt_finish > NOW()
ORDER BY dt_add DESC
LIMIT 9';

$result = $db->query($sql);
$lots_list = $result->fetch_all(MYSQLI_ASSOC);

$main_content = include_template('main.tpl.php', [
    'nav_list' => $nav_list,
    'lots_list' => $lots_list
]);

$layout_content = include_template('layout.tpl.php', [
    'nav_list' => $nav_list,
    'content' => $main_content,
    'title' => 'Главная',
    'hide_nav_list' => true
]);

echo $layout_content;
