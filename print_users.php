<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

$apiUrl = config('services.external_api.url');
echo "EXTERNAL_API_URL config: $apiUrl\n";

try {
    $response = Http::timeout(5)->get($apiUrl . '/users');
    if ($response->successful()) {
        $users = $response->json('data');
        print_r($users);
    } else {
        echo "API Call failed with status: " . $response->status() . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
