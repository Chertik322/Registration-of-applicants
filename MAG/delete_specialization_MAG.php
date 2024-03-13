<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include_once ("../configs/config.php");
include_once ("../configs/functions.php");
include_once ("../configs/db_connect.php");
// Получаем имя пользователя по user_id
$user_id = $_SESSION['user_id'];
$userName = get_username_by_id($connection, $user_id);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['student_id']) && isset($_POST['specialization_id'])) {
        $student_id = $_POST['student_id'];
        $specialization_id = $_POST['specialization_id'];

        // Проверяем, существует ли запись с заданным student_id в таблице Students_SPO
        $student_exists = checkStudentExists_MAG($connection, $student_id);

        if ($student_exists) {
            // Проверяем, существует ли запись с заданным specialization_id в таблице Specializations_SPO
            $specialization_exists = checkSpecializationExists_MAG($connection, $specialization_id);

            if ($specialization_exists) {
                // Удаление специализации у абитуриента
                $result = deleteSpecializationFromStudent_MAG($connection, $student_id, $specialization_id);

                if ($result) {
                    echo "success";
// Записываем событие в action_log_SPO
$specialization_name = getSpecializationName_MAG($connection, $specialization_id);
$description = "$userName удалил(а) специальность: $specialization_name";
$log_result = delActionToLog_MAG($connection, $student_id, $specialization_name);
if (!$log_result) {
    echo "Ошибка при записи в action_log_SPO";
}
                } else {
                    echo "Ошибка при удалении специализации";
                }
            } else {
                echo "Ошибка при удалении специализации: Неверный идентификатор специализации";
            }
        } else {
            echo "Ошибка при удалении специализации: Неверный идентификатор абитуриента";
        }
    } else {
        echo "Ошибка при удалении специализации: Недостаточно данных";
    }
}
?>
