<?php

namespace Tests\Feature;

use App\Models\BankTransferBatch;
use App\Models\BankTransferItem;
use App\Models\ChartOfAccount;
use App\Models\Client;
use App\Models\EfakturBatch;
use App\Models\EfakturBatchItem;
use App\Models\EfakturSubmission;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class P5FinanceUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_accounting_pages_render_with_existing_data(): void
    {
        [$user, $organization] = $this->createFinanceUser();

        $cashAccount = ChartOfAccount::query()->create([
            'organization_id' => $organization->id,
            'code' => '1100',
            'name' => 'Kas',
            'account_type' => 'ASSET',
            'normal_balance' => 'DEBIT',
            'category' => 'CURRENT',
            'is_active' => true,
        ]);

        $revenueAccount = ChartOfAccount::query()->create([
            'organization_id' => $organization->id,
            'code' => '4100',
            'name' => 'Pendapatan Jasa',
            'account_type' => 'REVENUE',
            'normal_balance' => 'CREDIT',
            'category' => 'OPERATING',
            'is_active' => true,
        ]);

        $journalEntry = JournalEntry::query()->create([
            'organization_id' => $organization->id,
            'posted_by_user_id' => $user->id,
            'journal_number' => 'JRN-0001',
            'entry_date' => now()->toDateString(),
            'status' => 'POSTED',
            'reference_number' => 'REF-001',
            'description' => 'Pencatatan pendapatan koperasi',
        ]);

        JournalEntryLine::query()->create([
            'journal_entry_id' => $journalEntry->id,
            'chart_of_account_id' => $cashAccount->id,
            'debit' => 1500000,
            'credit' => 0,
            'memo' => 'Kas masuk',
        ]);

        JournalEntryLine::query()->create([
            'journal_entry_id' => $journalEntry->id,
            'chart_of_account_id' => $revenueAccount->id,
            'debit' => 0,
            'credit' => 1500000,
            'memo' => 'Pendapatan jasa',
        ]);

        $this->actingAs($user)
            ->get('/finance/chart-of-accounts')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Finance/ChartOfAccounts/Index')
                ->has('accounts.data', 2)
            );

        $this->actingAs($user)
            ->get('/finance/journal-entries')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Finance/JournalEntries/Index')
                ->has('entries.data', 1)
                ->has('accounts', 2)
            );

        $this->actingAs($user)
            ->get('/finance/trial-balance')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Finance/TrialBalance')
                ->has('rows', 2)
            );

        $this->actingAs($user)
            ->get('/finance/balance-sheet')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Finance/BalanceSheet')
                ->has('statement.assets', 1)
            );

        $this->actingAs($user)
            ->get('/finance/income-statement')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Finance/IncomeStatement')
                ->has('statement.revenues', 1)
            );
    }

    public function test_finance_efaktur_and_bank_reconciliation_pages_render(): void
    {
        [$user, $organization] = $this->createFinanceUser();

        $client = Client::query()->create([
            'organization_id' => $organization->id,
            'code' => 'CL-001',
            'name' => 'PT Pelanggan Utama',
            'address' => 'Jl. Pelanggan No. 1',
            'contact_person' => 'Budi',
            'phone' => '021000000',
            'client_type' => 'PRIVATE',
            'email' => 'client@example.com',
        ]);

        $invoice = Invoice::query()->create([
            'organization_id' => $organization->id,
            'unit_id' => $organization->id,
            'client_id' => $client->id,
            'invoice_no' => 'INV-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'amount' => 1000000,
            'tax_amount' => 110000,
            'total_amount' => 1110000,
            'status' => 'APPROVED',
        ]);

        $efakturBatch = EfakturBatch::query()->create([
            'organization_id' => $organization->id,
            'reference' => 'EFK-001',
            'status' => 'READY',
        ]);

        EfakturBatchItem::query()->create([
            'batch_id' => $efakturBatch->id,
            'invoice_id' => $invoice->id,
        ]);

        EfakturSubmission::query()->create([
            'invoice_id' => $invoice->id,
            'provider' => 'sandbox',
            'status' => 'SUBMITTED',
            'request_payload' => ['invoice' => $invoice->invoice_no],
            'response_payload' => ['status' => 'ok'],
        ]);

        $bankBatch = BankTransferBatch::query()->create([
            'organization_id' => $organization->id,
            'bank_name' => 'Bank Koperasi',
            'account_number' => '1234567890',
            'status' => 'PENDING',
            'format' => 'CSV',
            'batch_date' => now()->toDateString(),
            'reference' => 'BANK-001',
        ]);

        BankTransferItem::query()->create([
            'batch_id' => $bankBatch->id,
            'beneficiary_name' => 'PT Pelanggan Utama',
            'beneficiary_account' => '9876543210',
            'amount' => 1110000,
            'currency' => 'IDR',
            'reference' => 'INV-001',
            'invoice_id' => $invoice->id,
            'status' => 'MATCHED',
        ]);

        $this->actingAs($user)
            ->get('/finance/efaktur')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Finance/Efaktur/Index')
                ->has('invoices.data', 1)
                ->has('batches.data', 1)
            );

        $this->actingAs($user)
            ->get('/finance/efaktur/submit')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Finance/Efaktur/Submit')
                ->has('eligibleInvoices', 1)
            );

        $this->actingAs($user)
            ->get('/finance/efaktur/status')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Finance/Efaktur/Status')
                ->has('submissions.data', 1)
            );

        $this->actingAs($user)
            ->get('/finance/bank-reconciliation')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Finance/BankReconciliation/Index')
                ->has('batches.data', 1)
            );

        $this->actingAs($user)
            ->get('/finance/bank-reconciliation/'.$bankBatch->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Finance/BankReconciliation/Show')
                ->where('stats.items_count', 1)
            );
    }

    private function createFinanceUser(): array
    {
        Role::query()->firstOrCreate(['name' => 'System Admin', 'guard_name' => 'web']);

        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $user->assignRole('System Admin');

        return [$user, $organization];
    }
}
