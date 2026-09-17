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
    return $app->make(App\Http\Controllers\DashboardController::class)->index();
});

echo "Type: " . get_class($response) . "\n";
if ($response instanceof \Illuminate\View\View) {
    $rendered = $response->render();
    echo "SUCCESS: Dashboard rendered successfully without error!\n";
    echo "Output length: " . strlen($rendered) . " bytes\n";
} else {
    echo "Response: " . var_export($response, true) . "\n";
}
