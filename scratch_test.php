<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try { 
    $o = App\Models\Order::latest('id')->where('status', 1)->first(); 
    if ($o) {
        $result = app(\App\Services\Payment\OrderFulfillmentService::class)->finalize($o->gateway_order_code); 
        print_r($result);
    } else {
        echo "No pending order";
    }
} catch (\Throwable $e) { 
    echo get_class($e) . "\n";
    echo $e->getMessage() . "\n"; 
    echo $e->getTraceAsString() . "\n"; 
}
