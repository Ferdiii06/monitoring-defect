<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=monitoring_defect;charset=utf8mb4', 'root', '');
$stmt = $pdo->query('SHOW TABLES');
foreach ($stmt->fetchAll(PDO::FETCH_NUM) as $row) {
    echo $row[0] . PHP_EOL;
}
