<?php

namespace App\Forms;

use App\Classes\Hook;
use App\Models\CustomerAddress;
use App\Models\User;
use App\Models\UserAttribute;
use App\Services\CustomerService;
use App\Services\SettingsPage;
use App\Services\UserOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserProfileForm extends SettingsPage
{
    const IDENTIFIER = 'ns.user-profile';

    public function __construct()
    {
        $options = app()->make( UserOptions::class );

        $this->form = [
            'tabs' => Hook::filter( 'ns-user-profile-form', [
                'attribute' => include ( dirname( __FILE__ ) . '/user-profile/attribute.php' ),
                'shipping' => include ( dirname( __FILE__ ) . '/user-profile/shipping.php' ),
                'billing' => include ( dirname( __FILE__ ) . '/user-profile/billing.php' ),
                'security' => include ( dirname( __FILE__ ) . '/user-profile/security.php' ),
                'token' => include ( dirname( __FILE__ ) . '/user-profile/token.php' ),
            ] ),
        ];
    }

    public function saveForm( Request $request )
    {
        ns()->restrict( [ 'manage.profile' ] );

        $validator = Validator::make( $request->input( 'security' ), [] );

        $results = [];
        $results[] = $this->processCredentials( $request, $validator );
        $results[] = $this->processOptions( $request );
        $results[] = $this->processAddresses( $request );
        $results[] = $this->processAttribute( $request );
        $results = collect( $results )->filter( fn( $result ) => ! empty( $result ) )->values();

        return [
            'status' => 'success',
            'message' => __( 'The profile has been successfully saved.' ),
            'data' => compact( 'results', 'validator' ),
        ];
    }

    public function processAttribute( $request )
    {
        $allowedInputs = collect( $this->form[ 'tabs' ][ 'attribute' ][ 'fields' ] )
            ->map( fn( $field ) => $field[ 'name' ] )
            ->toArray();

        if ( ! empty( $allowedInputs ) ) {
            $user = UserAttribute::where( 'user_id', Auth::user()->id )
                ->firstOrNew( [
                    'user_id' => Auth::id(),
                ] );

            foreach ( $request->input( 'attribute' ) as $key => $value ) {
                if ( in_array( $key, $allowedInputs ) ) {
                    $user->$key = strip_tags( $value );
                }
            }

            $user->save();

            return [
                'status' => 'success',
                'message' => __( 'The user attribute has been saved.' ),
            ];
        }

        return [];
    }

    public function processOptions( $request )
    {
        /**
         * @var UserOptions
         */
        $userOptions = app()->make( UserOptions::class );

        if ( $request->input( 'options' ) ) {
            foreach ( $request->input( 'options' ) as $field => $value ) {
                if ( ! in_array( $field, [ 'password', 'old_password', 'password_confirm' ] ) ) {
                    if ( empty( $value ) ) {
                        $userOptions->delete( $field );
                    } else {
                        $userOptions->set( $field, $value );
                    }
                }
            }

            return [
                'status' => 'success',
                'message' => __( 'The options has been successfully updated.' ),
            ];
        }

        return [];
    }

    public function processCredentials( $request, $validator )
    {
        if ( ! empty( $request->input( 'security.old_password' ) ) ) {
            if ( ! Hash::check( $request->input( 'security.old_password' ), Auth::user()->password ) ) {
                $validator->errors()->add( 'security.old_password', __( 'Wrong password provided' ) );

                return [
                    'status' => 'error',
                    'message' => __( 'Wrong old password provided' ),
                ];
            } else {
                $user = User::find( Auth::id() );
                $user->password = Hash::make( $request->input( 'security.password' ) );
                $user->save();

                return [
                    'status' => 'success',
                    'message' => __( 'Password Successfully updated.' ),
                ];
            }
        }

        return [];
    }

    /**
     * Saves address for the logged user.
     */
    public function processAddresses( Request $request ): array
    {
        /**
         * @var CustomerService $customerService
         */
        $customerService = app()->make( CustomerService::class );
        $validFields = collect( $customerService->getAddressFields() )
            ->map( fn( $field ) => $field[ 'name' ] )
            ->toArray();

        $billing = $request->input( 'billing' );
        $shipping = $request->input( 'shipping' );

        $currentBilling = CustomerAddress::from( Auth::id(), 'billing' )->firstOrNew();
        $currentShipping = CustomerAddress::from( Auth::id(), 'shipping' )->firstOrNew();

        foreach ( $validFields as $field ) {
            $currentBilling->$field = $billing[ $field ];
            $currentShipping->$field = $shipping[ $field ];
        }

        $currentBilling->customer_id = Auth::id();
        $currentBilling->type = 'billing';
        $currentBilling->author = Auth::id();
        $currentBilling->save();

        $currentShipping->customer_id = Auth::id();
        $currentShipping->type = 'shipping';
        $currentShipping->author = Auth::id();
        $currentShipping->save();

        return [
            'status' => 'success',
            'message' => __( 'The addresses were successfully updated.' ),
        ];
    }
}
