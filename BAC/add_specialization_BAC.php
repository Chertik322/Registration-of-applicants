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
        $student_exists = checkStudentExists_BAC($connection, $student_id);

        if ($student_exists) {
            // Проверяем, существует ли запись с заданным specialization_id в таблице Specializations_SPO
            $specialization_exists = checkSpecializationExists_BAC($connection, $specialization_id);

            if ($specialization_exists) {
                // Проверяем, существует ли уже специализация у студента
                $specialization_already_exists = CheckSpecializationByStudent_BAC($connection, $student_id, $specialization_id);

                if ($specialization_already_exists) {
                    echo "exists"; // Специализация уже существует у студента
                    exit();
                }

                // Добавление специализации к абитуриенту
                $result = insertSpecializationToStudent_BAC($connection, $student_id, $specialization_id);

                if ($result) {
                    echo "success";
                    // Записываем событие в action_log_SPO
                    $specialization_name = getSpecializationName_BAC($connection, $specialization_id);
                    $description = "$userName добавил(а) специальность: $specialization_name";
                    $log_result = addActionToLog_BAC($connection, $student_id, $specialization_name);
                    
                    if (!$log_result) {
                        echo "Ошибка при записи в action_log_BAC";
                    }
                } else {
                    echo "Ошибка при добавлении специализации";
                }
            } else {
                echo "Ошибка при добавлении специализации: Неверный идентификатор специализации";
            }
        } else {
            echo "Ошибка при добавлении специализации: Неверный идентификатор абитуриента";
        }
    } else {
        echo "Ошибка при добавлении специализации: Недостаточно данных";
    }
}
?>
