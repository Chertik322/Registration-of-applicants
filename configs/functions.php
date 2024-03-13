<?php
include_once ("config.php");
include_once ("db_connect.php");

function ajax_echo(
    $title = '',
    $text = '',
    $error = false,
    $type = 'ERROR',
    $other = null
) {
    return json_encode(array(
        "error" => $error,
        "type" => $type,
        "title" => $title,
        "desc" => $text,
        "other" => $other,
        "datetime" => array(
            'd' => date('d'),
            'm' => date('m'),
            'Y' => date('Y'),
            'H' => date('H'),
            'i' => date('i'),
            's' => date('s'),
            'full' => date('d-m-Y H:i:s'),
        )
    ));
}
function get_user_rights_status ($connection, $user_id){
    $stmt = mysqli_prepare($connection, "SELECT user_rights FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $rights_status);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return $rights_status;
}
function generate_students_table($students, $connection) {
  $html = '';
  foreach ($students as $student) {
      $html .= '<tr class="' . ($student['originals'] != 0 ? 'table-success' : '') . '">';
      $html .= '<td class="border-left">';
      $html .= '<form method="post">';
      $html .= '<input type="hidden" name="student_id" value="' . $student['student_id'] . '">';
      $html .= '<div class="form-group">';
      $html .= '<select class="form-control" name="processed" onchange="this.form.submit()">';
      $html .= '<option value="0" ' . ($student['processed'] == '0' ? 'selected' : '') . '>Не обработан</option>';
      $html .= '<option value="1" ' . ($student['processed'] == '1' ? 'selected' : '') . '>Обработан</option>';
      $html .= '</select>';
      $html .= '</div>';
      if ($student['processed'] != 0) {
          $html .= '<span> Дата обработки: <br>' . date('d-m-Y', strtotime($student['submission_processed'])) . '</br></span>';
      } else {
          $html .= '<span>Еще не обработан</span>';
      }
      $html .= '</form>';
      $html .= '</td>';
      $html .= '<td class="border-left" data-column="student-number">';
      $html .= $student['student_id'] . '<br/> Дата подачи заявления: <br>' . date('d-m-Y', strtotime($student['date_of_submission'])) . '</br>';
      $html .= '<br/>';
      $html .= '<button class="btn btn-link" data-toggle="modal" data-target="#actionLogModal_' . $student['student_id'] . '">Подробнее</button>';
      $html .= '</td>';
      $html .= '<td class="border-left editable" data-column="name">' . $student['last_name'] . ' ' . $student['first_name'] . ' ' . $student['middle_name'] . '</td>';
      $html .= '<td class="border-left sortable editable" data-column="rezults">' . $student['rezults'] . '</td>';
      $html .= '<td class="border-left">';
      $studentSpecializations = getStudentSpecializations($connection, $student['student_id']);
      if (!empty($studentSpecializations)) {
          $html .= '<div class="specialization-column">'; 
          foreach ($studentSpecializations as $specialization) {
            if($specialization['delete_status']!=1){
              $specialization_name = $specialization['specialization_name'];
              $application_number = $specialization['application_number'];
              $html .= '<div class="specialization-item">';
              $html .= $application_number . $specialization_name;
              $html .= '</div>';
            }
          }
          $html .= '</div>'; 

          $html .= '<div class="button-column d-flex">'; 
          $html .= '<div class="dropdown mr-0">';
          $html .= '<button class="btn dropdown-toggle" type="button" id="specializationButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">✔️</button>';
          $html .= '<div class="dropdown-menu" aria-labelledby="specializationButton">';
          $specializationclass1s = getSpecializationsClass1($connection);
          $class = getClassOfSpecialization($connection, $student['student_id']);
          $specializationclass0s = getSpecializationsClass0($connection);
          
          if (!empty($specializationclass0s) && $class === null) {
              foreach ($specializationclass0s as $specializationclass0) {
                  $specialization_id = $specializationclass0['specialization_id']; 
                  $specialization_name = $specializationclass0['specialization_name'];
                  // Проверяем, не имеется ли уже данная специализация у студента
                  if (!checkSpecializationExistsForStudent($connection, $student['student_id'], $specialization_id)) {
                      $html .= '<a class="dropdown-item specialization-option" href="#" data-student_id="' . $student['student_id'] . '" data-specialization_id="' . $specialization_id . '" data-specialization="' . $specialization_name . '">' . $specialization_name . '</a>';
                  }
              }
          }
          
          if ($class == 1) {
              if (!empty($specializationclass1s)) {
                  foreach ($specializationclass1s as $specializationclass1) {
                      $specialization_id = $specializationclass1['specialization_id']; 
                      $specialization_name = $specializationclass1['specialization_name'];
                      // Проверяем, не имеется ли уже данная специализация у студента
                      if (!checkSpecializationExistsForStudent($connection, $student['student_id'], $specialization_id)) {
                          $html .= '<a class="dropdown-item specialization-option" href="#" data-student_id="' . $student['student_id'] . '" data-specialization_id="' . $specialization_id . '" data-specialization="' . $specialization_name . '">' . $specialization_name . '</a>';
                      }
                  }
              }
          }
          
          
          $html .= '</div>';
          $html .= '</div>';
          $html .= '<div class="dropdown">';
          $html .= '<button class="btn dropdown-toggle delete-specialization-btn" type="button" id="deleteSpecializationButton_' . $student['student_id'] . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">❌</button>';
          $html .= '<div class="dropdown-menu delete-dropdown-menu" aria-labelledby="deleteSpecializationButton_' . $student['student_id'] . '">';
          // Код для выпадающего списка удаления специализаций
          $allstudentspecializations = getallstudentspecializations($connection, $student['student_id']);
          if (!empty($allstudentspecializations)) {
              foreach ($allstudentspecializations as $specialization) {
                  $specialization_id = $specialization['specialization_id']; // Используем идентификатор специализации вместо имени
                  $specialization_name = $specialization['specialization_name'];
                  $html .= '<a class="dropdown-item delete-specialization-option" href="#" data-student_id="' . $student['student_id'] . '" data-specialization_id="' . $specialization_id . '">' . $specialization_name . '</a>';
              }
          }
          $html .= '</div>';
          $html .= '</div>';
          $html .= '</div>'; // Закрываем обертывающий div для кнопок
      }
      $html .= '</td>';
      $html .= '<td class="border-left">';
      $html .= '<div class="editable"  data-column="phone_number">' . $student['phone_number'] . '</div>';
      $html .= '<br/>';
      $html .= '<a id="telegram-icon" href="https://t.me/' . $student['phone_number'] . '" class="action-log-icon" data-student-id="' . $student['student_id'] . '" data-icon-type="телеграмм" data-rezults="' . $student['rezults'] . '" target="_blank"><img src="../resourses/telegram.png" alt="telegram" width="32" height="32"></i></a>';
      $html .= '<a href="viber://chat?number=' . $student['phone_number'] . '" class="action-log-icon" data-student-id="' . $student['student_id'] . '" data-icon-type="вайбер" target="_blank"><img src="../resourses/viber.png" alt="viber" width="32" height="32"></a>';
      $html .= '<a href="https://wa.me/' . $student['phone_number'] . '" class="action-log-icon" data-student-id="' . $student['student_id'] . '" data-icon-type="ватсап" data-rezults="' . $student['rezults'] . '" target="_blank"><img src="../resourses/whatsapp.png" alt="WhatsApp" width="32" height="32"></a>';
      $html .= '<br/>';
      $html .= '<button id="copyMessageButton" class="btn btn-primary" data-toggle="modal" data-target="#myModal" data-student-id="' . $student['student_id'] . '" data-student-name="' . $student['first_name'] . '">Сообщение</button>';
      $html .= '</td>';      
    $html .= '<td class="editable border-left" data-column="foreign_language">' . $student['foreign_language'] . '</td>';
    $html .= '<td class="editable border-left" data-column="description">' . $student['description'] . '</td>';
    $html .= '<td class = "border-left">';
    $html .= '<form method="post">';
    $html .= '<input type="hidden" name="student_id" value="' . $student['student_id'] . '">';
    $html .= '<div class="form-group">';
    $html .= '<select class="form-control" name="originals" onchange="this.form.submit()">';
    $html .= '<option value="0" ' . ($student['originals'] == '0' ? 'selected' : '') . '>Не сдано</option>';
    foreach ($studentSpecializations as $specialization) {
      $specializationName = $specialization['specialization_name'];
      $isSelected = $student['originals'] == $specializationName;
      $html .= '<option value="' . $specializationName . '"' . ($isSelected ? ' selected' : '') . '>' . $specializationName . '</option>';
    }
    $html .= '</select>';
    $html .= '</div>';
    $html .= '</form>';

    if ($student['originals'] != 0) {
      $html .= '<span> Дата подачи оригиналов: <br>' . date('d-m-Y', strtotime($student['submission_originals'])) . '</br></span>';
    } else {
      $html .= '<span>Не сдано</span>';
    }

    $html .= '</td>';
  }
  return $html;
}
function generate_students_table_BAC($students, $connection) {
  $html = '';
  foreach ($students as $student) {
      $html .= '<tr class="' . ($student['originals'] != 0 ? 'table-success' : '') . '">';
      $html .= '<td class="border-left">';
      $html .= '<form method="post">';
      $html .= '<input type="hidden" name="student_id" value="' . $student['student_id'] . '">';
      $html .= '<div class="form-group">';
      $html .= '<select class="form-control" name="processed" onchange="this.form.submit()">';
      $html .= '<option value="0" ' . ($student['processed'] == '0' ? 'selected' : '') . '>Не обработан</option>';
      $html .= '<option value="1" ' . ($student['processed'] == '1' ? 'selected' : '') . '>Обработан</option>';
      $html .= '</select>';
      $html .= '</div>';
      if ($student['processed'] != 0) {
          $html .= '<span> Дата обработки: <br>' . date('d-m-Y', strtotime($student['submission_processed'])) . '</br></span>';
      } else {
          $html .= '<span>Еще не обработан</span>';
      }
      $html .= '</form>';
      $html .= '</td>';
      $html .= '<td class="border-left" data-column="student-number">';
      $html .= $student['student_id'] . '<br/> Дата подачи заявления: <br>' . date('d-m-Y', strtotime($student['date_of_submission'])) . '</br>';
      $html .= '<br/>';
      $html .= '<button class="btn btn-link" data-toggle="modal" data-target="#actionLogModal_' . $student['student_id'] . '">Подробнее</button>';
      $html .= '</td>';
      $html .= '<td class="border-left editable" data-column="name">' . $student['last_name'] . ' ' . $student['first_name'] . ' ' . $student['middle_name'] . '</td>';
      $html .= '<td class="border-left sortable editable" data-column="rezults">' . $student['rezults'] . '</td>';
      $html .= '<td class="border-left">';
      $studentSpecializations = getStudentSpecializations_BAC($connection, $student['student_id']);
      if (!empty($studentSpecializations)) {
          $html .= '<div class="specialization-column">'; // Добавляем обертывающий div для столбца со специальностями
          foreach ($studentSpecializations as $specialization) {
              $specialization_name = $specialization['specialization_name'];
              $application_number = $specialization['application_number'];

              $html .= '<div class="specialization-item">';
              $html .= $application_number . $specialization_name;
              $html .= '</div>';
          }
          $html .= '</div>'; // Закрываем обертывающий div для столбца со специальностями

          $html .= '<div class="button-column d-flex">'; // Добавляем обертывающий div с классом "d-flex" для создания горизонтального расположения элементов
          $html .= '<div class="dropdown mr-0">'; // Добавляем класс "mr-2" для создания отступа между кнопками
          $html .= '<button class="btn dropdown-toggle" type="button" id="specializationButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">✔️</button>';
          $html .= '<div class="dropdown-menu" aria-labelledby="specializationButton">';
          $allspecializations = getSpecializations_BAC($connection);
          if (!empty($allspecializations)) {
              foreach ($allspecializations as $specialization) {
                  $specialization_id = $specialization['specialization_id']; // Используем идентификатор специализации вместо имени
                  $specialization_name = $specialization['specialization_name'];
                  $html .= '<a class="dropdown-item specialization-option" href="#" data-student_id="' . $student['student_id'] . '" data-specialization_id="' . $specialization_id . '" data-specialization="' . $specialization_name . '">' . $specialization_name . '</a>';
              }
          }
          $html .= '</div>';
          $html .= '</div>';
          $html .= '<div class="dropdown">';
          $html .= '<button class="btn dropdown-toggle delete-specialization-btn" type="button" id="deleteSpecializationButton_' . $student['student_id'] . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">❌</button>';
          $html .= '<div class="dropdown-menu delete-dropdown-menu" aria-labelledby="deleteSpecializationButton_' . $student['student_id'] . '">';
          // Код для выпадающего списка удаления специализаций
          $allstudentspecializations = getallstudentspecializations_BAC($connection, $student['student_id']);
          if (!empty($allstudentspecializations)) {
              foreach ($allstudentspecializations as $specialization) {
                  $specialization_id = $specialization['specialization_id']; // Используем идентификатор специализации вместо имени
                  $specialization_name = $specialization['specialization_name'];
                  $html .= '<a class="dropdown-item delete-specialization-option" href="#" data-student_id="' . $student['student_id'] . '" data-specialization_id="' . $specialization_id . '">' . $specialization_name . '</a>';
              }
          }
          $html .= '</div>';
          $html .= '</div>';
          $html .= '</div>'; // Закрываем обертывающий div для кнопок
      }
      $html .= '</td>';
      $html .= '<td class="border-left">';
      $html .= '<div class="editable"  data-column="phone_number">' . $student['phone_number'] . '</div>';
      $html .= '<br/>';
      $html .= '<a id="telegram-icon" href="https://t.me/' . $student['phone_number'] . '" class="action-log-icon" data-student-id="' . $student['student_id'] . '" data-icon-type="телеграмм" data-rezults="' . $student['rezults'] . '" target="_blank"><img src="../resourses/telegram.png" alt="telegram" width="32" height="32"></i></a>';
      $html .= '<a href="viber://chat?number=' . $student['phone_number'] . '" class="action-log-icon" data-student-id="' . $student['student_id'] . '" data-icon-type="вайбер" target="_blank"><img src="../resourses/viber.png" alt="viber" width="32" height="32"></a>';
      $html .= '<a href="https://wa.me/' . $student['phone_number'] . '" class="action-log-icon" data-student-id="' . $student['student_id'] . '" data-icon-type="ватсап" data-rezults="' . $student['rezults'] . '" target="_blank"><img src="../resourses/whatsapp.png" alt="WhatsApp" width="32" height="32"></a>';
      $html .= '</td>';      
    $html .= '<td class="editable border-left" data-column="foreign_language">' . $student['foreign_language'] . '</td>';
    $html .= '<td class="editable border-left" data-column="description">' . $student['description'] . '</td>';
    $html .= '<td class = "border-left">';
    $html .= '<form method="post">';
    $html .= '<input type="hidden" name="student_id" value="' . $student['student_id'] . '">';
    $html .= '<div class="form-group">';
    $html .= '<select class="form-control" name="originals" onchange="this.form.submit()">';
    $html .= '<option value="0" ' . ($student['originals'] == '0' ? 'selected' : '') . '>Не сдано</option>';
    foreach ($studentSpecializations as $specialization) {
      $specializationName = $specialization['specialization_name'];
      $isSelected = $student['originals'] == $specializationName;
      $html .= '<option value="' . $specializationName . '"' . ($isSelected ? ' selected' : '') . '>' . $specializationName . '</option>';
    }
    $html .= '</select>';
    $html .= '</div>';
    $html .= '</form>';

    if ($student['originals'] != 0) {
      $html .= '<span> Дата подачи оригиналов: <br>' . date('d-m-Y', strtotime($student['submission_originals'])) . '</br></span>';
    } else {
      $html .= '<span>Не сдано</span>';
    }

    $html .= '</td>';
  }
  return $html;
}
function generate_students_table_MAG($students, $connection) {
  $html = '';
  foreach ($students as $student) {
      $html .= '<tr class="' . ($student['originals'] != 0 ? 'table-success' : '') . '">';
      $html .= '<td class="border-left">';
      $html .= '<form method="post">';
      $html .= '<input type="hidden" name="student_id" value="' . $student['student_id'] . '">';
      $html .= '<div class="form-group">';
      $html .= '<select class="form-control" name="processed" onchange="this.form.submit()">';
      $html .= '<option value="0" ' . ($student['processed'] == '0' ? 'selected' : '') . '>Не обработан</option>';
      $html .= '<option value="1" ' . ($student['processed'] == '1' ? 'selected' : '') . '>Обработан</option>';
      $html .= '</select>';
      $html .= '</div>';
      if ($student['processed'] != 0) {
          $html .= '<span> Дата обработки: <br>' . date('d-m-Y', strtotime($student['submission_processed'])) . '</br></span>';
      } else {
          $html .= '<span>Еще не обработан</span>';
      }
      $html .= '</form>';
      $html .= '</td>';
      $html .= '<td class="border-left" data-column="student-number">';
      $html .= $student['student_id'] . '<br/> Дата подачи заявления: <br>' . date('d-m-Y', strtotime($student['date_of_submission'])) . '</br>';
      $html .= '<br/>';
      $html .= '<button class="btn btn-link" data-toggle="modal" data-target="#actionLogModal_' . $student['student_id'] . '">Подробнее</button>';
      $html .= '</td>';
      $html .= '<td class="border-left editable" data-column="name">' . $student['last_name'] . ' ' . $student['first_name'] . ' ' . $student['middle_name'] . '</td>';
      $html .= '<td class="border-left sortable editable" data-column="rezults">' . $student['rezults'] . '</td>';
      $html .= '<td class="border-left">';
      $studentSpecializations = getStudentSpecializations_MAG($connection, $student['student_id']);
      if (!empty($studentSpecializations)) {
          $html .= '<div class="specialization-column">'; // Добавляем обертывающий div для столбца со специальностями
          foreach ($studentSpecializations as $specialization) {
              $specialization_name = $specialization['specialization_name'];
              $application_number = $specialization['application_number'];

              $html .= '<div class="specialization-item">';
              $html .= $application_number . $specialization_name;
              $html .= '</div>';
          }
          $html .= '</div>'; // Закрываем обертывающий div для столбца со специальностями

          $html .= '<div class="button-column d-flex">'; // Добавляем обертывающий div с классом "d-flex" для создания горизонтального расположения элементов
          $html .= '<div class="dropdown mr-0">'; // Добавляем класс "mr-2" для создания отступа между кнопками
          $html .= '<button class="btn dropdown-toggle" type="button" id="specializationButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">✔️</button>';
          $html .= '<div class="dropdown-menu" aria-labelledby="specializationButton">';
          $allspecializations = getSpecializations_MAG($connection);
          if (!empty($allspecializations)) {
              foreach ($allspecializations as $specialization) {
                  $specialization_id = $specialization['specialization_id']; // Используем идентификатор специализации вместо имени
                  $specialization_name = $specialization['specialization_name'];
                  $html .= '<a class="dropdown-item specialization-option" href="#" data-student_id="' . $student['student_id'] . '" data-specialization_id="' . $specialization_id . '" data-specialization="' . $specialization_name . '">' . $specialization_name . '</a>';
              }
          }
          $html .= '</div>';
          $html .= '</div>';
          $html .= '<div class="dropdown">';
          $html .= '<button class="btn dropdown-toggle delete-specialization-btn" type="button" id="deleteSpecializationButton_' . $student['student_id'] . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">❌</button>';
          $html .= '<div class="dropdown-menu delete-dropdown-menu" aria-labelledby="deleteSpecializationButton_' . $student['student_id'] . '">';
          // Код для выпадающего списка удаления специализаций
          $allstudentspecializations = getallstudentspecializations_MAG($connection, $student['student_id']);
          if (!empty($allstudentspecializations)) {
              foreach ($allstudentspecializations as $specialization) {
                  $specialization_id = $specialization['specialization_id']; // Используем идентификатор специализации вместо имени
                  $specialization_name = $specialization['specialization_name'];
                  $html .= '<a class="dropdown-item delete-specialization-option" href="#" data-student_id="' . $student['student_id'] . '" data-specialization_id="' . $specialization_id . '">' . $specialization_name . '</a>';
              }
          }
          $html .= '</div>';
          $html .= '</div>';
          $html .= '</div>'; // Закрываем обертывающий div для кнопок
      }
      $html .= '</td>';
      $html .= '<td class="border-left">';
$html .= '<div class="editable"  data-column="phone_number">' . $student['phone_number'] . '</div>';
$html .= '<br/>';
$html .= '<a id="telegram-icon" href="https://t.me/' . $student['phone_number'] . '" class="action-log-icon" data-student-id="' . $student['student_id'] . '" data-icon-type="телеграмм" data-rezults="' . $student['rezults'] . '" target="_blank"><img src="../resourses/telegram.png" alt="telegram" width="32" height="32"></i></a>';
      $html .= '<a href="viber://chat?number=' . $student['phone_number'] . '" class="action-log-icon" data-student-id="' . $student['student_id'] . '" data-icon-type="вайбер" target="_blank"><img src="../resourses/viber.png" alt="viber" width="32" height="32"></a>';
      $html .= '<a href="https://wa.me/' . $student['phone_number'] . '" class="action-log-icon" data-student-id="' . $student['student_id'] . '" data-icon-type="ватсап" data-rezults="' . $student['rezults'] . '" target="_blank"><img src="../resourses/whatsapp.png" alt="WhatsApp" width="32" height="32"></a>';
$html .= '</td>';
     
    $html .= '<td class="editable border-left" data-column="foreign_language">' . $student['foreign_language'] . '</td>';
    $html .= '<td class="editable border-left" data-column="description">' . $student['description'] . '</td>';
    $html .= '<td class = "border-left">';
    $html .= '<form method="post">';
    $html .= '<input type="hidden" name="student_id" value="' . $student['student_id'] . '">';
    $html .= '<div class="form-group">';
    $html .= '<select class="form-control" name="originals" onchange="this.form.submit()">';
    $html .= '<option value="0" ' . ($student['originals'] == '0' ? 'selected' : '') . '>Не сдано</option>';
    foreach ($studentSpecializations as $specialization) {
      $specializationName = $specialization['specialization_name'];
      $isSelected = $student['originals'] == $specializationName;
      $html .= '<option value="' . $specializationName . '"' . ($isSelected ? ' selected' : '') . '>' . $specializationName . '</option>';
    }
    $html .= '</select>';
    $html .= '</div>';
    $html .= '</form>';

    if ($student['originals'] != 0) {
      $html .= '<span> Дата подачи оригиналов: <br>' . date('d-m-Y', strtotime($student['submission_originals'])) . '</br></span>';
    } else {
      $html .= '<span>Не сдано</span>';
    }

    $html .= '</td>';
  }
  return $html;
}
// Функция для получения журнала действий студента
function getActions($connection, $studentId) {
  $sql = "SELECT description, timestamp FROM action_log_SPO WHERE student_id = $studentId";
  $result = mysqli_query($connection, $sql);

  if (!$result) {
    die('Ошибка выполнения запроса: ' . mysqli_error($connection));
  }

  $actions = array();
  while ($row = mysqli_fetch_assoc($result)) {
    $description = $row['description'];
    $actionTime = $row['timestamp'];
    $actions[] = array('description' => $description, 'timestamp' => $actionTime);
  }
  

  return $actions;
}
function getActions_BAC($connection, $studentId) {
  $sql = "SELECT description, timestamp FROM action_log_BAC WHERE student_id = $studentId";
  $result = mysqli_query($connection, $sql);

  if (!$result) {
    die('Ошибка выполнения запроса: ' . mysqli_error($connection));
  }

  $actions = array();
  while ($row = mysqli_fetch_assoc($result)) {
    $description = $row['description'];
    $actionTime = $row['timestamp'];
    $actions[] = array('description' => $description, 'timestamp' => $actionTime);
  }
  

  return $actions;
}
function getActions_MAG($connection, $studentId) {
  $sql = "SELECT description, timestamp FROM action_log_MAG WHERE student_id = $studentId";
  $result = mysqli_query($connection, $sql);

  if (!$result) {
    die('Ошибка выполнения запроса: ' . mysqli_error($connection));
  }

  $actions = array();
  while ($row = mysqli_fetch_assoc($result)) {
    $description = $row['description'];
    $actionTime = $row['timestamp'];
    $actions[] = array('description' => $description, 'timestamp' => $actionTime);
  }
  

  return $actions;
}
function login($connection, $email, $password) {
  // Получаем пользователя по email
  $user = get_user_by_email($connection, $email);
  
  // Если пользователя нет в базе данных, возвращаем false
  if (!$user) {
    return false;
  }
  
  // Если пароль не совпадает, возвращаем false
  if (!password_verify($password, $user['password'])) {
    return false;
  }
  
  // Если все проверки прошли успешно, сохраняем user_id в сессии
  $_SESSION['user_id'] = $user['id'];
  $_SESSION['name'] = $user['name'];
  
  // Возвращаем true, чтобы показать, что авторизация прошла успешно
  return true;
}
function update_originals_status($connection, $student_id, $originals){
    if ($originals !== '0') {
      $currentDateTime = date('Y-m-d H:i:s');
        $query = "UPDATE Students_SPO SET originals = ?, submission_originals = ? WHERE student_id = ?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "sss", $originals, $currentDateTime, $student_id);
    } else {
        $query = "UPDATE Students_SPO SET originals = ?, submission_originals = NULL WHERE student_id = ?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "ss", $originals, $student_id);
    }
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}
function update_originals_status_BAC($connection, $student_id, $originals){
    if ($originals !== '0') {
      $currentDateTime = date('Y-m-d H:i:s');
        $query = "UPDATE Students_BAC SET originals = ?, submission_originals = ? WHERE student_id = ?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "sss", $originals, $currentDateTime, $student_id);
    } else {
        $query = "UPDATE Students_BAC SET originals = ?, submission_originals = NULL WHERE student_id = ?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "ss", $originals, $student_id);
    }
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}
function update_originals_status_MAG($connection, $student_id, $originals){
    if ($originals !== '0') {
      $currentDateTime = date('Y-m-d H:i:s');
        $query = "UPDATE Students_MAG SET originals = ?, submission_originals = ? WHERE student_id = ?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "sss", $originals, $currentDateTime, $student_id);
    } else {
        $query = "UPDATE Students_MAG SET originals = ?, submission_originals = NULL WHERE student_id = ?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "ss", $originals, $student_id);
    }
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}
function deleteSpecializationFromStudent($connection, $student_id, $specialization_id) {
  $specialization_id = mysqli_real_escape_string($connection, $specialization_id);
  $student_id = mysqli_real_escape_string($connection, $student_id);

  // Изменяем значение delete_status с null на 1
  $query = "UPDATE Student_specialization_SPO
            SET delete_status = 1 
            WHERE student_id = '$student_id' AND specialization_id = '$specialization_id'";
  $result = mysqli_query($connection, $query);

  if (!$result) {
      // Если произошла ошибка при выполнении запроса, возвращаем false
      return false;
  }

  // Успешно изменено, возвращаем true
  return true;
}

function deleteSpecializationFromStudent_BAC($connection, $student_id, $specialization_id) {
  $specialization_id = mysqli_real_escape_string($connection, $specialization_id);
  $student_id = mysqli_real_escape_string($connection, $student_id);

  // Изменяем значение delete_status с null на 1
  $query = "UPDATE Student_specialization_BAC 
            SET delete_status = 1 
            WHERE student_id = '$student_id' AND specialization_id = '$specialization_id'";
  $result = mysqli_query($connection, $query);

  if (!$result) {
      // Если произошла ошибка при выполнении запроса, возвращаем false
      return false;
  }

  // Успешно изменено, возвращаем true
  return true;
}

function deleteSpecializationFromStudent_MAG($connection, $student_id, $specialization_id) {
  $specialization_id = mysqli_real_escape_string($connection, $specialization_id);
  $query = "DELETE FROM Student_specialization_MAG 
            WHERE student_id = '$student_id' AND specialization_id = '$specialization_id'";
  $result = mysqli_query($connection, $query);

  if (!$result) {
    // Если произошла ошибка при выполнении запроса, возвращаем false
    return false;
  }

  // Успешно удалено, возвращаем true
  return true;
}
function getallstudentspecializations($connection, $student_id) {
  $student_id = mysqli_real_escape_string($connection, $student_id);
  
  $query = "SELECT s.specialization_id, s.specialization_name
            FROM Specializations_SPO s
            INNER JOIN Student_specialization_SPO ss ON s.specialization_id = ss.specialization_id
            WHERE ss.student_id = '$student_id' 
            AND (ss.delete_status IS NULL OR ss.delete_status = 0)"; // Проверяем, что delete_status не равен 1 или NULL
  $result = mysqli_query($connection, $query);

  if ($result) {
      // Получение всех строк результата в виде массива
      $specializations = mysqli_fetch_all($result, MYSQLI_ASSOC);
      mysqli_free_result($result);
      return $specializations;
  } else {
      die(mysqli_error($connection));
  }
}



function getallstudentspecializations_BAC($connection, $student_id) {
  $student_id = mysqli_real_escape_string($connection, $student_id);
  
  $query = "SELECT s.specialization_id, s.specialization_name
  FROM Specializations_BAC s
  INNER JOIN Student_specialization_BAC ss ON s.specialization_id = ss.specialization_id
  WHERE ss.student_id = '$student_id'";
  $result = mysqli_query($connection, $query);

  if ($result) {
      // Получение всех строк результата в виде массива
      $specializations = mysqli_fetch_all($result, MYSQLI_ASSOC);
      mysqli_free_result($result);
      return $specializations;
  } else {
      die(mysqli_error($connection));
  }
}
function getallstudentspecializations_MAG($connection, $student_id) {
  $student_id = mysqli_real_escape_string($connection, $student_id);
  
  $query = "SELECT s.specialization_id, s.specialization_name
  FROM Specializations_MAG s
  INNER JOIN Student_specialization_MAG ss ON s.specialization_id = ss.specialization_id
  WHERE ss.student_id = '$student_id'";
  $result = mysqli_query($connection, $query);

  if ($result) {
      // Получение всех строк результата в виде массива
      $specializations = mysqli_fetch_all($result, MYSQLI_ASSOC);
      mysqli_free_result($result);
      return $specializations;
  } else {
      die(mysqli_error($connection));
  }
}
// Добавление абитуриента
function add_student($connection, $first_name, $last_name, $middle_name, $user_id, $phone_number, $rezults, $languages, $description, $form_submission) {
  $query = "INSERT INTO Students_SPO (user_id, first_name, last_name, middle_name, phone_number, rezults, foreign_language, description, form_submission) 
            VALUES ('$user_id', '$first_name', '$last_name', '$middle_name', '$phone_number', '$rezults' , '$languages', '$description', '$form_submission')";
  mysqli_query($connection, $query);
  return mysqli_insert_id($connection);
}
function add_student_BAC($connection, $first_name, $last_name, $middle_name, $user_id, $phone_number, $rezults, $languages, $description, $form_submission) {
  $query = "INSERT INTO Students_BAC (user_id, first_name, last_name, middle_name, phone_number, rezults, foreign_language, description, form_submission) 
            VALUES ('$user_id', '$first_name', '$last_name', '$middle_name', '$phone_number', '$rezults' , '$languages', '$description', '$form_submission')";
  mysqli_query($connection, $query);
  return mysqli_insert_id($connection);
}
function add_student_MAG($connection, $first_name, $last_name, $middle_name, $user_id, $phone_number, $rezults, $languages, $description, $form_submission) {
  $query = "INSERT INTO Students_MAG (user_id, first_name, last_name, middle_name, phone_number, rezults, foreign_language, description, form_submission) 
            VALUES ('$user_id', '$first_name', '$last_name', '$middle_name', '$phone_number', '$rezults' , '$languages', '$description', '$form_submission')";
  mysqli_query($connection, $query);
  return mysqli_insert_id($connection);
}
// Добавление специальности конкретному студенту
function addSpecializationsToStudent_SPO($connection, $student_id, $specializations) {
  $errors = []; // Массив для хранения ошибок

  foreach ($specializations as $specialization_id) {
    $specialization_id = mysqli_real_escape_string($connection, $specialization_id);
    $query = "INSERT INTO Student_specialization_SPO (student_id, specialization_id, application_number) 
    SELECT '$student_id', '$specialization_id', COALESCE(MAX(application_number), 0) + 1
    FROM Student_specialization_SPO
    WHERE specialization_id = '$specialization_id'";
    
    $result = mysqli_query($connection, $query);

    if (!$result) {
      $errors[] = mysqli_error($connection); // Добавляем ошибку в массив
    }
  }

  if (!empty($errors)) {
    return $errors; // Возвращаем массив ошибок, если они есть
  } else {
    return true; // Возвращаем true, если все специализации были успешно добавлены
  }
}

function addSpecializationsToStudent_BAC($connection, $student_id, $specializations) {
  foreach ($specializations as $specialization_id) {
    $specialization_id = mysqli_real_escape_string($connection, $specialization_id);
    $query = "INSERT INTO Student_specialization_BAC (student_id, specialization_id, application_number) 
    SELECT '$student_id', '$specialization_id', COALESCE(MAX(application_number), 0) + 1
    FROM Student_specialization_BAC
    WHERE specialization_id = '$specialization_id'";
    $result = mysqli_query($connection, $query);
  }
  return $result; // Возвращаем результат выполнения запроса
}
function addSpecializationsToStudent_MAG($connection, $student_id, $specializations) {
  foreach ($specializations as $specialization_id) {
    $specialization_id = mysqli_real_escape_string($connection, $specialization_id);
    $query = "INSERT INTO Student_specialization_MAG (student_id, specialization_id, application_number) 
    SELECT '$student_id', '$specialization_id', COALESCE(MAX(application_number), 0) + 1
    FROM Student_specialization_MAG
    WHERE specialization_id = '$specialization_id'";
    $result = mysqli_query($connection, $query);
  }
  return $result; // Возвращаем результат выполнения запроса
}
function checkStudentExists($connection, $student_id) {
  $query = "SELECT * FROM Students_SPO WHERE student_id = $student_id";
  $result = mysqli_query($connection, $query);
  return mysqli_num_rows($result) > 0;
}
function checkStudentExists_BAC($connection, $student_id) {
  $query = "SELECT * FROM Students_BAC WHERE student_id = $student_id";
  $result = mysqli_query($connection, $query);
  return mysqli_num_rows($result) > 0;
}
function checkStudentExists_MAG($connection, $student_id) {
  $query = "SELECT * FROM Students_MAG WHERE student_id = $student_id";
  $result = mysqli_query($connection, $query);
  return mysqli_num_rows($result) > 0;
}
function getSpecializationName($connection, $specialization_id) {
  $query = "SELECT specialization_name FROM Specializations_SPO WHERE specialization_id = '$specialization_id'";
  $result = mysqli_query($connection, $query);
  $row = mysqli_fetch_assoc($result);

  if ($row) {
      return $row['specialization_name'];
  }

  return null;
}
function getSpecializationName_BAC($connection, $specialization_id) {
  $query = "SELECT specialization_name FROM Specializations_BAC WHERE specialization_id = '$specialization_id'";
  $result = mysqli_query($connection, $query);
  $row = mysqli_fetch_assoc($result);

  if ($row) {
      return $row['specialization_name'];
  }

  return null;
}
function getSpecializationName_MAG($connection, $specialization_id) {
  $query = "SELECT specialization_name FROM Specializations_MAG WHERE specialization_id = '$specialization_id'";
  $result = mysqli_query($connection, $query);
  $row = mysqli_fetch_assoc($result);

  if ($row) {
      return $row['specialization_name'];
  }

  return null;
}
function checkSpecializationExists($connection, $specialization_id) {
  $query = "SELECT * FROM Specializations_SPO WHERE specialization_id = $specialization_id";
  $result = mysqli_query($connection, $query);
  return mysqli_num_rows($result) > 0;
}
function checkSpecializationExists_BAC($connection, $specialization_id) {
  $query = "SELECT * FROM Specializations_BAC WHERE specialization_id = $specialization_id";
  $result = mysqli_query($connection, $query);
  return mysqli_num_rows($result) > 0;
}
function checkSpecializationExists_MAG($connection, $specialization_id) {
  $query = "SELECT * FROM Specializations_MAG WHERE specialization_id = $specialization_id";
  $result = mysqli_query($connection, $query);
  return mysqli_num_rows($result) > 0;
}
function insertOrUpdateSpecializationToStudent($connection, $student_id, $specialization_id) {
  $query = "SELECT * FROM Student_specialization_SPO WHERE student_id = '$student_id' AND specialization_id = '$specialization_id'";
  $result = mysqli_query($connection, $query);

  if(mysqli_num_rows($result) > 0) {
    // Если специализация уже есть у студента, обновляем delete_status на 0
    $update_query = "UPDATE Student_specialization_SPO SET delete_status = 0 WHERE student_id = '$student_id' AND specialization_id = '$specialization_id'";
    return mysqli_query($connection, $update_query);
  } else {
    // Если специализации нет у студента, добавляем новую запись
    $insert_query = "INSERT INTO Student_specialization_SPO (student_id, specialization_id, application_number, delete_status) 
      SELECT '$student_id', '$specialization_id', COALESCE(MAX(application_number), 0) + 1, 0
      FROM Student_specialization_SPO
      WHERE specialization_id = '$specialization_id'";
    return mysqli_query($connection, $insert_query);
  }
}
function insertSpecializationToStudent_BAC($connection, $student_id, $specialization_id) {
  $query = "INSERT INTO Student_specialization_BAC (student_id, specialization_id, application_number) 
      SELECT '$student_id', '$specialization_id', COALESCE(MAX(application_number), 0) + 1
      FROM Student_specialization_BAC
      WHERE specialization_id = '$specialization_id'";
  return mysqli_query($connection, $query);
}
function insertSpecializationToStudent_MAG($connection, $student_id, $specialization_id) {
  $query = "INSERT INTO Student_specialization_MAG (student_id, specialization_id, application_number) 
      SELECT '$student_id', '$specialization_id', COALESCE(MAX(application_number), 0) + 1
      FROM Student_specialization_MAG
      WHERE specialization_id = '$specialization_id'";
  return mysqli_query($connection, $query);
}
// Функция добавления лога
function addActionToLog($connection, $student_id, $specialization_name) {
  // Получаем имя пользователя по user_id
$user_id = $_SESSION['user_id'];
$userName = get_username_by_id($connection, $user_id);
  $action = "$userName добавил(а) специальность: $specialization_name";
  $query = "INSERT INTO action_log_SPO (student_id, `description`)
            VALUES ('$student_id', '$action')";
  $result = mysqli_query($connection, $query);
  return $result;
}
function addActionToLog_BAC($connection, $student_id, $specialization_name) {
  // Получаем имя пользователя по user_id
$user_id = $_SESSION['user_id'];
$userName = get_username_by_id($connection, $user_id);
  $action = "$userName добавил(а) специальность: $specialization_name";
  $query = "INSERT INTO action_log_BAC (student_id, `description`)
            VALUES ('$student_id', '$action')";
  $result = mysqli_query($connection, $query);
  return $result;
}
function addActionToLog_MAG($connection, $student_id, $specialization_name) {
  // Получаем имя пользователя по user_id
$user_id = $_SESSION['user_id'];
$userName = get_username_by_id($connection, $user_id);
  $action = "$userName добавил(а) специальность: $specialization_name";
  $query = "INSERT INTO action_log_MAG (student_id, `description`)
            VALUES ('$student_id', '$action')";
  $result = mysqli_query($connection, $query);
  return $result;
}
function delActionToLog($connection, $student_id, $specialization_name) {
  // Получаем имя пользователя по user_id
$user_id = $_SESSION['user_id'];
$userName = get_username_by_id($connection, $user_id);
  $action = "$userName удалил(а) специальность: $specialization_name";
  $query = "INSERT INTO action_log_SPO (student_id, `description`)
            VALUES ('$student_id', '$action')";
  $result = mysqli_query($connection, $query);
  
  if (!$result) {
    echo "Ошибка при выполнении запроса: " . mysqli_error($connection);
  }
  
  return $result;
}
function delActionToLog_BAC($connection, $student_id, $specialization_name) {
  // Получаем имя пользователя по user_id
$user_id = $_SESSION['user_id'];
$userName = get_username_by_id($connection, $user_id);
  $action = "$userName удалил(а) специальность: $specialization_name";
  $query = "INSERT INTO action_log_BAC (student_id, `description`)
            VALUES ('$student_id', '$action')";
  $result = mysqli_query($connection, $query);
  
  if (!$result) {
    echo "Ошибка при выполнении запроса: " . mysqli_error($connection);
  }
  
  return $result;
}
function delActionToLog_MAG($connection, $student_id, $specialization_name) {
  // Получаем имя пользователя по user_id
$user_id = $_SESSION['user_id'];
$userName = get_username_by_id($connection, $user_id);
  $action = "$userName удалил(а) специальность: $specialization_name";
  $query = "INSERT INTO action_log_MAG (student_id, `description`)
            VALUES ('$student_id', '$action')";
  $result = mysqli_query($connection, $query);
  
  if (!$result) {
    echo "Ошибка при выполнении запроса: " . mysqli_error($connection);
  }
  
  return $result;
}
// Поиск абитуриента по айди пользователя
function get_students_by_user_id($connection, $user_id) {
    $query = "SELECT * FROM Students_SPO WHERE user_id = '$user_id' ORDER BY date_of_submission ASC";
    $result = mysqli_query($connection, $query);
    if (!$result) {
        die(mysqli_error($connection));
    }
    $students = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }

    return $students;
}
function get_students_by_user_id_BAC($connection, $user_id) {
  $query = "SELECT * FROM Students_BAC WHERE user_id = '$user_id' ORDER BY date_of_submission ASC";
  $result = mysqli_query($connection, $query);
  if (!$result) {
      die(mysqli_error($connection));
  }
  $students = array();
  while ($row = mysqli_fetch_assoc($result)) {
      $students[] = $row;
  }

  return $students;
}
function get_students_by_user_id_MAG($connection, $user_id) {
  $query = "SELECT * FROM Students_MAG WHERE user_id = '$user_id' ORDER BY date_of_submission ASC";
  $result = mysqli_query($connection, $query);
  if (!$result) {
      die(mysqli_error($connection));
  }
  $students = array();
  while ($row = mysqli_fetch_assoc($result)) {
      $students[] = $row;
  }

  return $students;
}
function search_students_by_specialization($connection, $user_id, $search) {
  $query = "SELECT s.* FROM Students_SPO s
  JOIN Student_specialization_SPO ss ON s.student_id = ss.student_id
  WHERE s.user_id = ? AND ss.specialization_id = ?";
  $stmt = mysqli_prepare($connection, $query);
  mysqli_stmt_bind_param($stmt, "is", $user_id, $search);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  if ($result) {
      // Получение всех строк результата в виде массива
      $students = mysqli_fetch_all($result, MYSQLI_ASSOC);
      mysqli_free_result($result);
      return $students;
  } else {
      die(mysqli_error($connection));
  }
}
function search_students_by_specialization_BAC($connection, $user_id, $search) {
  $query = "SELECT s.* FROM Students_BAC s
  JOIN Student_specialization_BAC ss ON s.student_id = ss.student_id
  WHERE s.user_id = ? AND ss.specialization_id = ?";
  $stmt = mysqli_prepare($connection, $query);
  mysqli_stmt_bind_param($stmt, "is", $user_id, $search);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  if ($result) {
      // Получение всех строк результата в виде массива
      $students = mysqli_fetch_all($result, MYSQLI_ASSOC);
      mysqli_free_result($result);
      return $students;
  } else {
      die(mysqli_error($connection));
  }
}
function search_students_by_specialization_MAG($connection, $user_id, $search) {
  $query = "SELECT s.* FROM Students_MAG s
  JOIN Student_specialization_MAG ss ON s.student_id = ss.student_id
  WHERE s.user_id = ? AND ss.specialization_id = ?";
  $stmt = mysqli_prepare($connection, $query);
  mysqli_stmt_bind_param($stmt, "is", $user_id, $search);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  if ($result) {
      // Получение всех строк результата в виде массива
      $students = mysqli_fetch_all($result, MYSQLI_ASSOC);
      mysqli_free_result($result);
      return $students;
  } else {
      die(mysqli_error($connection));
  }
}
function search_students_with_originals($connection, $user_id, $specialization_name) {
  $query = "SELECT * FROM Students_SPO WHERE user_id = ? AND originals = ? ORDER BY originals ASC";
  $stmt = mysqli_prepare($connection, $query);
  mysqli_stmt_bind_param($stmt, "is", $user_id, $specialization_name);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  if ($result) {
    $students = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_free_result($result);
    return $students;
  } else {
    die(mysqli_error($connection));
  }
}
function search_students_with_originals_BAC($connection, $user_id, $specialization_name) {
  $query = "SELECT * FROM Students_BAC WHERE user_id = ? AND originals = ? ORDER BY originals ASC";
  $stmt = mysqli_prepare($connection, $query);
  mysqli_stmt_bind_param($stmt, "is", $user_id, $specialization_name);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  if ($result) {
    $students = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_free_result($result);
    return $students;
  } else {
    die(mysqli_error($connection));
  }
}
function search_students_with_originals_MAG($connection, $user_id, $specialization_name) {
  $query = "SELECT * FROM Students_MAG WHERE user_id = ? AND originals = ? ORDER BY originals ASC";
  $stmt = mysqli_prepare($connection, $query);
  mysqli_stmt_bind_param($stmt, "is", $user_id, $specialization_name);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  if ($result) {
    $students = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_free_result($result);
    return $students;
  } else {
    die(mysqli_error($connection));
  }
}
function checkSpecializationExistsForStudent($connection, $student_id, $specialization_id) {
  $query = "SELECT COUNT(*) AS count 
  FROM Student_specialization_SPO 
  WHERE student_id = $student_id 
  AND specialization_id = $specialization_id 
  AND delete_status = 0"; 
  $result = mysqli_query($connection, $query);

  if ($result) {
      $row = mysqli_fetch_assoc($result);
      $count = $row['count'];
      mysqli_free_result($result);
      return $count > 0;
  } else {
      die(mysqli_error($connection));
  }
}
// Функция для получения специализаций, учитывая класс уже существующих специализаций у студента
function getSpecializationsByClass($connection, $student_id) {
  $query = "SELECT DISTINCT specialization_id, specialization_name 
            FROM Specializations_SPO 
            WHERE class = (SELECT class FROM Specializations_SPO WHERE specialization_id IN 
                            (SELECT specialization_id FROM Student_specialization_SPO WHERE student_id = $student_id))
            ORDER BY specialization_name";
  $result = mysqli_query($connection, $query);

  if ($result) {
      // Получение всех строк результата в виде массива
      $specializations = mysqli_fetch_all($result, MYSQLI_ASSOC);
      mysqli_free_result($result);
      return $specializations;
  } else {
      die(mysqli_error($connection));
  }
}
function getClassOfSpecialization($connection, $student_id) {
  // Запрос для получения класса специальности, учитывая специальности студента
  $query = "SELECT MAX(class) AS max_class 
            FROM Specializations_SPO 
            WHERE specialization_id IN (
                SELECT specialization_id 
                FROM Student_specialization_SPO 
                WHERE student_id = $student_id
            )";
  
  $result = mysqli_query($connection, $query);

  if ($result) {
      $row = mysqli_fetch_assoc($result);
      $class = $row['max_class'];
      mysqli_free_result($result);
      return $class;
  } else {
      die(mysqli_error($connection));
  }
}

function getSpecializations($connection) {
  $query = "SELECT specialization_id, specialization_name FROM Specializations_SPO";
  $result = mysqli_query($connection, $query);

  if ($result) {
      // Получение всех строк результата в виде массива
      $specializations = mysqli_fetch_all($result, MYSQLI_ASSOC);
      mysqli_free_result($result);
      return $specializations;
  } else {
      die(mysqli_error($connection));
  }
}
function getSpecializationsClass0($connection) {
  $query = "SELECT specialization_id, specialization_name FROM Specializations_SPO WHERE class IS NULL OR class = 0";
  $result = mysqli_query($connection, $query);

  if ($result) {
      // Получение всех строк результата в виде массива
      $specializations = mysqli_fetch_all($result, MYSQLI_ASSOC);
      mysqli_free_result($result);
      return $specializations;
  } else {
      die(mysqli_error($connection));
  }
}

function getSpecializationsClass1($connection) {
  $query = "SELECT specialization_id, specialization_name FROM Specializations_SPO WHERE class = 1 ";
  $result = mysqli_query($connection, $query);

  if ($result) {
      // Получение всех строк результата в виде массива
      $specializations = mysqli_fetch_all($result, MYSQLI_ASSOC);
      mysqli_free_result($result);
      return $specializations;
  } else {
      die(mysqli_error($connection));
  }
}
function getSpecializations_BAC($connection) {
  $query = "SELECT specialization_id, specialization_name FROM Specializations_BAC";
  $result = mysqli_query($connection, $query);

  if ($result) {
      // Получение всех строк результата в виде массива
      $specializations = mysqli_fetch_all($result, MYSQLI_ASSOC);
      mysqli_free_result($result);
      return $specializations;
  } else {
      die(mysqli_error($connection));
  }
}
function getSpecializations_MAG($connection) {
  $query = "SELECT specialization_id, specialization_name FROM Specializations_MAG";
  $result = mysqli_query($connection, $query);

  if ($result) {
      // Получение всех строк результата в виде массива
      $specializations = mysqli_fetch_all($result, MYSQLI_ASSOC);
      mysqli_free_result($result);
      return $specializations;
  } else {
      die(mysqli_error($connection));
  }
}
// Функция для получения студентов по специальности
function get_students_by_specialization($connection, $specialization) {
  $specialization = mysqli_real_escape_string($connection, $specialization);
  $query = "SELECT s.*, ss.application_number, ss.originals_status, ss.submission_originals
            FROM Students_SPO AS s
            INNER JOIN Student_specialization_SPO AS ss ON s.student_id = ss.student_id
            INNER JOIN Specializations_SPO AS sp ON ss.specialization_id = sp.specialization_id
            WHERE sp.specialization_name = '$specialization'";
  $result = mysqli_query($connection, $query);
  $students = array();
  while ($row = mysqli_fetch_assoc($result)) {
      $students[] = $row;
  }
  return $students;
}
function get_students_by_specialization_BAC($connection, $specialization) {
  $specialization = mysqli_real_escape_string($connection, $specialization);
  $query = "SELECT s.*, ss.application_number, ss.originals_status, ss.submission_originals
            FROM Students_BAC AS s
            INNER JOIN Student_specialization_BAC AS ss ON s.student_id = ss.student_id
            INNER JOIN Specializations_BAC AS sp ON ss.specialization_id = sp.specialization_id
            WHERE sp.specialization_name = '$specialization'";
  $result = mysqli_query($connection, $query);
  $students = array();
  while ($row = mysqli_fetch_assoc($result)) {
      $students[] = $row;
  }
  return $students;
}
function get_students_by_specialization_MAG($connection, $specialization) {
  $specialization = mysqli_real_escape_string($connection, $specialization);
  $query = "SELECT s.*, ss.application_number, ss.originals_status, ss.submission_originals
            FROM Students_MAG AS s
            INNER JOIN Student_specialization_MAG AS ss ON s.student_id = ss.student_id
            INNER JOIN Specializations_MAG AS sp ON ss.specialization_id = sp.specialization_id
            WHERE sp.specialization_name = '$specialization'";
  $result = mysqli_query($connection, $query);
  $students = array();
  while ($row = mysqli_fetch_assoc($result)) {
      $students[] = $row;
  }
  return $students;
}

function getStudentSpecializationsClass0($connection, $student_id){
  $specializations = array();

  // Запрос для получения специализаций студента с номером application_number
  $specializationsQuery = "SELECT Specializations_SPO.specialization_name, Student_specialization_SPO.application_number, Student_specialization_SPO.delete_status
                           FROM Students_SPO
                           JOIN Student_specialization_SPO ON Students_SPO.student_id = Student_specialization_SPO.student_id
                           JOIN Specializations_SPO ON Student_specialization_SPO.specialization_id = Specializations_SPO.specialization_id
                           WHERE Specializations_SPO.class = 0, Students_SPO.student_id = $student_id";
  $specializationsResult = mysqli_query($connection, $specializationsQuery);

  if ($specializationsResult && mysqli_num_rows($specializationsResult) > 0) {
      while ($row = mysqli_fetch_assoc($specializationsResult)) {
          $specialization_name = $row['specialization_name'];
          $application_number = $row['application_number'];
          $delete_status=$row['delete_status'];
          
          // Добавляем специализацию в массив
          $specializations[] = array(
              'specialization_name' => $specialization_name,
              'application_number' => $application_number,
              'delete_status'=>$delete_status
          );
      }
  }

  return $specializations;
}
// Функция для получения специализаций студента по его student_id
function getStudentSpecializations($connection, $student_id){
    $specializations = array();

    // Запрос для получения специализаций студента с номером application_number
    $specializationsQuery = "SELECT Specializations_SPO.specialization_name, Student_specialization_SPO.application_number, Student_specialization_SPO.delete_status
                             FROM Students_SPO
                             JOIN Student_specialization_SPO ON Students_SPO.student_id = Student_specialization_SPO.student_id
                             JOIN Specializations_SPO ON Student_specialization_SPO.specialization_id = Specializations_SPO.specialization_id
                             WHERE Students_SPO.student_id = $student_id";
    $specializationsResult = mysqli_query($connection, $specializationsQuery);

    if ($specializationsResult && mysqli_num_rows($specializationsResult) > 0) {
        while ($row = mysqli_fetch_assoc($specializationsResult)) {
            $specialization_name = $row['specialization_name'];
            $application_number = $row['application_number'];
            $delete_status=$row['delete_status'];
            
            // Добавляем специализацию в массив
            $specializations[] = array(
                'specialization_name' => $specialization_name,
                'application_number' => $application_number,
                'delete_status'=>$delete_status,
            );
        }
    }

    return $specializations;
}
function getStudentSpecializations_BAC($connection, $student_id){
    $specializations = array();

    // Запрос для получения специализаций студента с номером application_number
    $specializationsQuery = "SELECT Specializations_BAC.specialization_name, Student_specialization_BAC.application_number
                             FROM Students_BAC
                             JOIN Student_specialization_BAC ON Students_BAC.student_id = Student_specialization_BAC.student_id
                             JOIN Specializations_BAC ON Student_specialization_BAC.specialization_id = Specializations_BAC.specialization_id
                             WHERE Students_BAC.student_id = $student_id";
    $specializationsResult = mysqli_query($connection, $specializationsQuery);

    if ($specializationsResult && mysqli_num_rows($specializationsResult) > 0) {
        while ($row = mysqli_fetch_assoc($specializationsResult)) {
            $specialization_name = $row['specialization_name'];
            $application_number = $row['application_number'];
            
            // Добавляем специализацию в массив
            $specializations[] = array(
                'specialization_name' => $specialization_name,
                'application_number' => $application_number
            );
        }
    }

    return $specializations;
}
function getStudentSpecializations_MAG($connection, $student_id)
{
    $specializations = array();

    // Запрос для получения специализаций студента с номером application_number
    $specializationsQuery = "SELECT Specializations_MAG.specialization_name, Student_specialization_MAG.application_number
                             FROM Students_MAG
                             JOIN Student_specialization_MAG ON Students_MAG.student_id = Student_specialization_MAG.student_id
                             JOIN Specializations_MAG ON Student_specialization_MAG.specialization_id = Specializations_MAG.specialization_id
                             WHERE Students_MAG.student_id = $student_id";
    $specializationsResult = mysqli_query($connection, $specializationsQuery);

    if ($specializationsResult && mysqli_num_rows($specializationsResult) > 0) {
        while ($row = mysqli_fetch_assoc($specializationsResult)) {
            $specialization_name = $row['specialization_name'];
            $application_number = $row['application_number'];
            
            // Добавляем специализацию в массив
            $specializations[] = array(
                'specialization_name' => $specialization_name,
                'application_number' => $application_number
            );
        }
    }

    return $specializations;
}

// Функция поиска конкретного студента по его имени
function search_students_by_name($connection, $user_id, $search) {
  $query = "SELECT * FROM Students_SPO WHERE user_id = '$user_id' AND CONCAT(first_name, ' ', last_name, ' ', middle_name) LIKE '%$search%'";
  $result = mysqli_query($connection, $query);

  if ($result) {
      // Получение всех строк результата в виде массива
      $students = mysqli_fetch_all($result, MYSQLI_ASSOC);
      mysqli_free_result($result);
      return $students;
  } else {
      die(mysqli_error($connection));
  }
}
function search_students_by_name_BAC($connection, $user_id, $search) {
  $query = "SELECT * FROM Students_BAC WHERE user_id = '$user_id' AND CONCAT(first_name, ' ', last_name, ' ', middle_name) LIKE '%$search%'";
  $result = mysqli_query($connection, $query);

  if ($result) {
      // Получение всех строк результата в виде массива
      $students = mysqli_fetch_all($result, MYSQLI_ASSOC);
      mysqli_free_result($result);
      return $students;
  } else {
      die(mysqli_error($connection));
  }
}
function search_students_by_name_MAG($connection, $user_id, $search) {
  $query = "SELECT * FROM Students_MAG WHERE user_id = '$user_id' AND CONCAT(first_name, ' ', last_name, ' ', middle_name) LIKE '%$search%'";
  $result = mysqli_query($connection, $query);

  if ($result) {
      // Получение всех строк результата в виде массива
      $students = mysqli_fetch_all($result, MYSQLI_ASSOC);
      mysqli_free_result($result);
      return $students;
  } else {
      die(mysqli_error($connection));
  }
}
function searchOriginalsBySpecialization($connection, $specializationName) {
  // Выполнение запроса
  $query = "SELECT s.* FROM Students s
  INNER JOIN Student_specialization_SPO ss ON s.student_id = ss.student_id
  INNER JOIN Specializations_SPO sp ON ss.specialization_id = sp.specialization_id
  WHERE sp.specialization_name = '$specializationName' 
  AND s.originals = '$specializationName' 
  AND ss.specialization_id = (SELECT specialization_id FROM Specializations_SPO WHERE specialization_name = '$specializationName')
  ORDER BY ss.application_number";
  
  $result = mysqli_query($connection, $query);

  return $result;
}
function searchOriginalsBySpecialization_BAC($connection, $specializationName) {
  // Выполнение запроса
  $query = "SELECT s.* FROM Students s
  INNER JOIN Student_specialization_BAC ss ON s.student_id = ss.student_id
  INNER JOIN Specializations_BAC sp ON ss.specialization_id = sp.specialization_id
  WHERE sp.specialization_name = '$specializationName' 
  AND s.originals = '$specializationName' 
  AND ss.specialization_id = (SELECT specialization_id FROM Specializations_SPO WHERE specialization_name = '$specializationName')
  ORDER BY ss.application_number";
  
  $result = mysqli_query($connection, $query);

  return $result;
}
function searchOriginalsBySpecialization_MAG($connection, $specializationName) {
  // Выполнение запроса
  $query = "SELECT s.* FROM Students s
  INNER JOIN Student_specialization_MAG ss ON s.student_id = ss.student_id
  INNER JOIN Specializations_MAG sp ON ss.specialization_id = sp.specialization_id
  WHERE sp.specialization_name = '$specializationName' 
  AND s.originals = '$specializationName' 
  AND ss.specialization_id = (SELECT specialization_id FROM Specializations_SPO WHERE specialization_name = '$specializationName')
  ORDER BY ss.application_number";
  
  $result = mysqli_query($connection, $query);

  return $result;
}
// Выход
function logout() {
    session_unset();
    session_destroy();
  }
// Добавление пользователя
  function add_user($connection, $name, $email, $password) {
    $stmt = mysqli_prepare($connection, "INSERT INTO users(name, email, password) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'sss', $name, $email, $password);
    mysqli_stmt_execute($stmt);
    return mysqli_insert_id($connection);
}
// Поиск пользователя по его почте
function get_user_by_email($connection, $email) {
    $stmt = mysqli_prepare($connection, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

function get_username_by_id($connection, $user_id) {
  $stmt = mysqli_prepare($connection, "SELECT name FROM users WHERE id = ?");
  mysqli_stmt_bind_param($stmt, 's', $user_id);
  mysqli_stmt_execute($stmt);

  $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

  mysqli_stmt_close($stmt);

  return $result['name']; // Возвращаем только имя пользователя
}

// Формтирование номера телефона
function phonenumber_format($phone){
    $phone = preg_replace('/[^0-9]/', '', $phone);
    $phone = preg_replace('/(\d{1})(\d{3})(\d{3})(\d{4})/', '+7$2$3$4', $phone);
    return $phone;
}
function update_processed_status($connection, $student_id, $processed){
    if ($processed !== '0') {
      $currentDateTime = date('Y-m-d H:i:s');
        $query = "UPDATE Students_SPO SET processed = ?, submission_processed = ? WHERE student_id = ?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "sss", $processed, $currentDateTime, $student_id);
    } else {
        $query = "UPDATE Students_SPO SET processed = ?, submission_processed = NULL WHERE student_id = ?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "ss", $processed, $student_id);
    }
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}
function update_processed_status_BAC($connection, $student_id, $processed){
  if ($processed !== '0') {
    $currentDateTime = date('Y-m-d H:i:s');
      $query = "UPDATE Students_BAC SET processed = ?, submission_processed = ? WHERE student_id = ?";
      $stmt = mysqli_prepare($connection, $query);
      mysqli_stmt_bind_param($stmt, "sss", $processed, $currentDateTime, $student_id);
  } else {
      $query = "UPDATE Students_BAC SET processed = ?, submission_processed = NULL WHERE student_id = ?";
      $stmt = mysqli_prepare($connection, $query);
      mysqli_stmt_bind_param($stmt, "ss", $processed, $student_id);
  }
  
  $result = mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
  
  return $result;
}
function update_processed_status_MAG($connection, $student_id, $processed){
  if ($processed !== '0') {
    $currentDateTime = date('Y-m-d H:i:s');
      $query = "UPDATE Students_MAG SET processed = ?, submission_processed = ? WHERE student_id = ?";
      $stmt = mysqli_prepare($connection, $query);
      mysqli_stmt_bind_param($stmt, "sss", $processed, $currentDateTime, $student_id);
  } else {
      $query = "UPDATE Students_MAG SET processed = ?, submission_processed = NULL WHERE student_id = ?";
      $stmt = mysqli_prepare($connection, $query);
      mysqli_stmt_bind_param($stmt, "ss", $processed, $student_id);
  }
  
  $result = mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
  
  return $result;
}
function CheckSpecializationByStudent($connection, $student_id, $specialization_id) {
  // Запрос для проверки существования специализации у студента
  $checkQuery = "SELECT * FROM Student_specialization_SPO WHERE student_id = $student_id AND specialization_id = $specialization_id";
  $checkResult = mysqli_query($connection, $checkQuery);

  // Если есть хотя бы одна запись, значит специализация уже существует у студента
  if ($checkResult && mysqli_num_rows($checkResult) > 0) {
    return true;
  }

  return false;
}
function CheckSpecializationByStudent_BAC($connection, $student_id, $specialization_id) {
  // Запрос для проверки существования специализации у студента
  $checkQuery = "SELECT * FROM Student_specialization_BAC WHERE student_id = $student_id AND specialization_id = $specialization_id";
  $checkResult = mysqli_query($connection, $checkQuery);

  // Если есть хотя бы одна запись, значит специализация уже существует у студента
  if ($checkResult && mysqli_num_rows($checkResult) > 0) {
    return true;
  }

  return false;
}
function CheckSpecializationByStudent_MAG($connection, $student_id, $specialization_id) {
  // Запрос для проверки существования специализации у студента
  $checkQuery = "SELECT * FROM Student_specialization_MAG WHERE student_id = $student_id AND specialization_id = $specialization_id";
  $checkResult = mysqli_query($connection, $checkQuery);

  // Если есть хотя бы одна запись, значит специализация уже существует у студента
  if ($checkResult && mysqli_num_rows($checkResult) > 0) {
    return true;
  }

  return false;
}
function getlastaddstudent($connection, $user_id) {
  $query = "SELECT first_name, last_name, middle_name FROM Students_SPO WHERE user_id = '$user_id' ORDER BY date_of_submission DESC LIMIT 1";
  $result = mysqli_query($connection, $query);
  if (!$result) {
      die(mysqli_error($connection));
  }
  $row = mysqli_fetch_assoc($result);
  mysqli_free_result($result);
  
  return $row;
}
function getLastAddedStudentId($connection) {
  // Запрос для получения student_id последнего добавленного студента
  $query = "SELECT student_id FROM Students_SPO ORDER BY date_of_submission DESC LIMIT 1";
  $result = mysqli_query($connection, $query);
  
  if ($result && mysqli_num_rows($result) > 0) {
      $row = mysqli_fetch_assoc($result);
      return $row['student_id'];
  } else {
      return null;
  }
}
function getlastaddstudent_BAC($connection, $user_id) {
  $query = "SELECT first_name, last_name, middle_name FROM Students_BAC WHERE user_id = '$user_id' ORDER BY date_of_submission DESC LIMIT 1";
  $result = mysqli_query($connection, $query);
  if (!$result) {
      die(mysqli_error($connection));
  }
  $row = mysqli_fetch_assoc($result);
  mysqli_free_result($result);
  
  return $row;
}
function getLastAddedStudentId_BAC($connection) {
  // Запрос для получения student_id последнего добавленного студента
  $query = "SELECT student_id FROM Students_BAC ORDER BY date_of_submission DESC LIMIT 1";
  $result = mysqli_query($connection, $query);
  
  if ($result && mysqli_num_rows($result) > 0) {
      $row = mysqli_fetch_assoc($result);
      return $row['student_id'];
  } else {
      return null;
  }
}
function getlastaddstudent_MAG($connection, $user_id) {
  $query = "SELECT first_name, last_name, middle_name FROM Students_MAG WHERE user_id = '$user_id' ORDER BY date_of_submission DESC LIMIT 1";
  $result = mysqli_query($connection, $query);
  if (!$result) {
      die(mysqli_error($connection));
  }
  $row = mysqli_fetch_assoc($result);
  mysqli_free_result($result);
  
  return $row;
}
function getLastAddedStudentId_MAG($connection) {
  // Запрос для получения student_id последнего добавленного студента
  $query = "SELECT student_id FROM Students_MAG ORDER BY date_of_submission DESC LIMIT 1";
  $result = mysqli_query($connection, $query);
  
  if ($result && mysqli_num_rows($result) > 0) {
      $row = mysqli_fetch_assoc($result);
      return $row['student_id'];
  } else {
      return null;
  }
}
function getStudentSpecializationsForLastStudent($connection){
  $specializations = array();

  // Получаем student_id последнего добавленного студента
  $lastAddedStudentId = getLastAddedStudentId($connection);

  // Запрос для получения специализаций студента с номером application_number
  $specializationsQuery = "SELECT Specializations_SPO.specialization_name, Student_specialization_SPO.application_number
                           FROM Students_SPO
                           JOIN Student_specialization_SPO ON Students_SPO.student_id = Student_specialization_SPO.student_id
                           JOIN Specializations_SPO ON Student_specialization_SPO.specialization_id = Specializations_SPO.specialization_id
                           WHERE Students_SPO.student_id = $lastAddedStudentId";
  $specializationsResult = mysqli_query($connection, $specializationsQuery);

  if ($specializationsResult && mysqli_num_rows($specializationsResult) > 0) {
      while ($row = mysqli_fetch_assoc($specializationsResult)) {
          $specialization_name = $row['specialization_name'];
          $application_number = $row['application_number'];
          
          // Добавляем специализацию в массив
          $specializations[] = array(
              'specialization_name' => $specialization_name,
              'application_number' => $application_number
          );
      }
  }

  return $specializations;
}
function getStudentSpecializationsForLastStudent_BAC($connection){
  $specializations = array();

  // Получаем student_id последнего добавленного студента
  $lastAddedStudentId = getLastAddedStudentId_BAC($connection);

  // Запрос для получения специализаций студента с номером application_number
  $specializationsQuery = "SELECT Specializations_BAC.specialization_name, Student_specialization_BAC.application_number
                           FROM Students_BAC
                           JOIN Student_specialization_BAC ON Students_BAC.student_id = Student_specialization_BAC.student_id
                           JOIN Specializations_BAC ON Student_specialization_BAC.specialization_id = Specializations_BAC.specialization_id
                           WHERE Students_BAC.student_id = $lastAddedStudentId";
  $specializationsResult = mysqli_query($connection, $specializationsQuery);

  if ($specializationsResult && mysqli_num_rows($specializationsResult) > 0) {
      while ($row = mysqli_fetch_assoc($specializationsResult)) {
          $specialization_name = $row['specialization_name'];
          $application_number = $row['application_number'];
          
          // Добавляем специализацию в массив
          $specializations[] = array(
              'specialization_name' => $specialization_name,
              'application_number' => $application_number
          );
      }
  }

  return $specializations;
}
function getStudentSpecializationsForLastStudent_MAG($connection){
  $specializations = array();

  // Получаем student_id последнего добавленного студента
  $lastAddedStudentId = getLastAddedStudentId_MAG($connection);

  // Запрос для получения специализаций студента с номером application_number
  $specializationsQuery = "SELECT Specializations_MAG.specialization_name, Student_specialization_MAG.application_number
                           FROM Students_MAG
                           JOIN Student_specialization_MAG ON Students_MAG.student_id = Student_specialization_MAG.student_id
                           JOIN Specializations_MAG ON Student_specialization_MAG.specialization_id = Specializations_MAG.specialization_id
                           WHERE Students_MAG.student_id = $lastAddedStudentId";
  $specializationsResult = mysqli_query($connection, $specializationsQuery);

  if ($specializationsResult && mysqli_num_rows($specializationsResult) > 0) {
      while ($row = mysqli_fetch_assoc($specializationsResult)) {
          $specialization_name = $row['specialization_name'];
          $application_number = $row['application_number'];
          
          // Добавляем специализацию в массив
          $specializations[] = array(
              'specialization_name' => $specialization_name,
              'application_number' => $application_number
          );
      }
  }

  return $specializations;
}
function rezult_format($value) {
  // Используем number_format для форматирования значения с 4 знаками после запятой
  return number_format($value, 4, '.', '');
}
?>