<?php
    session_start();
    include_once("./configs/config.php");
    include_once("./configs/functions.php");
    include_once("./configs/db_connect.php");
?>
<!DOCTYPE html>
<html>
<head>
  <title>Абитуриенты</title>
  <!-- Подключение стилей Bootstrap -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <style>
    body {
      background-color: #f2f2f2;
    }

    .container {
      max-width: 800px;
      margin: 0 auto;
      padding: 20px;
      background-color: none;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    h1 {
      font-family:'Arial Narrow';
      color: #ffffff;
      text-align: center;
      margin-top: 0;
    }

    p {
      font-family:  'Arial';
      color: #ffffff;
      text-align: center;
      margin-top: 20px;
    }

    .buttons-container {
      display: flex;
      justify-content: center;
      margin-top: 50px;
    }

    .button {
      font-family:  'Arial';
      font-size: 24px;
      padding: 20px 40px;
      margin: 10px;
      background-color: #1864B0;
      color: #ffffff;
      border: none;
      border-radius: 30px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .button:hover {
      background-color: #23527c;
    }

    body {
  background-image: url(./resourses/rey_fon.jpg);
  background-size: cover;
  background-repeat: no-repeat;
  background-position: center center;
  height: 80vh;
  margin: 0;
  overflow: hidden; /* Это предотвращает прокрутку */
}

.bottom-half {
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 50vh; /* Половина высоты экрана */
  background-color: #0B2D50;
}


  </style>
</head>
<body>
  <div class="bottom-half">
    <div style="margin-top: 25px;">
  <?php
if (isset($_SESSION['user_id'])) {
  $name = $_SESSION['user_id'];
  $query = "SELECT name FROM users WHERE id = $name";
  $result = mysqli_query($connection, $query);
  $name = mysqli_fetch_assoc($result);
  echo "<h1>Добро пожаловать, " . $name['name'] . "!</h1>";
  echo "<p>Вы вошли в свой аккаунт <a href='logout.php' class='btn btn-primary' style='border-radius: 30px;background-color: #1864B0;'>Выйти</a></p>";
} else {
  echo "<h1>Добро пожаловать, Гость!</h1>";
  echo "<p>Вы не авторизованы <a href='login.php' class='btn btn-primary'>Войти</a></p>";
}
?>
    <div class="buttons-container">
      <a href="./SPO/SPO.php">
        <button class="button">СПО</button>
      </a>
      <a href="./BAC/BAC.php">
        <button class="button">Бакалавриат</button>
      </a>
      <a href="./MAG/MAG.php">
        <button class="button">Магистратура</button>
      </a>
</div>
    </div>
  </div>
</body>
</html>
<style>
  footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 10px; /* Высота футера */
    background-color: rgb(85,117,116);
    padding: 20px;
}

a {
  font-family:  'Arial';
  margin-right: 10px;
  text-decoration: none;
  color: #000;
}

  </style>
<footer style="font-family:  'Arial'; display: flex; justify-content: space-between; align-items: center; background-color: rgb(255, 250, 250); padding: 20px;">
  <div>
    <a>Обратиться к разработчику:</a>
    <a href="https://t.me/blissya" target="_blank"><img src="./resourses/telegram.png" alt="telegram" width="32" height="32"></a>
    <a href="https://vk.com/vi_cho_tyty" target="_blank"><img src="./resourses/vk.png" alt="vk" width="32" height="32"></a>
  </div>
  <div>
    <a>Другие работы разработчика:</a>
    <a href="./Kval/welcome.php" target="_blank"><img src="./resourses/crm.png" alt="vk" width="32" height="32"></a>
    <a href="./Vizitka/Denis.php" target="_blank"><img src="./resourses/garantiya.png" alt="vk" width="32" height="32"></a>
  </div>
</footer>

</html>