<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Test the full view rendering
    $accounts = \App\Models\InstagramAccount::withCount(['conversations', 'users'])->get();
    $view = view('accounts.index', compact('accounts'))->render();
    echo "View rendered OK, length: " . strlen($view) . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}
