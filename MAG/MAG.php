<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
include_once ("../configs/config.php");
include_once ("../configs/functions.php");
include_once ("../configs/db_connect.php");

// Обработка добавления абитуриента
if (isset($_POST['full_name']) && isset($_POST['Phone_number']) && isset($_POST['description']) && isset($_POST['form_submission'])) {
  // Получение данных из формы
  $full_name = mysqli_real_escape_string($connection, $_POST['full_name']);
  $phone_number = mysqli_real_escape_string($connection, $_POST['Phone_number']);
  $phone_number_formatted = phonenumber_format($phone_number); // Применение функции phonenumber_format
 // Пример использования функции
$rezults_input = $_POST['rezults']; // Получаем значение из формы
$rezults_formatted = rezult_format($rezults_input);
  $name_parts = explode(' ', $full_name);
  $first_name = mysqli_real_escape_string($connection, $name_parts[1]);
  $last_name = mysqli_real_escape_string($connection, $name_parts[0]);
  $middle_name = mysqli_real_escape_string($connection, $name_parts[2]);
  $description = mysqli_real_escape_string($connection, $_POST['description']);
  $form_submission = mysqli_real_escape_string($connection, $_POST['form_submission']);
  $languages = isset($_POST['languages']) ? implode(', ', $_POST['languages']) : '';
  $languages = mysqli_real_escape_string($connection, $languages); // Экранируем специальные символы в строке с языками
  add_student_MAG($connection, $first_name, $last_name, $middle_name, $_SESSION['user_id'], $phone_number_formatted, $rezults_formatted ,$languages, $description, $form_submission);
  if (mysqli_error($connection)) {
      die(mysqli_error($connection));
  }
}
if (isset($_POST['specialization_id'])) {
	$specializations = $_POST['specialization_id'];
	$student_id = mysqli_insert_id($connection);
	 // Добавляем специальности
	 addSpecializationsToStudent_MAG($connection, $student_id, $specializations);
   }
// Обработка изменения статуса сдачи оригиналов
if (isset($_POST['student_id']) && isset($_POST['originals'])) {
    $student_id = mysqli_real_escape_string($connection, $_POST['student_id']);
    $originals = mysqli_real_escape_string($connection, $_POST['originals']);
    update_originals_status_MAG($connection, $student_id, $originals);
}
// Получение списка абитуриентов для текущего пользователя
$students = get_students_by_user_id_MAG($connection, $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html>
<head>
	<title>Абитуриенты РЭУ</title>
	<!-- Подключаем стили Bootstrap -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
	<!-- Навигационное меню -->
  <nav class="navbar navbar-expand-lg navbar-light bg-light">
  <a class="navbar-brand" href="../index.php">На главную</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <ul class="navbar-nav">
  <li class="nav-item">
        <a class="nav-link ml-auto" href="../MAG/MAG.php">Добавить абитуриента</a>
      </li>
      <li class="nav-item">
        <a class="nav-link ml-auto" href="../MAG/entrants_MAG.php">Список абитуриентов</a>
      </li>
</ul>
  <div class="collapse navbar-collapse" id="navbarNav">
    <ul class="navbar-nav ml-auto">
      <li class="nav-item mr-auto">
        <a class="nav-link" href="../logout.php">Выйти</a>
      </li>
    </ul>
  </div>
</nav>
	<div class="container mt-4">
  <h4> Информация о последнем добавленном абитуриенте: </h4>
  <?php 
  if (getlastaddstudent($connection,$_SESSION['user_id'])!=null){
    $student = getlastaddstudent($connection, $_SESSION['user_id']);
    echo "ФИО: ", $student['first_name'] . ' ' . $student['last_name'] . ' ' . $student['middle_name'] . ' ';
    $specialization_laststudents = getStudentSpecializationsForLastStudent($connection);
    echo "<br>";
    echo "Специальности: ";
    $count = count($specialization_laststudents);
    foreach ($specialization_laststudents as $key => $specialization) {
        echo $specialization['application_number'];
        echo $specialization['specialization_name'];
        if ($key < $count - 1) {
            echo ', ';
        }
    }
  }
  else {
    echo "Абитуриенты не добавлены";
  }
?>
 <div class ="border-top mb-4"></div>
		<h3>Форма добавления абитуриента на магистратуру</h3>
		<form method="post">
    <div class="form-group">
				<label for="full_name">ФИО</label>
				<input type="text" class="form-control" id="full_name" name="full_name" required placeholder="Фамилия Имя Отчество">
			</div>
      <div class="form-group">
      <label for="Phone_number">Номер телефона</label>
    <input type="tel" class="form-control" id="Phone_number" name="Phone_number" required placeholder="89501234567" pattern="[0-9]{11}" title="Номер телефона должен содержать 11 цифр">
</div>
      <div class ="border-top mb-4"></div>
      <div class="form-group">
    <label for="rezults">Общие баллы</label>
    <input type="number" class="form-control" id="rezults" name="rezults" min="0" required placeholder="Введите значение">
</div>
<label><h5>Направление обучения</h5></label>
			<div class ="border-top mb-4"></div>
        <div class="row">
<div class = "col">
<label><h6>Очно-договор</h6></label>
          <div class="form-check">
  <input class="form-check-input" type="checkbox" id="specialization2" name="specialization_id[]" value="2">
  <label class="form-check-label" for="specialization2">Экономика</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="checkbox" id="specialization3" name="specialization_id[]" value="3">
  <label class="form-check-label" for="specialization3">Менеджмент</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="checkbox" id="specialization4" name="specialization_id[]" value="4">
  <label class="form-check-label" for="specialization4">Торговое дело</label>
</div>
</div>
<div class = "col">
<label><h6>Заочно-договор</h6></label>
          <div class="form-check">
  <input class="form-check-input" type="checkbox" id="specialization7" name="specialization_id[]" value="7">
  <label class="form-check-label" for="specialization7">Экономика</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="checkbox" id="specialization8" name="specialization_id[]" value="8">
  <label class="form-check-label" for="specialization8">Менеджмент</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="checkbox" id="specialization9" name="specialization_id[]" value="9">
  <label class="form-check-label" for="specialization9">Торговое дело</label>
</div>
</div>
		  </div>
		  <div class ="border-top mb-4"></div>
        <h5>Иностранный язык</h5>
        <form method="post">
        <div class="form-check">
    <input class="form-check-input" type="checkbox" id="Английский" name="languages[]" value="Английский">
    <label class="form-check-label" for="Английский"> Английский </label>
</div>
<div class="form-check">
    <input class="form-check-input" type="checkbox" id="Немецкий" name="languages[]" value="Немецкий">
    <label class="form-check-label" for="Немецкий"> Немецкий </label>
</div>
<div class="form-check">
    <input class="form-check-input" type="checkbox" id="Французский" name="languages[]" value="Французский">
    <label class="form-check-label" for="Французский"> Французский </label>
</div>
<!-- Дополнительное поле для своего варианта -->
<div class="form-check">
    <input class="form-check-input" type="checkbox" id="СвойВариант" name="languages[]" value="">
    <label class="form-check-label" for="СвойВариант"> Свой вариант </label>
    <!-- Дополнительное поле ввода для своего варианта -->
    <input type="text" class="form-control" id="СвойВариантText" name="languages_text" placeholder="Введите свой вариант" style="display: none;" oninput="updateCheckboxValue(this)">
</div>

<script>
// Получаем элементы checkbox и поле ввода
var checkbox = document.getElementById('СвойВариант');
var inputField = document.getElementById('СвойВариантText');

// Добавляем обработчик события изменения значения текстового поля
function updateCheckboxValue(inputElement) {
    checkbox.value = inputElement.value;
}

// Добавляем обработчик события изменения состояния checkbox
checkbox.addEventListener('change', function() {
    // Если checkbox выбран, отображаем поле ввода, иначе скрываем его
    inputField.style.display = checkbox.checked ? 'block' : 'none';
});
</script>
		  <div class ="border-top mb-4"></div>
        <h5>Форма подачи документов</h5>
        <form method="post">
          <div class="form-check">
            <input class="form-check-input" type="radio" id="Лично" name="form_submission" value="Лично">
			<label class="form-check-label" for="Лично"> Лично </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" id="Почта" name="form_submission" value="Почта">
			 <label class="form-check-label" for="Почта"> Почта </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" id="Личный кабинет абитуриента" name="form_submission" value="Личный кабинет абитуриента">
			<label class="form-check-label" for="Личный кабинет абитуриента"> Личный кабинет абитуриента </label>
          </div>
		  <div class="form-check">
            <input class="form-check-input" type="radio" id="Сервис ЕПГУ" name="form_submission" value="Сервис ЕПГУ">
			<label class="form-check-label" for="Сервис ЕПГУ"> Сервис ЕПГУ </label>
          </div>
		  <div class ="border-top mb-4"></div>
		<div class="form-group">
				<label for="description">Комментарий</label>
				<input type="text" class="form-control" id="description" name="description">
			</div>
			<button type="submit"  style='border-radius: 30px;background-color: #1864B0;'class="btn btn-primary">Добавить</button>
		</form>
	</div>

	<!-- Подключаем скрипты Bootstrap -->
	<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>