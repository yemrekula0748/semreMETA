<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Login as admin
$user = \App\Models\User::find(1);
auth()->login($user);

echo "User: " . $user->name . "\n";
echo "Has view_accounts: " . ($user->can('view_accounts') ? 'YES' : 'NO') . "\n";

try {
    $accounts = \App\Models\InstagramAccount::withCount(['conversations', 'users'])->get();
    $view = view('accounts.index', compact('accounts'))->render();
    echo "View rendered OK, length: " . strlen($view) . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    // Get inner exception
    if ($e->getPrevious()) {
        echo "Inner: " . $e->getPrevious()->getMessage() . "\n";
        echo "Inner file: " . $e->getPrevious()->getFile() . ":" . $e->getPrevious()->getLine() . "\n";
    }
}
