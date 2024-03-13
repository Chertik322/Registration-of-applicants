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
        $student_exists = checkStudentExists($connection, $student_id);

        if ($student_exists) {
            // Проверяем, существует ли запись с заданным specialization_id в таблице Specializations_SPO
            $specialization_exists = checkSpecializationExists($connection, $specialization_id);

            if ($specialization_exists) {
                // Добавление или обновление специализации у студента
                $result = insertOrUpdateSpecializationToStudent($connection, $student_id, $specialization_id);

                if ($result) {
                    echo "success";
                    // Записываем событие в action_log_SPO
                    $specialization_name = getSpecializationName($connection, $specialization_id);
                    $description = "$userName добавил(а) или обновил(а) специальность: $specialization_name";
                    $log_result = addActionToLog($connection, $student_id, $description);
                    
                    if (!$log_result) {
                        echo "Ошибка при записи в action_log_SPO";
                    }
                } else {
                    echo "Ошибка при добавлении или обновлении специализации";
                }
            } else {
                echo "Ошибка при добавлении или обновлении специализации: Неверный идентификатор специализации";
            }
        } else {
            echo "Ошибка при добавлении или обновлении специализации: Неверный идентификатор абитуриента";
        }
    } else {
        echo "Ошибка при добавлении или обновлении специализации: Недостаточно данных";
    }
}
?>
