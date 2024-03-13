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
if (isset($_POST['student_id']) && isset($_POST['originals'])) {
    $student_id = mysqli_real_escape_string($connection, $_POST['student_id']);
    $originals = mysqli_real_escape_string($connection, $_POST['originals']);
    update_originals_status_BAC($connection, $student_id, $originals);
}
if (isset($_POST['processed'])) {
  $student_id = mysqli_real_escape_string($connection, $_POST['student_id']);
  $processed = mysqli_real_escape_string($connection, $_POST['processed']);
  update_processed_status_BAC($connection, $student_id, $processed);
}
// Поиск студентов по их имени
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($connection, $_GET['search']);
    $students = search_students_by_name_BAC($connection, $_SESSION['user_id'], $search);
} else {
    $students = array(); // Создаем пустой массив, если нет результатов поиска
}
// Получение списка специальностей студентов
if (isset($_POST['student_id'])) {
    $student_id = mysqli_real_escape_string($connection, $_POST['student_id']);
    $specializations = getStudentSpecializations_BAC($connection, $student_id);
}
// Поиск студентов по их специальности
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($connection, $_GET['search']);
    $students = search_students_by_specialization_BAC($connection, $_SESSION['user_id'], $search);
} else {
    $students = array(); // Создаем пустой массив, если нет результатов поиска
}
// Поиск студентов по их оригиналам
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($connection, $_GET['search']);
    $students = search_students_with_originals_BAC($connection, $_SESSION['user_id'], $search);
} else {
    $students = array(); // Создаем пустой массив, если нет результатов поиска
}
// Получение студентов для текущего пользователя
$specializations = getStudentSpecializations_BAC($connection, $_SESSION['user_id']);
$search_specializations=getSpecializations_BAC($connection);
$students = get_students_by_user_id_BAC($connection, $_SESSION['user_id']);
// Проверяем, была ли отправлена форма поиска
if (isset($_POST['search_type'])) {
    // Получаем выбранный тип поиска
    $searchType = $_POST['search_type'];
    // Обработка поиска по имени
    if ($searchType === 'name') {
      $searchName = $_POST['search_name'];
      // Выполните необходимые действия для поиска по имени, например, запрос в базу данных
      $students = search_students_by_name_BAC($connection, $_SESSION['user_id'], $searchName);
    }
    // Обработка поиска по специальности
    if ($searchType === 'specialization') {
      $searchSpecialization = $_POST['search_specialization'];
      // Выполните необходимые действия для поиска по специальности, например, запрос в базу данных
      $students = search_students_by_specialization_BAC($connection, $_SESSION['user_id'], $searchSpecialization);
    }
    if ($searchType === 'originals') {
      $specializationName = $_POST['specialization_name'];
      $students = searchOriginalsBySpecialization_BAC($connection, $specializationName);
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
        <a class="nav-link ml-auto" href="../BAC/BAC.php">Добавить абитуриента</a>
      </li>
      <li class="nav-item">
        <a class="nav-link ml-auto" href="../BAC/entrants_BAC.php">Список абитуриентов</a>
      </li>
</ul>
  <div class="collapse navbar-collapse" id="navbarNav">
    <ul class="navbar-nav ml-auto">
    <form method="post" action="export_BAC.php"> <!-- Указываем путь к файлу обработчику -->
    <button type="submit" style="border: none; background: none; padding: 0; margin: 0;">
        <img src="../resourses/excel.png" alt="Excel" width="45" height="45" style="border: none; outline: none;">
    </button>
</form>
    <button id="showSearchContainerButton" class="btn btn-primary">Поиск</button>
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
      <div class="form-group">
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
      <button type="submit" class="btn btn-primary">Поиск</button>
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
<!-- Список задач -->
<div class="border-top border-0 mb-2"></div>
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
                <?php 
                 echo generate_students_table_BAC($students, $connection); ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p>У вас пока нет абитуриентов.</p>
<?php endif; ?>
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
    var valueA = getStudentNumber_BAC(cellA.textContent.trim());
    var valueB = getStudentNumber_BAC(cellB.textContent.trim());

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

function getStudentNumber_BAC(cellValue) {
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

    // Проверяем, есть ли сохраненное значение в localStorage
    const savedSearchType = localStorage.getItem('search_type');
    if (savedSearchType) {
      // Устанавливаем сохраненное значение в select
      searchTypeSelect.value = savedSearchType;

      // В зависимости от выбранного типа поиска, устанавливаем соответствующую видимость формы
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

    // Обработчик события изменения выбора типа поиска
    searchTypeSelect.addEventListener('change', function() {
      const selectedSearchType = this.value;

      // Сохраняем выбранный тип поиска в localStorage
      localStorage.setItem('search_type', selectedSearchType);

      // Устанавливаем видимость формы в соответствии с выбранным типом поиска
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
        if (value >= 90 && value <= 300) {
          updateStudentData_BAC(student_id, column, value);
        } else {
          // Вывод предупреждения при некорректном значении
          alert('Значение должно быть от 90 до 300');
        }
      } else if (column === 'phone_number') {
        if (value.startsWith('+7') && value.length === 12 && /^\+7\d+$/.test(value)) {
          updateStudentData_BAC(student_id, column, value);
        } else {
          // Вывод предупреждения при некорректном значении
          alert('Номер телефона должен начинаться с "+7" и состоять из 10 цифр');
        }
      } else if (column === 'name') {
        var nameParts = value.split(' ');
        if (nameParts.length === 3) {
          updateStudentData_BAC(student_id, column, value);
        } else {
          // Вывод предупреждения при некорректном значении
          alert('ФИО должно состоять из трех частей - Фамилии, Имени и Отчества');
        }
      } else {
        updateStudentData_BAC(student_id, column, value);
      }
    }
  });

  // Обновление данных студента
  function updateStudentData_BAC(student_id, column, value) {
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
      rezults = value;
    }

    $.ajax({
      url: 'save_changes_BAC.php',
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
      url: 'details_BAC.php', // Замените на вашу обработчик запросов для получения данных о студенте
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
function insertSpecializationToStudent_BAC(student_id, specialization_id) {
  $.ajax({
  url: 'add_specialization_BAC.php',
  type: 'POST',
  data: {
    student_id: student_id,
    specialization_id: specialization_id
  },
  success: function(response) {
    console.log(response); // Выводим ответ сервера в консоль для отладки
    if (response === "success") {
      console.log("Специализация успешно добавлена");
      location.reload(true);
    } else if (response === "exists") {
      console.log("Специализация уже существует у студента");
      alert("Специализация уже существует у студента"); // Выводим предупреждение на экран
    } else {
      console.log("Ошибка при добавлении специализации");
    }
  },
  error: function(xhr, status, error) {
    console.log("Ошибка при выполнении AJAX-запроса: " + error);
  }
});
}
  function deleteSpecializationFromStudent_BAC(student_id, specialization_id) {
    $.ajax({
      url: 'delete_specialization_BAC.php',
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
    $('.dropdown-toggle').click(function() {
      $(this).siblings('.dropdown-menu').toggle();
    });

    $('.specialization-option').click(function(e) {
      e.preventDefault();
      var student_id = $(this).data('student_id');
      var specialization_id = $(this).data('specialization_id');
      var specialization = $(this).data('specialization');

      $('#specializationButton_' + student_id).text(specialization);
      insertSpecializationToStudent_BAC(student_id, specialization_id);
      $(this).closest('.dropdown-menu').hide();
    });

    $('.delete-specialization-option').click(function(e) {
      e.preventDefault();
      var student_id = $(this).data('student_id');
      var specialization_id = $(this).data('specialization_id');
      var specialization = $(this).text();

      $('#deleteSpecializationButton_' + student_id).text(specialization);
      deleteSpecializationFromStudent_BAC(student_id, specialization_id);
      $(this).closest('.dropdown-menu').hide();
    });
  });
</script>
<script>
  function insertActionToLog_BAC(studentId, iconType) {
    var data = {
      student_id: studentId,
      icon_type: iconType
    };

    $.ajax({
      url: 'add_action_to_log_BAC.php',
      type: 'POST',
      data: data,
      success: function(response) {
        console.log(response); // Выводим ответ сервера в консоль для отладки
        if (response === 'success') {
          // Обработка успешного ответа от сервера, если нужно
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
      
      insertActionToLog_BAC(studentId, iconType);
    });
  }
</script>
<script>
document.querySelectorAll('.action-log-icon').forEach(function(icon) {
    icon.addEventListener('click', function(event) {
        var rezults = parseFloat(icon.dataset.rezults);

        var message = "Привет, ";

        if (rezults >= 90 && rezults < 150) {
            message += "предлагаем тебе поступить на договорную основу!";
        } else if (rezults >= 150 && rezults < 250) {
            message += "есть специальности на бюджет для тебя!";
        } else if (rezults >= 250 && rezults <= 300) {
            message += "ты можешь поступить на бюджет!";
        } else {
            message += "ты поступил!";
        }

        copyToClipboard(message);
        alert("Сообщение скопировано в буфер обмена: " + message);
    });

    icon.addEventListener('auxclick', function(event) {
        // Обработка события auxclick (средняя кнопка мыши) для открытия ссылки в новой вкладке
        if (event.button === 1) {
            window.open(icon.getAttribute('href'), '_blank');
        }
    });
});

function copyToClipboard(text) {
    var textarea = document.createElement("textarea");
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
}
</script>
</div>
</div>
</body>
</html>
