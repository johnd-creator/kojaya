<?php

namespace App\Observers;

use App\Models\MedicalCheckup;

class MedicalCheckupObserver
{
    /**
     * Handle the MedicalCheckup "created" event.
     */
    public function created(MedicalCheckup $medicalCheckup): void
    {
        //
    }

    /**
     * Handle the MedicalCheckup "updated" event.
     */
    public function updated(MedicalCheckup $medicalCheckup): void
    {
        //
    }

    /**
     * Handle the MedicalCheckup "deleted" event.
     */
    public function deleted(MedicalCheckup $medicalCheckup): void
    {
        //
    }

    /**
     * Handle the MedicalCheckup "restored" event.
     */
    public function restored(MedicalCheckup $medicalCheckup): void
    {
        //
    }

    /**
     * Handle the MedicalCheckup "force deleted" event.
     */
    public function forceDeleted(MedicalCheckup $medicalCheckup): void
    {
        //
    }
}
