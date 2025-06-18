<?php
$host = 'localhost';
$dbname = 'cloudshopping';
$username = 'root'; // 根据实际修改
$password = 'root123'; // 根据实际修改

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}
?>