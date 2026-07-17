<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=monitoring_defect;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$stmt = $pdo->query("SELECT TABLE_SCHEMA, TABLE_NAME, ENGINE, TABLE_TYPE, TABLE_ROWS, CREATE_TIME, UPDATE_TIME FROM information_schema.tables WHERE table_schema='monitoring_defect' AND table_name='migrations'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
var_export($row);
echo "\n--- SHOW CREATE TABLE migrations ---\n";
$stmt2 = $pdo->query('SHOW CREATE TABLE migrations');
$create = $stmt2->fetch(PDO::FETCH_ASSOC);
var_export($create);
