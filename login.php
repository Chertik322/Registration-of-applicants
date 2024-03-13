<?php
session_start();
include_once ("./configs/config.php");
include_once ("./configs/functions.php");
include_once ("./configs/db_connect.php");

// Если пользователь уже авторизован, перенаправляем его на главную страницу
if (isset($_SESSION['user_id'])) {
  header('Location: index.php');
  exit();
}

// Если данные были отправлены через POST-запрос, пытаемся авторизовать пользователя
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $email = $_POST['email'];
  $password = $_POST['password'];
  if (login($connection, $email, $password)) {
    // Если авторизация прошла успешно, перенаправляем пользователя на главную страницу
    header('Location: index.php');
    exit();
  } else {
        die('Ошибка при подготовке запроса: ' . mysqli_error($connection));
  }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Авторизация</title>
    <!-- Подключение стилей Bootstrap -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .container {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            background-color: none;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
           border-radius: 30px;
        }
        h1 {
            font-family:'Arial Narrow';
            color: #ffffff;
            text-align: center;
            margin-top: 0;
        }

        form {
            margin-top: 20px;
        }

        label {
            color: #ffffff;
            display: block;
            margin-bottom: 5px;
        }

        input[type="email"],
        input[type="password"] {

            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #cccccc;
            border-radius: 30px;
        }

        button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #1864B0;
            color: #ffffff;
            border: none;
            border-radius: 30px;
            cursor: pointer;
        }

        button:hover {
            background-color: #23527c;
        }

        p {
            font-family:  'Arial';
            color:#ffffff;
            text-align: center;
            margin-top: 20px;
        }

        a {
            font-family:  'Arial';
            color: #337ab7;
        }
        body {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100vh; /* Занимает 100% высоты видимой области */
  margin: 0;
  background-color: #0B2D50;
  background-size: cover;
  background-repeat: no-repeat;
}
.corner-image {
  position: fixed;
  top: 7px;
  right: 10px;
  width: 250px; /* Установите нужную ширину */
  height: auto; /* Автоматическая высота, чтобы сохранить пропорции */
}
    </style>
</head>
<body>
<img src="./resourses/rey_logo.png" alt="Your Image" class="corner-image">
    <div class="container">
        <h1 class="mb-4">Авторизация</h1>
        <?php
        // Если есть ошибки, выводим их
        if(isset($error_message)){
            echo "<p style='color:red;'>{$error_message}</p>";
        }
        ?>
        <form method="post">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" class="form-control" id="email" name="email">
            </div>
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" class="form-control" id="password" name="password">
            </div>
            <div>
                <button type="submit" class="button" name="login">Войти</button>
            </div>
        </form>
        <p class="text-center">Еще не зарегистрированы?<button class="button"><a href="register.php" style="text-decoration: none; color: #ffffff;">Зарегистрироваться</a></button></p>
    </div>
</body>
</html>
