<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductUnitQuantity;
use Illuminate\Console\Command;

class GenerateScaleBarcodeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ns:scale-barcode
                            {--product= : Product ID to generate barcode for}
                            {--unit-quantity= : Product Unit Quantity ID}
                            {--plu= : PLU code to use}
                            {--value= : Weight (kg) or Price value}
                            {--type= : Override type: weight or price}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate sample scale barcodes for testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if scale barcode is enabled
        $enabled = ns()->option->get('ns_scale_barcode_enabled', 'no');
        
        if ($enabled !== 'yes') {
            $this->error('Scale barcode feature is not enabled.');
            $this->info('Please enable it in Settings > POS > Scale Barcode');
            return 1;
        }

        // Display current configuration
        $this->displayConfiguration();

        $productCode = $this->getProductCode();
        $value = $this->getValue();
        
        // Generate the barcode
        $barcode = $this->generateBarcode($productCode, $value);

        // Display results
        $this->displayResults($barcode, $productCode, $value);

        return 0;
    }

    /**
     * Display current scale barcode configuration
     */
    protected function displayConfiguration(): void
    {
        $this->info('Current Scale Barcode Configuration:');
        $this->table(
            ['Setting', 'Value'],
            [
                ['Enabled', ns()->option->get('ns_scale_barcode_enabled', 'no')],
                ['Prefix', ns()->option->get('ns_scale_barcode_prefix', '2')],
                ['Type', ns()->option->get('ns_scale_barcode_type', 'weight')],
                ['Product Code Length', ns()->option->get('ns_scale_barcode_product_length', 5)],
                ['Value Length', ns()->option->get('ns_scale_barcode_value_length', 5)],
            ]
        );
        $this->newLine();
    }

    /**
     * Get product code from various sources
     */
    protected function getProductCode(): string
    {
        // Check if PLU option is provided
        if ($this->option('plu')) {
            $plu = $this->option('plu');
            $this->info("Using provided PLU: {$plu}");
            return $this->formatProductCode($plu);
        }

        // Check if unit quantity ID is provided
        if ($this->option('unit-quantity')) {
            $unitQuantityId = $this->option('unit-quantity');
            $unitQuantity = ProductUnitQuantity::find($unitQuantityId);
            
            if (!$unitQuantity) {
                $this->error("Product Unit Quantity with ID {$unitQuantityId} not found.");
                exit(1);
            }

            if ($unitQuantity->scale_plu) {
                $this->info("Using PLU from Product Unit Quantity: {$unitQuantity->scale_plu}");
                return $this->formatProductCode($unitQuantity->scale_plu);
            } else {
                $this->warn("Product Unit Quantity {$unitQuantityId} doesn't have a PLU assigned.");
            }
        }

        // Check if product ID is provided
        if ($this->option('product')) {
            $productId = $this->option('product');
            $product = Product::with('unit_quantities')->find($productId);
            
            if (!$product) {
                $this->error("Product with ID {$productId} not found.");
                exit(1);
            }

            $this->info("Product: {$product->name}");
            
            // Check if product has unit quantities with PLU
            $unitQuantitiesWithPLU = $product->unit_quantities->filter(fn($uq) => !empty($uq->scale_plu));
            
            if ($unitQuantitiesWithPLU->isNotEmpty()) {
                if ($unitQuantitiesWithPLU->count() === 1) {
                    $plu = $unitQuantitiesWithPLU->first()->scale_plu;
                    $this->info("Using PLU from product: {$plu}");
                    return $this->formatProductCode($plu);
                } else {
                    $this->info("Product has multiple unit quantities with PLU:");
                    $choices = [];
                    foreach ($unitQuantitiesWithPLU as $uq) {
                        $unitName = $uq->unit->name ?? 'Unknown';
                        $choices[] = "{$uq->scale_plu} ({$unitName})";
                    }
                    $selected = $this->choice('Select a PLU to use:', $choices);
                    $plu = explode(' ', $selected)[0];
                    return $this->formatProductCode($plu);
                }
            }
        }

        // Interactive mode: ask for product code
        $productCodeLength = (int) ns()->option->get('ns_scale_barcode_product_length', 5);
        
        $this->info("No PLU provided. You can:");
        $this->info("1. Enter a custom product code");
        $this->info("2. Search for a product by name");
        
        $choice = $this->choice('What would you like to do?', ['Enter custom code', 'Search product'], 0);
        
        if ($choice === 'Enter custom code') {
            $code = $this->ask("Enter product code (max {$productCodeLength} digits)", '12345');
            return $this->formatProductCode($code);
        } else {
            return $this->searchAndSelectProduct();
        }
    }

    /**
     * Search for products and let user select
     */
    protected function searchAndSelectProduct(): string
    {
        $search = $this->ask('Enter product name to search');
        
        $products = Product::where('name', 'LIKE', "%{$search}%")
            ->with('unit_quantities.unit')
            ->limit(10)
            ->get();
        
        if ($products->isEmpty()) {
            $this->warn('No products found. Using default code.');
            return $this->formatProductCode('12345');
        }

        $choices = [];
        $productMap = [];
        
        foreach ($products as $product) {
            $unitQuantitiesWithPLU = $product->unit_quantities->filter(fn($uq) => !empty($uq->scale_plu));
            
            if ($unitQuantitiesWithPLU->isNotEmpty()) {
                foreach ($unitQuantitiesWithPLU as $uq) {
                    $unitName = $uq->unit->name ?? 'Unknown';
                    $key = "{$product->name} - {$unitName} (PLU: {$uq->scale_plu})";
                    $choices[] = $key;
                    $productMap[$key] = $uq->scale_plu;
                }
            } else {
                $key = "{$product->name} (No PLU)";
                $choices[] = $key;
                $productMap[$key] = null;
            }
        }

        if (empty($choices)) {
            $this->warn('No products with PLU found. Using default code.');
            return $this->formatProductCode('12345');
        }

        $selected = $this->choice('Select a product:', $choices);
        $plu = $productMap[$selected];
        
        if ($plu) {
            return $this->formatProductCode($plu);
        } else {
            $code = $this->ask('Enter custom product code for this product', '12345');
            return $this->formatProductCode($code);
        }
    }

    /**
     * Format product code to match configured length
     */
    protected function formatProductCode(string $code): string
    {
        $productCodeLength = (int) ns()->option->get('ns_scale_barcode_product_length', 5);
        
        // Remove non-numeric characters
        $code = preg_replace('/[^0-9]/', '', $code);
        
        // Pad with zeros or truncate
        if (strlen($code) < $productCodeLength) {
            $code = str_pad($code, $productCodeLength, '0', STR_PAD_LEFT);
        } elseif (strlen($code) > $productCodeLength) {
            $code = substr($code, 0, $productCodeLength);
            $this->warn("Product code truncated to {$productCodeLength} digits: {$code}");
        }
        
        return $code;
    }

    /**
     * Get value (weight or price) from user
     */
    protected function getValue(): float
    {
        if ($this->option('value')) {
            return (float) $this->option('value');
        }

        $type = $this->option('type') ?? ns()->option->get('ns_scale_barcode_type', 'weight');
        
        if ($type === 'weight') {
            $value = $this->ask('Enter weight in kilograms (e.g., 1.5 for 1.5kg)', '1.5');
            return (float) $value;
        } else {
            $value = $this->ask('Enter price (e.g., 12.50)', '12.50');
            return (float) $value;
        }
    }

    /**
     * Generate the scale barcode
     */
    protected function generateBarcode(string $productCode, float $value): string
    {
        $prefix = ns()->option->get('ns_scale_barcode_prefix', '2');
        $type = $this->option('type') ?? ns()->option->get('ns_scale_barcode_type', 'weight');
        $valueLength = (int) ns()->option->get('ns_scale_barcode_value_length', 5);
        
        // Convert value to integer format
        if ($type === 'weight') {
            // Convert kg to grams
            $encodedValue = (int) ($value * 1000);
        } else {
            // Convert currency to cents
            $encodedValue = (int) ($value * 100);
        }
        
        // Format value with leading zeros
        $formattedValue = str_pad($encodedValue, $valueLength, '0', STR_PAD_LEFT);
        
        // Ensure value doesn't exceed length
        if (strlen($formattedValue) > $valueLength) {
            $this->warn("Value exceeds maximum length, truncating...");
            $formattedValue = substr($formattedValue, 0, $valueLength);
        }
        
        // Construct barcode (without check digit for now)
        $barcodeWithoutCheckDigit = $prefix . $productCode . $formattedValue;
        
        // Calculate EAN-13 check digit
        $checkDigit = $this->calculateEAN13CheckDigit($barcodeWithoutCheckDigit);
        
        return $barcodeWithoutCheckDigit . $checkDigit;
    }

    /**
     * Calculate EAN-13 check digit
     */
    protected function calculateEAN13CheckDigit(string $barcode): int
    {
        // Pad to 12 digits if needed
        $barcode = str_pad($barcode, 12, '0', STR_PAD_LEFT);
        $barcode = substr($barcode, 0, 12);
        
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $barcode[$i];
            // Multiply odd positions by 1, even positions by 3
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }
        
        $checkDigit = (10 - ($sum % 10)) % 10;
        
        return $checkDigit;
    }

    /**
     * Display generated barcode results
     */
    protected function displayResults(string $barcode, string $productCode, float $value): void
    {
        $type = $this->option('type') ?? ns()->option->get('ns_scale_barcode_type', 'weight');
        $prefix = ns()->option->get('ns_scale_barcode_prefix', '2');
        $productCodeLength = (int) ns()->option->get('ns_scale_barcode_product_length', 5);
        $valueLength = (int) ns()->option->get('ns_scale_barcode_value_length', 5);
        
        $this->newLine();
        $this->info('═══════════════════════════════════════════════');
        $this->info('           Generated Scale Barcode             ');
        $this->info('═══════════════════════════════════════════════');
        $this->newLine();
        
        $this->line("  <fg=green;options=bold>{$barcode}</>");
        $this->newLine();
        
        // Visual breakdown
        $prefixPart = substr($barcode, 0, strlen($prefix));
        $productPart = substr($barcode, strlen($prefix), $productCodeLength);
        $valuePart = substr($barcode, strlen($prefix) + $productCodeLength, $valueLength);
        $checkDigit = substr($barcode, -1);
        
        $this->info('Barcode Breakdown:');
        $this->line("  Prefix:        <fg=cyan>{$prefixPart}</>");
        $this->line("  Product Code:  <fg=yellow>{$productPart}</>");
        $this->line("  {$type} Value:  <fg=magenta>{$valuePart}</>");
        $this->line("  Check Digit:   <fg=blue>{$checkDigit}</>");
        $this->newLine();
        
        $this->info('Decoded Information:');
        $this->line("  Product Code:  {$productCode}");
        
        if ($type === 'weight') {
            $this->line("  Weight:        {$value} kg (" . ($value * 1000) . " grams)");
        } else {
            $this->line("  Price:         " . number_format($value, 2) . " (" . ($value * 100) . " cents)");
        }
        
        $this->newLine();
        $this->info('Test Commands:');
        $this->line("  <fg=gray># Test parsing this barcode:</>");
        $this->line("  <fg=white>php artisan tinker</>");
        $this->line("  <fg=white>app(\App\Services\ProductService::class)->parseScaleBarcode('{$barcode}')</>");
        $this->newLine();
        
        $this->info('═══════════════════════════════════════════════');
    }
}
