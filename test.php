<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $controller = new App\Http\Controllers\User\BookingApiController();
    $response = $controller->getShowtimeInfo(1);
    
    echo $response->content();
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "IN: " . $e->getFile() . " LINE " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "IN: " . $e->getFile() . " LINE " . $e->getLine() . "\n";
}
