<?php

declare(strict_types=1);

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Tests\TestCase;

class Phase4FullRegressionInventoryTest extends TestCase
{
    public const FUNCTIONAL_EVIDENCE = [
        'tests/Feature/Auth/AuthenticationAndAccessFunctionalTest.php',
        'tests/Feature/Member/MemberLifecycleAndProfileFunctionalTest.php',
        'tests/Feature/Cooperative/AdminMemberManagementFunctionalTest.php',
        'tests/Feature/Cooperative/ContributionsDuesPaymentsFunctionalTest.php',
        'tests/Feature/Cooperative/StoreCreditFunctionalTest.php',
        'tests/Feature/Cooperative/PosCashierFunctionalTest.php',
        'tests/Feature/Cooperative/LoanFunctionalTest.php',
        'tests/Feature/Cooperative/FinanceLedgerFunctionalTest.php',
        'tests/Feature/Cooperative/OrganizationPermissionIsolationFunctionalTest.php',
        'tests/Feature/Cooperative/FailureRecoveryEdgeFunctionalTest.php',
    ];

    public const POSTGRESQL_SUITES = [
        'PostgreSQLConcurrency',
        'Document05PostgreSQL',
        'BackupRestoreDrill',
    ];

    public const POSTGRESQL_CONCURRENCY_EVIDENCE = [
        'tests/Feature/Cooperative/PosDailyClosingConcurrencyTest.php',
        'tests/Feature/Cooperative/PosShiftCheckoutConcurrencyTest.php',
        'tests/Feature/Cooperative/PosVoidConcurrencyTest.php',
        'tests/Feature/PaymentConcurrencyTest.php',
        'tests/Feature/Cooperative/MemberLifecycleConcurrencyTest.php',
        'tests/Feature/Cooperative/StoreCreditConcurrencyTest.php',
        'tests/Feature/Cooperative/MemberImportConcurrencyTest.php',
        'tests/Feature/Auth/Sso/GoogleSsoMemberMatchingConcurrencyTest.php',
    ];

    public function test_phase_four_functional_evidence_files_remain_in_the_repository(): void
    {
        foreach (self::FUNCTIONAL_EVIDENCE as $path) {
            $this->assertFileExists(base_path($path), "Missing Phase 4 functional evidence: {$path}");
        }
    }

    public function test_postgresql_regression_files_remain_owned_by_the_dedicated_suites(): void
    {
        $configurationPath = base_path('phpunit.pgsql.xml');
        $document = new DOMDocument;
        $this->assertTrue($document->load($configurationPath), 'PostgreSQL PHPUnit configuration must remain valid XML.');

        $xpath = new DOMXPath($document);
        $suiteNodes = $xpath->query('/phpunit/testsuites/testsuite');
        $suiteNames = [];
        foreach ($suiteNodes as $suiteNode) {
            $suiteNames[] = $suiteNode->getAttribute('name');
        }

        $this->assertEqualsCanonicalizing(self::POSTGRESQL_SUITES, $suiteNames);
        $ownedFiles = [];

        foreach ($xpath->query('/phpunit/testsuites/testsuite[@name="PostgreSQLConcurrency"]/file') as $fileNode) {
            $ownedFiles[] = $fileNode->textContent;
        }

        foreach (self::POSTGRESQL_CONCURRENCY_EVIDENCE as $path) {
            $this->assertContains($path, $ownedFiles, "PostgreSQLConcurrency must own {$path}");
            $this->assertFileExists(base_path($path), "Missing PostgreSQL concurrency evidence: {$path}");
        }
    }
}
