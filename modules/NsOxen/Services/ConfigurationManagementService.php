<?php

namespace Modules\NsOxen\Services;

use App\Models\Coupon;
use App\Models\CouponCategory;
use App\Models\CouponCustomer;
use App\Models\CouponCustomerGroup;
use App\Models\CouponProduct;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\RewardSystem;
use App\Models\Tax;
use App\Models\TaxGroup;
use App\Models\Unit;
use App\Models\UnitGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ConfigurationManagementService
{
    private const COUPON_RELATIONS = [
        'product_ids' => [CouponProduct::class, Product::class, 'product_id'],
        'category_ids' => [CouponCategory::class, ProductCategory::class, 'category_id'],
        'customer_ids' => [CouponCustomer::class, Customer::class, 'customer_id'],
        'customer_group_ids' => [CouponCustomerGroup::class, CustomerGroup::class, 'group_id'],
    ];

    /** @return array<string, mixed> */
    public function saveUnitGroup( User $user, ?int $id, array $input ): array
    {
        $this->ensurePatch( $id, $input, ['name', 'description'] );
        $group = $id === null ? new UnitGroup : UnitGroup::query()->find( $id );
        if ( ! $group instanceof UnitGroup ) {
            throw new OxenException( 'NOT_FOUND', __m( 'Unit group not found.', 'NsOxen' ), 404 );
        }

        foreach ( ['name', 'description'] as $field ) {
            if ( array_key_exists( $field, $input ) ) {
                $group->{$field} = $input[$field];
            }
        }
        $group->author_id = $user->id;
        $group->save();

        return $group->only( ['id', 'name', 'description', 'author_id'] );
    }

    /** @return array<string, mixed> */
    public function saveUnit( User $user, ?int $id, array $input ): array
    {
        $this->ensurePatch( $id, $input, ['name', 'identifier', 'group_id', 'value', 'base_unit', 'description', 'preview_url'] );
        return DB::transaction( function () use ( $user, $id, $input ): array {
            $unit = $id === null ? new Unit : Unit::query()->whereKey( $id )->lockForUpdate()->first();
            if ( ! $unit instanceof Unit ) {
                throw new OxenException( 'NOT_FOUND', __m( 'Unit not found.', 'NsOxen' ), 404 );
            }

            $groupId = (int) ( $input['group_id'] ?? $unit->group_id );
            if ( ! UnitGroup::query()->whereKey( $groupId )->lockForUpdate()->exists() ) {
                throw new OxenException( 'VALIDATION_FAILED', __m( 'The selected unit group does not exist.', 'NsOxen' ) );
            }

            $name = (string) ( $input['name'] ?? $unit->name );
            $identifier = array_key_exists( 'identifier', $input )
                ? ( filled( $input['identifier'] ) ? (string) $input['identifier'] : Str::slug( $name ) )
                : ( $id === null ? Str::slug( $name ) : (string) $unit->identifier );
            if ( $identifier === '' ) {
                throw new OxenException( 'VALIDATION_FAILED', __m( 'The unit identifier could not be generated.', 'NsOxen' ) );
            }
            if ( Unit::query()->where( 'identifier', $identifier )->when( $id !== null, fn ( $query ) => $query->whereKeyNot( $id ) )->exists() ) {
                throw new OxenException( 'CONFLICT', __m( 'The unit identifier is already in use.', 'NsOxen' ), 409 );
            }

            $baseUnit = array_key_exists( 'base_unit', $input ) ? (bool) $input['base_unit'] : ( $id === null ? false : (bool) $unit->base_unit );
            if ( $baseUnit ) {
                Unit::query()->where( 'group_id', $groupId )->when( $id !== null, fn ( $query ) => $query->whereKeyNot( $id ) )->update( ['base_unit' => false] );
            }

            foreach ( ['name', 'value', 'description', 'preview_url'] as $field ) {
                if ( array_key_exists( $field, $input ) ) {
                    $unit->{$field} = $input[$field];
                }
            }
            $unit->identifier = $identifier;
            $unit->group_id = $groupId;
            $unit->base_unit = $baseUnit;
            $unit->author_id = $user->id;
            $unit->save();

            return $unit->only( ['id', 'name', 'identifier', 'group_id', 'value', 'base_unit', 'description', 'preview_url', 'author_id'] );
        }, 3 );
    }

    /** @return array<string, mixed> */
    public function saveTaxGroup( User $user, ?int $id, array $input ): array
    {
        $this->ensurePatch( $id, $input, ['name', 'description'] );
        $group = $id === null ? new TaxGroup : TaxGroup::query()->find( $id );
        if ( ! $group instanceof TaxGroup ) {
            throw new OxenException( 'NOT_FOUND', __m( 'Tax group not found.', 'NsOxen' ), 404 );
        }
        foreach ( ['name', 'description'] as $field ) {
            if ( array_key_exists( $field, $input ) ) {
                $group->{$field} = $input[$field];
            }
        }
        $group->author_id = $user->id;
        $group->save();

        return $group->only( ['id', 'name', 'description', 'author_id'] );
    }

    /** @return array<string, mixed> */
    public function saveTax( User $user, ?int $id, array $input ): array
    {
        $this->ensurePatch( $id, $input, ['name', 'rate', 'tax_group_id', 'description'] );
        $tax = $id === null ? new Tax : Tax::query()->find( $id );
        if ( ! $tax instanceof Tax ) {
            throw new OxenException( 'NOT_FOUND', __m( 'Tax not found.', 'NsOxen' ), 404 );
        }
        $groupId = (int) ( $input['tax_group_id'] ?? $tax->tax_group_id );
        if ( ! TaxGroup::query()->whereKey( $groupId )->exists() ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'The selected tax group does not exist.', 'NsOxen' ) );
        }
        foreach ( ['name', 'rate', 'description'] as $field ) {
            if ( array_key_exists( $field, $input ) ) {
                $tax->{$field} = $input[$field];
            }
        }
        $tax->tax_group_id = $groupId;
        $tax->author_id = $user->id;
        $tax->save();

        return $tax->only( ['id', 'name', 'rate', 'tax_group_id', 'description', 'author_id'] );
    }

    /** @return array<string, mixed> */
    public function saveCustomerGroup( User $user, ?int $id, array $input ): array
    {
        $this->ensurePatch( $id, $input, ['name', 'description', 'reward_system_id', 'minimal_credit_payment'] );
        $group = $id === null ? new CustomerGroup : CustomerGroup::query()->find( $id );
        if ( ! $group instanceof CustomerGroup ) {
            throw new OxenException( 'NOT_FOUND', __m( 'Customer group not found.', 'NsOxen' ), 404 );
        }
        $rewardSystemId = array_key_exists( 'reward_system_id', $input ) ? $input['reward_system_id'] : $group->reward_system_id;
        if ( $rewardSystemId !== null && (int) $rewardSystemId !== 0 && ! RewardSystem::query()->whereKey( (int) $rewardSystemId )->exists() ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'The selected reward system does not exist.', 'NsOxen' ) );
        }
        foreach ( ['name', 'description', 'minimal_credit_payment'] as $field ) {
            if ( array_key_exists( $field, $input ) ) {
                $group->{$field} = $input[$field];
            }
        }
        $group->reward_system_id = $rewardSystemId === null ? null : (int) $rewardSystemId;
        $group->author_id = $user->id;
        $group->save();

        return $group->only( ['id', 'name', 'description', 'reward_system_id', 'minimal_credit_payment', 'author_id'] );
    }

    /** @return array<string, mixed> */
    public function saveCoupon( User $user, ?int $id, array $input ): array
    {
        $this->ensurePatch( $id, $input, ['name', 'code', 'type', 'discount_value', 'valid_until', 'minimum_cart_value', 'maximum_cart_value', 'valid_hours_start', 'valid_hours_end', 'limit_usage', 'product_ids', 'category_ids', 'customer_ids', 'customer_group_ids'] );
        return DB::transaction( function () use ( $user, $id, $input ): array {
            $coupon = $id === null ? new Coupon : Coupon::query()->whereKey( $id )->lockForUpdate()->first();
            if ( ! $coupon instanceof Coupon ) {
                throw new OxenException( 'NOT_FOUND', __m( 'Coupon not found.', 'NsOxen' ), 404 );
            }

            $attributes = [
                'name' => $input['name'] ?? $coupon->name,
                'code' => $input['code'] ?? $coupon->code,
                'type' => $input['type'] ?? $coupon->type,
                'discount_value' => $input['discount_value'] ?? $coupon->discount_value,
                'valid_until' => array_key_exists( 'valid_until', $input ) ? $input['valid_until'] : $coupon->valid_until,
                'minimum_cart_value' => $input['minimum_cart_value'] ?? ( $coupon->minimum_cart_value ?? 0 ),
                'maximum_cart_value' => $input['maximum_cart_value'] ?? ( $coupon->maximum_cart_value ?? 0 ),
                'valid_hours_start' => array_key_exists( 'valid_hours_start', $input ) ? $input['valid_hours_start'] : $coupon->valid_hours_start,
                'valid_hours_end' => array_key_exists( 'valid_hours_end', $input ) ? $input['valid_hours_end'] : $coupon->valid_hours_end,
                'limit_usage' => $input['limit_usage'] ?? ( $coupon->limit_usage ?? 0 ),
            ];
            $validated = Validator::make( $attributes, [
                'name' => ['required', 'string', 'max:255'],
                'code' => ['required', 'string', 'max:255'],
                'type' => ['required', 'in:' . Coupon::TYPE_PERCENTAGE . ',' . Coupon::TYPE_FLAT],
                'discount_value' => ['required', 'numeric', 'min:0'],
                'valid_until' => ['nullable', 'date'],
                'minimum_cart_value' => ['required', 'numeric', 'min:0'],
                'maximum_cart_value' => ['required', 'numeric', 'min:0'],
                'valid_hours_start' => ['nullable', 'date'],
                'valid_hours_end' => ['nullable', 'date', 'after_or_equal:valid_hours_start'],
                'limit_usage' => ['required', 'integer', 'min:0'],
            ] )->validate();
            if ( (float) $validated['maximum_cart_value'] > 0 && (float) $validated['maximum_cart_value'] < (float) $validated['minimum_cart_value'] ) {
                throw new OxenException( 'VALIDATION_FAILED', __m( 'The maximum cart value must be zero or at least the minimum cart value.', 'NsOxen' ) );
            }
            if ( $validated['type'] === Coupon::TYPE_PERCENTAGE && (float) $validated['discount_value'] > 100 ) {
                throw new OxenException( 'VALIDATION_FAILED', __m( 'Percentage coupon discounts cannot exceed 100.', 'NsOxen' ) );
            }
            if ( Coupon::query()->where( 'code', $validated['code'] )->when( $id !== null, fn ( $query ) => $query->whereKeyNot( $id ) )->exists() ) {
                throw new OxenException( 'CONFLICT', __m( 'The coupon code is already in use.', 'NsOxen' ), 409 );
            }

            foreach ( self::COUPON_RELATIONS as $inputKey => [$relationModel, $targetModel, $targetKey] ) {
                if ( array_key_exists( $inputKey, $input ) ) {
                    $this->ensureRelatedIdsExist( $input[$inputKey], $targetModel, $inputKey );
                }
            }

            foreach ( $validated as $field => $value ) {
                $coupon->{$field} = $value;
            }
            $coupon->author_id = $user->id;
            $coupon->save();

            foreach ( self::COUPON_RELATIONS as $inputKey => [$relationModel, $targetModel, $targetKey] ) {
                if ( ! array_key_exists( $inputKey, $input ) ) {
                    continue;
                }
                $relationModel::query()->where( 'coupon_id', $coupon->id )->delete();
                foreach ( $input[$inputKey] as $targetId ) {
                    /** @var Model $relation */
                    $relation = new $relationModel;
                    $relation->coupon_id = $coupon->id;
                    $relation->{$targetKey} = $targetId;
                    $relation->save();
                }
            }

            return $this->getCoupon( $coupon->id );
        }, 3 );
    }

    /** @return array<string, mixed> */
    public function getCoupon( int $id ): array
    {
        $coupon = Coupon::query()->find( $id );
        if ( ! $coupon instanceof Coupon ) {
            throw new OxenException( 'NOT_FOUND', __m( 'Coupon not found.', 'NsOxen' ), 404 );
        }

        return [
            ...$coupon->only( ['id', 'name', 'code', 'type', 'discount_value', 'valid_until', 'minimum_cart_value', 'maximum_cart_value', 'valid_hours_start', 'valid_hours_end', 'limit_usage', 'author_id'] ),
            'product_ids' => CouponProduct::query()->where( 'coupon_id', $id )->orderBy( 'product_id' )->pluck( 'product_id' )->map( fn ( mixed $value ): int => (int) $value )->all(),
            'category_ids' => CouponCategory::query()->where( 'coupon_id', $id )->orderBy( 'category_id' )->pluck( 'category_id' )->map( fn ( mixed $value ): int => (int) $value )->all(),
            'customer_ids' => CouponCustomer::query()->where( 'coupon_id', $id )->orderBy( 'customer_id' )->pluck( 'customer_id' )->map( fn ( mixed $value ): int => (int) $value )->all(),
            'customer_group_ids' => CouponCustomerGroup::query()->where( 'coupon_id', $id )->orderBy( 'group_id' )->pluck( 'group_id' )->map( fn ( mixed $value ): int => (int) $value )->all(),
        ];
    }

    /** @return array<int, object> */
    public function searchTaxGroups( string $search, int $limit ): array
    {
        return TaxGroup::query()
            ->select( ['id', 'name', 'description'] )
            ->withCount( 'taxes' )
            ->withSum( 'taxes as total_rate', 'rate' )
            ->when( $search !== '', fn ( $query ) => $query->where( 'name', 'like', '%' . $search . '%' ) )
            ->latest( 'id' )
            ->limit( $limit )
            ->get()
            ->all();
    }

    /** @param list<string> $fields */
    private function ensurePatch( ?int $id, array $input, array $fields ): void
    {
        if ( $id !== null && array_intersect( array_keys( $input ), $fields ) === [] ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'At least one field must be supplied for an update.', 'NsOxen' ) );
        }
    }

    /**
     * @param  list<int>  $ids
     * @param  class-string<Model>  $model
     */
    private function ensureRelatedIdsExist( array $ids, string $model, string $field ): void
    {
        $existing = $model::query()->whereKey( $ids )->count();
        if ( $existing !== count( $ids ) ) {
            throw new OxenException( 'VALIDATION_FAILED', sprintf( __m( 'One or more IDs in %s do not exist.', 'NsOxen' ), $field ) );
        }
    }
}
