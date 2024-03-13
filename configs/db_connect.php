<?php 

include_once("config.php");

$connection = mysqli_connect($DB['host'], $DB['login'], $DB['password'], $DB['name']);

if (mysqli_connect_errno()) {
    echo "Ошибка подключения к базе данных (" . mysqli_connect_errno . "): " . mysqli_connect_errno();
    exit();
}

if (!mysqli_set_charset($connection, "utf8mb4")) {
    printf("Ошибка при установке набора символов utf8mb4: %s\n", mysqli_error($connection));
    exit();
}
