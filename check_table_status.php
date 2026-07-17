<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=monitoring_defect;charset=utf8mb4','root','');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$stmt = $pdo->query("SELECT TABLE_NAME, ENGINE, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH FROM information_schema.tables WHERE table_schema='monitoring_defect'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo sprintf("%s | %s | rows=%s | data=%s | idx=%s\n", $row['TABLE_NAME'], $row['ENGINE'], $row['TABLE_ROWS'], $row['DATA_LENGTH'], $row['INDEX_LENGTH']);
}
