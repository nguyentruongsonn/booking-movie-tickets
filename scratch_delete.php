<?php
$models = ['Customers.php', 'Showtimes.php', 'Orders.php', 'Movies.php', 'Promotions.php', 'Products.php', 'Rooms.php', 'Seats.php', 'Tickets.php', 'Invoices.php', 'InvoiceDetails.php', 'Categories.php', 'Employees.php', 'categories_movies.php', 'seat_type.php', 'Price_rules.php', 'Customer_promotion.php'];
foreach($models as $m){
    @unlink(__DIR__ . '/app/Models/' . $m);
    echo "Deleted $m\n";
}
