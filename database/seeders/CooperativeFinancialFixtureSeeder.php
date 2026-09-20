<?php

namespace Database\Seeders;

use App\Enums\InstallmentStatus;
use App\Enums\LoanStatus;
use App\Enums\MemberStoreAccountStatus;
use App\Enums\MemberStoreLedgerEffect;
use App\Enums\MemberStoreLedgerEntryType;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\CooperativeReceipt;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\LoanPayment;
use App\Models\LoanType;
use App\Models\MemberStoreAccount;
use App\Models\MemberStoreLedgerEntry;
use App\Models\Organization;
use App\Models\PosCategory;
use App\Models\PosPayment;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\User;
use App\Support\SeedSafety\SeederEnvironmentGuard;
use Illuminate\Database\Seeder;

class CooperativeFinancialFixtureSeeder extends Seeder
{
    /**
     * Seed canonical non-production deterministic financial fixtures.
     * Guarded strictly for local, testing, and playwright environments.
     */
    public function run(): void
    {
        // 1. Fail-closed environment guard
        SeederEnvironmentGuard::assertAllowed(static::class);

        // 2. Canonical direct dependencies bootstrap
        $this->call([
            CooperativeMemberLifecycleSeeder::class,
            CooperativeReferenceSeeder::class,
            LoanTypeSeeder::class,
        ]);

        // 3. Resolve organizational anchor (strictly KOP-001)
        $headOffice = Organization::query()->where('code', 'KOP-001')->firstOrFail();

        // 4. Resolve actors
        $pengurus = User::query()->where('email', 'seed.pengurus@kojaya.test')->firstOrFail();
        $manajer = User::query()->where('email', 'seed.manajer@kojaya.test')->firstOrFail();
        $adminKop = User::query()->where('email', 'seed.admin.kop@kojaya.test')->firstOrFail();
        $kasir = User::query()->where('email', 'seed.kasir@kojaya.test')->firstOrFail();

        // 5. Resolve active canonical members
        $p10 = CooperativeMember::query()->where('member_no', 'DEV-KOP-010')->firstOrFail();
        $p12 = CooperativeMember::query()->where('member_no', 'DEV-KOP-012')->firstOrFail();
        $p13 = CooperativeMember::query()->where('member_no', 'DEV-KOP-013')->firstOrFail();

        // 6. Resolve contribution types
        $pokok = CooperativeContributionType::query()->where('code', 'POKOK')->firstOrFail();
        $wajib = CooperativeContributionType::query()->where('code', 'WAJIB')->firstOrFail();

        // 7. Seed canonical POS products under KOP-001
        $products = $this->seedPosProducts($headOffice);

        // 8. Seed P10: NORMAL + COMPLETED / PAID state
        $this->seedP10Financials(
            $headOffice,
            $p10,
            $pokok,
            $wajib,
            $pengurus,
            $manajer,
            $adminKop,
            $kasir,
            $products,
        );

        // 9. Seed P12: UNPAID + ACTIVE LOAN + VALID BOUNDARY state
        $this->seedP12Financials($headOffice, $p12, $wajib, $pengurus, $manajer, $kasir, $products);

        // 10. Explicitly guarantee P13 (EMPTY) and non-active members (P06-P09) have 0 financial fixtures
        $this->enforceEmptyStateForReservedMembers([
            $p13->id,
            ...CooperativeMember::query()
                ->whereIn('member_no', ['DEV-KOP-006', 'DEV-KOP-007', 'DEV-KOP-008', 'DEV-KOP-009'])
                ->pluck('id')
                ->all(),
        ]);
    }

    /**
     * Seed canonical POS products exclusively under KOP-001.
     *
     * @return array<string, PosProduct>
     */
    private function seedPosProducts(Organization $headOffice): array
    {
        $categorySembako = PosCategory::query()
            ->where('organization_id', $headOffice->id)
            ->where('slug', 'sembako')
            ->first()
            ?? PosCategory::query()->create([
                'organization_id' => $headOffice->id,
                'slug' => 'sembako',
                'name' => 'Sembako',
                'is_active' => true,
            ]);

        $categoryMinuman = PosCategory::query()
            ->where('organization_id', $headOffice->id)
            ->where('slug', 'minuman')
            ->first()
            ?? PosCategory::query()->create([
                'organization_id' => $headOffice->id,
                'slug' => 'minuman',
                'name' => 'Minuman',
                'is_active' => true,
            ]);

        // Catalog definitions with reconciled stock:
        // SEED-POS-001: 100 initial - (1 sold P10 + 6 sold P12) = 93 final stock
        // SEED-POS-002: 100 initial - 1 sold P10 = 99 final stock
        // SEED-POS-003: 100 initial - 0 sold = 100 final stock
        // SEED-POS-004: 80 initial - 0 sold = 80 final stock
        $catalog = [
            'SEED-POS-001' => [
                'pos_category_id' => $categorySembako->id,
                'barcode' => '8999001000010',
                'name' => 'Beras Seeder Premium 5kg',
                'cost_price' => 65000,
                'sale_price' => 75000,
                'stock' => 93,
                'minimum_stock' => 10,
                'unit' => 'sak',
                'is_active' => true,
            ],
            'SEED-POS-002' => [
                'pos_category_id' => $categorySembako->id,
                'barcode' => '8999001000027',
                'name' => 'Minyak Goreng Seeder 2L',
                'cost_price' => 28000,
                'sale_price' => 35000,
                'stock' => 99,
                'minimum_stock' => 15,
                'unit' => 'pouch',
                'is_active' => true,
            ],
            'SEED-POS-003' => [
                'pos_category_id' => $categorySembako->id,
                'barcode' => '8999001000034',
                'name' => 'Gula Pasir Seeder 1kg',
                'cost_price' => 12000,
                'sale_price' => 15000,
                'stock' => 100,
                'minimum_stock' => 20,
                'unit' => 'kg',
                'is_active' => true,
            ],
            'SEED-POS-004' => [
                'pos_category_id' => $categoryMinuman->id,
                'barcode' => '8999001000041',
                'name' => 'Kopi Bubuk Seeder 250g',
                'cost_price' => 15000,
                'sale_price' => 22000,
                'stock' => 80,
                'minimum_stock' => 10,
                'unit' => 'pack',
                'is_active' => true,
            ],
        ];

        $products = [];
        foreach ($catalog as $sku => $attrs) {
            $products[$sku] = PosProduct::query()->updateOrCreate(
                ['sku' => $sku],
                [
                    'organization_id' => $headOffice->id,
                    ...$attrs,
                ],
            );
        }

        return $products;
    }

    /**
     * Seed P10: Paid contributions, approved payments, receipts, savings ledger,
     * positive store account, routine POS purchase, and historical paid-off loan.
     *
     * @param  array<string, PosProduct>  $products
     */
    private function seedP10Financials(
        Organization $headOffice,
        CooperativeMember $p10,
        CooperativeContributionType $pokok,
        CooperativeContributionType $wajib,
        User $pengurus,
        User $manajer,
        User $adminKop,
        User $kasir,
        array $products,
    ): void {
        // A. POKOK: 2026-01 -> PAID
        $pokokAmount = (float) $pokok->default_amount;
        $invPokok = CooperativeDuesInvoice::query()->updateOrCreate(
            [
                'cooperative_member_id' => $p10->id,
                'cooperative_contribution_type_id' => $pokok->id,
                'period' => '2026-01',
            ],
            [
                'amount' => $pokokAmount,
                'paid_amount' => $pokokAmount,
                'due_date' => '2026-01-10',
                'status' => 'PAID',
            ],
        );

        $payPokok = CooperativePayment::query()->updateOrCreate(
            ['reference_no' => 'SEED-PAY-POKOK-010-202601'],
            [
                'cooperative_member_id' => $p10->id,
                'cooperative_dues_invoice_id' => $invPokok->id,
                'cooperative_contribution_type_id' => $pokok->id,
                'user_id' => $adminKop->id,
                'amount' => $pokokAmount,
                'payment_method' => 'CASH',
                'paid_at' => '2026-01-05',
                'status' => 'APPROVED',
                'approved_at' => '2026-01-05 10:00:00',
                'approved_by' => $pengurus->id,
                'receipt_no' => 'SEED-RC-010-001',
                'receipt_issued_at' => '2026-01-05 10:00:00',
                'notes' => 'Pembayaran Simpanan Pokok P10',
            ],
        );

        CooperativeReceipt::query()->updateOrCreate(
            ['receipt_no' => 'SEED-RC-010-001'],
            [
                'cooperative_payment_id' => $payPokok->id,
                'cooperative_member_id' => $p10->id,
                'pdf_path' => 'cooperative/receipts/SEED-RC-010-001.pdf',
                'issued_at' => '2026-01-05 10:00:00',
                'issued_by' => $pengurus->id,
            ],
        );

        CooperativeLedgerEntry::query()->updateOrCreate(
            [
                'source_type' => CooperativePayment::class,
                'source_id' => $payPokok->id,
                'entry_type' => 'SAVING_PAYMENT',
            ],
            [
                'cooperative_member_id' => $p10->id,
                'organization_id' => $headOffice->id,
                'cooperative_payment_id' => $payPokok->id,
                'cooperative_contribution_type_id' => $pokok->id,
                'ledger_scope' => 'SAVINGS',
                'category_snapshot' => $pokok->category,
                'debit' => 0,
                'credit' => $pokokAmount,
                'period' => '2026-01',
                'description' => 'Pembayaran Simpanan Pokok',
                'posted_at' => '2026-01-05',
            ],
        );

        // B. WAJIB: 2026-01 -> PAID
        $wajibAmount = (float) $wajib->default_amount;
        $invWajib = CooperativeDuesInvoice::query()->updateOrCreate(
            [
                'cooperative_member_id' => $p10->id,
                'cooperative_contribution_type_id' => $wajib->id,
                'period' => '2026-01',
            ],
            [
                'amount' => $wajibAmount,
                'paid_amount' => $wajibAmount,
                'due_date' => '2026-01-10',
                'status' => 'PAID',
            ],
        );

        $payWajib = CooperativePayment::query()->updateOrCreate(
            ['reference_no' => 'SEED-PAY-WAJIB-010-202601'],
            [
                'cooperative_member_id' => $p10->id,
                'cooperative_dues_invoice_id' => $invWajib->id,
                'cooperative_contribution_type_id' => $wajib->id,
                'user_id' => $adminKop->id,
                'amount' => $wajibAmount,
                'payment_method' => 'CASH',
                'paid_at' => '2026-01-05',
                'status' => 'APPROVED',
                'approved_at' => '2026-01-05 10:00:00',
                'approved_by' => $pengurus->id,
                'receipt_no' => 'SEED-RC-010-002',
                'receipt_issued_at' => '2026-01-05 10:00:00',
                'notes' => 'Pembayaran Simpanan Wajib P10',
            ],
        );

        CooperativeReceipt::query()->updateOrCreate(
            ['receipt_no' => 'SEED-RC-010-002'],
            [
                'cooperative_payment_id' => $payWajib->id,
                'cooperative_member_id' => $p10->id,
                'pdf_path' => 'cooperative/receipts/SEED-RC-010-002.pdf',
                'issued_at' => '2026-01-05 10:00:00',
                'issued_by' => $pengurus->id,
            ],
        );

        CooperativeLedgerEntry::query()->updateOrCreate(
            [
                'source_type' => CooperativePayment::class,
                'source_id' => $payWajib->id,
                'entry_type' => 'SAVING_PAYMENT',
            ],
            [
                'cooperative_member_id' => $p10->id,
                'organization_id' => $headOffice->id,
                'cooperative_payment_id' => $payWajib->id,
                'cooperative_contribution_type_id' => $wajib->id,
                'ledger_scope' => 'SAVINGS',
                'category_snapshot' => $wajib->category,
                'debit' => 0,
                'credit' => $wajibAmount,
                'period' => '2026-01',
                'description' => 'Pembayaran Simpanan Wajib',
                'posted_at' => '2026-01-05',
            ],
        );

        // C. Store Account: limit 500,000, balance +150,000
        $storeAccount = MemberStoreAccount::query()->firstOrCreate(
            [
                'organization_id' => $headOffice->id,
                'cooperative_member_id' => $p10->id,
            ],
            [
                'credit_limit' => 500000,
                'status' => MemberStoreAccountStatus::Active->value,
                'opened_at' => '2026-01-01 08:00:00',
            ],
        );
        $storeAccount->credit_limit = 500000;
        $storeAccount->balance = 150000;
        $storeAccount->status = MemberStoreAccountStatus::Active->value;
        $storeAccount->save();

        MemberStoreLedgerEntry::query()->firstOrCreate(
            [
                'account_id' => $storeAccount->id,
                'idempotency_key' => 'seed-store-ledger:010:opening',
            ],
            [
                'organization_id' => $headOffice->id,
                'entry_type' => MemberStoreLedgerEntryType::OpeningBalance->value,
                'amount' => 150000,
                'effect' => MemberStoreLedgerEffect::Credit->value,
                'balance_before' => 0,
                'balance_after' => 150000,
                'reference_type' => 'store_account',
                'reference_id' => (string) $storeAccount->id,
                'actor_user_id' => $adminKop->id,
                'reason' => 'Saldo awal pembukaan akun toko',
                'occurred_at' => '2026-01-01 08:00:00',
            ],
        );

        // D. Routine Completed POS Purchase: Subtotal 110,000 paid CASH
        $posTx = PosTransaction::query()->updateOrCreate(
            ['client_reference' => 'SEED-POS-REF-010-001'],
            [
                'organization_id' => $headOffice->id,
                'transaction_no' => 'SEED-POS-TX-010-001',
                'cooperative_member_id' => $p10->id,
                'cashier_id' => $kasir->id,
                'subtotal' => 110000,
                'discount_amount' => 0,
                'total_amount' => 110000,
                'gross_profit' => 17000,
                'cash_received' => 110000,
                'cash_change' => 0,
                'status' => 'COMPLETED',
                'sold_at' => '2026-05-15 11:00:00',
            ],
        );

        // Item 1: 1 x SEED-POS-001 (75,000)
        PosTransactionItem::query()->updateOrCreate(
            [
                'pos_transaction_id' => $posTx->id,
                'pos_product_id' => $products['SEED-POS-001']->id,
            ],
            [
                'quantity' => 1,
                'unit_price' => 75000,
                'cost_price' => 65000,
                'unit_profit' => 10000,
                'line_total' => 75000,
                'line_profit' => 10000,
            ],
        );

        // Item 2: 1 x SEED-POS-002 (35,000)
        PosTransactionItem::query()->updateOrCreate(
            [
                'pos_transaction_id' => $posTx->id,
                'pos_product_id' => $products['SEED-POS-002']->id,
            ],
            [
                'quantity' => 1,
                'unit_price' => 35000,
                'cost_price' => 28000,
                'unit_profit' => 7000,
                'line_total' => 35000,
                'line_profit' => 7000,
            ],
        );

        PosPayment::query()->updateOrCreate(
            [
                'pos_transaction_id' => $posTx->id,
                'payment_method' => 'CASH',
            ],
            [
                'amount' => 110000,
                'reference_no' => 'SEED-POS-PAY-010-001',
            ],
        );

        // E. Historical Paid-Off Loan: productive, 3,000,000 principal, 6 months
        $productiveType = LoanType::query()->where('code', 'productive')->firstOrFail();
        $loanClosed = Loan::query()->updateOrCreate(
            ['reference_no' => 'SEED-LOAN-CLOSED-010-001'],
            [
                'cooperative_member_id' => $p10->id,
                'organization_id' => $headOffice->id,
                'loan_type_id' => $productiveType->id,
                'user_id' => $p10->user_id,
                'principal_amount' => 3000000,
                'interest_rate' => (float) $productiveType->interest_rate,
                'admin_fee' => (float) $productiveType->admin_fee,
                'late_fee_per_day' => (float) $productiveType->late_fee_per_day,
                'term_months' => 6,
                'installment_amount' => 537500,
                'total_interest_amount' => 225000,
                'total_amount' => 3225000,
                'outstanding_amount' => 0,
                'applied_at' => '2025-09-01',
                'first_due_date' => '2025-10-10',
                'manager_reviewed_at' => '2025-09-02 09:00:00',
                'manager_reviewed_by' => $manajer->id,
                'approved_at' => '2025-09-02 14:00:00',
                'approved_by' => $pengurus->id,
                'disbursed_at' => '2025-09-03 10:00:00',
                'disbursed_by' => $pengurus->id,
                'status' => LoanStatus::PaidOff,
                'purpose' => 'Modal usaha produktif',
                'notes' => 'Pinjaman lunas historis deterministik',
            ],
        );

        // Disbursement ledger
        CooperativeLedgerEntry::query()->updateOrCreate(
            [
                'source_type' => Loan::class,
                'source_id' => $loanClosed->id,
                'entry_type' => 'LOAN_DISBURSEMENT',
            ],
            [
                'cooperative_member_id' => $p10->id,
                'organization_id' => $headOffice->id,
                'cooperative_payment_id' => null,
                'ledger_scope' => 'LOAN',
                'debit' => 3000000,
                'credit' => 0,
                'period' => '2025-09',
                'description' => 'Pencairan pinjaman koperasi produktif P10',
                'posted_at' => '2025-09-03',
            ],
        );

        // 6 installments and 6 payments (all paid)
        $dueDates = [
            '2025-10-10',
            '2025-11-10',
            '2025-12-10',
            '2026-01-10',
            '2026-02-10',
            '2026-03-10',
        ];

        foreach ($dueDates as $idx => $dueDate) {
            $installmentNo = $idx + 1;
            $inst = LoanInstallment::query()->updateOrCreate(
                [
                    'loan_id' => $loanClosed->id,
                    'installment_no' => $installmentNo,
                ],
                [
                    'due_date' => $dueDate,
                    'principal_amount' => 500000,
                    'interest_amount' => 37500,
                    'fee_amount' => 0,
                    'penalty_amount' => 0,
                    'amount_due' => 537500,
                    'amount_paid' => 537500,
                    'paid_at' => $dueDate,
                    'status' => InstallmentStatus::Paid,
                ],
            );

            $loanPay = LoanPayment::query()->updateOrCreate(
                ['reference_no' => 'SEED-LOAN-PAY-010-00'.$installmentNo],
                [
                    'loan_id' => $loanClosed->id,
                    'loan_installment_id' => $inst->id,
                    'cooperative_member_id' => $p10->id,
                    'user_id' => $pengurus->id,
                    'amount' => 537500,
                    'principal_amount' => 500000,
                    'interest_amount' => 37500,
                    'fee_amount' => 0,
                    'penalty_amount' => 0,
                    'paid_at' => $dueDate,
                    'payment_method' => 'CASH',
                    'status' => 'APPROVED',
                    'approved_at' => $dueDate.' 10:00:00',
                    'approved_by' => $pengurus->id,
                    'notes' => 'Pembayaran angsuran ke-'.$installmentNo,
                ],
            );

            CooperativeLedgerEntry::query()->updateOrCreate(
                [
                    'source_type' => LoanPayment::class,
                    'source_id' => $loanPay->id,
                    'entry_type' => 'LOAN_PAYMENT',
                ],
                [
                    'cooperative_member_id' => $p10->id,
                    'organization_id' => $headOffice->id,
                    'cooperative_payment_id' => null,
                    'ledger_scope' => 'LOAN',
                    'debit' => 0,
                    'credit' => 537500,
                    'period' => substr($dueDate, 0, 7),
                    'description' => 'Pembayaran angsuran pinjaman ke-'.$installmentNo,
                    'posted_at' => $dueDate,
                ],
            );
        }
    }

    /**
     * Seed P12: UNPAID contribution, valid near-limit store account balance (-450k / limit 500k),
     * store POS purchase, and active ongoing loan.
     *
     * @param  array<string, PosProduct>  $products
     */
    private function seedP12Financials(
        Organization $headOffice,
        CooperativeMember $p12,
        CooperativeContributionType $wajib,
        User $pengurus,
        User $manajer,
        User $kasir,
        array $products,
    ): void {
        // A. UNPAID WAJIB: 2026-06
        $wajibAmount = (float) $wajib->default_amount;
        CooperativeDuesInvoice::query()->updateOrCreate(
            [
                'cooperative_member_id' => $p12->id,
                'cooperative_contribution_type_id' => $wajib->id,
                'period' => '2026-06',
            ],
            [
                'amount' => $wajibAmount,
                'paid_amount' => 0,
                'due_date' => '2026-06-10',
                'status' => 'UNPAID',
            ],
        );

        // B. Store Account: limit 500,000, balance -450,000 (availableCredit = 50,000)
        $storeAccount = MemberStoreAccount::query()->firstOrCreate(
            [
                'organization_id' => $headOffice->id,
                'cooperative_member_id' => $p12->id,
            ],
            [
                'credit_limit' => 500000,
                'status' => MemberStoreAccountStatus::Active->value,
                'opened_at' => '2026-01-01 08:00:00',
            ],
        );
        $storeAccount->credit_limit = 500000;
        $storeAccount->balance = -450000;
        $storeAccount->status = MemberStoreAccountStatus::Active->value;
        $storeAccount->save();

        // C. POS Purchase using MEMBER_STORE_ACCOUNT (6 x SEED-POS-001 @ 75k = 450k)
        $posTx = PosTransaction::query()->updateOrCreate(
            ['client_reference' => 'SEED-POS-REF-012-001'],
            [
                'organization_id' => $headOffice->id,
                'transaction_no' => 'SEED-POS-TX-012-001',
                'cooperative_member_id' => $p12->id,
                'cashier_id' => $kasir->id,
                'subtotal' => 450000,
                'discount_amount' => 0,
                'total_amount' => 450000,
                'gross_profit' => 60000,
                'cash_received' => 0,
                'cash_change' => 0,
                'status' => 'COMPLETED',
                'sold_at' => '2026-05-20 14:00:00',
            ],
        );

        PosTransactionItem::query()->updateOrCreate(
            [
                'pos_transaction_id' => $posTx->id,
                'pos_product_id' => $products['SEED-POS-001']->id,
            ],
            [
                'quantity' => 6,
                'unit_price' => 75000,
                'cost_price' => 65000,
                'unit_profit' => 10000,
                'line_total' => 450000,
                'line_profit' => 60000,
            ],
        );

        PosPayment::query()->updateOrCreate(
            [
                'pos_transaction_id' => $posTx->id,
                'payment_method' => 'MEMBER_STORE_ACCOUNT',
            ],
            [
                'amount' => 450000,
                'reference_no' => 'SEED-POS-PAY-012-001',
            ],
        );

        // Store Ledger Entry debiting 450,000
        MemberStoreLedgerEntry::query()->firstOrCreate(
            [
                'account_id' => $storeAccount->id,
                'idempotency_key' => 'seed-store-ledger:012:pos-purchase',
            ],
            [
                'organization_id' => $headOffice->id,
                'entry_type' => MemberStoreLedgerEntryType::PosPurchase->value,
                'amount' => 450000,
                'effect' => MemberStoreLedgerEffect::Debit->value,
                'balance_before' => 0,
                'balance_after' => -450000,
                'reference_type' => 'pos_transaction',
                'reference_id' => (string) $posTx->id,
                'actor_user_id' => $kasir->id,
                'purchaser_name' => 'Seed Member Google',
                'transaction_no' => 'SEED-POS-TX-012-001',
                'reason' => 'Pembelian POS SEED-POS-TX-012-001',
                'occurred_at' => '2026-05-20 14:00:00',
            ],
        );

        // D. Active Loan: productive, 3,000,000 principal, 6 months
        $productiveType = LoanType::query()->where('code', 'productive')->firstOrFail();
        $loanActive = Loan::query()->updateOrCreate(
            ['reference_no' => 'SEED-LOAN-ACTIVE-012-001'],
            [
                'cooperative_member_id' => $p12->id,
                'organization_id' => $headOffice->id,
                'loan_type_id' => $productiveType->id,
                'user_id' => $p12->user_id,
                'principal_amount' => 3000000,
                'interest_rate' => (float) $productiveType->interest_rate,
                'admin_fee' => (float) $productiveType->admin_fee,
                'late_fee_per_day' => (float) $productiveType->late_fee_per_day,
                'term_months' => 6,
                'installment_amount' => 537500,
                'total_interest_amount' => 225000,
                'total_amount' => 3225000,
                'outstanding_amount' => 3225000,
                'applied_at' => '2026-05-01',
                'first_due_date' => '2026-06-10',
                'manager_reviewed_at' => '2026-05-02 09:00:00',
                'manager_reviewed_by' => $manajer->id,
                'approved_at' => '2026-05-02 14:00:00',
                'approved_by' => $pengurus->id,
                'disbursed_at' => '2026-05-03 10:00:00',
                'disbursed_by' => $pengurus->id,
                'status' => LoanStatus::Active,
                'purpose' => 'Modal kerja usaha aktif',
                'notes' => 'Pinjaman aktif deterministik P12',
            ],
        );

        // Loan disbursement ledger
        CooperativeLedgerEntry::query()->updateOrCreate(
            [
                'source_type' => Loan::class,
                'source_id' => $loanActive->id,
                'entry_type' => 'LOAN_DISBURSEMENT',
            ],
            [
                'cooperative_member_id' => $p12->id,
                'organization_id' => $headOffice->id,
                'cooperative_payment_id' => null,
                'ledger_scope' => 'LOAN',
                'debit' => 3000000,
                'credit' => 0,
                'period' => '2026-05',
                'description' => 'Pencairan pinjaman koperasi produktif P12',
                'posted_at' => '2026-05-03',
            ],
        );

        // 6 Installments (pending, no payments)
        $dueDates = [
            '2026-06-10',
            '2026-07-10',
            '2026-08-10',
            '2026-09-10',
            '2026-10-10',
            '2026-11-10',
        ];

        foreach ($dueDates as $idx => $dueDate) {
            $installmentNo = $idx + 1;
            LoanInstallment::query()->updateOrCreate(
                [
                    'loan_id' => $loanActive->id,
                    'installment_no' => $installmentNo,
                ],
                [
                    'due_date' => $dueDate,
                    'principal_amount' => 500000,
                    'interest_amount' => 37500,
                    'fee_amount' => 0,
                    'penalty_amount' => 0,
                    'amount_due' => 537500,
                    'amount_paid' => 0,
                    'paid_at' => null,
                    'status' => InstallmentStatus::Pending,
                ],
            );
        }
    }

    /**
     * Enforce strictly zero financial records for P13 and non-active personas.
     *
     * @param  array<int, int>  $memberIds
     */
    private function enforceEmptyStateForReservedMembers(array $memberIds): void
    {
        if (empty($memberIds)) {
            return;
        }

        // Clean any accidental drifted seed records for empty-state members
        CooperativeDuesInvoice::query()->whereIn('cooperative_member_id', $memberIds)->delete();
        CooperativePayment::query()->whereIn('cooperative_member_id', $memberIds)->delete();
        CooperativeReceipt::query()->whereIn('cooperative_member_id', $memberIds)->delete();
        CooperativeLedgerEntry::query()->whereIn('cooperative_member_id', $memberIds)->delete();
        MemberStoreAccount::query()->whereIn('cooperative_member_id', $memberIds)->delete();
        Loan::query()->whereIn('cooperative_member_id', $memberIds)->delete();
        PosTransaction::query()->whereIn('cooperative_member_id', $memberIds)->delete();
    }
}
