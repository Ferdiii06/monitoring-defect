<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=monitoring_defect;charset=utf8mb4','root','');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("SHOW TABLES LIKE 'defects'");
    $res = $stmt->fetchAll(PDO::FETCH_NUM);
    echo count($res) ? 'exists' : 'missing';
} catch (PDOException $e) {
    echo 'ERROR: '.$e->getMessage();
}
