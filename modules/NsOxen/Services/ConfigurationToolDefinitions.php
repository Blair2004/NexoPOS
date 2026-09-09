<?php

namespace Modules\NsOxen\Services;

use Modules\NsOxen\Data\OxenToolDefinition;

class ConfigurationToolDefinitions
{
    /** @return list<OxenToolDefinition> */
    public function definitions(): array
    {
        $idempotency = ['type' => 'string'];
        $nullableString = ['type' => ['string', 'null']];
        $identifier = ['type' => ['string', 'null'], 'maxLength' => 100];
        $positiveInteger = ['type' => 'integer', 'minimum' => 1];
        $eligibilityIds = ['type' => 'array', 'maxItems' => 100, 'uniqueItems' => true, 'items' => $positiveInteger];

        $unitGroupProperties = ['name' => ['type' => 'string', 'maxLength' => 255], 'description' => $nullableString, 'idempotency_key' => $idempotency];
        $unitProperties = [
            'name' => ['type' => 'string', 'maxLength' => 255], 'identifier' => $identifier,
            'group_id' => $positiveInteger, 'value' => ['type' => 'number', 'exclusiveMinimum' => 0],
            'base_unit' => ['type' => 'boolean'], 'description' => $nullableString,
            'preview_url' => $nullableString, 'idempotency_key' => $idempotency,
        ];
        $taxGroupProperties = $unitGroupProperties;
        $taxProperties = [
            'name' => ['type' => 'string', 'maxLength' => 255], 'rate' => ['type' => 'number', 'minimum' => 0],
            'tax_group_id' => $positiveInteger, 'description' => $nullableString, 'idempotency_key' => $idempotency,
        ];
        $couponProperties = [
            'name' => ['type' => 'string', 'maxLength' => 255], 'code' => ['type' => 'string', 'maxLength' => 255],
            'type' => ['type' => 'string', 'enum' => ['percentage_discount', 'flat_discount']],
            'discount_value' => ['type' => 'number', 'minimum' => 0], 'valid_until' => $nullableString,
            'minimum_cart_value' => ['type' => 'number', 'minimum' => 0], 'maximum_cart_value' => ['type' => 'number', 'minimum' => 0],
            'valid_hours_start' => $nullableString, 'valid_hours_end' => $nullableString,
            'limit_usage' => ['type' => 'integer', 'minimum' => 0],
            'product_ids' => $eligibilityIds, 'category_ids' => $eligibilityIds,
            'customer_ids' => $eligibilityIds, 'customer_group_ids' => $eligibilityIds,
            'idempotency_key' => $idempotency,
        ];
        $customerGroupProperties = [
            'name' => ['type' => 'string', 'maxLength' => 255], 'description' => $nullableString,
            'reward_system_id' => ['type' => ['integer', 'null'], 'minimum' => 1],
            'minimal_credit_payment' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
            'idempotency_key' => $idempotency,
        ];

        return [
            $this->write( 'create_unit_group', 'Create unit group', 'Create a product unit group after approval.', 'Products', 'nexopos.create.products-units', $unitGroupProperties, ['name', 'idempotency_key'], $this->unitGroupRules( true ) ),
            $this->write( 'update_unit_group', 'Update unit group', 'Patch a product unit group after approval.', 'Products', 'nexopos.update.products-units', ['id' => $positiveInteger, ...$unitGroupProperties], ['id', 'idempotency_key'], $this->unitGroupRules( false ) ),
            $this->write( 'create_unit', 'Create unit', 'Create a product unit and preserve base-unit exclusivity after approval.', 'Products', 'nexopos.create.products-units', $unitProperties, ['name', 'group_id', 'value', 'base_unit', 'idempotency_key'], $this->unitRules( true ) ),
            $this->write( 'update_unit', 'Update unit', 'Patch a product unit and preserve base-unit exclusivity after approval.', 'Products', 'nexopos.update.products-units', ['id' => $positiveInteger, ...$unitProperties], ['id', 'idempotency_key'], $this->unitRules( false ) ),
            $this->write( 'create_tax_group', 'Create tax group', 'Create a tax group after approval.', 'Taxes', 'nexopos.create.taxes', $taxGroupProperties, ['name', 'idempotency_key'], $this->unitGroupRules( true ) ),
            $this->write( 'update_tax_group', 'Update tax group', 'Patch a tax group after approval.', 'Taxes', 'nexopos.update.taxes', ['id' => $positiveInteger, ...$taxGroupProperties], ['id', 'idempotency_key'], $this->unitGroupRules( false ) ),
            $this->write( 'create_tax', 'Create tax', 'Create a tax within an existing tax group after approval.', 'Taxes', 'nexopos.create.taxes', $taxProperties, ['name', 'rate', 'tax_group_id', 'idempotency_key'], $this->taxRules( true ) ),
            $this->write( 'update_tax', 'Update tax', 'Patch a tax and its tax-group assignment after approval.', 'Taxes', 'nexopos.update.taxes', ['id' => $positiveInteger, ...$taxProperties], ['id', 'idempotency_key'], $this->taxRules( false ) ),
            $this->write( 'create_coupon', 'Create coupon', 'Create a coupon with bounded eligibility restrictions after approval.', 'Customers', 'nexopos.create.coupons', $couponProperties, ['name', 'code', 'type', 'discount_value', 'idempotency_key'], $this->couponRules( true ) ),
            $this->write( 'update_coupon', 'Update coupon', 'Patch a coupon. Omitted eligibility lists stay unchanged; empty lists clear them.', 'Customers', 'nexopos.update.coupons', ['id' => $positiveInteger, ...$couponProperties], ['id', 'idempotency_key'], $this->couponRules( false ) ),
            $this->write( 'create_customer_group', 'Create customer group', 'Create a customer group after approval.', 'Customers', 'nexopos.create.customers-groups', $customerGroupProperties, ['name', 'idempotency_key'], $this->customerGroupRules( true ) ),
            $this->write( 'update_customer_group', 'Update customer group', 'Patch a customer group after approval.', 'Customers', 'nexopos.update.customers-groups', ['id' => $positiveInteger, ...$customerGroupProperties], ['id', 'idempotency_key'], $this->customerGroupRules( false ) ),
            $this->write( 'update_media', 'Replace media content', 'Replace the content of an existing media item without changing its ID or URL.', 'Media', 'nexopos.update.medias', ['media_id' => $positiveInteger, 'base64_content' => ['type' => 'string'], 'idempotency_key' => $idempotency], ['media_id', 'base64_content', 'idempotency_key'], ['media_id' => ['required', 'integer', 'min:1'], 'base64_content' => ['required', 'string'], 'idempotency_key' => ['required', 'string', 'max:100']], true ),
            $this->write( 'generate_store_logo', 'Generate store logo', 'Generate transparent square and landscape PNG logos and assign them to store and receipt settings.', 'Settings', ['nexopos.upload.medias', 'manage.options'], ['prompt' => ['type' => 'string', 'minLength' => 3, 'maxLength' => 2000], 'idempotency_key' => $idempotency], ['prompt', 'idempotency_key'], ['prompt' => ['required', 'string', 'min:3', 'max:2000'], 'idempotency_key' => ['required', 'string', 'max:100']], true ),
            $this->read( 'search_tax_groups', 'Search tax groups', 'Search tax groups with tax counts and total rates.', 'Taxes', 'nexopos.read.taxes', ['search' => ['type' => ['string', 'null']], 'limit' => ['type' => ['integer', 'null'], 'minimum' => 1, 'maximum' => 50]], ['search' => ['nullable', 'string', 'max:255'], 'limit' => ['nullable', 'integer', 'between:1,50']] ),
            $this->read( 'get_coupon', 'Get coupon', 'Get complete coupon configuration and eligibility lists.', 'Customers', 'nexopos.read.coupons', ['id' => $positiveInteger], ['id' => ['required', 'integer', 'min:1']], ['id'] ),
        ];
    }

    /** @return array<string, mixed> */
    private function unitGroupRules( bool $create ): array
    {
        return ['name' => [$create ? 'required' : 'sometimes', 'string', 'max:255'], 'description' => ['sometimes', 'nullable', 'string', 'max:1000'], 'idempotency_key' => ['required', 'string', 'max:100']];
    }

    /** @return array<string, mixed> */
    private function unitRules( bool $create ): array
    {
        return [
            'name' => [$create ? 'required' : 'sometimes', 'string', 'max:255'], 'identifier' => ['sometimes', 'nullable', 'string', 'max:100'],
            'group_id' => [$create ? 'required' : 'sometimes', 'integer', 'min:1'], 'value' => [$create ? 'required' : 'sometimes', 'numeric', 'gt:0'],
            'base_unit' => [$create ? 'required' : 'sometimes', 'boolean'], 'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'preview_url' => ['sometimes', 'nullable', 'url', 'max:2048'], 'idempotency_key' => ['required', 'string', 'max:100'],
        ];
    }

    /** @return array<string, mixed> */
    private function taxRules( bool $create ): array
    {
        return ['name' => [$create ? 'required' : 'sometimes', 'string', 'max:255'], 'rate' => [$create ? 'required' : 'sometimes', 'numeric', 'min:0'], 'tax_group_id' => [$create ? 'required' : 'sometimes', 'integer', 'min:1'], 'description' => ['sometimes', 'nullable', 'string', 'max:1000'], 'idempotency_key' => ['required', 'string', 'max:100']];
    }

    /** @return array<string, mixed> */
    private function couponRules( bool $create ): array
    {
        $rules = [
            'name' => [$create ? 'required' : 'sometimes', 'string', 'max:255'], 'code' => [$create ? 'required' : 'sometimes', 'string', 'max:255'],
            'type' => [$create ? 'required' : 'sometimes', 'in:percentage_discount,flat_discount'], 'discount_value' => [$create ? 'required' : 'sometimes', 'numeric', 'min:0'],
            'valid_until' => ['sometimes', 'nullable', 'date'], 'minimum_cart_value' => ['sometimes', 'numeric', 'min:0'], 'maximum_cart_value' => ['sometimes', 'numeric', 'min:0'],
            'valid_hours_start' => ['sometimes', 'nullable', 'date'], 'valid_hours_end' => ['sometimes', 'nullable', 'date'], 'limit_usage' => ['sometimes', 'integer', 'min:0'],
            'idempotency_key' => ['required', 'string', 'max:100'],
        ];
        foreach ( ['product_ids', 'category_ids', 'customer_ids', 'customer_group_ids'] as $field ) {
            $rules[$field] = ['sometimes', 'array', 'max:100'];
            $rules[$field . '.*'] = ['integer', 'min:1', 'distinct'];
        }

        return $rules;
    }

    /** @return array<string, mixed> */
    private function customerGroupRules( bool $create ): array
    {
        return ['name' => [$create ? 'required' : 'sometimes', 'string', 'max:255'], 'description' => ['sometimes', 'nullable', 'string', 'max:1000'], 'reward_system_id' => ['sometimes', 'nullable', 'integer', 'min:1'], 'minimal_credit_payment' => ['sometimes', 'integer', 'between:0,100'], 'idempotency_key' => ['required', 'string', 'max:100']];
    }

    /** @param array<string, mixed> $properties @param list<string> $required @param array<string, mixed> $rules */
    private function write( string $name, string $title, string $description, string $category, string|array $permission, array $properties, array $required, array $rules, bool $confirmation = false ): OxenToolDefinition
    {
        return $this->definition( $name, $title, $description, $category, $permission, $properties, $rules, $required, 'write', $confirmation );
    }

    /** @param array<string, mixed> $properties @param array<string, mixed> $rules @param list<string> $required */
    private function read( string $name, string $title, string $description, string $category, string $permission, array $properties, array $rules, array $required = [] ): OxenToolDefinition
    {
        return $this->definition( $name, $title, $description, $category, $permission, $properties, $rules, $required );
    }

    /** @param array<string, mixed> $properties @param array<string, mixed> $rules @param list<string> $required */
    private function definition( string $name, string $title, string $description, string $category, string|array $permission, array $properties, array $rules, array $required, string $risk = 'read', bool $confirmation = false ): OxenToolDefinition
    {
        return new OxenToolDefinition( $name, $title, $description, $category, 'NsOxen', ['type' => 'object', 'properties' => $properties, 'required' => $required, 'additionalProperties' => false], ['type' => 'object', 'additionalProperties' => true], $rules, $risk === 'read' ? 'oxen:read' : 'oxen:write', (array) $permission, $risk, $confirmation );
    }
}
