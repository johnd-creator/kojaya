<?php

namespace App\Jobs;

use App\Models\EmployeeCertificate;
use App\Models\EmployeeContract;
use App\Models\MedicalCheckup;
use App\Notifications\ContractExpiring;
use App\Notifications\McuDue;
use App\Notifications\SioExpiring;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as QueueQueueable;
use Illuminate\Support\Facades\Log;

class SendExpiryReminder implements ShouldQueue
{
    use QueueQueueable;

    public function __construct()
    {
        $this->onQueue('high');
    }

    public function handle(): void
    {
        Log::info('Checking for expiring items...');

        // Contract reminders: 90, 60, 30 days
        $contractDaysToCheck = [90, 60, 30];
        foreach ($contractDaysToCheck as $days) {
            $this->checkContractsExpiringIn($days);
        }

        // SIO certificate reminders: 60, 30 days
        $sioDaysToCheck = [60, 30];
        foreach ($sioDaysToCheck as $days) {
            $this->checkSioCertificatesExpiringIn($days);
        }

        // MCU due reminders: 30 days
        $this->checkMcuDueIn(30);
    }

    protected function checkContractsExpiringIn(int $days): void
    {
        $expiryDate = now()->addDays($days);

        $expiringContracts = EmployeeContract::where('end_date', $expiryDate->toDateString())
            ->where('status', 'ACTIVE')
            ->with('employee.user')
            ->get();

        foreach ($expiringContracts as $contract) {
            $this->sendReminder($contract, $days);
        }
    }

    protected function sendReminder(EmployeeContract $contract, int $days): void
    {
        $employee = $contract->employee;
        $user = $employee?->user;

        if (! $user) {
            Log::warning("No user found for employee {$employee->id}");

            return;
        }

        $notification = new ContractExpiring($contract, $days);

        $enabledChannels = $user->notificationPreference?->getEnabledChannels() ?? ['database'];

        $user->notify($notification);

        Log::info("Sent contract expiry reminder to user {$user->id} for contract {$contract->id}");
    }

    protected function checkSioCertificatesExpiringIn(int $days): void
    {
        Log::info("Checking for SIO certificates expiring in {$days} days...");

        $expiryDate = now()->addDays($days);

        $expiringCertificates = EmployeeCertificate::where('certificate_type', 'SIO_K3')
            ->where('status', 'VALID')
            ->whereDate('expiry_date', $expiryDate)
            ->with('employee.user')
            ->get();

        foreach ($expiringCertificates as $certificate) {
            $this->sendSioReminder($certificate, $days);
        }
    }

    protected function sendSioReminder(EmployeeCertificate $certificate, int $days): void
    {
        $employee = $certificate->employee;
        $user = $employee?->user;

        if (! $user) {
            Log::warning("No user found for employee {$employee->id}");

            return;
        }

        $notification = new SioExpiring($certificate, $days);

        $user->notify($notification);

        Log::info("Sent SIO expiry reminder to user {$user->id} for certificate {$certificate->id}");
    }

    protected function checkMcuDueIn(int $days): void
    {
        Log::info("Checking for MCU due in {$days} days...");

        $dueDate = now()->addDays($days);

        $dueMcus = MedicalCheckup::whereDate('next_checkup_date', $dueDate)
            ->with('employee.user')
            ->get();

        foreach ($dueMcus as $mcu) {
            $this->sendMcuReminder($mcu, $days);
        }
    }

    protected function sendMcuReminder(MedicalCheckup $mcu, int $days): void
    {
        $employee = $mcu->employee;
        $user = $employee?->user;

        if (! $user) {
            Log::warning("No user found for employee {$employee->id}");

            return;
        }

        $notification = new McuDue($mcu, $days);

        $user->notify($notification);

        Log::info("Sent MCU due reminder to user {$user->id} for MCU {$mcu->id}");
    }
}
