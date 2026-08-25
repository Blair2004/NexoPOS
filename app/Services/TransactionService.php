<?php

namespace App\Services;

use App\Accounting\AccountingEventCatalog;
use App\Accounting\AccountingRuleValidator;
use App\Classes\Hook;
use App\Classes\JsonResponse;
use App\Events\ShouldRefreshReportEvent;
use App\Events\TransactionAfterCreatedEvent;
use App\Events\TransactionAfterUpdatedEvent;
use App\Exceptions\NotAllowedException;
use App\Exceptions\NotFoundException;
use App\Fields\DirectTransactionFields;
use App\Fields\EntityTransactionFields;
use App\Fields\ReccurringTransactionFields;
use App\Fields\ScheduledTransactionFields;
use App\Models\AccountingJournal;
use App\Models\Order;
use App\Models\PaymentType;
use App\Models\Procurement;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\TransactionAccount;
use App\Models\TransactionActionRule;
use App\Models\TransactionActionRuleLine;
use App\Models\TransactionHistory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionService
{
    public function __construct( public DateService $dateService )
    {
        // ...
    }

    public function triggerRecurringTransaction( Transaction $transaction )
    {
        if ( ! $transaction->recurring ) {
            throw new NotAllowedException( __( 'This transaction is not recurring.' ) );
        }

        if ( (bool) $transaction->active === false ) {
            return [
                'status' => 'info',
                'message' => __( 'An unactive transaction cannot be triggered.' ),
            ];
        }

        $transactionHistory = $this->recordTransactionHistory( $transaction );

        return [
            'status' => 'success',
            'message' => __( 'The recurring transaction has been triggered.' ),
            'data' => compact( 'transaction', 'transactionHistory' ),
        ];
    }

    public function reflectTransactionFromRule( TransactionHistory $transactionHistory, ?TransactionActionRule $rule )
    {
        if ( $transactionHistory->is_reflection ) {
            throw new NotAllowedException( __( 'This transaction history is already a reflection.' ) );
        }

        if ( $transactionHistory->type === Transaction::TYPE_INDIRECT && ! $rule instanceof TransactionActionRule ) {
            throw new NotAllowedException( __( 'To reflect an indirect transaction, a transaction action rule must be provided.' ) );
        }

        $accounts = config( 'accounting' )[ 'accounts' ];
        $subAccount = TransactionAccount::find( $transactionHistory->transaction_account_id );

        if ( $subAccount instanceof TransactionAccount ) {
            /**
             * If the transaction history is not attached
             * to not transaction created manually, it's an indirect transcation
             * and should therefore rely on the rule to determine the account
             */
            if ( $transactionHistory->transaction === null ) {
                $counterAccount = TransactionAccount::find( $rule->offset_account_id );
                $operation = $accounts[ $counterAccount->category_identifier ][ $rule->do ];
            } elseif ( $transactionHistory->transaction instanceof Transaction && in_array( $transactionHistory->transaction->type, [
                Transaction::TYPE_DIRECT,
                Transaction::TYPE_ENTITY,
                Transaction::TYPE_RECURRING,
                Transaction::TYPE_SCHEDULED,
            ] ) ) {
                $operation = $transactionHistory->operation === 'debit' ? 'credit' : 'debit';
                $counterAccount = TransactionAccount::find( ns()->option->get( 'ns_accounting_default_paid_expense_offset_account' ) );
            } else {
                throw new NotAllowedException( __( 'Invalid transaction history provided for reflection.' ) );
            }

            // This will display an error if the offset account is not set.
            if ( ! $counterAccount instanceof TransactionAccount ) {
                throw new NotFoundException( __( 'The offset account is not found.' ) );
            }

            if ( $counterAccount instanceof TransactionAccount ) {
                $counterTransaction = new TransactionHistory;
                $counterTransaction->value = $transactionHistory->value;
                $counterTransaction->transaction_id = $transactionHistory->transaction_id;
                $counterTransaction->operation = $operation;
                $counterTransaction->author_id = $transactionHistory->author_id;
                $counterTransaction->name = $transactionHistory->name;
                $counterTransaction->status = TransactionHistory::STATUS_ACTIVE;
                $counterTransaction->trigger_date = ns()->date->toDateTimeString();
                $counterTransaction->type = $transactionHistory->type;
                $counterTransaction->procurement_id = $transactionHistory->procurement_id;
                $counterTransaction->order_id = $transactionHistory->order_id;
                $counterTransaction->order_refund_id = $transactionHistory->order_refund_id;
                $counterTransaction->order_product_id = $transactionHistory->order_product_id;
                $counterTransaction->order_refund_product_id = $transactionHistory->order_refund_product_id;
                $counterTransaction->register_history_id = $transactionHistory->register_history_id;
                $counterTransaction->customer_account_history_id = $transactionHistory->customer_account_history_id;
                $counterTransaction->transaction_account_id = $counterAccount->id;
                $counterTransaction->is_reflection = true;
                $counterTransaction->reflection_source_id = $transactionHistory->id;

                $counterTransaction->save();
            }
        } else {
            ns()->notification->create(
                title: __( 'Accounting Misconfiguration' ),
                identifier: 'accounting-reflection-transaction-misconfiguration',
                url: ns()->route( 'ns.dashboard.transactions-rules' ),
                description: __( 'Unable to reflect the transaction as the account type is not found.' )
            )->dispatchForPermissions( [ 'nexopos.create.transactions-account' ] );
        }
    }

    /**
     * Get the transaction account by code
     */
    public function deleteTransactionReflection( TransactionHistory $transactionHistory )
    {
        $reflection = TransactionHistory::where( 'reflection_source_id', $transactionHistory->id )->first();

        if ( $reflection instanceof TransactionHistory ) {
            $reflection->delete();

            /**
             * We'll instruct NexoPOS to perform
             * a backend jobs to update the report.
             */
            ShouldRefreshReportEvent::dispatch( $transactionHistory->created_at );

            return [
                'status' => 'success',
                'message' => __( 'The reflection has been deleted.' ),
            ];
        }

        return [
            'status' => 'info',
            'message' => __( 'No reflection found.' ),
        ];
    }

    public function create( $fields )
    {
        $transaction = new Transaction;

        foreach ( $fields as $field => $value ) {
            $transaction->$field = $value;
        }

        $transaction->author_id = Auth::id();
        $transaction->save();

        event( new TransactionAfterCreatedEvent( $transaction, request()->all() ) );

        return [
            'status' => 'success',
            'message' => __( 'The transaction has been successfully saved.' ),
            'data' => compact( 'transaction' ),
        ];
    }

    public function edit( $id, $fields )
    {
        $transaction = $this->get( $id );

        if ( $transaction instanceof Transaction ) {
            foreach ( $fields as $field => $value ) {
                $transaction->$field = $value;
            }

            $transaction->author_id = Auth::id();
            $transaction->save();

            event( new TransactionAfterUpdatedEvent( $transaction, request()->all() ) );

            return [
                'status' => 'success',
                'message' => __( 'The transaction has been successfully updated.' ),
                'data' => compact( 'transaction' ),
            ];
        }

        throw new NotFoundException( __( 'Unable to find the transaction using the provided identifier.' ) );
    }

    /**
     * get a specific transaction using
     * the provided id
     *
     * @throws NotFoundException
     */
    public function get( ?int $id = null ): Collection|Transaction
    {
        if ( $id === null ) {
            return Transaction::get();
        }

        $transaction = Transaction::find( $id );

        if ( ! $transaction instanceof Transaction ) {
            throw new NotFoundException( __( 'Unable to find the requested transaction using the provided id.' ) );
        }

        return $transaction;
    }

    /**
     * Delete an transaction using the
     * provided id
     *
     * @param int transaction id
     * @return array
     */
    public function deleteTransaction( Transaction $transaction )
    {
        $transaction->history()->delete();
        $transaction->delete();

        return [
            'status' => 'success',
            'message' => __( 'The transaction has been correctly deleted.' ),
        ];
    }

    /**
     * Retreive a specific account type
     * or all account type
     */
    public function getTransactionAccountByID( ?int $id = null )
    {
        if ( $id !== null ) {
            $account = TransactionAccount::find( $id );

            if ( ! $account instanceof TransactionAccount ) {
                throw new NotFoundException( __( 'Unable to find the requested account type using the provided id.' ) );
            }

            return $account;
        }

        return TransactionAccount::get();
    }

    /**
     * Get all transaction accounts
     *
     * @return Collection
     */
    public function getSubAccounts()
    {
        return TransactionAccount::whereNotNull( 'sub_category_id' )->get();
    }

    public function getActions()
    {
        return [
            TransactionActionRule::RULE_PROCUREMENT_PAID => __( 'Procurement Paid' ),
            TransactionActionRule::RULE_PROCUREMENT_UNPAID => __( 'Procurement Unpaid' ),
            TransactionActionRule::RULE_PROCUREMENT_FROM_UNPAID_TO_PAID => __( 'Paid Procurement From Unpaid' ),
            TransactionActionRule::RULE_ORDER_PAID => __( 'Order Paid' ),
            TransactionActionRule::RULE_ORDER_UNPAID => __( 'Order Unpaid' ),
            TransactionActionRule::RULE_ORDER_REFUNDED => __( 'Order Refund' ),
            TransactionActionRule::RULE_ORDER_PARTIALLY_PAID => __( 'Order Partially Paid' ),
            TransactionActionRule::RULE_ORDER_PARTIALLY_REFUNDED => __( 'Order Partially Refunded' ),
            TransactionActionRule::RULE_ORDER_FROM_UNPAID_TO_PAID => __( 'Order From Unpaid To Paid' ),
            TransactionActionRule::RULE_ORDER_PAID_VOIDED => __( 'Paid Order Voided' ),
            TransactionActionRule::RULE_ORDER_UNPAID_VOIDED => __( 'Unpaid Order Voided' ),
            TransactionActionRule::RULE_ORDER_COGS => __( 'Order COGS' ),
            TransactionActionRule::RULE_PRODUCT_DAMAGED => __( 'Product Damaged' ),
            TransactionActionRule::RULE_PRODUCT_RETURNED => __( 'Product Returned' ),
        ];
    }

    public function getActionLabel( $action )
    {
        $actions = $this->getActions();

        return $actions[ $action ] ?? __( 'Unknown' );
    }

    public function getRules(): array
    {
        $catalog = app( AccountingEventCatalog::class );

        return [
            'groups' => TransactionActionRule::query()
                ->where( 'active', true )
                ->with( 'lines.account' )
                ->orderBy( 'on' )
                ->get(),
            'events' => $catalog->all(),
        ];
    }

    /**
     * Delete specific account type
     *
     * @param  bool  $force
     * @return array
     */
    public function deleteTransactionAccount( TransactionAccount $account, $force = true )
    {
        if ( $account->transactions->count() > 0 && $force === false ) {
            throw new NotAllowedException( __( 'You cannot delete an account type that has transaction bound.' ) );
        }

        /**
         * if there is not transaction, it
         * won't be looped
         */
        $account->transactions->map( function ( $transaction ) {
            $this->deleteTransaction( $transaction );
        } );

        $account->delete();

        return [
            'status' => 'success',
            'message' => __( 'The account type has been deleted.' ),
        ];
    }

    /**
     * Get a specific transaction
     * account using the provided ID
     *
     * @throws NotFoundException
     */
    public function getTransaction( int $id ): TransactionAccount
    {
        $accountType = TransactionAccount::with( 'transactions' )->find( $id );

        if ( ! $accountType instanceof TransactionAccount ) {
            throw new NotFoundException( __( 'Unable to find the transaction account using the provided ID.' ) );
        }

        return $accountType;
    }

    /**
     * Creates an accounting account
     */
    public function createAccount( array $fields ): array
    {
        $accounting = config( 'accounting' );

        if ( ! isset( $accounting[ 'accounts' ][ $fields[ 'category_identifier' ] ] ) ) {
            throw new NotAllowedException( __( 'The account type is not found.' ) );
        }

        /**
         * if the account is not provided, we'll try to create
         * a custom numbering using the main account number including it's
         * name and the sub account name.
         */
        $fields[ 'account' ] = ! isset( $fields[ 'account' ] ) ? $this->getAccountNumber( $fields[ 'category_identifier' ], $fields[ 'name' ] ) : $fields[ 'account' ];

        /**
         * We want to prevent creating the same account
         * if the account code is similar. This is mostly
         * done for testing purposes.
         */
        $accountCode = explode( '-', $fields[ 'account' ] );
        unset( $accountCode[0] );
        $accountCode = implode( '-', $accountCode );
        $account = TransactionAccount::where( 'account', 'like', '%' . $accountCode . '%' )->firstOrNew();

        foreach ( $fields as $field => $value ) {
            $account->$field = $value;
        }

        $account->author_id = ns()->getValidAuthor();
        $account->save();

        return [
            'status' => 'success',
            'message' => __( 'The account has been created.' ),
            'data' => compact( 'account' ),
        ];
    }

    /**
     * Update specified expense
     * account using a provided ID
     *
     * @todo not covered
     */
    public function editTransactionAccount( TransactionAccount $account, array $fields ): array
    {
        foreach ( $fields as $field => $value ) {
            $account->$field = $value;
        }

        $account->author_id = Auth::id();
        $account->save();

        return [
            'status' => 'success',
            'message' => __( 'The transaction account has been updated.' ),
            'data' => compact( 'account' ),
        ];
    }

    /**
     * Will delete all cash flow
     * related to the specific order
     *
     * @return void
     */
    public function deleteOrderTransactionsHistory( $order )
    {
        TransactionHistory::where( 'order_id', $order->id )->delete();
    }

    /**
     * Will trigger all transactions history
     *
     * @return array
     */
    public function triggerTransactionHistory( TransactionHistory $transactionHistory )
    {
        if ( $transactionHistory->status === TransactionHistory::STATUS_PENDING ) {
            $transactionHistory->status = TransactionHistory::STATUS_ACTIVE;
            $transactionHistory->save();

            return [
                'status' => 'success',
                'message' => __( 'The transaction history has been triggered.' ),
                'data' => compact( 'transactionHistory' ),
            ];
        }

        return [
            'status' => 'error',
            'message' => __( 'The transaction history has already been triggered.' ),
        ];
    }

    /**
     * Will trigger for not recurring transaction
     *
     * @throws NotAllowedException
     */
    public function triggerTransaction( Transaction $transaction ): array
    {
        if ( ! in_array( $transaction->type, [
            Transaction::TYPE_DIRECT,
            Transaction::TYPE_ENTITY,
            Transaction::TYPE_SCHEDULED,
            Transaction::TYPE_INDIRECT,
        ] ) ) {
            throw new NotAllowedException( __( 'This transaction type can\'t be triggered.' ) );
        }

        $histories = $this->recordTransactionHistory( $transaction );

        /**
         * a non recurring transaction
         * once triggered should be disabled to
         * prevent further execution on modification.
         */
        $transaction->active = false;
        $transaction->save();

        return [
            'status' => 'success',
            'message' => __( 'The transaction has been successfully triggered.' ),
            'data' => compact( 'transaction', 'histories' ),
        ];
    }

    /**
     * Will provide all transactions linked to a specific account
     *
     * @param  int        $id the account id
     * @return Collection
     */
    public function getAccountTransactions( $id )
    {
        $accountType = $this->getTransaction( $id );

        return $accountType->transactions;
    }

    /**
     * Will prepare a transaction history based on a transaction reference
     *
     * @return array
     */
    public function prepareTransactionHistoryRecord( Transaction $transaction )
    {
        $history = $this->iniTransactionHistory( $transaction );
        $history->status = TransactionHistory::STATUS_PENDING;
        $history->trigger_date = $transaction->scheduled_date;
        $history->save();

        return [
            'status' => 'success',
            'message' => __( 'The transaction history is created.' ),
        ];
    }

    /**
     * Will prepare a transaction history based on a transaction reference
     *
     * @return TransactionHistory
     *
     * @throws NotFoundException
     */
    public function iniTransactionHistory( Transaction $transaction )
    {
        $mainIdentifier = $transaction->account->category_identifier;
        $mainAccount = config( 'accounting.accounts' )[ $mainIdentifier ];

        if ( ! $mainAccount ) {
            throw new NotFoundException( sprintf( __( 'The account type %s is not found.' ), $mainIdentifier ) );
        }

        $history = new TransactionHistory;
        $history->value = $transaction->value;
        $history->transaction_id = $transaction->id;
        $history->operation = $mainAccount[ 'increase' ]; // if the operation is not defined, by default is a "debit"
        $history->author_id = $transaction->author_id;
        $history->name = $transaction->name;
        $history->status = TransactionHistory::STATUS_ACTIVE;
        $history->trigger_date = ns()->date->toDateTimeString();
        $history->type = $transaction->type;
        $history->procurement_id = $transaction->procurement_id ?? 0; // if the cash flow is created from a procurement
        $history->order_id = $transaction->order_id ?? 0; // if the cash flow is created from a refund
        $history->order_refund_id = $transaction->order_refund_id ?? 0; // if the cash flow is created from a refund
        $history->order_product_id = $transaction->order_product_id ?? 0; // if the cash flow is created from a refund
        $history->order_refund_product_id = $transaction->order_refund_product_id ?? 0; // if the cash flow is created from a refund
        $history->register_history_id = $transaction->register_history_id ?? 0; // if the cash flow is created from a register transaction
        $history->customer_account_history_id = $transaction->customer_account_history_id ?? 0; // if the cash flow is created from a customer payment.
        $history->transaction_account_id = $transaction->account->id;

        return $history;
    }

    /**
     * Will record a transaction history based on a transaction reference
     *
     * @param  Transaction       $transaction
     * @return Collection | bool
     *
     * @throws ModelNotFoundException
     */
    public function recordTransactionHistory( $transaction )
    {
        if ( ! empty( $transaction->group_id ) ) {
            return Role::find( $transaction->group_id )->users()->get()->map( function ( $user ) use ( $transaction ) {
                if ( $transaction->account instanceof TransactionAccount ) {
                    $history = new TransactionHistory;
                    $history->value = $transaction->value;
                    $history->transaction_id = $transaction->id;
                    $history->operation = 'debit';
                    $history->author_id = $transaction->author_id;
                    $history->trigger_date = ns()->date->toDateTimeString();
                    $history->type = $transaction->type;
                    $history->status = TransactionHistory::STATUS_ACTIVE;
                    $history->name = str_replace( '{user}', ucwords( $user->username ), $transaction->name );
                    $history->transaction_account_id = $transaction->account->id;
                    $history->save();

                    return $history;
                }

                return false;
            } )->filter(); // only return valid history created
        } else {
            if ( $transaction->account instanceof TransactionAccount ) {
                $history = $this->iniTransactionHistory( $transaction );
                $history->save();

                return collect( [ $history ] );
            } else {
                throw new ModelNotFoundException( sprintf( 'The transaction account is not found.' ) );
            }
        }
    }

    /**
     * Process recorded transactions
     * and check whether they are supposed to be processed
     * on the current day.
     *
     * @return array of process results.
     */
    public function handleRecurringTransactions( ?Carbon $date = null )
    {
        if ( $date === null ) {
            $date = $this->dateService->copy();
        }

        $processStatus = Transaction::recurring()
            ->active()
            ->get()
            ->map( function ( $transaction ) use ( $date ) {
                switch ( $transaction->occurrence ) {
                    case 'month_starts':
                        $transactionScheduledDate = $date->copy()->startOfMonth();
                        break;
                    case 'month_mid':
                        $transactionScheduledDate = $date->copy()->startOfMonth()->addDays( 14 );
                        break;
                    case 'month_ends':
                        $transactionScheduledDate = $date->copy()->endOfMonth();
                        break;
                    case 'x_before_month_ends':
                        $transactionScheduledDate = $date->copy()->endOfMonth()->subDays( $transaction->occurrence_value );
                        break;
                    case 'x_after_month_starts':
                        $transactionScheduledDate = $date->copy()->startOfMonth()->addDays( $transaction->occurrence_value );
                        break;
                    case 'on_specific_day':
                        $transactionScheduledDate = $date->copy();
                        $transactionScheduledDate->day = $transaction->occurrence_value;
                        break;
                    case 'every_x_minutes':
                        $transactionScheduledDate = $date->copy();
                        $transactionScheduledDate->day = $transaction->occurrence_value;
                        break;
                    case 'every_x_hours':
                        $transactionScheduledDate = $date->copy();
                        $transactionScheduledDate->hour = now()->hour;
                        break;
                    case 'every_x_days':
                        $transactionScheduledDate = $date->copy();
                        $transactionScheduledDate->minute = now()->minute;
                        break;
                }

                if ( isset( $transactionScheduledDate ) && $transactionScheduledDate instanceof Carbon ) {
                    /**
                     * Checks if the recurring transactions about to be saved has been
                     * already issued on the occuring day.
                     */
                    if ( $date->isSameDay( $transactionScheduledDate ) ) {
                        if ( ! $this->hadTransactionHistory( $transactionScheduledDate, $transaction ) ) {
                            $histories = $this->recordTransactionHistory( $transaction );

                            return [
                                'status' => 'success',
                                'data' => compact( 'transaction', 'histories' ),
                                'message' => sprintf( __( 'The transaction "%s" has been processed on day "%s".' ), $transaction->name, $date->toDateTimeString() ),
                            ];
                        }

                        return [
                            'status' => 'error',
                            'message' => sprintf( __( 'The transaction "%s" has already been processed.' ), $transaction->name ),
                        ];
                    }
                }

                return [
                    'status' => 'error',
                    'message' => sprintf( __( 'The transactions "%s" hasn\'t been processed, as it\'s out of date.' ), $transaction->name ),
                ];
            } );

        $successFulProcesses = collect( $processStatus )->filter( fn( $process ) => $process[ 'status' ] === 'success' );

        return [
            'status' => 'success',
            'data' => $processStatus->toArray(),
            'message' => $successFulProcesses->count() === $processStatus->count() ?
                __( 'The process has been correctly executed and all transactions has been processed.' ) :
                    sprintf( __( 'The process has been executed with some failures. %s/%s process(es) succeeded.' ), $successFulProcesses->count(), $processStatus->count() ),
        ];
    }

    /**
     * Check if an transaction has been executed during a day.
     * To prevent many recurring transactions to trigger multiple times
     * during a day.
     */
    public function hadTransactionHistory( $date, Transaction $transaction )
    {
        $history = TransactionHistory::where( 'transaction_id', $transaction->id )
            ->where( 'created_at', '>=', $date->startOfDay()->toDateTimeString() )
            ->where( 'created_at', '<=', $date->endOfDay()->toDateTimeString() )
            ->get();

        return ! $history->isEmpty();
    }

    /**
     * Will record a transaction resulting from a paid procurement
     *
     * @return void
     */
    public function handleProcurementTransaction( Procurement $procurement )
    {
        $transactionHistory = new TransactionHistory;
        $accounts = config( 'accounting' )[ 'accounts' ];

        if ( $procurement->payment_status === Procurement::PAYMENT_UNPAID ) {
            $rule = TransactionActionRule::where( 'on', TransactionActionRule::RULE_PROCUREMENT_UNPAID )->first();
            $transactionHistory->name = sprintf(
                __( 'Unpaid Procurement: %s' ),
                $procurement->name
            );
        } elseif ( $procurement->payment_status === Procurement::PAYMENT_PAID ) {
            /**
             * if the transaction has some previous records
             * this probably means the procurement was initially stored as unpaid.
             * therefore we should use the from unpaid to paid rule.
             */
            $previousRecordsCount = TransactionHistory::where( 'procurement_id', $procurement->id )->count();

            if ( $previousRecordsCount > 0 ) {
                $rule = TransactionActionRule::where( 'on', TransactionActionRule::RULE_PROCUREMENT_FROM_UNPAID_TO_PAID )->first();
                $transactionHistory->name = sprintf(
                    __( 'Paid Procurement: %s' ),
                    $procurement->name
                );
            } else {
                $rule = TransactionActionRule::where( 'on', TransactionActionRule::RULE_PROCUREMENT_PAID )->first();
                $transactionHistory->name = sprintf(
                    __( 'Paid Procurement: %s' ),
                    $procurement->name
                );
            }
        } else {
            throw new NotAllowedException( __( 'The procurement payment status is not supported.' ) );
        }

        /**
         * We'll check if the account and the offset account
         * are found before proceeding.
         */
        if ( ! $rule instanceof TransactionActionRule ) {
            if ( $procurement->payment_status === Procurement::PAYMENT_PAID ) {
                return ns()->notification->create(
                    title: __( 'Accounting Misconfiguration' ),
                    identifier: 'accounting-procurement-misconfiguration',
                    url: ns()->route( 'ns.dashboard.transactions-rules' ),
                    description: __( 'Unable to record accounting transactions for paid procurement. Until the accounts rules are set, records are skipped.' )
                )->dispatchForPermissions( [ 'nexopos.create.transactions-account' ] );
            } else {
                return ns()->notification->create(
                    title: __( 'Accounting Misconfiguration' ),
                    identifier: 'accounting-procurement-misconfiguration',
                    url: ns()->route( 'ns.dashboard.transactions-rules' ),
                    description: __( 'Unable to record accounting transactions for unpaid procurement. Until the accounts rules are set, records are skipped.' )
                )->dispatchForPermissions( [ 'nexopos.create.transactions-account' ] );
            }
        }

        $account = TransactionAccount::find( $rule->account_id );
        $offset = TransactionAccount::find( $rule->offset_account_id );

        if ( ! $account instanceof TransactionAccount ) {
            return ns()->notification->create(
                title: __( 'Accounting Misconfiguration' ),
                identifier: 'accounting-procurement-misconfiguration',
                url: ns()->route( 'ns.dashboard.transactions-rules' ),
                description: sprintf( __( 'Unable to record accounting transactions as the account set to the rule assigned to the action "%s" can\'t be found.' ), $this->getActionLabel( $rule->on ) )
            )->dispatchForPermissions( [ 'nexopos.create.transactions-account' ] );
        }

        if ( ! $offset instanceof TransactionAccount ) {
            return ns()->notification->create(
                title: __( 'Accounting Misconfiguration' ),
                identifier: 'accounting-procurement-misconfiguration',
                url: ns()->route( 'ns.dashboard.transactions-rules' ),
                description: sprintf( __( 'Unable to record accounting transactions as the offset account set to the rule assigned to the action "%s" can\'t be found.' ), $this->getActionLabel( $rule->on ) )
            )->dispatchForPermissions( [ 'nexopos.create.transactions-account' ] );
        }

        $operation = $accounts[ $account->category_identifier ][ $rule->action ];

        $transactionHistory->value = $procurement->cost;
        $transactionHistory->author_id = $procurement->author_id;
        $transactionHistory->transaction_account_id = $account->id;
        $transactionHistory->operation = $operation;
        $transactionHistory->type = Transaction::TYPE_DIRECT;
        $transactionHistory->trigger_date = $procurement->created_at;
        $transactionHistory->status = TransactionHistory::STATUS_ACTIVE;
        $transactionHistory->procurement_id = $procurement->id;
        $transactionHistory->rule_id = $rule->id;
        $transactionHistory->save();
    }

    /**
     * Will record a transaction resulting from a paid procurement
     *
     * @return void
     */
    public function handleUnpaidToPaidSaleTransaction( Order $order )
    {
        $rule = TransactionActionRule::where( 'on', TransactionActionRule::RULE_ORDER_FROM_UNPAID_TO_PAID )->first();

        if ( ! $rule instanceof TransactionActionRule ) {
            return ns()->notification->create(
                title: __( 'Accounting Rule Misconfiguration' ),
                identifier: 'accounting-unpaid-to-paid-order-misconfiguration',
                url: ns()->route( 'ns.dashboard.transactions-rules' ),
                description: sprintf(
                    __( 'Unable to record accounting transactions for the order %s which was initially unpaid and then paid. No rule was set for this.' ),
                    $order->code
                )
            )->dispatchForPermissions( [ 'nexopos.create.transactions-account' ] );
        }

        $accounts = config( 'accounting' )[ 'accounts' ];
        $account = TransactionAccount::find( $rule->account_id );

        if ( ! $account instanceof TransactionAccount ) {
            return ns()->notification->create(
                title: __( 'Accounting Rule Misconfiguration' ),
                identifier: 'accounting-unpaid-to-paid-order-misconfiguration',
                url: ns()->route( 'ns.dashboard.transactions-rules' ),
                description: sprintf(
                    __( 'Unable to record accounting transactions for the order %s which was initially unpaid and then paid. The account set to the rule assigned to the action "%s" can\'t be found.' ),
                    $order->code,
                    $this->getActionLabel( $rule->on )
                )
            )->dispatchForPermissions( [ 'nexopos.create.transactions-account' ] );
        }

        $operation = $accounts[ $account->category_identifier ][ $rule->action ];

        $this->createOrderTransactionHistory(
            order: $order,
            operation: $operation,
            value: 'total',
            name: sprintf(
                __( 'Order: %s' ),
                $order->code
            ),
            account: $account,
            rule: $rule
        );
    }

    public function handleUnpaidToVoidSaleTransaction( Order $order )
    {
        $rule = TransactionActionRule::where( 'on', TransactionActionRule::RULE_ORDER_UNPAID_VOIDED )->first();

        if ( ! $rule instanceof TransactionActionRule ) {
            return ns()->notification->create(
                title: __( 'Accounting Rule Misconfiguration' ),
                identifier: 'accounting-unpaid-to-void-order-misconfiguration',
                url: ns()->route( 'ns.dashboard.transactions-rules' ),
                description: sprintf(
                    __( 'Unable to record accounting transactions for the order %s which was initially unpaid and then voided. No rule was set for this.' ),
                    $order->code
                )
            )->dispatchForPermissions( [ 'nexopos.create.transactions-account' ] );
        }

        $accounts = config( 'accounting' )[ 'accounts' ];
        $account = TransactionAccount::find( $rule->account_id );

        if ( ! $account instanceof TransactionAccount ) {
            return ns()->notification->create(
                title: __( 'Accounting Rule Misconfiguration' ),
                identifier: 'accounting-unpaid-to-void-order-misconfiguration',
                url: ns()->route( 'ns.dashboard.transactions-rules' ),
                description: sprintf(
                    __( 'Unable to record accounting transactions for the order %s which was initially unpaid and then voided. The account set to the rule assigned to the action "%s" can\'t be found.' ),
                    $order->code,
                    $this->getActionLabel( $rule->on )
                )
            )->dispatchForPermissions( [ 'nexopos.create.transactions-account' ] );
        }

        $operation = $accounts[ $account->category_identifier ][ $rule->action ];

        $transactionHistory = $this->createOrderTransactionHistory(
            order: $order,
            operation: $operation,
            value: 'total',
            account: $account,
            name: sprintf(
                __( 'Void Order: %s' ),
                $order->code
            ),
            rule: $rule
        );

        return JsonResponse::success(
            message: __( 'The transaction has been recorded.' ),
            data: compact( 'transactionHistory' )
        );
    }

    /**
     * Will record a transaction resulting from a paid procurement
     *
     * @return void
     */
    public function handlePaidToVoidSaleTransaction( Order $order )
    {
        $rule = TransactionActionRule::where( 'on', TransactionActionRule::RULE_ORDER_PAID_VOIDED )->first();

        if ( ! $rule instanceof TransactionActionRule ) {
            return ns()->notification->create(
                title: __( 'Accounting Rule Misconfiguration' ),
                identifier: 'accounting-paid-to-void-order-misconfiguration',
                url: ns()->route( 'ns.dashboard.transactions-rules' ),
                description: sprintf(
                    __( 'Unable to record accounting transactions for the order %s which was initially paid and then voided. No rule was set for this.' ),
                    $order->code
                )
            )->dispatchForPermissions( [ 'nexopos.create.transactions-account' ] );
        }

        $accounts = config( 'accounting' )[ 'accounts' ];
        $account = TransactionAccount::find( $rule->account_id );

        if ( ! $account instanceof TransactionAccount ) {
            return ns()->notification->create(
                title: __( 'Accounting Rule Misconfiguration' ),
                identifier: 'accounting-paid-to-void-order-misconfiguration',
                url: ns()->route( 'ns.dashboard.transactions-rules' ),
                description: sprintf(
                    __( 'Unable to record accounting transactions for the order %s which was initially paid and then voided. The account set to the rule assigned to the action "%s" can\'t be found.' ),
                    $order->code,
                    $this->getActionLabel( $rule->on )
                )
            )->dispatchForPermissions( [ 'nexopos.create.transactions-account' ] );
        }

        $operation = $accounts[ $account->category_identifier ][ $rule->action ];

        $transactionHistory = $this->createOrderTransactionHistory(
            order: $order,
            operation: $operation,
            value: 'total',
            account: $account,
            name: sprintf(
                __( 'Void Order: %s' ),
                $order->code
            ),
            rule: $rule
        );

        return [
            'status' => 'success',
            'message' => __( 'The transaction has been recorded.' ),
            'data' => compact( 'transactionHistory' ),
        ];
    }

    /**
     * Will record a transaction using an order, rule and other informations
     *
     * @param  string                $operation
     * @param  string                $name
     * @param  TransactionAccount    $account
     * @param  TransactionActionRule $rule
     * @param  string                $value
     * @return TransactionHistory
     */
    private function createOrderTransactionHistory( Order $order, $operation, $name, $account, $rule, $value )
    {
        $transactionHistory = new TransactionHistory;
        $transactionHistory->name = $name;
        $transactionHistory->value = $order->$value;
        $transactionHistory->author_id = $order->author_id;
        $transactionHistory->transaction_account_id = $account->id;
        $transactionHistory->operation = $operation;
        $transactionHistory->type = Transaction::TYPE_INDIRECT;
        $transactionHistory->trigger_date = $order->created_at;
        $transactionHistory->status = TransactionHistory::STATUS_ACTIVE;
        $transactionHistory->order_id = $order->id;
        $transactionHistory->rule_id = $rule->id;
        $transactionHistory->save();

        return $transactionHistory;
    }

    /**
     * Will record a transaction for any created order
     *
     * @return array
     */
    public function handleSaleTransaction( Order $order )
    {
        $ruleOn = match ( $order->payment_status ) {
            Order::PAYMENT_PAID => TransactionActionRule::RULE_ORDER_PAID,
            Order::PAYMENT_UNPAID => TransactionActionRule::RULE_ORDER_UNPAID,
            Order::PAYMENT_REFUNDED => TransactionActionRule::RULE_ORDER_REFUNDED,
            Order::PAYMENT_PARTIALLY => TransactionActionRule::RULE_ORDER_PARTIALLY_PAID,
        };

        $accounts = config( 'accounting' )[ 'accounts' ];
        $rule = TransactionActionRule::where( 'on', $ruleOn )->first();

        if ( ! $rule instanceof TransactionActionRule ) {
            ns()->notification->create(
                title: __( 'Accounting Rule Misconfiguration' ),
                identifier: 'accounting-sale-misconfiguration',
                url: ns()->route( 'ns.dashboard.transactions-rules' ),
                description: sprintf(
                    __( 'Unable to record accounting transactions for the order %s. No rule was set for this.' ),
                    $order->code
                )
            )->dispatchForPermissions( [ 'nexopos.create.transactions-account' ] );

            return [
                'status' => 'error',
                'message' => __( 'The transaction has been skipped.' ),
            ];
        }

        $account = TransactionAccount::find( $rule->account_id );
        $offset = TransactionAccount::find( $rule->offset_account_id );

        if ( ! $account instanceof TransactionAccount ) {
            return ns()->notification->create(
                title: __( 'Accounting Rule Misconfiguration' ),
                identifier: 'accounting-sale-misconfiguration',
                url: ns()->route( 'ns.dashboard.transactions-rules' ),
                description: sprintf(
                    __( 'Unable to record accounting transactions for the order %s. The accounts set to the rule assigned to the action "%s" can\'t be found.' ),
                    $order->code,
                    $this->getActionLabel( $rule->on )
                )
            )->dispatchForPermissions( [ 'nexopos.create.transactions-account' ] );

            return [
                'status' => 'error',
                'message' => __( 'The transaction has been skipped.' ),
            ];
        }

        $operation = $accounts[ $account->category_identifier ][ $rule->action ];

        $transactionHistory = $this->createOrderTransactionHistory(
            order: $order,
            operation: $operation,
            value: 'total',
            name: sprintf(
                __( 'Order: %s' ),
                $order->code
            ),
            account: $account,
            rule: $rule
        );

        return [
            'status' => 'success',
            'message' => __( 'The transaction has been recorded.' ),
            'data' => compact( 'transactionHistory' ),
        ];
    }

    /**
     * Will record COGS transaction for paid order
     *
     * @return array
     */
    public function handleCogsFromSale( Order $order )
    {
        $rule = TransactionActionRule::where( 'on', TransactionActionRule::RULE_ORDER_COGS )->first();
        $accounts = config( 'accounting' )[ 'accounts' ];

        if ( ! $rule instanceof TransactionActionRule ) {
            return ns()->notification->create(
                title: __( 'Accounting Rule Misconfiguration' ),
                identifier: 'accounting-sale-misconfiguration',
                url: ns()->route( 'ns.dashboard.transactions-rules' ),
                description: sprintf(
                    __( 'Unable to record COGS transactions for order %s. Until the accounts rules are set, records are skipped.' ),
                    $order->code
                )
            )->dispatchForPermissions( [ 'nexopos.create.transactions-account' ] );
        }

        /**
         * We'll only take this into account
         * if the order is paid.
         */
        if ( $order->payment_status === Order::PAYMENT_PAID ) {
            $account = TransactionAccount::find( $rule->account_id );
            $offset = TransactionAccount::find( $rule->offset_account_id );

            if ( ! $account instanceof TransactionAccount || ! $offset instanceof TransactionAccount ) {
                return ns()->notification->create(
                    title: __( 'Accounting Rule Misconfiguration' ),
                    identifier: 'accounting-sale-misconfiguration',
                    url: ns()->route( 'ns.dashboard.transactions-rules' ),
                    description: sprintf(
                        __( 'Unable to record COGS transactions for order %s. Make sure either the account or the offset account are set and assigned to the rule %s.' ),
                        $order->code,
                        $this->getActionLabel( $rule->on )
                    )
                )->dispatchForPermissions( [ 'nexopos.create.transactions-account' ] );
            }

            $operation = $accounts[ $account->category_identifier ][ $rule->action ];

            $transactionHistory = $this->createOrderTransactionHistory(
                order: $order,
                operation: $operation,
                value: 'total_cogs',
                name: sprintf(
                    __( 'COGS: %s' ),
                    $order->code
                ),
                account: $account,
                rule: $rule
            );

            return [
                'status' => 'success',
                'message' => __( 'The COGS transaction has been recorded.' ),
                'data' => compact( 'transactionHistory' ),
            ];
        }
    }

    /**
     * Provides the configuration for the transaction
     *
     * @return array
     */
    public function getConfigurations( Transaction $transaction )
    {
        $recurringFields = new ReccurringTransactionFields( $transaction );
        $directFields = new DirectTransactionFields( $transaction );
        $entityFields = new EntityTransactionFields( $transaction );
        $scheduledFields = new ScheduledTransactionFields( $transaction );

        $asyncTransactions = [];
        $warningMessage = false;

        /**
         * Those features can only be enabled
         * if the jobs are configured correctly.
         */
        if ( ns()->canPerformAsynchronousOperations() ) {
            $asyncTransactions = [
                [
                    'identifier' => ReccurringTransactionFields::getIdentifier(),
                    'label' => __( 'Recurring Expense' ),
                    'icon' => asset( 'images/recurring.png' ),
                    'fields' => $recurringFields->get(),
                ], [
                    'identifier' => EntityTransactionFields::getIdentifier(),
                    'label' => __( 'Entity Expense' ),
                    'icon' => asset( 'images/salary.png' ),
                    'fields' => $entityFields->get(),
                ], [
                    'identifier' => ScheduledTransactionFields::getIdentifier(),
                    'label' => __( 'Scheduled Expense' ),
                    'icon' => asset( 'images/schedule.png' ),
                    'fields' => $scheduledFields->get(),
                ],
            ];
        } else {
            $warningMessage = sprintf(
                __( 'Some expense type are disabled as NexoPOS is not able to <a target="_blank" href="%s">perform asynchronous requests</a>.' ),
                'https://my.nexopos.com/en/documentation/troubleshooting/workers-or-async-requests-disabled'
            );
        }

        $configurations = Hook::filter( 'ns-transactions-configurations', [
            [
                'identifier' => DirectTransactionFields::getIdentifier(),
                'label' => __( 'Direct Expense' ),
                'icon' => asset( 'images/budget.png' ),
                'fields' => $directFields->get(),
            ], ...$asyncTransactions,
        ] );

        $recurrence = Hook::filter( 'ns-transactions-recurrence', [
            [
                'type' => 'select',
                'label' => __( 'Condition' ),
                'name' => 'occurrence',
                'value' => $transaction->occurrence ?? '',
                'options' => Hook::filter( 'ns-transactions-recurrence-options', Helper::kvToJsOptions( [
                    Transaction::OCCURRENCE_START_OF_MONTH => __( 'First Day Of Month' ),
                    Transaction::OCCURRENCE_END_OF_MONTH => __( 'Last Day Of Month' ),
                    Transaction::OCCURRENCE_MIDDLE_OF_MONTH => __( 'Month middle Of Month' ),
                    Transaction::OCCURRENCE_X_AFTER_MONTH_STARTS => __( '{day} after month starts' ),
                    Transaction::OCCURRENCE_X_BEFORE_MONTH_ENDS => __( '{day} before month ends' ),
                    Transaction::OCCURRENCE_SPECIFIC_DAY => __( 'Every {day} of the month' ),
                    Transaction::OCCURRENCE_EVERY_X_MINUTES => __( 'Every {minutes}' ),
                    Transaction::OCCURRENCE_EVERY_X_HOURS => __( 'Every {hours}' ),
                    Transaction::OCCURRENCE_EVERY_X_DAYS => __( 'Every {days}' ),
                ] ) ),
            ], [
                'type' => 'number',
                'label' => __( 'Days' ),
                'name' => 'occurrence_value',
                'value' => $transaction->occurrence_value ?? 0,
                'shows' => [
                    'occurrence' => [
                        Transaction::OCCURRENCE_X_AFTER_MONTH_STARTS,
                        Transaction::OCCURRENCE_X_BEFORE_MONTH_ENDS,
                        Transaction::OCCURRENCE_SPECIFIC_DAY,
                        Transaction::OCCURRENCE_EVERY_X_MINUTES,
                        Transaction::OCCURRENCE_EVERY_X_HOURS,
                        Transaction::OCCURRENCE_EVERY_X_DAYS,
                    ],
                ],
                'description' => __( 'Make sure set a day that is likely to be executed' ),
            ],
        ] );

        return compact( 'recurrence', 'configurations', 'warningMessage' );
    }

    /**
     * Deletes procurement transactions
     *
     * @return array
     */
    public function deleteProcurementTransactions( Procurement $procurement )
    {
        $transactionHistories = TransactionHistory::where( 'procurement_id', $procurement->id )
            ->where( 'is_reflection', false )
            ->get();

        foreach ( $transactionHistories as $transactionHistory ) {
            $transactionHistory->delete();
        }

        return [
            'status' => 'success',
            'message' => __( 'The procurement transactions has been deleted.' ),
        ];
    }

    /**
     * Clear and create default accounts.
     *
     * @return array
     */
    public function createDefaultAccounts()
    {
        $this->upgradeAccountingFoundation();

        return [
            'status' => 'success',
            'message' => __( 'The default accounts have been verified.' ),
        ];
    }

    /**
     * Will clear all accounts
     *
     * @return array
     */
    public function clearAllAccounts()
    {
        TransactionHistory::truncate();
        AccountingJournal::truncate();
        TransactionActionRuleLine::truncate();
        TransactionActionRule::truncate();
        Transaction::truncate();
        TransactionAccount::truncate();

        return [
            'status' => 'success',
            'message' => __( 'The accounts configuration was cleared' ),
        ];
    }

    /**
     * Returns the account number using an account name and a current name
     *
     * @return string
     */
    public function getAccountNumber( string $accountName, string $currentName )
    {
        $accounts = config( 'accounting' )[ 'accounts' ];
        $account = $accounts[ $accountName ];

        if ( $account ) {
            $count = TransactionAccount::where( 'category_identifier', $accountName )->count();

            return $account[ 'account' ] + ( $count + 1 ) . '-' . Str::slug( $accountName ) . '-' . Str::slug( $currentName );
        }

        throw new NotAllowedException( __( 'Invalid account name' ) );
    }

    /**
     * Creates all sub accounts
     * and creates accounting rules.
     */
    public function createAllSubAccounts(): void
    {
        $this->upgradeAccountingFoundation();
    }

    /**
     * Sets transaction rule
     */
    public function setTransactionActionRule( string $on, string $action, int $account_id, string $do, int $offset_account_id, ?TransactionActionRule $transactionActionRule = null ): array
    {
        $transactionActionRule = $transactionActionRule instanceof TransactionActionRule
            ? $transactionActionRule
            : TransactionActionRule::firstOrNew( [ 'on' => $on ] );
        $transactionActionRule->on = $on;
        $transactionActionRule->action = $action;
        $transactionActionRule->account_id = $account_id;
        $transactionActionRule->do = $do;
        $transactionActionRule->offset_account_id = $offset_account_id;
        $transactionActionRule->save();

        return [
            'status' => 'success',
            'message' => __( 'The accounting action has been saved' ),
        ];
    }

    /**
     * Saves transactions rule.
     */
    public function saveTransactionRule( array $rule ): array
    {
        app( AccountingRuleValidator::class )->validate( $rule );

        $transactionRule = DB::transaction( function () use ( $rule ): TransactionActionRule {
            $transactionRule = TransactionActionRule::query()->find( $rule['id'] ?? 0 ) ?? new TransactionActionRule;
            $fallback = TransactionAccount::query()->where( 'system_identifier', 'payment_clearing' )->firstOrFail();
            $first = $rule['lines'][0];
            $second = $rule['lines'][1];

            TransactionActionRule::query()
                ->where( 'on', $rule['on'] )
                ->when( $transactionRule->exists, fn( $query ) => $query->whereKeyNot( $transactionRule->id ) )
                ->update( [ 'active' => false ] );

            $transactionRule->fill( [
                'on' => $rule['on'],
                'action' => $first['effect'],
                'account_id' => $first['account_id'] ?? $fallback->id,
                'do' => $second['effect'],
                'offset_account_id' => $second['account_id'] ?? $fallback->id,
                'active' => $rule['active'] ?? true,
            ] );
            $transactionRule->save();
            $retainedLineIds = [];

            foreach ( $rule['lines'] as $index => $line ) {
                $ruleLine = isset( $line['id'] )
                    ? $transactionRule->lines()->whereKey( $line['id'] )->first()
                    : null;
                $ruleLine ??= $transactionRule->lines()->make();
                $ruleLine->fill( [
                    'account_id' => $line['account_id'] ?? null,
                    'dynamic_account_role' => $line['dynamic_account_role'] ?? null,
                    'effect' => $line['effect'],
                    'amount_source' => $line['amount_source'],
                    'display_order' => $index,
                ] );
                $ruleLine->save();
                $retainedLineIds[] = $ruleLine->id;
            }

            $transactionRule->lines()->whereNotIn( 'id', $retainedLineIds )->delete();

            return $transactionRule->load( 'lines.account' );
        } );

        return [
            'status' => 'success',
            'message' => __( 'The transaction rule has been saved.' ),
            'data' => [ 'rule' => $transactionRule ],
        ];
    }

    public function deleteTransactionRule( TransactionActionRule $rule ): array
    {
        if ( $rule->locked ) {
            throw new NotAllowedException( __( 'This accounting rule cannot be deleted.' ) );
        }

        DB::transaction( function () use ( $rule ): void {
            if ( $rule->journals()->exists() ) {
                $rule->active = false;
                $rule->save();

                return;
            }

            $rule->lines()->delete();
            $rule->delete();
        } );

        return [
            'status' => 'success',
            'message' => __( 'The transaction rule has been deleted.' ),
        ];
    }

    /**
     * Provides transaction account using category identifiery
     *
     * @param  string $category_identifier
     * @param  int    $exclude_id
     * @return array
     */
    public function getTransactionAccountFromCategory( $category_identifier, $exclude_id = null )
    {
        $query = TransactionAccount::where( 'category_identifier', $category_identifier );

        if ( ! empty( $exclude_id ) ) {
            $query->where( 'id', '!=', $exclude_id );
        }

        $accounts = $query->get();

        return Helper::toJsOptions( $accounts, [ 'id', 'name' ] );
    }

    /**
     * Safely upgrades an existing installation and provisions fresh installations.
     */
    public function upgradeAccountingFoundation(): void
    {
        DB::transaction( function (): void {
            $accounts = $this->provisionAccountingChart();
            $this->migrateLegacyRulePairs();
            $this->provisionLegacyCompatibilityRules( $accounts );
            $this->provisionGroupedAccountingRules( $accounts );
            $this->configureDefaultPaymentAccounts( $accounts );
        } );

        app( AccountingJournalService::class )->postOpeningBalance();
    }

    /**
     * @return array<string, TransactionAccount>
     */
    private function provisionAccountingChart(): array
    {
        $chart = [
            'fixed_assets' => [ '1001', 'Fixed Assets', 'assets', null ],
            'current_assets' => [ '1002', 'Current Assets', 'assets', null ],
            'inventory' => [ '1003', 'Inventory', 'assets', 'current_assets' ],
            'cash' => [ '1004', 'Cash', 'assets', 'current_assets' ],
            'bank' => [ '1005', 'Bank', 'assets', 'current_assets' ],
            'accounts_receivable' => [ '1006', 'Accounts Receivable', 'assets', 'current_assets' ],
            'payment_clearing' => [ '1007', 'Payment Clearing', 'assets', 'current_assets' ],
            'current_liabilities' => [ '2001', 'Current Liabilities', 'liabilities', null ],
            'accounts_payable' => [ '2002', 'Accounts Payable', 'liabilities', 'current_liabilities' ],
            'sales_tax_payable' => [ '2003', 'Sales Tax Payable', 'liabilities', 'current_liabilities' ],
            'customer_deposits' => [ '2004', 'Customer Deposits', 'liabilities', 'current_liabilities' ],
            'owner_capital' => [ '3001', 'Owner Capital', 'equity', null ],
            'owner_drawings' => [ '3002', 'Owner Drawings', 'equity', null ],
            'retained_earnings' => [ '3003', 'Retained Earnings', 'equity', null ],
            'sales_revenue' => [ '4001', 'Sales Revenue', 'revenues', null ],
            'sales_returns' => [ '4002', 'Sales Returns / Refunds', 'revenues', null ],
            'cogs' => [ '5001', 'Cost of Goods Sold', 'expenses', null ],
            'operating_expenses' => [ '5100', 'Operating Expenses', 'expenses', null ],
            'rent' => [ '5101', 'Rent', 'expenses', 'operating_expenses' ],
            'salaries_wages' => [ '5102', 'Salaries & Wages', 'expenses', 'operating_expenses' ],
            'utilities' => [ '5103', 'Utilities', 'expenses', 'operating_expenses' ],
            'maintenance' => [ '5104', 'Maintenance', 'expenses', 'operating_expenses' ],
            'other_expenses' => [ '5105', 'Other Expenses', 'expenses', 'operating_expenses' ],
            'inventory_variance' => [ '5106', 'Inventory Variance', 'expenses', 'operating_expenses' ],
        ];
        $legacyNames = [
            'inventory' => [ 'Inventory Account' ],
            'cash' => [ 'Procurement Cash', 'Cash' ],
            'accounts_payable' => [ 'Procurement Payable', 'Account Payable' ],
            'accounts_receivable' => [ 'Receivables' ],
            'payment_clearing' => [ 'Sales' ],
            'sales_revenue' => [ 'Sales Revenues' ],
            'sales_returns' => [ 'Refunds' ],
            'cogs' => [ 'Sales COGS' ],
        ];
        $accounts = [];

        foreach ( $chart as $identifier => [ $code, $name, $category ] ) {
            $account = TransactionAccount::query()->where( 'system_identifier', $identifier )->first();

            if ( ! $account instanceof TransactionAccount && isset( $legacyNames[ $identifier ] ) ) {
                $account = TransactionAccount::query()
                    ->whereNull( 'system_identifier' )
                    ->whereIn( 'name', $legacyNames[ $identifier ] )
                    ->where( 'category_identifier', $category )
                    ->whereDoesntHave( 'histories' )
                    ->get()
                    ->first( fn( TransactionAccount $candidate ): bool => preg_match(
                        '/^\\d+-' . preg_quote( $category, '/' ) . '-/',
                        (string) $candidate->account
                    ) === 1 );

                if ( $account instanceof TransactionAccount ) {
                    $account->system_identifier = $identifier;
                    $account->account = $code;
                    $account->name = __( $name );
                    $account->save();
                }
            }

            if ( ! $account instanceof TransactionAccount ) {
                $account = new TransactionAccount;
                $account->system_identifier = $identifier;
                $account->account = $code;
                $account->name = __( $name );
                $account->category_identifier = $category;
                $account->author_id = ns()->getValidAuthor();
                $account->save();
            }

            $accounts[ $identifier ] = $account;
        }

        foreach ( $chart as $identifier => [ , , , $parentIdentifier ] ) {
            if ( $parentIdentifier !== null && $accounts[ $identifier ]->sub_category_id !== $accounts[ $parentIdentifier ]->id ) {
                $accounts[ $identifier ]->sub_category_id = $accounts[ $parentIdentifier ]->id;
                $accounts[ $identifier ]->save();
            }
        }

        return $accounts;
    }

    private function migrateLegacyRulePairs(): void
    {
        TransactionActionRule::query()->orderBy( 'id' )->get()->groupBy( 'on' )->each( function ( $rules ): void {
            $keeper = $rules->first();
            $nextOrder = $keeper->lines()->max( 'display_order' ) ?? -1;

            foreach ( $rules as $rule ) {
                if ( $rule->lines()->doesntExist() ) {
                    foreach ( [
                        [ 'account_id' => $rule->account_id, 'effect' => $rule->action ],
                        [ 'account_id' => $rule->offset_account_id, 'effect' => $rule->do ],
                    ] as $legacyLine ) {
                        $keeper->lines()->create( array_merge( $legacyLine, [
                            'amount_source' => 'total',
                            'display_order' => ++$nextOrder,
                        ] ) );
                    }
                } elseif ( $rule->id !== $keeper->id ) {
                    foreach ( $rule->lines as $line ) {
                        $keeper->lines()->create( [
                            'account_id' => $line->account_id,
                            'dynamic_account_role' => $line->dynamic_account_role,
                            'effect' => $line->effect,
                            'amount_source' => $line->amount_source,
                            'display_order' => ++$nextOrder,
                        ] );
                    }
                }

                if ( $rule->id !== $keeper->id ) {
                    $rule->active = false;
                    $rule->save();
                }
            }

            $keeper->active = true;
            $keeper->save();
        } );
    }

    /**
     * @param array<string, TransactionAccount> $accounts
     */
    private function provisionLegacyCompatibilityRules( array $accounts ): void
    {
        $rules = [
            self::legacyRule( TransactionActionRule::RULE_PROCUREMENT_UNPAID, $accounts['inventory'], 'increase', $accounts['accounts_payable'], 'increase' ),
            self::legacyRule( TransactionActionRule::RULE_PROCUREMENT_PAID, $accounts['inventory'], 'increase', $accounts['payment_clearing'], 'decrease' ),
            self::legacyRule( TransactionActionRule::RULE_PROCUREMENT_FROM_UNPAID_TO_PAID, $accounts['accounts_payable'], 'decrease', $accounts['payment_clearing'], 'decrease' ),
            self::legacyRule( TransactionActionRule::RULE_ORDER_UNPAID, $accounts['accounts_receivable'], 'increase', $accounts['sales_revenue'], 'increase' ),
            self::legacyRule( TransactionActionRule::RULE_ORDER_FROM_UNPAID_TO_PAID, $accounts['payment_clearing'], 'increase', $accounts['accounts_receivable'], 'decrease' ),
            self::legacyRule( TransactionActionRule::RULE_ORDER_PAID, $accounts['payment_clearing'], 'increase', $accounts['sales_revenue'], 'increase' ),
            self::legacyRule( TransactionActionRule::RULE_ORDER_PARTIALLY_PAID, $accounts['payment_clearing'], 'increase', $accounts['sales_revenue'], 'increase' ),
            self::legacyRule( TransactionActionRule::RULE_ORDER_REFUNDED, $accounts['sales_returns'], 'decrease', $accounts['payment_clearing'], 'decrease' ),
            self::legacyRule( TransactionActionRule::RULE_ORDER_PARTIALLY_REFUNDED, $accounts['sales_returns'], 'decrease', $accounts['payment_clearing'], 'decrease' ),
            self::legacyRule( TransactionActionRule::RULE_ORDER_COGS, $accounts['cogs'], 'increase', $accounts['inventory'], 'decrease' ),
            self::legacyRule( TransactionActionRule::RULE_PRODUCT_DAMAGED, $accounts['inventory_variance'], 'increase', $accounts['inventory'], 'decrease' ),
            self::legacyRule( TransactionActionRule::RULE_PRODUCT_RETURNED, $accounts['cogs'], 'decrease', $accounts['inventory'], 'increase' ),
            self::legacyRule( TransactionActionRule::RULE_ORDER_PAID_VOIDED, $accounts['sales_returns'], 'decrease', $accounts['payment_clearing'], 'decrease' ),
            self::legacyRule( TransactionActionRule::RULE_ORDER_UNPAID_VOIDED, $accounts['sales_returns'], 'decrease', $accounts['accounts_receivable'], 'decrease' ),
        ];

        foreach ( $rules as $definition ) {
            if ( TransactionActionRule::query()->where( 'on', $definition['on'] )->exists() ) {
                continue;
            }

            $this->createRuleGroup( $definition['on'], $definition['lines'] );
        }
    }

    /**
     * @param array<string, TransactionAccount> $accounts
     */
    private function provisionGroupedAccountingRules( array $accounts ): void
    {
        $groups = [
            AccountingEventCatalog::ORDER_FINALIZED => [
                [ $accounts['accounts_receivable'], null, 'increase', 'total' ],
                [ $accounts['sales_revenue'], null, 'increase', 'net_sale' ],
                [ $accounts['sales_tax_payable'], null, 'increase', 'tax' ],
                [ $accounts['cogs'], null, 'increase', 'cogs' ],
                [ $accounts['inventory'], null, 'decrease', 'cogs' ],
            ],
            AccountingEventCatalog::ORDER_PAYMENT => [
                [ null, 'payment_account', 'increase', 'payment_amount' ],
                [ $accounts['accounts_receivable'], null, 'decrease', 'payment_amount' ],
            ],
            AccountingEventCatalog::ORDER_REFUND => [
                [ $accounts['sales_returns'], null, 'decrease', 'net_refund' ],
                [ $accounts['sales_tax_payable'], null, 'decrease', 'refunded_tax' ],
                [ $accounts['accounts_receivable'], null, 'decrease', 'refund_unpaid' ],
                [ null, 'refund_payment_account', 'decrease', 'refund_paid' ],
            ],
            AccountingEventCatalog::RETURN_GOOD => [
                [ $accounts['inventory'], null, 'increase', 'refund_cost' ],
                [ $accounts['cogs'], null, 'decrease', 'refund_cost' ],
            ],
            AccountingEventCatalog::RETURN_DAMAGED => [
                [ $accounts['inventory_variance'], null, 'increase', 'refund_cost' ],
                [ $accounts['cogs'], null, 'decrease', 'refund_cost' ],
            ],
            AccountingEventCatalog::PROCUREMENT_RECEIPT => [
                [ $accounts['inventory'], null, 'increase', 'procurement_cost' ],
                [ $accounts['accounts_payable'], null, 'increase', 'procurement_cost' ],
            ],
            AccountingEventCatalog::PROCUREMENT_PAYMENT => [
                [ $accounts['accounts_payable'], null, 'decrease', 'payment_amount' ],
                [ $accounts['payment_clearing'], null, 'decrease', 'payment_amount' ],
            ],
            AccountingEventCatalog::ADJUSTMENT_NEGATIVE => [
                [ $accounts['inventory_variance'], null, 'increase', 'adjustment_cost' ],
                [ $accounts['inventory'], null, 'decrease', 'adjustment_cost' ],
            ],
            AccountingEventCatalog::ADJUSTMENT_POSITIVE => [
                [ $accounts['inventory'], null, 'increase', 'adjustment_cost' ],
                [ $accounts['inventory_variance'], null, 'decrease', 'adjustment_cost' ],
            ],
            AccountingEventCatalog::WALLET_ADDITION => [
                [ $accounts['payment_clearing'], null, 'increase', 'wallet_amount' ],
                [ $accounts['customer_deposits'], null, 'increase', 'wallet_amount' ],
            ],
            AccountingEventCatalog::WALLET_DEDUCTION => [
                [ $accounts['customer_deposits'], null, 'decrease', 'wallet_amount' ],
                [ $accounts['payment_clearing'], null, 'decrease', 'wallet_amount' ],
            ],
        ];

        foreach ( $groups as $event => $lines ) {
            if ( TransactionActionRule::query()->where( 'on', $event )->exists() ) {
                continue;
            }

            $this->createRuleGroup( $event, $lines );
        }
    }

    /**
     * @param array<int, array{0: TransactionAccount|null, 1: string|null, 2: string, 3: string}> $lines
     */
    private function createRuleGroup( string $event, array $lines ): TransactionActionRule
    {
        $fallback = TransactionAccount::query()->where( 'system_identifier', 'payment_clearing' )->firstOrFail();
        $first = $lines[0];
        $second = $lines[1];
        $rule = TransactionActionRule::query()->create( [
            'on' => $event,
            'action' => $first[2],
            'account_id' => ( $first[0] ?? $fallback )->id,
            'do' => $second[2],
            'offset_account_id' => ( $second[0] ?? $fallback )->id,
            'locked' => false,
            'active' => true,
        ] );

        foreach ( $lines as $index => [ $account, $role, $effect, $source ] ) {
            $rule->lines()->create( [
                'account_id' => $account?->id,
                'dynamic_account_role' => $role,
                'effect' => $effect,
                'amount_source' => $source,
                'display_order' => $index,
            ] );
        }

        return $rule;
    }

    /**
     * @return array{on: string, lines: array<int, array{0: TransactionAccount, 1: null, 2: string, 3: string}>}
     */
    private static function legacyRule( string $event, TransactionAccount $account, string $effect, TransactionAccount $offset, string $offsetEffect ): array
    {
        return [
            'on' => $event,
            'lines' => [
                [ $account, null, $effect, 'total' ],
                [ $offset, null, $offsetEffect, 'total' ],
            ],
        ];
    }

    /**
     * @param array<string, TransactionAccount> $accounts
     */
    private function configureDefaultPaymentAccounts( array $accounts ): void
    {
        $defaults = [
            'cash-payment' => [ $accounts['cash']->id, 'increase' ],
            'bank-payment' => [ $accounts['bank']->id, 'increase' ],
            'account-payment' => [ $accounts['customer_deposits']->id, 'decrease' ],
        ];

        foreach ( $defaults as $identifier => [ $accountId, $effect ] ) {
            $paymentType = PaymentType::query()->where( 'identifier', $identifier )->first();

            if ( $paymentType && $paymentType->accounting_account_id === null ) {
                $paymentType->accounting_account_id = $accountId;
                $paymentType->accounting_incoming_effect = $effect;
                $paymentType->save();
            }
        }

        ns()->option->set( 'ns_accounting_default_paid_expense_offset_account', $accounts['payment_clearing']->id );
    }
}
