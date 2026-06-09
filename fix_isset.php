<?php
$files = glob('app/Mcp/Tools/*.php');
foreach ($files as $file) {
    if (in_array(basename($file), ['GetOrderTool.php', 'GetDashboardSummaryTool.php', 'GetLowStockProductsTool.php', 'GetProductTool.php', 'SearchProductSalesTool.php', 'SearchOrdersTool.php', 'SearchProductsTool.php', 'SearchCustomersTool.php', 'SearchWalletHistoryTool.php', 'GetCustomerTool.php'])) continue;

    $content = file_get_contents($file);
    // Fix isset($request->get(...))
    $content = preg_replace('/isset\(\$request->get\((.*?)\)\)/', '($request->get($1) !== null)', $content);
    file_put_contents($file, $content);
}
