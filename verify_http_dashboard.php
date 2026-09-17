<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/dashboard', 'GET');
$middleware = new \Illuminate\Session\Middleware\StartSession($app->make(\Illuminate\Session\SessionManager::class));

$response = $middleware->handle($request, function ($req) use ($app) {
    session([
        'logged_in' => true,
        'user_id' => 1,
        'user_name' => 'Admin QA',
        'user_role' => 'Administrator'
    ]);
    $view = $app->make(App\Http\Controllers\DashboardController::class)->index();
    return response($view->render());
});

echo "Status: " . $response->getStatusCode() . "\n";
echo "SUCCESS: Dashboard rendered successfully without error!\n";
echo "Output length: " . strlen($response->getContent()) . " bytes\n";

// Also verify FinalAssyInspectTypeController index view
$masterRequest = Illuminate\Http\Request::create('/master/final-assy-inspect-types', 'GET');
$masterResponse = $middleware->handle($masterRequest, function ($req) use ($app) {
    session([
        'logged_in' => true,
        'user_id' => 1,
        'user_name' => 'Admin QA',
        'user_role' => 'Administrator'
    ]);
    $view = $app->make(App\Http\Controllers\FinalAssyInspectTypeController::class)->index();
    return response($view->render());
});

echo "Master Final Assy Inspect Type Status: " . $masterResponse->getStatusCode() . "\n";
echo "SUCCESS: Master Final Assy Inspect Type rendered successfully!\n";
echo "Master output length: " . strlen($masterResponse->getContent()) . " bytes\n";
