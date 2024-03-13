<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
include_once ("../vendor/autoload.php");
include_once ("../configs/config.php");
include_once ("../configs/functions.php");
include_once ("../configs/db_connect.php");

// Обработка изменения статуса задачи
if (isset($_POST['student_id']) && isset($_POST['originals'])) {
    $student_id = mysqli_real_escape_string($connection, $_POST['student_id']);
    $originals = mysqli_real_escape_string($connection, $_POST['originals']);
    update_originals_status_MAG($connection, $student_id, $originals);
}
if (isset($_POST['processed'])) {
  $student_id = mysqli_real_escape_string($connection, $_POST['student_id']);
  $processed = mysqli_real_escape_string($connection, $_POST['processed']);
  update_processed_status_MAG($connection, $student_id, $processed);
}
// Поиск студентов по их имени
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($connection, $_GET['search']);
    $students = search_students_by_name_MAG($connection, $_SESSION['user_id'], $search);
} else {
    $students = array(); // Создаем пустой массив, если нет результатов поиска
}
// Получение списка специальностей студентов
if (isset($_POST['student_id'])) {
    $student_id = mysqli_real_escape_string($connection, $_POST['student_id']);
    $specializations = getStudentSpecializations_MAG($connection, $student_id);
}
// Поиск студентов по их специальности
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($connection, $_GET['search']);
    $students = search_students_by_specialization_MAG($connection, $_SESSION['user_id'], $search);
} else {
    $students = array(); // Создаем пустой массив, если нет результатов поиска
}
// Поиск студентов по их оригиналам
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($connection, $_GET['search']);
    $students = search_students_with_originals_MAG($connection, $_SESSION['user_id'], $search);
} else {
    $students = array(); // Создаем пустой массив, если нет результатов поиска
}
// Получение студентов для текущего пользователя
$specializations = getStudentSpecializations_MAG($connection, $_SESSION['user_id']);
$search_specializations=getSpecializations_MAG($connection);
$students = get_students_by_user_id_MAG($connection, $_SESSION['user_id']);
// Проверяем, была ли отправлена форма поиска
if (isset($_POST['search_type'])) {
    // Получаем выбранный тип поиска
    $searchType = $_POST['search_type'];
    // Обработка поиска по имени
    if ($searchType === 'name') {
      $searchName = $_POST['search_name'];
      // Выполните необходимые действия для поиска по имени, например, запрос в базу данных
      $students = search_students_by_name_MAG($connection, $_SESSION['user_id'], $searchName);
    }
    // Обработка поиска по специальности
    if ($searchType === 'specialization') {
      $searchSpecialization = $_POST['search_specialization'];
      // Выполните необходимые действия для поиска по специальности, например, запрос в базу данных
      $students = search_students_by_specialization_MAG($connection, $_SESSION['user_id'], $searchSpecialization);
    }
    if ($searchType === 'originals') {
      $specializationName = $_POST['specialization_name'];
      $students = searchOriginalsBySpecialization_MAG($connection, $specializationName);
    }
}
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Создаем объект Spreadsheet
$spreadsheet = new Spreadsheet();

// Получаем активный лист
$sheet = $spreadsheet->getActiveSheet();

// Заполняем ячейки данными
$sheet->setCellValue('A1', 'ФИО');
$sheet->setCellValue('B1', 'Общие баллы');
$sheet->setCellValue('C1', 'Специальности');
$sheet->setCellValue('D1', 'Номер телефона');
$sheet->setCellValue('E1', 'Статус сдачи оригинала');
$sheet->setCellValue('F1', 'Статус обработки');

// Пример заполнения данными из вашей функции generate_students_table
$row = 2;
foreach ($students as $student) {
    $fullName = $student['last_name'] . ' ' . $student['first_name'] . ' ' . $student['middle_name'];
    $specializations = getStudentSpecializations($connection, $student['student_id']);
    $specializationNames = array_column($specializations, 'specialization_name');
    
    $sheet->setCellValue('A' . $row, $fullName);
    $sheet->setCellValue('B' . $row, $student['rezults']);
    $sheet->setCellValue('C' . $row, implode(', ', $specializationNames));
    $sheet->setCellValue('D' . $row, $student['phone_number']);
    $sheet->setCellValue('E' . $row, empty($student['originals']) || $student['originals'] == '0' ? 'Не сдано' :  $student['originals'] );
    $sheet->setCellValue('F' . $row, empty($student['processed']) || $student['processed'] == '0' ? 'Не обработан' : 'Обработан');
    
    $row++;
}
// Автоматически подгоняем размеры колонок
$columnIterator = $sheet->getColumnIterator();
foreach ($columnIterator as $column) {
    $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
}
// Выравниваем все ячейки по центру
$styleArray = [
    'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
    ],
];

$cellRange = 'A1:F' . ($row - 1);
$sheet->getStyle($cellRange)->applyFromArray($styleArray);
// Создаем объект Writer для сохранения в файл
$writer = new Xlsx($spreadsheet);

// Формируем имя файла с текущей датой и временем
$filename = 'Абитуриенты ' . date('d-m-Y') . ' Магистратура'. '.xlsx';

// Отправляем файл в браузер для скачивания
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
exit;
?>
