<?php

namespace Calc395;

use \DateTime;

class Calc395
{

    public $startDate;

    public $endtDate;

    public $data;

    public $changes;

    public $payments;

    public $ignoring;

    public $loans;

    public $list;

    public $intervals;

    public $intervalsPenalty;

    public $amount;

    public $final_amount;

    private $rates;

    public $penalty_summ;

    public $penalty_days;

    public $mean_rate;

    public function setAmount($amount)
    {
        $this->amount = round($amount, 2);
    }

    public function __construct()
    {
        $this->loadJson();
    }

    public static function getScriptTag(): string
    {
        
        // Указываем путь к файлу script.js
        $scriptFilePath = __DIR__ . '/script.js';
        
        // Проверяем существование файла
        if (!file_exists($scriptFilePath)) {
            return '<script>console.error("Script file not found.");</script>';
        }
        
        // Считываем содержимое файла
        $scriptContent = file_get_contents($scriptFilePath);
        
        // Возвращаем скрипт в теге <script>
        return "<script>{$scriptContent}</script>";
    }

    private function loadJson()
    {
        // Путь к JSON-файлу в той же директории
        $filePath = __DIR__ . '/rates.json';

        if (!file_exists($filePath)) {
            throw new \Exception("Файл $filePath не найден!");
        }

        $jsonContent = file_get_contents($filePath);

        // Попытка декодировать JSON
        $rates = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Ошибка декодирования JSON: " . json_last_error_msg());
        }

        $this->rates = $rates;
    }

    public function getRates(): array
    {
        return $this->rates;
    }

    public function sortRatesByDate(array &$rates): void
    {
        usort($rates, fn($a, $b) => strtotime($a['endDate']) - strtotime($b['endDate']));
    }

    public function calculatePenaltyInterest($debt, $rate, $daysOverdue, $daysInYear): float 
    {
    
        // Преобразуем ставку в долю
        $rateDecimal = $rate / 100;
    
        // Расчет процентов
        $interest = ($debt * $rateDecimal / $daysInYear) * $daysOverdue;
    
        return round($interest, 2); // Округление до двух знаков

    }

    public function BruteDays($startDate, $endDate)
    {

        $start_debt = $this->amount;
        $debt = $start_debt;

        // Создаем объекты DateTime
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);

        // Создаем объекты DateTime
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $end->modify('+1 day'); // Включаем конечную дату

        $days = [];

        // Цикл по дням
        while ($start < $end) {

            // Используем метод format для вывода даты
            $date = $start->format("Y-m-d");

            if($changes = $this->changes){

                foreach($changes as $change){

                    $ch_date = new DateTime($change['date']);
                    $now_date = new DateTime($date);

                    if($ch_date == $now_date){

                        $debt += $change['summ'];

                    }

                }

            }

            $day = [];
             #debt - сумма долга
             #days - количество дней
             #dy - дней в году (весокосный или нет)
             #rate - процентная ставца ЦБ РФ

            $day['date'] = $date;

            //var_dump($this->ignoreChekDay($date));

            if($this->ignoreChekDay($date)){
                $day['days'] = 0;
            }else{
                $day['days'] = 1;
            }
            
            $day['debt'] = $debt;
            $day['dy'] = date("L", strtotime($start->format("Y-m-d"))) ? 366 : 365;
            $day['rate'] = $this->findInterval($date);

            $days[] = $day;

            $start->modify('+1 day'); // Переход к следующему дню

        }

        return $days;

    }

    public function findInterval($targetDate, $param = false)
    {

        $intervals = $this->getRates();
        $targetDate = new DateTime($targetDate);

        foreach($intervals as $interval){

            $startDate = new DateTime($interval['startDate']);
            $endDate = new DateTime($interval['endDate']);

            if ($targetDate >= $startDate AND $targetDate <= $endDate) {

                if($param){

                    return $interval;

                }else{

                    return $interval['rate'];

                }

            }

        }

    }

    public function setIgnoring(){

        if(isset($this->data['changes']['ignoring'])){
            return $this->ignoring = $this->data['changes']['ignoring'];
        }else{
            return false;
        }
        
    }

    public function getIgnoring(){

        if(isset($this->ignoring) and count($this->ignoring)>0){

            return $this->ignoring;

        }else{

            return false;

        }

    }

    public function setChanges()
    {

        $changes = [];
        
        if (isset($this->data['changes']['plus'])){

            $this->payments = $this->data['changes']['plus'];

            foreach($this->data['changes']['plus'] as $pay){

                $pay['summ'] = -$pay['summ'];
                $changes[] = $pay;

            }

        }

        if (isset($this->data['changes']['minus'])){

            $this->loans = $this->data['changes']['minus'];

            foreach($this->data['changes']['minus'] as $loan){

                $loan['summ'] = (int)$loan['summ'];
                $changes[] = $loan;

            }

        }

        if(count($changes) > 0){

            $this->changes = $changes;
            
        }

    }

    function mergeIntervals(): array
    {

        $data = $this->list;

        $result = [];
        $currentInterval = null;
    
        foreach ($data as $entry) {
            if ($currentInterval === null) {
                // Инициализация первого интервала
                $currentInterval = [
                    'from' => $entry['date'],
                    'to' => $entry['date'],
                    'days' => $entry['days'],
                    'debt' => $entry['debt'],
                    'dy' => $entry['dy'],
                    'rate' => $entry['rate']
                ];
            } elseif (
                $entry['debt'] === $currentInterval['debt'] &&
                $entry['dy'] === $currentInterval['dy'] &&
                $entry['rate'] === $currentInterval['rate']
            ) {
                // Если параметры совпадают, продлеваем интервал
                $currentInterval['to'] = $entry['date'];
                $currentInterval['days'] += $entry['days'];
            } else {
                // Сохраняем текущий интервал и начинаем новый
                $result[] = $currentInterval;
                $currentInterval = [
                    'from' => $entry['date'],
                    'to' => $entry['date'],
                    'days' => $entry['days'],
                    'debt' => $entry['debt'],
                    'dy' => $entry['dy'],
                    'rate' => $entry['rate']
                ];
            }
        }
    
        // Добавляем последний интервал, если он есть
        if ($currentInterval !== null) {
            $result[] = $currentInterval;
        }
    
        return $result;
    }
    
    

    public function add($data){

        $this->data = $data;
        $this->startDate = $data['startDate'];
        $this->endtDate = $data['endDate'];
        $this->setAmount($data['amount']);
        $this->setChanges();
        $this->setIgnoring();
        $this->list = $this->BruteDays($data['startDate'],  $data['endDate']);
        $this->intervals = $this->mergeIntervals();
        $this->setResultIntervals();

    }

    public function setResultIntervals()
    {

        $penalty_summ = 0;  #сумма процентов
        $penalty_days = 0;  #дней просрочки

        $mean_rate = 0;     #средняя ставка

        $intervals = $this->intervals;

        $final_amount = 0;

        $intervalsPenalty = [];

        foreach($intervals as $interval){

            $interval['penalty'] = $this->calculatePenaltyInterest($interval['debt'], $interval['rate'], $interval['days'], $interval['dy']);

            $penalty_summ += $interval['penalty'];
            $penalty_days += $interval['days'];
            $mean_rate += $interval['rate']*$interval['days'];

            $intervalsPenalty[] = $interval;

            $final_amount = $interval['debt'];

        }

        if($penalty_days == 0){
            $mean_rate = $penalty_days;
        }else{
            $mean_rate = round($mean_rate/$penalty_days, 2);
        }
        

        $this->penalty_days = $penalty_days;
        $this->penalty_summ = $penalty_summ;
        $this->mean_rate = $mean_rate;
        $this->intervalsPenalty = $intervalsPenalty;
        $this->final_amount = $final_amount;

    }

    public function getResult(){

        $data = new \stdClass();
        $data->intervals = $this->intervalsPenalty;
        $data->amount = round($this->amount, 2);
        $data->final_amount = round($this->final_amount, 2);
        $data->penalty_days = (int)$this->penalty_days;
        $data->penalty_summ = round($this->penalty_summ, 2);
        $data->mean_rate = round($this->mean_rate, 2);
        $data->debt = round($this->final_amount + $this->penalty_summ, 2);

        $data->startDate = $this->startDate;
        $data->endtDate = $this->endtDate;

        $data->ignoring = $this->ignoring;

        return $data;

    }

    static public function getCalcStyle()
    {

        echo '<style>';

            $filePath = __DIR__ . '/style.css';
            echo file_get_contents($filePath);

        echo '</style>';

    }
    static public function getCalcTmplHtml()
    {

        $filePath = __DIR__ . '/calccontainer.html';
        $htmlContent = file_get_contents($filePath);

        $language = self::getLang();

        // Замена переменных
        foreach ($language as $key => $value) {
            $htmlContent = str_replace('{{' . $key . '}}', $value, $htmlContent);
        }

        echo $htmlContent;

    }

    static public function getLangJSON()
    {

        echo '<script>';

            $jsonData = json_encode(self::getLang());
            echo "var calcLangData = $jsonData";

        echo '</script>';

    }

    static public function getLang()
    {

        $iniPath = __DIR__ . '/lang_ru.ini';
        return parse_ini_file($iniPath);

    }

    public function ignoreChekDay($date)
    {

        $check = false;

        if($this->ignoring){  //Проверяем день на вхождение в интервалы исключений

            $date = new DateTime($date);

            foreach($this->ignoring as $ignore){

                $date1 = new DateTime($ignore['date1']);
                $date2 = new DateTime($ignore['date2']);

                if($date >= $date1 and $date <= $date2){

                    return true;

                }

            }

        }

        return $check;

    }
    
}
