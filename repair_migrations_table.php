<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=monitoring_defect;charset=utf8mb4','root','', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec('DROP TABLE IF EXISTS migrations');
    echo "DROP TABLE migrations executed successfully\n";
    $pdo->exec('CREATE TABLE migrations (migration varchar(255) not null, batch int not null) engine=InnoDB');
    echo "CREATE TABLE migrations executed successfully\n";
} catch (PDOException $e) {
    echo 'ERROR: '.$e->getMessage()."\n";
    exit(1);
}
