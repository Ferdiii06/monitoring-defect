<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$ports = [8000, 8001];
foreach ($ports as $port) {
    try {
        $url = "http://127.0.0.1:$port/";
        $res = Http::timeout(2)->get($url);
        echo "Port $port status: " . $res->status() . "\n";
        echo "Body: " . substr($res->body(), 0, 150) . "\n\n";
    } catch (\Exception $e) {
        echo "Port $port error: " . $e->getMessage() . "\n\n";
    }
}
