<?php 
//  Функции работы с "криптовалютой" ;)

function get_price_by_date_price_array ($price_data, $date_target)
{
    /*
    Функция получает массив дат и цен (по ключу - дата в формате UTS, по значению - цена)
    Второй аргумент - дата (timestamp), на которую нужно узнать и вернуть цену
    Возвращает цену на дату, полученную во втором аргументе (тоже timestamp)
    */
    $debug = 0;

    #Сортируем массив цен по возрастанию даты
    #$price_data = array_reverse ($price_data, TRUE);
    ksort($price_data, SORT_NUMERIC);
    
    if ($debug) print_r($price_data);

    //  Массив пуст - ошибка входящих данных
    if (count($price_data) == 0)
    {
        return false;
    }

    //  Если массив содержит только одно число - вернём цену из первого элемента массива
    if (count($price_data) == 1)
    {
        reset ($price_data);
        $price = current ($price_data);
        return $price;
    }
    
    //  Получим самую первую дату и самую последнюю дату в массиве
    reset ($price_data);
    $first_date = (!empty($price_data)) ? array_keys($price_data)[0] : null;
    $last_date = (!empty($price_data)) ? array_keys($price_data)[count($price_data)-1] : null;
    
    if ($debug)
    {
        $var = 'first_date'; echo "$var = ".$$var."<br>";
        $var = 'last_date'; echo "$var = ".$$var."<br>";
        $var = 'var'; echo "$var = ".$$var."<br>";
        $var = 'var'; echo "$var = ".$$var."<br>";
        $var = 'var'; echo "$var = ".$$var."<br>";
        $var = 'var'; echo "$var = ".$$var."<br>";
        $var = 'var'; echo "$var = ".$$var."<br>";
        $var = 'var'; echo "$var = ".$$var."<br>";
    }
    
    //  Если заданная дата меньше, чем начальная дата - то экстраполируем, используя первые две даты из массива
    if ($date_target <= $first_date)
    {
        $second_date = array_keys($price_data)[1];
        $cur_price = get_interpolated_price ($first_date, $second_date, $price_data[$first_date], $price_data[$second_date], $date_target);
        if ($debug)
        {
            echo '$date_target <= $first_date<br>';
            $var = 'second_date'; echo "$var = ".$$var."<br>";
            $var = 'cur_price'; echo "$var = ".$$var."<br>";
        }
        return $cur_price;
    }
    
    //  Если заданная дата больше, чем последняя дата - экстраполируем, используя последние две даты из массива
    if ($date_target >= $last_date)
    {
        $pre_last_date = array_keys($price_data)[count($price_data)-2]; //  Предпоследняя дата
        $cur_price = get_interpolated_price ($pre_last_date, $last_date, $price_data[$pre_last_date], $price_data[$last_date], $date_target);
        if ($debug)
        {
            echo '$date_target >= $last_date<br>';
            $var = 'pre_last_date'; echo "$var = ".$$var."<br>";
            $var = 'cur_price'; echo "$var = ".$$var."<br>";
        }
        return $cur_price;
    }

    //  Обработаем ситуацию "Если заданная дата находится между датами, указанными в файле"
    //  Пройдёмся по массиву до тех пор, пока не встретим дату, больше или равную $date_target
    reset ($price_data);
    while (true)
    {
        //  Получаем следующую дату
        $start_date = key($price_data);
        next ($price_data);
        $end_date = key($price_data);
        //  Мы нашли нужные даты, когда целевая дата больше или равна начальной дате, но меньше, чем конечная дата
        if (($date_target >= $start_date) && ($date_target <= $end_date))
        {
            break;
        }
        if ($end_date === NULL)
        {
            if ($debug) echo "Strange error!<br>";
            //  Какая-то странная ошибка во входных данных
            break;
        }
    }

    if ($debug) {$var = 'start_date'; echo "$var = ".$$var."<br>";}
    if ($debug) {$var = 'end_date'; echo "$var = ".$$var."<br>";}
    $cur_price = get_interpolated_price ($start_date, $end_date, $price_data[$start_date], $price_data[$end_date], $date_target);
    if ($debug) {$var = 'cur_price'; echo "$var = ".$$var."<br>";}
    return $cur_price;
    
    /*
    foreach ($price_data as $date => $price)
    {
        //  $date - дата => $price - цена на эту дату
        if ($date >= $date_target)
        {
            //  Нашли дату, которую нужно использовать
        }
    }
    */

    return 1232;

}

function get_interpolated_price ($date1, $date2, $price1, $price2, $date_target)
{
    /*  Функция возвращает цену криптовалюты на заданное время, используя степенную интерполяцию (то есть цена меняется в одинаковое количество раз за одинаковые интервалы времени)
    получает в аргументах
    $date1 - дата начала
    $date2 - дата окончания
    $price1 - цена в начале
    $price2 - цена в окончании
    $date_target - дата на которую нужно вычислить цену
    
    Возвращает цену на целевую дату
    */
    
    #Вычислим, во сколько раз изменилась (увеличилась) цена от начальной до конечной даты
    $mul = $price2 / $price1;
    #Вычислим длительность периода в секундах
    $delta_sec = $date2 - $date1;
    ##Вычислим 
    #$offset = $date_target - $date1;
    #Нормализуем целевую дату так, чтобы она попадала в диапазон от 0 до 1 (0 - если равна начальной дате, 1 - если равна конечной, 0.5 - если ровно в средине, и т.д.)
    $norma_pow = ($date_target - $date1) / ($date2 - $date1);
    #Вычислим во сколько раз увеличится (изменится) цена от начальной даты до целевой даты
    $mul_target = pow ($mul, $norma_pow);
    #Вычислим целевую цену (начальную изменим в нужное количество раз
    $price_target = $price1 * $mul_target;
    
    $debug = 0;
    if ($debug)
    {
        echo "<br>";
        #echo "price_target = ".number_format($price_target, 9, '.', '');
        echo "price_target = ".sprintf("%01.7f", $price_target);
        echo "<br>";
        echo "mul_target = ${mul_target}";
        echo "<br>";
        echo "norma_pow = ${norma_pow}";
        echo "<br>";
        echo "delta_sec = ${delta_sec}";
        echo "<br>";
        echo "mul = ${mul}";
        echo "<br>";
        echo "date1 = ${date1}";
        echo "<br>";
        echo "date2 = ${date2}";
        echo "<br>";
        echo "price1 = ${price1}";
        echo "<br>";
        echo "price2 = ${price2}";
        echo "<br>";
    }

    $price_target = number_format ($price_target, 9, ".", "");
    return $price_target;
}


