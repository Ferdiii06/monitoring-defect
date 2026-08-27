<?php
$lines = file('storage/logs/laravel.log');
$errors = array_filter($lines, function($l) { return strpos($l, 'local.ERROR') !== false; });
echo end($errors);
