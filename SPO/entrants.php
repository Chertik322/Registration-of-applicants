<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
include_once ("../configs/config.php");
include_once ("../configs/functions.php");
include_once ("../configs/db_connect.php");

// Обработка изменения статуса задачи
if (isset($_POST['originals'])) {
    $student_id = mysqli_real_escape_string($connection, $_POST['student_id']);
    $originals = mysqli_real_escape_string($connection, $_POST['originals']);
    update_originals_status($connection, $student_id, $originals);
}
if (isset($_POST['processed'])) {
  $student_id = mysqli_real_escape_string($connection, $_POST['student_id']);
  $processed = mysqli_real_escape_string($connection, $_POST['processed']);
  update_processed_status($connection, $student_id, $processed);
}
// Поиск студентов по их имени
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($connection, $_GET['search']);
    $students = search_students_by_name($connection, $_SESSION['user_id'], $search);
} else {
    $students = array(); // Создаем пустой массив, если нет результатов поиска
}
// Получение списка специальностей студентов
if (isset($_POST['student_id'])) {
    $student_id = mysqli_real_escape_string($connection, $_POST['student_id']);
    $specializations = getStudentSpecializations($connection, $student_id);
}
// Поиск студентов по их специальности
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($connection, $_GET['search']);
    $students = search_students_by_specialization($connection, $_SESSION['user_id'], $search);
} else {
    $students = array(); // Создаем пустой массив, если нет результатов поиска
}
// Поиск студентов по их оригиналам
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($connection, $_GET['search']);
    $students = search_students_with_originals($connection, $_SESSION['user_id'], $search);
} else {
    $students = array(); // Создаем пустой массив, если нет результатов поиска
}
// Получение студентов для текущего пользователя
$specializations = getStudentSpecializations($connection, $_SESSION['user_id']);
$search_specializations=getSpecializations($connection);
$students = get_students_by_user_id($connection, $_SESSION['user_id']);
// Проверяем, была ли отправлена форма поиска
if (isset($_POST['search_type'])) {
    // Получаем выбранный тип поиска
    $searchType = $_POST['search_type'];
    // Обработка поиска по имени
    if ($searchType === 'name') {
      $searchName = $_POST['search_name'];
      // Выполните необходимые действия для поиска по имени, например, запрос в базу данных
      $students = search_students_by_name($connection, $_SESSION['user_id'], $searchName);
    }
    // Обработка поиска по специальности
    if ($searchType === 'specialization') {
      $searchSpecialization = $_POST['search_specialization'];
      // Выполните необходимые действия для поиска по специальности, например, запрос в базу данных
      $students = search_students_by_specialization($connection, $_SESSION['user_id'], $searchSpecialization);
    }
    if ($searchType === 'originals') {
      $specializationName = $_POST['specialization_name'];
      $students = searchOriginalsBySpecialization($connection, $specializationName);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
	<title>Абитуриенты РЭУ</title>
	<!-- Подключаем стили Bootstrap -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="../style.css">
  <style>
        body {
            color: #0B2D50; /* Цвет текста */
            font-family: 'Arial', sans-serif; /* Шрифт */
            background-color: #ECEDF0;
        }
        h1, h2, h3, h4, h5, h6, h7 {
  font-family: 'Arial Narrow', sans-serif;
}
.form-control{
  border-radius: 30px;
}
/* Основные стили */
.navbar-custom {
  font-family: 'Arial';
  background-color: #0B2D50;
  color: #ffffff; /* Цвет текста */
}

.navbar-custom .navbar-brand {
  color: #ffffff; /* Цвет текста для ссылки "На главную" */
}

.navbar-custom .navbar-nav .nav-link {
  color: #ffffff; /* Цвет текста для ссылок в навигационном меню */
}

/* Стили при наведении */
.navbar-custom .navbar-nav .nav-link:hover {
  color: #C69F5A; /* Серый цвет при наведении */
}
.navbar-custom .navbar-brand:hover {
  color: #C69F5A; /* Серый цвет при наведении для .navbar-brand */
}
button:hover img {
        filter: brightness(1.5); /* Увеличиваем яркость на 50% при наведении */
    }
    
    </style>
</head>
<body>

	<!-- Навигационное меню -->
  <nav class="navbar navbar-expand-lg navbar-light navbar-custom">
  <a class="navbar-brand" href="../index.php">На главную</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <ul class="navbar-nav">
  <li class="nav-item">
        <a class="nav-link ml-auto" href="../SPO/SPO.php">Добавить абитуриента</a>
      </li>
      <li class="nav-item">
        <a class="nav-link ml-auto" href="../SPO/entrants_SPO.php">Список абитуриентов</a>
      </li>
</ul>
  <div class="collapse navbar-collapse" id="navbarNav">
    <ul class="navbar-nav ml-auto">
    <form method="post" action="export.php">
    <!-- Указываем путь к файлу обработчику -->
    <button type="submit" style="border: none; background: none; padding: 0; margin: 0;">
        <img src="../resourses/excel.png" alt="Excel" width="45" height="45" style="border-radius: 30px; margin-right: 10px">
    </button>
</form>
    <button id="showSearchContainerButton" class="btn btn-primary" style='border-radius: 30px;background-color: #1864B0; margin-right: 10px'>Поиск</button>
      <li class="nav-item mr-auto">
        <a class="nav-link" href="../logout.php">Выйти</a>
      </li>
    </ul>
  </div>
</nav>

  <!-- Подключаем скрипты jQuery и Bootstrap -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<!-- Форма поиска по специальности -->
<div class="container">
  <div id="searchContainer" style="display: none;">
    <form method="POST">
      <div class="form-group" style="margin-top: 10px">
        <label for="search_type">Тип поиска:</label>
        <select class="form-control" id="search_type" name="search_type">
          <option value="name">По имени</option>
          <option value="specialization">По специальности</option>
          <option value="originals">По оригиналам</option>
        </select>
      </div>
      <div class="form-group" id="name_search_group">
        <label for="search_name">Имя:</label>
        <input type="text" class="form-control" id="search_name" name="search_name">
      </div>
      <div class="form-group" id="specialization_search_group" style="display: none;">
        <label for="search_specialization">Специальность:</label>
        <select class="form-control" id="search_specialization" name="search_specialization">
          <option value="">Выберите специальность</option>
          <?php foreach ($search_specializations as $specialization): ?>
            <option value="<?php echo $specialization['specialization_id']; ?>">
              <?php echo $specialization['specialization_name']; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" id="search_originals_group">
        <label for="specialization_name">Специальность:</label>
        <select class="form-control" id="specialization_name" name="specialization_name">
          <option value="">Выберите специальность</option>
          <?php foreach ($search_specializations as $specialization): ?>
            <option value="<?php echo $specialization['specialization_name']; ?>">
              <?php echo $specialization['specialization_name']; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary" style='border-radius: 30px;background-color: #1864B0; margin-right: 10px'>Поиск</button>
    </form>
  </div>
</div>

<script>
  document.getElementById('showSearchContainerButton').addEventListener('click', function() {
    var searchContainer = document.getElementById('searchContainer');
    if (searchContainer.style.display === 'none') {
      searchContainer.style.display = 'block';
    } else {
      searchContainer.style.display = 'none';
    }
  });
</script>
<style>
  
  </style>
<!-- Список абитуриентов -->
<div class="border-top border-0 mb-2" ></div>
<?php if (!empty($students)): ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th class="border-left">Обработано</th>
                    <th class="border-left"><a href="#" class="sort-link" data-column="student-number">Номер студента</a></th>
                    <th class="border-left">ФИО</th>
                    <th class="border-left"><a href="#" class="sort-link" data-column="rezults">Средний балл</a></th>
                    <th class="border-left">Специальности</th>
                    <th class="border-left">Номер телефона</th>
                    <th class="border-left">Иностранный язык</th>
                    <th class="border-left">Комменатрий</th>
                    <th class="border-left border-right">Оригинал</th>
                </tr>
            </thead>
            <tbody id="students-table">
                <?php echo generate_students_table($students, $connection); ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p>У вас пока нет абитуриентов.</p>
<?php endif; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
<!-- Модальное окно -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Выберите тему и сообщение</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="messageForm">
          <div class="form-group">
            <label for="messageTopic">Тема:</label>
            <select class="form-control" id="messageTopic" name="messageTopic">
              <option value="1">1 сентября</option>
              <option value="2">Не бюджет</option>
              <option value="3">Бюджет</option>
              <option value="4">Договор</option>
            </select>
          </div>
          <div class="form-group">
            <label for="messageOption">Выберите вариант сообщения:</label>
            <select class="form-control" id="messageOption" name="messageOption"></select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-dismiss="modal" id="closeModalButton">Закрыть</button>
        <button type="button" class="btn btn-primary" id="copyMessage">Копировать в буфер обмена</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var sortLinks = document.querySelectorAll('.sort-link');
  sortLinks.forEach(function(link) {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      var column = this.getAttribute('data-column');
      sortTable(column, this);
    });
  });
});
function sortTable(column, link) {
  var table = document.querySelector('table');
  var tableBody = table.querySelector('tbody');
  var rows = Array.from(tableBody.querySelectorAll('tr'));

  rows.sort(function(rowA, rowB) {
    var cellA = rowA.querySelector('td[data-column="' + column + '"]');
    var cellB = rowB.querySelector('td[data-column="' + column + '"]');
    var valueA = getStudentNumber(cellA.textContent.trim());
    var valueB = getStudentNumber(cellB.textContent.trim());

    // Сортировка по номеру студента
    return valueA - valueB;
  });
  // Проверка состояния сортировки
  var isAscending = link.classList.contains('ascending');
  if (isAscending) {
    rows.reverse();
    link.classList.remove('ascending');
  } else {
    link.classList.add('ascending');
  }

  // Удаление текущих строк из таблицы
  while (tableBody.firstChild) {
    tableBody.firstChild.remove();
  }

  // Добавление отсортированных строк в таблицу
  rows.forEach(function(row) {
    tableBody.appendChild(row);
  });
}

function getStudentNumber(cellValue) {
  // Извлечение первой цифровой последовательности из строки
  var numberPattern = /\d+/g;
  var numbers = cellValue.match(numberPattern);
  if (numbers && numbers.length > 0) {
    return parseInt(numbers[0]);
  }
  return 0; // Возвращаем 0, если цифровая последовательность не найдена
}

</script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const searchTypeSelect = document.getElementById('search_type');
    const nameSearchGroup = document.getElementById('name_search_group');
    const specializationSearchGroup = document.getElementById('specialization_search_group');
    const searchOriginalsGroup = document.getElementById('search_originals_group');
    const savedSearchType = localStorage.getItem('search_type');
    if (savedSearchType) {
      searchTypeSelect.value = savedSearchType;
      if (savedSearchType === 'name') {
        nameSearchGroup.style.display = 'block';
        specializationSearchGroup.style.display = 'none';
        searchOriginalsGroup.style.display = 'none';
      } else if (savedSearchType === 'specialization') {
        nameSearchGroup.style.display = 'none';
        specializationSearchGroup.style.display = 'block';
        searchOriginalsGroup.style.display = 'none';
      } else if (savedSearchType === 'originals') {
        nameSearchGroup.style.display = 'none';
        specializationSearchGroup.style.display = 'none';
        searchOriginalsGroup.style.display = 'block';
      }
    }
    searchTypeSelect.addEventListener('change', function() {
      const selectedSearchType = this.value;
      localStorage.setItem('search_type', selectedSearchType);
      if (selectedSearchType === 'name') {
        nameSearchGroup.style.display = 'block';
        specializationSearchGroup.style.display = 'none';
        searchOriginalsGroup.style.display = 'none';
      } else if (selectedSearchType === 'specialization') {
        nameSearchGroup.style.display = 'none';
        specializationSearchGroup.style.display = 'block';
        searchOriginalsGroup.style.display = 'none';
      } else if (selectedSearchType === 'originals') {
        nameSearchGroup.style.display = 'none';
        specializationSearchGroup.style.display = 'none';
        searchOriginalsGroup.style.display = 'block';
      }
    });
  });
</script>
<?php // Скрипт для редактирования ?>
<script>
$(document).ready(function() {
  // Обработка двойного щелчка на ячейке
  $('.editable').dblclick(function() {
    $(this).prop('contenteditable', true).focus();
  });

  // Обработка нажатия клавиши Enter в ячейке
  $('.editable').keypress(function(event) {
    var keycode = (event.keyCode ? event.keyCode : event.which);
    if (keycode == '13') {
      event.preventDefault();
      $(this).blur();
      var student_id = $(this).closest('tr').find('input[name="student_id"]').val();
      var column = $(this).data('column');
      var value = $(this).html();

      if (column === 'rezults') {
        if (value >= 3 && value <= 5) {
          updateStudentData(student_id, column, value);
        } else {
          // Вывод предупреждения при некорректном значении
          alert('Значение должно быть от 3 до 5');
        }
      } else if (column === 'phone_number') {
        if (value.startsWith('+7') && value.length === 12 && /^\+7\d+$/.test(value)) {
          updateStudentData(student_id, column, value);
        } else {
          // Вывод предупреждения при некорректном значении
          alert('Номер телефона должен начинаться с "+7" и состоять из 10 цифр');
        }
      } else if (column === 'name') {
        var nameParts = value.split(' ');
        if (nameParts.length === 3) {
          updateStudentData(student_id, column, value);
        } else {
          // Вывод предупреждения при некорректном значении
          alert('ФИО должно состоять из трех частей - Фамилии, Имени и Отчества');
        }
      } else {
        updateStudentData(student_id, column, value);
      }
    }
  });
  function rezult_format(value) {
  // Ваш код форматирования, например:
  return parseFloat(value).toFixed(4);
}

  // Обновление данных студента
  function updateStudentData(student_id, column, value) {
    var lastName = '';
    var firstName = '';
    var middleName = '';
    var phoneNumber = '';
    var description = '';
    var foreign_language = '';
    var rezults='';

    if (column === 'name') {
      var nameParts = value.split(' ');
      lastName = nameParts[0] || '';
      firstName = nameParts[1] || '';
      middleName = nameParts[2] || '';
    } else if (column === 'phone_number') {
      phoneNumber = value;
    } else if (column === 'description') {
      description = value;
    } else if (column === 'foreign_language') {
      foreign_language = value; 
    } else if (column === 'rezults') {
      rezults = rezult_format(value);
    }

    $.ajax({
      url: 'save_changes.php',
      type: 'POST',
      data: {
        student_id: student_id,
        column: column,
        lastName: lastName,
        firstName: firstName,
        middleName: middleName,
        phoneNumber: phoneNumber,
        description: description,
        foreign_language: foreign_language,
        rezults: rezults
      },
      success: function(response) {
        console.log(response);
      }
    });
  }
});
</script>
<script>
$(document).ready(function() {
  // Обработчик события клика на кнопку "Подробнее"
  $('.btn-link').click(function() {
    var studentId = $(this).data('target').split('_')[1];

    // Сохраняем ссылку на текущую кнопку для последующего использования внутри функции success
    var currentButton = $(this);

    // Проверяем, есть ли уже блок с журналом действий для этого абитуриента
    if (currentButton.next('.actions').length) {
      // Блок уже существует, значит, нужно его удалить (скрыть) и прекратить выполнение функции
      currentButton.next('.actions').remove();
      return;
    }

    // Отправляем AJAX-запрос для получения комментариев и журнала действий
    $.ajax({
      url: 'details.php', // Замените на вашу обработчик запросов для получения данных о студенте
      method: 'POST',
      data: { student_id: studentId },
      success: function(response) {
        // Создаем элемент для вывода журнала действий и добавляем его после текущей кнопки
        var actionsElement = $('<div class="actions"></div>').html(response);
        currentButton.after(actionsElement);
      },
      error: function() {
        alert('Ошибка при загрузке данных о студенте');
      }
    });
  });
});

</script>

<script>
function insertOrUpdateSpecializationToStudent(student_id, specialization_id) {
  $.ajax({
    url: 'add_specialization.php',
    type: 'POST',
    data: {
      student_id: student_id,
      specialization_id: specialization_id
    },
    success: function(response) {
      console.log(response); // Выводим ответ сервера в консоль для отладки
      if (response === "success") {
        console.log("Специализация успешно добавлена или обновлена");
        location.reload(true);
      } else if (response === "exists") {
        console.log("Специализация уже существует у студента");
        alert("Специализация уже существует у студента"); // Выводим предупреждение на экран
      } else {
        console.log("Ошибка при добавлении или обновлении специализации");
      }
    },
    error: function(xhr, status, error) {
      console.log("Ошибка при выполнении AJAX-запроса: " + error);
    }
  });
}

function deleteSpecializationFromStudent(student_id, specialization_id) {
  $.ajax({
    url: 'delete_specialization.php',
    type: 'POST',
    data: {
      student_id: student_id,
      specialization_id: specialization_id
    },
    success: function(response) {
      console.log(response); // Выводим ответ сервера в консоль для отладки
      if (response === "success") {
        console.log("Специализация успешно удалена");
        location.reload(true);
      } else {
        console.log("Ошибка при удалении специализации");
      }
    },
    error: function(xhr, status, error) {
      console.log("Ошибка при выполнении AJAX-запроса: " + error);
    }
  });
}

$(document).ready(function() {
  // Отображение/скрытие меню при клике на кнопке
  $('.dropdown-toggle').click(function() {
    $(this).siblings('.dropdown-menu').toggle();
  });

  // Обработчик клика по документу
  $(document).on('click', function(event) {
    var target = $(event.target);

    // Проверяем, является ли целью клика элемент выпадающего меню или кнопка
    if (!target.closest('.dropdown-menu').length && !target.hasClass('dropdown-toggle')) {
      // Скрываем все выпадающие меню
      $('.dropdown-menu').hide();
    }
  });

  // Обработчик клика по элементу с классом 'specialization-option'
  $('.specialization-option').click(function(e) {
    e.preventDefault();
    var student_id = $(this).data('student_id');
    var specialization_id = $(this).data('specialization_id');
    var specialization = $(this).data('specialization');

    $('#specializationButton_' + student_id).text(specialization);
    insertOrUpdateSpecializationToStudent(student_id, specialization_id);
    $(this).closest('.dropdown-menu').hide();
  });

  // Обработчик клика по элементу с классом 'delete-specialization-option'
  $('.delete-specialization-option').click(function(e) {
    e.preventDefault();
    var student_id = $(this).data('student_id');
    var specialization_id = $(this).data('specialization_id');
    var specialization = $(this).text();

    $('#deleteSpecializationButton_' + student_id).text(specialization);
    deleteSpecializationFromStudent(student_id, specialization_id);
    $(this).closest('.dropdown-menu').hide();
  });
});
</script>


<script>
  function insertActionToLog(studentId, iconType) {
    var data = {
      student_id: studentId,
      icon_type: iconType
    };

    $.ajax({
      url: 'add_action_to_log.php',
      type: 'POST',
      data: data,
      success: function(response) {
        console.log(response);
        if (response === 'success') {
        } else {
          console.log('Ошибка при добавлении записи в лог');
        }
      },
      error: function(xhr, status, error) {
        console.log('Ошибка при выполнении AJAX-запроса: ' + error);
      }
    });
  }

  var actionLogIcons = document.getElementsByClassName('action-log-icon');
  
  for (var i = 0; i < actionLogIcons.length; i++) {
    actionLogIcons[i].addEventListener('click', function(event) {
      var studentId = this.getAttribute('data-student-id');
      var iconType = this.getAttribute('data-icon-type');
      
      insertActionToLog(studentId, iconType);
    });
  }
</script>
<script>
document.getElementById('messageTopic').addEventListener('change', function() {
  var selectedTopic = this.value;
  var messageOptionSelect = document.getElementById('messageOption');
  
  // Очищаем текущие варианты сообщений
  messageOptionSelect.innerHTML = '';

  // Загружаем новые варианты сообщений в зависимости от выбранной темы
  var messages = getMessages(selectedTopic);
  messages.forEach(function(message, index) {
    var option = document.createElement('option');
    option.value = index + 1;
    option.textContent = message;
    messageOptionSelect.appendChild(option);
  });
});

document.getElementById('copyMessageButton').addEventListener('click', function() {
  var selectedTopic = document.getElementById('messageTopic').value;
  var selectedOption = document.getElementById('messageOption').value;

  // Получаем соответствующее сообщение для выбранной темы и варианта
  var message = getMessage(selectedTopic, selectedOption);

  // Получаем имя студента из атрибута data-student-name
  var studentName = this.getAttribute('data-student-name');

  // Заменяем '(имя студента)' на реальное имя
  message = message.replace('(имя студента)', studentName);

  // Создаем временный элемент textarea и помещаем в него текст сообщения
  var tempTextArea = document.createElement('textarea');
  tempTextArea.value = message;
  document.body.appendChild(tempTextArea);

  // Выделяем текст в textarea
  tempTextArea.select();

  try {
    // Используем API Clipboard для копирования текста в буфер обмена
    navigator.clipboard.writeText(tempTextArea.value).then(function() {
      console.log('Сообщение скопировано в буфер обмена');

      // Закрываем модальное окно программно
      document.getElementById('closeModalButton').click();
    }).catch(function(err) {
      console.error('Не удалось скопировать сообщение в буфер обмена', err);
    });
  } catch (err) {
    console.error('Не удалось скопировать сообщение в буфер обмена', err);
  } finally {
    // Удаляем временный элемент textarea
    document.body.removeChild(tempTextArea);
  }
});

// Функция для получения сообщений по теме
function getMessages(topic) {
  var studentName = document.getElementById('copyMessageButton').getAttribute('data-student-name');
  var messages = {
    '1': [
      'Привет ' + studentName + ', приходите в Плеханова на линейку',
      '...',
      '...',
      '...'
    ],
    '2': [
      '...',
      studentName + ', к сожалению, ты не прошел на бюджет',
      '...',
      '...'
    ],
    '3': [
      '...',
      '...',
      studentName + ', поздравляю, ты прошел на бюджет!',
      '...'
    ],
    '4': [
      '...',
      '...',
      '...',
      'Поздравляю, ' + studentName + ', оплати обучение'
    ]
  };
  
  return messages[topic] || [];
}


// Функция для получения сообщения по теме и варианту
function getMessage(topic, option) {
  var messages = getMessages(topic);
  return messages[option - 1] || '';
}
</script>
</div>
</div>
</body>
</html>