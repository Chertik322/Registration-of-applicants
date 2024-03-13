<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include_once ("../configs/config.php");
include_once ("../configs/functions.php");
include_once ("../configs/db_connect.php");

// Проверяем, если был отправлен AJAX-запрос с идентификатором студента
if (isset($_POST['student_id'])) {
    $studentId = $_POST['student_id'];
    $html = '';
    // Получаем комментарии и журнал действий студента
    $actions = getActions_MAG($connection, $studentId);
  
    // Формируем HTML-код для комментариев и журнала действий
    $html .= '<h6>Журнал действий:</h6>';
    $html .= '<ul>';
    foreach ($actions as $action) {
      $html .= '<li>' . $action['description'] . ' (' . $action['timestamp'] . ')</li>';
    }
    $html .= '</ul>';
  
    // Возвращаем полученный HTML-код в качестве ответа на AJAX-запрос
    echo $html;
    exit;
  }
?>
