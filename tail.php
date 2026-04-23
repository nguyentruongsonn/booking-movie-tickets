<?php
$log = file(__DIR__ . '/storage/logs/laravel.log');
$errors = preg_grep('/local\.ERROR:/', $log);
print_r(array_slice($errors, -5));
