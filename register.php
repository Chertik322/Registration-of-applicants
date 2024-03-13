<?php
session_start();
include_once ("./configs/config.php");
include_once ("./configs/functions.php");
include_once ("./configs/db_connect.php");
// Создание нового аккаунта и занесения информации о нем в базу данных
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Проверяем, что пользователь заполнил все поля
    if (!empty($_POST['name']) && !empty($_POST['email']) && !empty($_POST['password'])) {
        // Получаем данные из формы регистрации
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        // Проверяем, что пользователь с таким email еще не зарегистрирован
        $stmt = mysqli_prepare($connection, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error_msg = 'Пользователь с таким email уже зарегистрирован.';
        } else {
            // Регистрируем пользователя
            $stmt = mysqli_prepare($connection, "INSERT INTO users(name, email, password) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'sss', $name, $email, $password);
            if (mysqli_stmt_execute($stmt)) {
                // Авторизуем пользователя и перенаправляем на главную страницу
                $_SESSION['user_id'] = mysqli_insert_id($connection);
                header('Location: index.php');
                exit();
            } else {
                $error_msg = 'Не удалось зарегистрировать пользователя. Попробуйте еще раз позже.';
            }
        }
    } else {
        $error_msg = 'Пожалуйста, заполните все поля формы.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Регистрация</title>
    <!-- Подключение стилей Bootstrap -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Ваши дополнительные стили, если есть -->
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
        input[type="password"],
        input[type="name"] {

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
<div class="container">
<img src="./resourses/rey_logo.png" alt="Your Image" class="corner-image">
            <h1 class="text-center">Регистрация</h1>
            <?php if (isset($error_msg)) { ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_msg; ?>
                </div>
            <?php } ?>
            <form method="POST">
                <div class="form-group">
                    <label for="name">Введите ваше имя</label>
                    <input type="name" class="form-control" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email адрес</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Подтвердите пароль</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="button">Зарегистрироваться</button>
                <p class="text-center">Уже зарегистрированы?
                    <button class="button"><a href="login.php" style="text-decoration: none; color: #ffffff;">Войти</a></button>
                    </p>
            </form>
</div>
</body>
</html>