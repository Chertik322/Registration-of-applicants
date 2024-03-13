<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include_once ("../configs/config.php");
include_once ("../configs/functions.php");
include_once ("../configs/db_connect.php");

function updateStudentData($connection, $student_id, $column, $lastName, $firstName, $middleName, $phoneNumber, $description, $foreign_language,$rezults)
{
    $column = mysqli_real_escape_string($connection, $column);
    $lastName = mysqli_real_escape_string($connection, $lastName);
    $firstName = mysqli_real_escape_string($connection, $firstName);
    $middleName = mysqli_real_escape_string($connection, $middleName);
    $phoneNumber = mysqli_real_escape_string($connection, $phoneNumber);
    $student_id = mysqli_real_escape_string($connection, $student_id);
    $description = mysqli_real_escape_string($connection, $description);
    $foreign_language = mysqli_real_escape_string($connection, $foreign_language);
    $rezults= mysqli_real_escape_string($connection, $rezults);
    if ($column === 'name') {
        $query = "UPDATE Students_SPO SET last_name = ?, first_name = ?, middle_name = ? WHERE student_id = ?";
        $statement = mysqli_prepare($connection, $query);
        if ($statement) {
            mysqli_stmt_bind_param($statement, "ssss", $lastName, $firstName, $middleName, $student_id);
            if (mysqli_stmt_execute($statement)) {
                if (mysqli_stmt_affected_rows($statement) > 0) {
                    echo "Данные успешно обновлены";
                } else {
                    echo "Ошибка при обновлении данных";
                }
            } else {
                echo "Ошибка при выполнении запроса: " . mysqli_stmt_error($statement);
            }
            mysqli_stmt_close($statement);
        } else {
            echo "Ошибка при подготовке запроса: " . mysqli_error($connection);
        }
    } elseif ($column === 'phone_number') {
        $query = "UPDATE Students_SPO SET phone_number = ? WHERE student_id = ?";
        $statement = mysqli_prepare($connection, $query);
        if ($statement) {
            mysqli_stmt_bind_param($statement, "ss", $phoneNumber, $student_id);
            if (mysqli_stmt_execute($statement)) {
                if (mysqli_stmt_affected_rows($statement) > 0) {
                    echo "Данные успешно обновлены";
                    // После успешного выполнения запроса на обновление данных студента
                } else {
                    echo "Ошибка при обновлении данных";
                }
            } else {
                echo "Ошибка при выполнении запроса: " . mysqli_stmt_error($statement);
            }

            mysqli_stmt_close($statement);
        } else {
            echo "Ошибка при подготовке запроса: " . mysqli_error($connection);
        }
    } elseif ($column === 'description') {
        $query = "UPDATE Students_SPO SET description = ? WHERE student_id = ?";
        $statement = mysqli_prepare($connection, $query);
        if ($statement) {
            mysqli_stmt_bind_param($statement, "ss", $description, $student_id);
            if (mysqli_stmt_execute($statement)) {
                if (mysqli_stmt_affected_rows($statement) > 0) {
                    echo "Данные успешно обновлены";
                    // После успешного выполнения запроса на обновление данных студента
                } else {
                    echo "Ошибка при обновлении данных";
                }
            } else {
                echo "Ошибка при выполнении запроса: " . mysqli_stmt_error($statement);
            }

            mysqli_stmt_close($statement);
        } else {
            echo "Ошибка при подготовке запроса: " . mysqli_error($connection);
        }
    } elseif ($column === 'foreign_language') {
        $query = "UPDATE Students_SPO SET foreign_language = ? WHERE student_id = ?";
        $statement = mysqli_prepare($connection, $query);
        if ($statement) {
            mysqli_stmt_bind_param($statement, "ss", $foreign_language, $student_id);
            if (mysqli_stmt_execute($statement)) {
                if (mysqli_stmt_affected_rows($statement) > 0) {
                    echo "Данные успешно обновлены";
                    // После успешного выполнения запроса на обновление данных студента
                } else {
                    echo "Ошибка при обновлении данных";
                }
            } else {
                echo "Ошибка при выполнении запроса: " . mysqli_stmt_error($statement);
            }

            mysqli_stmt_close($statement);
        } else {
            echo "Ошибка при подготовке запроса: " . mysqli_error($connection);
        }
    }
    elseif ($column === 'rezults') {
        $query = "UPDATE Students_SPO SET rezults = ? WHERE student_id = ?";
        $statement = mysqli_prepare($connection, $query);
        if ($statement) {
            mysqli_stmt_bind_param($statement, "ss", $rezults, $student_id);
            if (mysqli_stmt_execute($statement)) {
                if (mysqli_stmt_affected_rows($statement) > 0) {
                    echo "Данные успешно обновлены";
                    // После успешного выполнения запроса на обновление данных студента
                } else {
                    echo "Ошибка при обновлении данных";
                }
            } else {
                echo "Ошибка при выполнении запроса: " . mysqli_stmt_error($statement);
            }

            mysqli_stmt_close($statement);
        } else {
            echo "Ошибка при подготовке запроса: " . mysqli_error($connection);
        }
    }
}

if (isset($_POST['column'])) {
    $student_id = $_POST['student_id'];
    $column = $_POST['column'];
    $lastName = $_POST['lastName'];
    $firstName = $_POST['firstName'];
    $middleName = $_POST['middleName'];
    $phoneNumber = $_POST['phoneNumber'];
    $description=$_POST['description'];
    $foreign_language=$_POST['foreign_language'];
    $rezults=$_POST['rezults'];
    // Обновление данных студента
    updateStudentData($connection, $student_id, $column, $lastName, $firstName, $middleName, $phoneNumber, $description, $foreign_language,$rezults);
}
?>
