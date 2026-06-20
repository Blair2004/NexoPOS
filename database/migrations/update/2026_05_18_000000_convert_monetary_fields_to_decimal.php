<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if ( Schema::hasTable( 'nexopos_customers_account_history' ) ) {
            Schema::table( 'nexopos_customers_account_history', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_customers_account_history', 'previous_amount' ) ) {
                    $table->decimal( 'previous_amount', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_customers_account_history', 'amount' ) ) {
                    $table->decimal( 'amount', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_customers_account_history', 'next_amount' ) ) {
                    $table->decimal( 'next_amount', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_transactions' ) ) {
            Schema::table( 'nexopos_transactions', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_transactions', 'value' ) ) {
                    $table->decimal( 'value', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_orders_coupons' ) ) {
            Schema::table( 'nexopos_orders_coupons', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_orders_coupons', 'minimum_cart_value' ) ) {
                    $table->decimal( 'minimum_cart_value', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_orders_coupons', 'maximum_cart_value' ) ) {
                    $table->decimal( 'maximum_cart_value', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_orders_coupons', 'value' ) ) {
                    $table->decimal( 'value', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_orders_payments' ) ) {
            Schema::table( 'nexopos_orders_payments', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_orders_payments', 'value' ) ) {
                    $table->decimal( 'value', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_orders_products' ) ) {
            Schema::table( 'nexopos_orders_products', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_orders_products', 'unit_price' ) ) {
                    $table->decimal( 'unit_price', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_orders_products', 'price_gross' ) ) {
                    $table->decimal( 'price_gross', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_orders_products', 'price_net' ) ) {
                    $table->decimal( 'price_net', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_orders_products', 'wholesale_tax_value' ) ) {
                    $table->decimal( 'wholesale_tax_value', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_orders_products', 'sale_tax_value' ) ) {
                    $table->decimal( 'sale_tax_value', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_orders_products', 'tax_value' ) ) {
                    $table->decimal( 'tax_value', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_orders_products', 'total_price' ) ) {
                    $table->decimal( 'total_price', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_orders_products', 'total_price_gross' ) ) {
                    $table->decimal( 'total_price_gross', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_orders_products', 'total_price_net' ) ) {
                    $table->decimal( 'total_price_net', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_orders_products', 'total_purchase_price' ) ) {
                    $table->decimal( 'total_purchase_price', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_orders' ) ) {
            Schema::table( 'nexopos_orders', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_orders', 'shipping' ) ) {
                    $table->decimal( 'shipping', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_orders', 'total_without_tax' ) ) {
                    $table->decimal( 'total_without_tax', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_orders', 'subtotal' ) ) {
                    $table->decimal( 'subtotal', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_orders', 'total_with_tax' ) ) {
                    $table->decimal( 'total_with_tax', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_orders', 'total_coupons' ) ) {
                    $table->decimal( 'total_coupons', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_orders', 'total_cogs' ) ) {
                    $table->decimal( 'total_cogs', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_orders', 'total' ) ) {
                    $table->decimal( 'total', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_orders', 'tax_value' ) ) {
                    $table->decimal( 'tax_value', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_orders', 'products_tax_value' ) ) {
                    $table->decimal( 'products_tax_value', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_orders', 'tendered' ) ) {
                    $table->decimal( 'tendered', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_orders', 'change' ) ) {
                    $table->decimal( 'change', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_orders_taxes' ) ) {
            Schema::table( 'nexopos_orders_taxes', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_orders_taxes', 'tax_value' ) ) {
                    $table->decimal( 'tax_value', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_procurements_products' ) ) {
            Schema::table( 'nexopos_procurements_products', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_procurements_products', 'gross_purchase_price' ) ) {
                    $table->decimal( 'gross_purchase_price', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_procurements_products', 'net_purchase_price' ) ) {
                    $table->decimal( 'net_purchase_price', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_procurements_products', 'purchase_price' ) ) {
                    $table->decimal( 'purchase_price', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_procurements_products', 'tax_value' ) ) {
                    $table->decimal( 'tax_value', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_procurements_products', 'total_purchase_price' ) ) {
                    $table->decimal( 'total_purchase_price', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_procurements' ) ) {
            Schema::table( 'nexopos_procurements', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_procurements', 'value' ) ) {
                    $table->decimal( 'value', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_procurements', 'cost' ) ) {
                    $table->decimal( 'cost', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_procurements', 'tax_value' ) ) {
                    $table->decimal( 'tax_value', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_products_histories' ) ) {
            Schema::table( 'nexopos_products_histories', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_products_histories', 'unit_price' ) ) {
                    $table->decimal( 'unit_price', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_products_histories', 'total_price' ) ) {
                    $table->decimal( 'total_price', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_products' ) ) {
            Schema::table( 'nexopos_products', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_products', 'tax_value' ) ) {
                    $table->decimal( 'tax_value', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_products_taxes' ) ) {
            Schema::table( 'nexopos_products_taxes', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_products_taxes', 'value' ) ) {
                    $table->decimal( 'value', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_products_unit_quantities' ) ) {
            Schema::table( 'nexopos_products_unit_quantities', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'sale_price' ) ) {
                    $table->decimal( 'sale_price', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'sale_price_edit' ) ) {
                    $table->decimal( 'sale_price_edit', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'sale_price_net' ) ) {
                    $table->decimal( 'sale_price_net', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'sale_price_gross' ) ) {
                    $table->decimal( 'sale_price_gross', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'sale_price_tax' ) ) {
                    $table->decimal( 'sale_price_tax', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'wholesale_price' ) ) {
                    $table->decimal( 'wholesale_price', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'wholesale_price_edit' ) ) {
                    $table->decimal( 'wholesale_price_edit', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'wholesale_price_gross' ) ) {
                    $table->decimal( 'wholesale_price_gross', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'wholesale_price_net' ) ) {
                    $table->decimal( 'wholesale_price_net', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'wholesale_price_tax' ) ) {
                    $table->decimal( 'wholesale_price_tax', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'custom_price' ) ) {
                    $table->decimal( 'custom_price', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'custom_price_edit' ) ) {
                    $table->decimal( 'custom_price_edit', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'custom_price_gross' ) ) {
                    $table->decimal( 'custom_price_gross', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'custom_price_net' ) ) {
                    $table->decimal( 'custom_price_net', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'custom_price_tax' ) ) {
                    $table->decimal( 'custom_price_tax', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'cogs' ) ) {
                    $table->decimal( 'cogs', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_providers' ) ) {
            Schema::table( 'nexopos_providers', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_providers', 'amount_due' ) ) {
                    $table->decimal( 'amount_due', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_providers', 'amount_paid' ) ) {
                    $table->decimal( 'amount_paid', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_registers_history' ) ) {
            Schema::table( 'nexopos_registers_history', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_registers_history', 'value' ) ) {
                    $table->decimal( 'value', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_registers_history', 'balance_before' ) ) {
                    $table->decimal( 'balance_before', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_registers_history', 'balance_after' ) ) {
                    $table->decimal( 'balance_after', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_registers' ) ) {
            Schema::table( 'nexopos_registers', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_registers', 'balance' ) ) {
                    $table->decimal( 'balance', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_rewards_system_rules' ) ) {
            Schema::table( 'nexopos_rewards_system_rules', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_rewards_system_rules', 'from' ) ) {
                    $table->decimal( 'from', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_rewards_system_rules', 'to' ) ) {
                    $table->decimal( 'to', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_rewards_system_rules', 'reward' ) ) {
                    $table->decimal( 'reward', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_rewards_system' ) ) {
            Schema::table( 'nexopos_rewards_system', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_rewards_system', 'target' ) ) {
                    $table->decimal( 'target', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_units' ) ) {
            Schema::table( 'nexopos_units', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_units', 'value' ) ) {
                    $table->decimal( 'value', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_transactions_histories' ) ) {
            Schema::table( 'nexopos_transactions_histories', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_transactions_histories', 'value' ) ) {
                    $table->decimal( 'value', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_orders_refunds' ) ) {
            Schema::table( 'nexopos_orders_refunds', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_orders_refunds', 'total' ) ) {
                    $table->decimal( 'total', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_orders_refunds', 'tax_value' ) ) {
                    $table->decimal( 'tax_value', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_orders_refunds', 'shipping' ) ) {
                    $table->decimal( 'shipping', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_orders_products_refunds' ) ) {
            Schema::table( 'nexopos_orders_products_refunds', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_orders_products_refunds', 'unit_price' ) ) {
                    $table->decimal( 'unit_price', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_orders_products_refunds', 'tax_value' ) ) {
                    $table->decimal( 'tax_value', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_orders_products_refunds', 'total_price' ) ) {
                    $table->decimal( 'total_price', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_dashboard_months' ) ) {
            Schema::table( 'nexopos_dashboard_months', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_dashboard_months', 'month_taxes' ) ) {
                    $table->decimal( 'month_taxes', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_dashboard_months', 'month_unpaid_orders' ) ) {
                    $table->decimal( 'month_unpaid_orders', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_dashboard_months', 'month_paid_orders' ) ) {
                    $table->decimal( 'month_paid_orders', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_dashboard_months', 'month_partially_paid_orders' ) ) {
                    $table->decimal( 'month_partially_paid_orders', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_dashboard_months', 'month_income' ) ) {
                    $table->decimal( 'month_income', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_dashboard_months', 'month_wasted_goods' ) ) {
                    $table->decimal( 'month_wasted_goods', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_dashboard_months', 'month_expenses' ) ) {
                    $table->decimal( 'month_expenses', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_dashboard_months', 'total_wasted_goods' ) ) {
                    $table->decimal( 'total_wasted_goods', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_dashboard_months', 'total_unpaid_orders' ) ) {
                    $table->decimal( 'total_unpaid_orders', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_dashboard_months', 'total_paid_orders' ) ) {
                    $table->decimal( 'total_paid_orders', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_dashboard_months', 'total_partially_paid_orders' ) ) {
                    $table->decimal( 'total_partially_paid_orders', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_dashboard_months', 'total_income' ) ) {
                    $table->decimal( 'total_income', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_dashboard_months', 'total_taxes' ) ) {
                    $table->decimal( 'total_taxes', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_dashboard_months', 'total_expenses' ) ) {
                    $table->decimal( 'total_expenses', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_coupons' ) ) {
            Schema::table( 'nexopos_coupons', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_coupons', 'minimum_cart_value' ) ) {
                    $table->decimal( 'minimum_cart_value', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_coupons', 'maximum_cart_value' ) ) {
                    $table->decimal( 'maximum_cart_value', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_customers_rewards' ) ) {
            Schema::table( 'nexopos_customers_rewards', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_customers_rewards', 'points' ) ) {
                    $table->decimal( 'points', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_customers_rewards', 'target' ) ) {
                    $table->decimal( 'target', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_orders_instalments' ) ) {
            Schema::table( 'nexopos_orders_instalments', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_orders_instalments', 'amount' ) ) {
                    $table->decimal( 'amount', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_products_subitems' ) ) {
            Schema::table( 'nexopos_products_subitems', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_products_subitems', 'sale_price' ) ) {
                    $table->decimal( 'sale_price', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_products_subitems', 'total_price' ) ) {
                    $table->decimal( 'total_price', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_transactions_balance_days' ) ) {
            Schema::table( 'nexopos_transactions_balance_days', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_transactions_balance_days', 'opening_balance' ) ) {
                    $table->decimal( 'opening_balance', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_transactions_balance_days', 'income' ) ) {
                    $table->decimal( 'income', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_transactions_balance_days', 'expense' ) ) {
                    $table->decimal( 'expense', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_transactions_balance_days', 'closing_balance' ) ) {
                    $table->decimal( 'closing_balance', 18, 5 )->default(0)->change();
                }
            });
        }

        if ( Schema::hasTable( 'nexopos_transactions_balance_months' ) ) {
            Schema::table( 'nexopos_transactions_balance_months', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_transactions_balance_months', 'opening_balance' ) ) {
                    $table->decimal( 'opening_balance', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_transactions_balance_months', 'income' ) ) {
                    $table->decimal( 'income', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_transactions_balance_months', 'expense' ) ) {
                    $table->decimal( 'expense', 18, 5 )->default(0)->change();
                }
                if ( Schema::hasColumn( 'nexopos_transactions_balance_months', 'closing_balance' ) ) {
                    $table->decimal( 'closing_balance', 18, 5 )->default(0)->change();
                }
            });
        }

    }

    public function down()
    {
        // Down migration
    }
};
