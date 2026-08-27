0<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $controller = app()->make('App\Http\Controllers\Api\DefectApiController');
    $response = $controller->getStats();
    echo "SUCCESS\n";
    echo $response->getContent();
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
