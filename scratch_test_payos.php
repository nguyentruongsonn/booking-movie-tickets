<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try { 
    $o = App\Models\Order::latest('id')->first(); 
    if ($o) {
        $gateway = app(\App\Services\Payment\PayOSGateway::class);
        $info = $gateway->getPaymentInfo($o->gateway_order_code);
        print_r($info);
    }
} catch (\Throwable $e) { 
    echo get_class($e) . "\n";
    echo $e->getMessage() . "\n"; 
}
