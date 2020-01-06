<?php 
/*
Скрипт вычисляет значение, используя степенную интерполяцию
*/

//  Подключим стандартные функции
require_once ('functions.php');
//  Подключим функции работы с криптой
require_once ('crypto_functions.php');

//  Смещение времени, относительно time() в секундах
$time_offset = 3600;

#Файл с массивом дат и курсов
$price_file = 'price.csv';
#Раздлелитель в файле с ценами (между датой и ценой)
$price_file_separator = ';';

#Читать даты и "курсы" из CSV-файла
$price_data = array();  //  массив для ценовых данных (ключ - дата, значение - цена)
if (($handle = fopen($price_file, "r")) !== FALSE) 
{
    while (($data = fgetcsv($handle, 65536, $price_file_separator)) !== FALSE) 
    {
        //  Валидим данные
        foreach ($data as $key => $val)
        {
            //$key => $val
            $data[$key] = trim ($val);
        }
        //  Фильтруем дату посимвольно
        $data[0] = mask_filter ($data[0], '9876543210,.-: ');
        //  Цену цену посимвольно
        $data[1] = str_replace (',', '.', $data[1]);    //  Заменим запятую на точку в цене
        $data[1] = mask_filter ($data[1], '9876543210.-');
        //  Преобразуем дату в формат timestamp
        $date = strtotime ($data[0]);
        $price = $data[1];
        if ($date !== false)
        {
            //  Удалось распознать дату, добавить это значение в массив дат и цен
            $price_data [$date] = $price;
        }
    }
    fclose($handle);
}
else
{
    die ("Error reading from '$price_file'");
}

if ( (!empty($_REQUEST['time'])) && ($_REQUEST['time'] > 0) )
{
    //  Получили другое время. Установим его вместо текущего времени
    $cur_date = strtotime ($_REQUEST['time']);
}
else
{
    //  Время не задано явно - используем текущее
    $cur_date = time();
    $cur_date += $time_offset;  //  Добавим смещение ко времени
}

#Для текущей даты получаем цену 
$cur_price = get_price_by_date_price_array($price_data, $cur_date);

#Сегодня, 12:00
$today12am = strtotime (date('Y-m-d 08:00:00', $cur_date));

//  Формируем массив прошедших дат
$dates_past = array (
    $today12am-24*3600*1,
    $today12am-24*3600*2,
    $today12am-24*3600*3,
    $today12am-24*3600*4,
    $today12am-24*3600*5,
    $today12am-24*3600*6,
    $today12am-24*3600*7,
    $today12am-24*3600*7*2,
    $today12am-24*3600*7*3,
    $today12am-24*3600*7*4,
    $today12am-24*3600*30,
    $today12am-24*3600*30*2,
    $today12am-24*3600*30*3,
    #$today12am-24*3600*30*6,
    #$today12am-24*3600*365,
    #$today12am-24*3600*365*2,
);
$dates_past = array_reverse ($dates_past, TRUE);

//  Формируем массив будущих дат
$dates_future = array (
    $today12am+24*3600*1,
    $today12am+24*3600*2,
    $today12am+24*3600*3,
    $today12am+24*3600*4,
    $today12am+24*3600*5,
    $today12am+24*3600*6,
    $today12am+24*3600*7,
    $today12am+24*3600*8,
    $today12am+24*3600*9,
    $today12am+24*3600*10,
    $today12am+24*3600*11,
    $today12am+24*3600*12,
    $today12am+24*3600*13,
    $today12am+24*3600*7*2,
    $today12am+24*3600*7*3,
    $today12am+24*3600*7*4,
    $today12am+24*3600*7*5,
    $today12am+24*3600*7*6,
    $today12am+24*3600*7*7,
    $today12am+24*3600*7*8,
    $today12am+24*3600*7*9,
    $today12am+24*3600*7*10,
    $today12am+24*3600*7*11,
    $today12am+24*3600*7*12,
    #$today12am+24*3600*30*4,
    #$today12am+24*3600*30*5,
    #$today12am+24*3600*30*6,
);

//  Формируем массив с популярными суммами обмена (рубли и ПашаКоины)
//  По значению - популярные количества валюты
$popular_rate = array(
'0.000000000001',
'0.00000000001',
'0.0000000001',
'0.000000001',
'0.00000001',
'0.0000001',
'0.000001',
'0.00001',
'0.0001',
'0.001',
'0.01',
'0.02',
'0.05',
'0.2',
'0.5',
1,
2,
3,
4,
5,
6,
7,
8,
9,
10,
20,
25,
30,
40,
50,
100,
150,
200,
500,
1000,
);
$popular_rate_ready = array(); //   Массив, куда будем складывать результаты. По ключу - рубли, по значению - ПашаКоины
//  Вычисляем значение для каждого популярного количества рублей
foreach ($popular_rate as $id => $rubles)
{
    //  $id - индекс массива, $rate - количество валюты
    $rate = number_format ($rubles / $cur_price, 2, ".", "");
    if ($rate > 0)
    {
        $popular_rate_ready[$rubles] = $rate;
    }
}
//  Вычисляем значение для каждого популярного количества ПашаКоинов
foreach ($popular_rate as $id => $count)
{
    //  $count - количество валюты
    $rubles = number_format ($count * $cur_price, 2, ".", "");
    if ($rubles > 0)
    {
        $popular_rate_ready[$rubles] = $count;
    }
}

//  Сортируем массив популярных количеств к обмену по возрастанию
ksort($popular_rate_ready, SORT_NUMERIC);

echo '<!DOCTYPE html><html lang="ru"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>PashaCoin '.
date('Y-m-d__H-i-s', $cur_date).'</title><link href="data:image/x-icon;base64,AAABAAEAEBAQAAEABAAoAQAAFgAAACgAAAAQAAAAIAAAAAEABAAAAAAAgAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAG//AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEQABEAEQAAERAAEQARAAEREAARABEAEQEQABEAEQEQARAAEQAREQABEAARABEQAAEQABEAEREAARAAEQARARABEAARABEAEQEREREAEQABEREREQARAAAQAAAAAAAAAAAAAAAAAAAAD//wAA//8AAP//AAA5ngAAOZwAADmZAAA5kwAAOYcAADmPAAA5hwAAOZMAADmZAAABnAAAAZ4AAP//AAD//wAA" rel="icon" type="image/x-icon" />
<meta name="viewport" content="width=device-width, initial-scale=1"><style type="text/css">
body{margin:.5em auto;padding:.5em;text-align:left;font-family:sans-serif;color:#fff;background-color:#333}table,td,th{border:1px solid gray;text-align:center;vertical-align:top}th{position:sticky;top:0;background:#111;text-align:center;border:2px solid #000;box-sizing:border-box;outline:1px solid #888}pre{margin:0;padding:0}p{margin-bottom:1.5em}a{color:#bbf}.rates_table{margin:2pt auto;width:100%;border-collapse:collapse}.nowrap{white-space:nowrap}.pre{display:block;unicode-bidi:embed;font-family:monospace;white-space:pre}.disclaimer{font-size:70%;text-align:center}tr:nth-child(odd){background-color:#222}.cur_price{text-align:center;width:100%}.cur_price_text{text-align:center;width:100%}.bigger_text{font-size:150%}h1,h2,h3,h4,h5,h6,h7{text-align:center;padding-top:2em}.first_td{width:30%}td{text-align:right}.nopadding{padding:0}
@media print 
{
    *
    {
        color: #000;
        background-colorcolor: #fff;
    }
    thead
    {
        display: table-header-group;
    }
}
</style></head><body>';
echo '<div class="cur_price">';
echo '<h1>';
echo 'Текущий курс PashaCoin к рублю';
echo '<br>';
echo '<div class="bigger_text">'.number_format ($cur_price, 9, ".", "").'</div>';
echo '</h1>';
echo '<div class="disclaimer">';
echo '1 ПашаКоин может быть куплен или продан за '.number_format ($cur_price, 2, ".", "").' рублей<br>';
echo '1 рубль может быть куплен или продан за '.number_format (1/$cur_price, 3, ".", "").' ПашаКоинов<br>';
echo 'Данные актуальны на '.date('Y-m-d H:i:s', $cur_date).' самарского времени<br>';
echo '</div>';
echo '<h2 class=nopadding><a href="http://brva.ru/pashacoin">brva.ru/pashacoin</a></h2>';
echo 'Постоянный адрес страницы';


echo '</div>';
echo '<div class="cur_price_text">';
echo '</div>';


//  Показываем таблицу курсов
echo '<h1>Прошлые курсы</h1>';
echo '<div class="disclaimer">';
echo 'Сколько стоил 1 ПашаКоин в рублях в разное время';
echo '</div>';
echo '<table class="rates_table">';
echo '<thead>';
echo '<tr><th>Дата (год, месяц, день)<th>Цена в рублях</tr>';
echo '</thead><tbody>';
foreach ($dates_past as $date)
{
    //  $date - дата
    echo '<tr>';
    echo '<td class="first_td">';
    echo date('Y-m-d', $date);
    echo '<td>';
    echo get_price_by_date_price_array($price_data, $date);
    echo '</tr>';
}
echo '</tbody>';
echo '</table>';

echo '<h1>Прогнозируемые курсы</h1>';
echo '<div class="disclaimer">';
echo 'На 8:00 по самарскому времени';
echo '</div>';
echo '<table class="rates_table">';
echo '<thead>';
echo '<tr><th>Дата (год, месяц, день)<th>Цена в рублях</tr>';
echo '</thead><tbody>';
foreach ($dates_future as $date)
{
    //  $date - дата
    echo '<tr>';
    echo '<td class="first_td">';
    echo date('Y-m-d', $date);
    echo '<td>';
    echo get_price_by_date_price_array($price_data, $date);
    echo '</tr>';
}
echo '</tbody>';
echo '</table>';
echo '<div class="disclaimer">';
echo 'Могут отличаться от реальных курсов в будущем';
echo '</div>';

//  Показываем таблицу популярных курсов
echo '<h1>Сколько стоит разное количество ПашаКоинов в рублях</h1>';
echo '<div class="disclaimer">';
echo 'По курсу на данный момент';
echo '</div>';
echo '<table class="rates_table">';
echo '<thead>';
echo '<tr><th>Рубли<th>ПашаКоины</tr>';
echo '</thead><tbody>';
foreach ($popular_rate_ready as $rubles => $pcoins)
{
    //  $rubles - количество рублей => $pcoins - количество ПашаКоинов
    if (($rubles > 0) && ($pcoins > 0))
    {
        echo '<tr>';
        echo '<td class="first_td">';
        echo number_format ($rubles, 2, ".", "");
        echo '<td>';
        echo number_format ($pcoins, 3, ".", "");
        echo '</tr>';
    }
}
echo '</tbody>';
echo '</table>';


echo '<br><br><br>';
echo '<div class="disclaimer">';
echo 'Не является публичным предложением о заключении сделки';
echo '</div>';

#echo "$cur_price";

#$var = 'cur_price'; echo "$var = ".$$var."<br>";

#TODO интерполировать курсы внутри дат из файла
#TODO Экстраполировать курсы за пределы дат из файла (вычислять курс к последней дате, "виртуально" добавлять эту дату в список)
#TODO Сохранять лог обращений с курсами на каждый момент времени
#TODO добавить счётчик yandex-метрики
#TODO показывать таблицу с прошлыми курсами и "прогнозом" курсов

#echo bcscale();
#echo "<br>";

#phpinfo();


?><script type="text/javascript">

!function(e,t,a){(t[a]=t[a]||[]).push(function(){try{t.yaCounter16400947=new Ya.Metrika({id:16400947,clickmap:!0,trackLinks:!0,accurateTrackBounce:!0,webvisor:!0})}catch(e){}});function c(){n.parentNode.insertBefore(r,n)}var n=e.getElementsByTagName("script")[0],r=e.createElement("script");r.type="text/javascript",r.async=!0,r.src="https://mc.yandex.ru/metrika/watch.js","[object Opera]"==t.opera?e.addEventListener("DOMContentLoaded",c,!1):c()}(document,window,"yandex_metrika_callbacks");
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/16400947" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
</body></html>
