<?php
$content = file_get_contents('app/Mcp/Tools/SearchProductSalesTool.php');

$replacements = [
    "SUM(nexopos_orders_products.quantity)" => "SUM(' . (new \\App\\Models\\OrderProduct)->getTable() . '.quantity)",
    "SUM(nexopos_orders_products.total_price)" => "SUM(' . (new \\App\\Models\\OrderProduct)->getTable() . '.total_price)",
];

$content = str_replace(array_keys($replacements), array_values($replacements), $content);
file_put_contents('app/Mcp/Tools/SearchProductSalesTool.php', $content);
