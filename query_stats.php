<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

try {
    $url = "http://127.0.0.1:8001/api/dashboard/stats";
    $res = Http::timeout(2)->get($url);
    echo "Stats status: " . $res->status() . "\n";
    echo "Stats body: " . $res->body() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
