<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

try {
    $url = "http://192.168.1.57:8000/";
    $res = Http::timeout(2)->get($url);
    echo "LAN IP status: " . $res->status() . "\n";
    echo "Body start: " . substr($res->body(), 0, 300) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
