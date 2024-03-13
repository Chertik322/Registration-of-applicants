<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include_once("../configs/config.php");
include_once("../configs/functions.php");
include_once("../configs/db_connect.php");

// Получаем значения из POST-запроса
$studentId = $_POST['student_id'];
$iconType = $_POST['icon_type']; 
// Получаем имя пользователя по user_id
$user_id = $_SESSION['user_id'];
$userName = get_username_by_id($connection, $user_id);
function addActionToLogIco_BAC($connection, $student_id, $iconType, $userName) {
    $action = "$userName заходил(а) в $iconType с данным абитуриентом";
    $query = "INSERT INTO action_log_BAC (student_id, `description`) VALUES (?, ?)";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, 'ss', $student_id, $action);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    if (!$result) {
        // Выводим подробную информацию об ошибке
        echo "Ошибка при выполнении запроса: " . mysqli_error($connection);
    }
    return $result;
}


// Вызываем функцию для добавления записи в лог
$result = addActionToLogIco_BAC($connection, $studentId, $iconType, $userName);

// Отправляем ответ об успешном выполнении запроса
if ($result) {
    echo "Запись успешно добавлена в лог.";
} else {
    echo "Ошибка при добавлении записи в лог.";
}

?>