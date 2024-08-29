<?php
namespace Tests\Feature;

use Modules\NsGastro\Tests\TestCase;
use Tests\Traits\WithAccountingTest;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithProcurementTest;

class AccountingProcurementTest extends TestCase
{
    use WithAuthentication, WithAccountingTest, WithProcurementTest;

    public function testCreateBankingAccounts()
    {
        $this->attemptAuthenticate();
        $this->createDefaultAccounts();
    }

    public function testCreateProcurement()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateAnUnpaidProcurement();
    }

    public function testCreateProcurementAndPay()
    {
        $this->attemptAuthenticate();
        $this->attemptUnpaidProcurement();
    }
}