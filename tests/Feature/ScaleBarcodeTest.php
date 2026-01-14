<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ScaleBarcodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set default scale barcode configuration
        ns()->option->set('ns_scale_barcode_enabled', 'yes');
        ns()->option->set('ns_scale_barcode_prefix', '2');
        ns()->option->set('ns_scale_barcode_type', 'weight');
        ns()->option->set('ns_scale_barcode_product_length', 5);
        ns()->option->set('ns_scale_barcode_value_length', 5);
    }

    public function test_scale_barcode_detection()
    {
        $service = app(ProductService::class);
        
        // Valid scale barcode (13 digits starting with 2)
        $validBarcode = '2123450012349';
        $this->assertTrue($service->isScaleBarcode($validBarcode));
        
        // Invalid barcode (doesn't start with 2)
        $invalidBarcode = '1234567890123';
        $this->assertFalse($service->isScaleBarcode($invalidBarcode));
        
        // Invalid barcode (wrong length)
        $wrongLength = '212345001234';
        $this->assertFalse($service->isScaleBarcode($wrongLength));
    }

    public function test_scale_barcode_parsing_weight()
    {
        $service = app(ProductService::class);
        
        // Barcode: 2-12345-00123-9
        // Product code: 12345
        // Weight: 00123 grams = 0.123 kg
        $barcode = '2123450012349';
        
        $result = $service->parseScaleBarcode($barcode);
        
        $this->assertEquals('12345', $result['product_code']);
        $this->assertEquals(0.123, $result['value']);
        $this->assertEquals('weight', $result['type']);
        $this->assertEquals($barcode, $result['original_barcode']);
    }

    public function test_scale_barcode_parsing_price()
    {
        // Change type to price
        ns()->option->set('ns_scale_barcode_type', 'price');
        
        $service = app(ProductService::class);
        
        // Barcode: 2-12345-01234-9
        // Product code: 12345
        // Price: 01234 cents = $12.34
        $barcode = '2123450123449';
        
        $result = $service->parseScaleBarcode($barcode);
        
        $this->assertEquals('12345', $result['product_code']);
        $this->assertEquals(12.34, $result['value']);
        $this->assertEquals('price', $result['type']);
    }

    public function test_scale_barcode_generation_weight()
    {
        $service = app(ProductService::class);
        
        // Generate barcode for product 12345 with 0.123 kg
        $barcode = $service->generateScaleBarcode('12345', 0.123);
        
        // Should be 13 digits
        $this->assertEquals(13, strlen($barcode));
        
        // Should start with prefix
        $this->assertEquals('2', substr($barcode, 0, 1));
        
        // Should contain product code
        $this->assertEquals('12345', substr($barcode, 1, 5));
        
        // Should contain weight (123 grams)
        $this->assertEquals('00123', substr($barcode, 6, 5));
    }

    public function test_scale_barcode_generation_price()
    {
        ns()->option->set('ns_scale_barcode_type', 'price');
        
        $service = app(ProductService::class);
        
        // Generate barcode for product 12345 with $12.34
        $barcode = $service->generateScaleBarcode('12345', 12.34);
        
        // Should contain price (1234 cents)
        $this->assertEquals('01234', substr($barcode, 6, 5));
    }

    public function test_scale_barcode_disabled()
    {
        ns()->option->set('ns_scale_barcode_enabled', 'no');
        
        $service = app(ProductService::class);
        
        $barcode = '2123450012349';
        $this->assertFalse($service->isScaleBarcode($barcode));
    }

    public function test_scale_barcode_configuration()
    {
        $service = app(ProductService::class);
        
        $config = $service->getConfiguration();
        
        $this->assertTrue($config['enabled']);
        $this->assertEquals('2', $config['prefix']);
        $this->assertEquals(5, $config['product_code_length']);
        $this->assertEquals(5, $config['value_length']);
        $this->assertEquals('weight', $config['type']);
        $this->assertEquals(13, $config['expected_length']);
    }

    public function test_extract_product_code()
    {
        $service = app(ProductService::class);
        
        $barcode = '2123450012349';
        $productCode = $service->extractProductCode($barcode);
        
        $this->assertEquals('12345', $productCode);
    }

    public function test_extract_value_weight()
    {
        $service = app(ProductService::class);
        
        $barcode = '2123450012349';
        $value = $service->extractValue($barcode);
        
        // 123 grams = 0.123 kg
        $this->assertEquals(0.123, $value);
    }

    public function test_extract_value_price()
    {
        ns()->option->set('ns_scale_barcode_type', 'price');
        
        $service = app(ProductService::class);
        
        $barcode = '2123450123449';
        $value = $service->extractValue($barcode);
        
        // 1234 cents = $12.34
        $this->assertEquals(12.34, $value);
    }
}
