<?php
$files = glob('app/Mcp/Tools/*.php');
foreach ($files as $file) {
    if (in_array(basename($file), ['GetOrderTool.php', 'GetDashboardSummaryTool.php', 'GetLowStockProductsTool.php', 'GetProductTool.php', 'SearchProductSalesTool.php', 'SearchOrdersTool.php', 'SearchProductsTool.php', 'SearchCustomersTool.php', 'SearchWalletHistoryTool.php', 'GetCustomerTool.php'])) continue;

    $content = file_get_contents($file);
    // schema should just be return [ ... ];
    $content = preg_replace('/public function schema\((.*?)\): array\s*\{\s*return Response::json\(\[/s', "public function schema($1): array\n    {\n        return [", $content);
    // Fix ] ); to ]; in schema
    $content = preg_replace('/\]\);\n    \}\n\n    public function handle/', "];\n    }\n\n    public function handle", $content);

    // handle should return Response::json([ ... ]);
    $content = preg_replace('/return Response::json\(\[(.*?)\].?\);/s', "return Response::json([$1]);", $content);

    file_put_contents($file, $content);
}
