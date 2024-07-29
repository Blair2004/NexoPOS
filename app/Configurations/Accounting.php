<?php
namespace App\Configurations;

use App\Classes\SettingForm;

class Accounting extends Configuration
{
    public function config()
    {
        return Configuration::form(
            title: __( 'Accounting' ),
            description: __( 'Configure your accounting settings.' ),
            tabs: SettingForm::tabs(
                SettingForm::tab(
                    title: __( 'General' ),
                    fields: [
                        SettingForm::text(
                            name: 'ns_sales_refunds_account',
                            title: __( 'Sales Refunds Account' ),
                            description: __( 'The account where sales refunds will be recorded.' ),
                            default: '1',
                        ),
                        SettingForm::text(
                            name: 'ns_sales_shipping_account',
                            title: __( 'Sales Shipping Account' ),
                            description: __( 'The account where sales shipping fees will be recorded.' ),
                            default: '2',
                        ),
                    ],
                ),
            ),
            controls: Configuration::controls(
                Configuration::button( __( 'Save' ), action: ns()->route( '' ) ),
                Configuration::button( __( 'Set Defaults' ) )
            ),
        );
    }

    public function autoload()
    {
        return true;
    }
}