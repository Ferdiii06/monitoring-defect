<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=monitoring_defect;charset=utf8mb4','root','');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$tables = ['users', 'migrations', 'activity_logs', 'cache', 'cache_locks', 'failed_jobs', 'job_batches', 'jobs', 'password_reset_tokens', 'personal_access_tokens', 'sessions'];
foreach ($tables as $table) {
    echo "Dropping $table... ";
    $pdo->exec("DROP TABLE IF EXISTS `$table`");
    echo "OK\n";
}
echo "Done.\n";
