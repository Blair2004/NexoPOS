<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling scale barcode parsing and detection.
 * Scale barcodes are special barcodes that encode product identifier
 * and weight/price information in a single barcode.
 * 
 * Common format: 2XXXXX-WWWWW-C
 * - 2: Prefix (configurable, typically 2 or 21-29)
 * - XXXXX: Product code (configurable length)
 * - WWWWW: Weight in grams or price in cents (configurable length)
 * - C: Check digit
 */
class ScaleBarcodeService
{
    /**
     * Type constants
     */
    const TYPE_WEIGHT = 'weight';
    const TYPE_PRICE = 'price';

    /**
     * Check if scale barcode feature is enabled
     */
    public function isEnabled(): bool
    {
        return ns()->option->get('ns_scale_barcode_enabled', 'no') === 'yes';
    }

    /**
     * Check if a barcode matches the scale barcode format
     */
    public function isScaleBarcode(string $barcode): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $prefix = $this->getPrefix();
        
        // Check if barcode starts with the configured prefix
        if (!str_starts_with($barcode, $prefix)) {
            return false;
        }

        // Check if barcode has the expected length
        $expectedLength = $this->getExpectedBarcodeLength();
        if (strlen($barcode) !== $expectedLength) {
            return false;
        }

        // Check if the barcode contains only digits
        if (!ctype_digit($barcode)) {
            return false;
        }

        return true;
    }

    /**
     * Parse a scale barcode and extract product code and weight/price
     * 
     * @return array{
     *   product_code: string,
     *   value: float,
     *   type: string,
     *   original_barcode: string
     * }
     * @throws Exception
     */
    public function parseScaleBarcode(string $barcode): array
    {
        if (!$this->isScaleBarcode($barcode)) {
            throw new Exception(__('Invalid scale barcode format'));
        }

        $productCode = $this->extractProductCode($barcode);
        $value = $this->extractValue($barcode);
        $type = $this->getType();

        return [
            'product_code' => $productCode,
            'value' => $value,
            'type' => $type,
            'original_barcode' => $barcode,
        ];
    }

    /**
     * Extract product code from scale barcode
     */
    public function extractProductCode(string $barcode): string
    {
        $prefixLength = strlen($this->getPrefix());
        $productCodeLength = $this->getProductCodeLength();

        return substr($barcode, $prefixLength, $productCodeLength);
    }

    /**
     * Extract weight or price value from scale barcode
     */
    public function extractValue(string $barcode): float
    {
        $prefixLength = strlen($this->getPrefix());
        $productCodeLength = $this->getProductCodeLength();
        $valueLength = $this->getValueLength();

        $startPosition = $prefixLength + $productCodeLength;
        $rawValue = substr($barcode, $startPosition, $valueLength);

        // Convert to float based on type
        $type = $this->getType();
        
        if ($type === self::TYPE_WEIGHT) {
            // Weight is typically stored in grams, convert to kg
            return (float) $rawValue / 1000;
        } else {
            // Price is typically stored in cents, convert to currency
            return (float) $rawValue / 100;
        }
    }

    /**
     * Get the configured scale barcode prefix
     */
    public function getPrefix(): string
    {
        return ns()->option->get('ns_scale_barcode_prefix', '2');
    }

    /**
     * Get the configured product code length
     */
    public function getProductCodeLength(): int
    {
        return (int) ns()->option->get('ns_scale_barcode_product_length', 5);
    }

    /**
     * Get the configured weight/price value length
     */
    public function getValueLength(): int
    {
        return (int) ns()->option->get('ns_scale_barcode_value_length', 5);
    }

    /**
     * Get the configured scale barcode type (weight or price)
     */
    public function getType(): string
    {
        return ns()->option->get('ns_scale_barcode_type', self::TYPE_WEIGHT);
    }

    /**
     * Calculate expected barcode length
     */
    private function getExpectedBarcodeLength(): int
    {
        $prefixLength = strlen($this->getPrefix());
        $productCodeLength = $this->getProductCodeLength();
        $valueLength = $this->getValueLength();
        $checkDigitLength = 1; // Standard EAN check digit

        return $prefixLength + $productCodeLength + $valueLength + $checkDigitLength;
    }

    /**
     * Get configuration as array
     */
    public function getConfiguration(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'prefix' => $this->getPrefix(),
            'product_code_length' => $this->getProductCodeLength(),
            'value_length' => $this->getValueLength(),
            'type' => $this->getType(),
            'expected_length' => $this->getExpectedBarcodeLength(),
        ];
    }

    /**
     * Format a product code into a full scale barcode
     * (useful for generating test barcodes or for barcode printing)
     * 
     * @param string $productCode Product identifier
     * @param float $value Weight in kg or price in currency
     * @return string Full scale barcode
     */
    public function generateScaleBarcode(string $productCode, float $value): string
    {
        $prefix = $this->getPrefix();
        $type = $this->getType();
        
        // Pad product code to configured length
        $productCodeLength = $this->getProductCodeLength();
        $paddedProductCode = str_pad($productCode, $productCodeLength, '0', STR_PAD_LEFT);
        
        // Convert value to raw format (grams or cents)
        if ($type === self::TYPE_WEIGHT) {
            $rawValue = (int) ($value * 1000); // kg to grams
        } else {
            $rawValue = (int) ($value * 100); // currency to cents
        }
        
        // Pad value to configured length
        $valueLength = $this->getValueLength();
        $paddedValue = str_pad($rawValue, $valueLength, '0', STR_PAD_LEFT);
        
        // Combine without check digit
        $barcodeWithoutCheck = $prefix . $paddedProductCode . $paddedValue;
        
        // Calculate and append check digit (EAN-13 algorithm)
        $checkDigit = $this->calculateEAN13CheckDigit($barcodeWithoutCheck);
        
        return $barcodeWithoutCheck . $checkDigit;
    }

    /**
     * Calculate EAN-13 check digit
     * 
     * @param string $barcode Barcode without check digit
     * @return int Check digit (0-9)
     */
    private function calculateEAN13CheckDigit(string $barcode): int
    {
        $sum = 0;
        $length = strlen($barcode);
        
        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $barcode[$i];
            // Odd positions (1-based) get multiplied by 1, even by 3
            $multiplier = ($i % 2 === 0) ? 1 : 3;
            $sum += $digit * $multiplier;
        }
        
        $checkDigit = (10 - ($sum % 10)) % 10;
        
        return $checkDigit;
    }
}
