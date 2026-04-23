<?php
$models = [
    'Customers' => 'Customer',
    'Showtimes' => 'Showtime',
    'Orders' => 'Order',
    'Movies' => 'Movie',
    'Promotions' => 'Promotion',
    'Products' => 'Product',
    'Rooms' => 'Room',
    'Seats' => 'Seat',
    'Tickets' => 'Ticket',
    'Invoices' => 'Invoice',
    'InvoiceDetails' => 'InvoiceDetail',
    'Categories' => 'Category',
    'Employees' => 'Employee',
    'categories_movies' => 'CategoryMovie',
    'seat_type' => 'SeatType',
    'Price_rules' => 'PriceRule',
    'Customer_promotion' => 'CustomerPromotion'
];

$dirs = [__DIR__ . '/app/', __DIR__ . '/routes/', __DIR__ . '/config/', __DIR__ . '/database/'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    $dirIter = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $iter = new RecursiveIteratorIterator($dirIter);
    $files = new RegexIterator($iter, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
    
    foreach($files as $file) {
        $path = $file[0];
        $content = file_get_contents($path);
        if (strpos($path, 'app\Models') !== false || strpos($path, 'app/Models') !== false) {
            $base = basename($path, '.php');
            if (array_key_exists($base, $models)) {
                continue; 
            }
        }

        $modified = false;
        foreach($models as $old => $new) {
            $count1 = 0;
            $content = preg_replace("/App\\\\Models\\\\{$old}\\b/", "App\\Models\\{$new}", $content, -1, $count1);
            
            $count2 = 0;
            $content = preg_replace("/\\b{$old}::/", "{$new}::", $content, -1, $count2);
            
            $count3 = 0;
            $content = preg_replace("/@var\\s+{$old}\\b/", "@var {$new}", $content, -1, $count3);
            $count4 = 0;
            $content = preg_replace("/\\b{$old}\\s+\\$/", "{$new} $", $content, -1, $count4);

            if ($count1 > 0 || $count2 > 0 || $count3 > 0 || $count4 > 0) {
                $modified = true;
            }
        }
        
        if ($modified) {
            file_put_contents($path, $content);
            echo "Updated: $path\n";
        }
    }
}
echo "Done replacing references.\n";
