<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/dashboard', 'GET');
$request->setLaravelSession($app['session.store']);
$session = $app['session.store'];
$session->put('user_id', 1);
$session->put('user_name', 'Admin QA');
$session->put('user_role', 'Administrator');

$response = $kernel->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Content length: " . strlen($response->getContent()) . "\n";
