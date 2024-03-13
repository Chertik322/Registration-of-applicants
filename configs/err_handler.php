<?php 

function err_handler2($errno, $errmsg, $filename, $linenum){
    GLOBAL $DB;

    $path_error_log = "../error.log";
    $date = date("d-m-Y H:i:s (T)");

    echo(json_encode(array(
        "error" => true,
        "type" => "FATAL_ERROR",
        "title" => "Критическая ошибка!",
        "desc" => $errmsg,
        "line" => $linenum,
        "errno" => $errno,
        "filename" => $filename,
        "datetime" => array(
            'Y' => date('Y'),
            'm' => date('m'),
            'd' => date('d'),
            'H' => date('H'),
            'i' => date('i'),
            's' => date('s'),
            'full' => date('Y-m-d H:i:s'),
        )
    )));

    file_put_contents($path_error_log, json_encode(array(
        "date" => $date,
        "desc" => $errmsg,
        "file" => $filename,
        "line" => $linenum,
        "errno" => $errno,
    ))."\r\n", FILE_APPEND);

    exit;
}

set_error_handler('err_handler2');