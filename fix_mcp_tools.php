<?php

$files = [
    'app/Mcp/Tools/BulkUpdateProductsTool.php',
    'app/Mcp/Tools/CreateCategoryTool.php',
    'app/Mcp/Tools/UpdateCategoryTool.php',
    'app/Mcp/Tools/UpdateProductTool.php',
    'app/Mcp/Tools/UpdateProductUnitQuantityTool.php',
    'app/Mcp/Tools/UpdateSettingsTool.php',
    'app/Mcp/Tools/DeleteMediaTool.php',
    'app/Mcp/Tools/UploadMediaTool.php',
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);

    // Fix imports
    if (strpos($content, 'use Laravel\Mcp\Response;') === false) {
        $content = preg_replace('/use Laravel\\\\Mcp\\\\Server\\\\Tool;/', "use Laravel\\Mcp\\Response;\nuse Laravel\\Mcp\\Server\\Tool;", $content);
    }
    
    // Fix error returns in handle
    $content = preg_replace('/return \$this->error\((.*?)\);/', 'return Response::error($1);', $content);

    // Fix handle signature
    $content = preg_replace('/public function handle\(array \$parameters\): array/', 'public function handle(\\Laravel\\Mcp\\Request $request): \\Laravel\\Mcp\\Response', $content);
    $content = preg_replace('/public function handle\(array \$parameters\): string/', 'public function handle(\\Laravel\\Mcp\\Request $request): \\Laravel\\Mcp\\Response', $content);

    // Replace $parameters inside handle with $request object calls
    $content = preg_replace('/\$parameters\[\'(.*?)\'\]/', '$request->get(\'$1\')', $content);
    $content = preg_replace('/array_key_exists\(\$field, \$parameters\)/', '$request->get($field) !== null', $content);
    $content = preg_replace('/\$parameters\[\$field\]/', '$request->get($field)', $content);
    $content = preg_replace('/empty\(\\$parameters\)/', 'empty($request->arguments())', $content);
    $content = preg_replace('/isset\(\$parameters\[(.*?)\]\)/', '($request->get($1) !== null)', $content);

    // Specific returns at the end of handle
    if (strpos($file, 'Media') !== false) {
        $content = preg_replace('/return ([\'"].*?[\'\"]);/', 'return Response::text($1);', $content);
    } else {
        // Find return [...]; and wrap it
        $content = preg_replace('/return \[([^]]+)\];/s', 'return Response::json([$1]);', $content);
        // Fix wrong schema returning Response::json([
        $content = preg_replace('/public function schema\((.*?)\): array\s*\{\s*return Response::json\(\[/s', "public function schema($1): array\n    {\n        return [", $content);
    }

    file_put_contents($file, $content);
    echo "Fixed $file\n";
}
