<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/accounts';
$_SERVER['REQUEST_METHOD'] = 'GET';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$req = Illuminate\Http\Request::create('/accounts', 'GET');

// Simulate auth by starting a session and logging in
$user = \App\Models\User::find(1);
$req->setUserResolver(function() use ($user) { return $user; });

// Boot the kernel bootstrap
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Login the user
\Illuminate\Support\Facades\Auth::setUser($user);

$resp = $kernel->handle($req);
echo 'Status: ' . $resp->getStatusCode() . PHP_EOL;
if ($resp->getStatusCode() >= 500) {
    echo substr($resp->getContent(), 0, 3000);
} else {
    echo "OK\n";
}
