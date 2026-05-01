<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizations = Organization::all();

        if ($organizations->isEmpty()) {
            $this->command->warn('No organizations found. Please seed organizations first.');

            return;
        }

        foreach ($organizations as $org) {
            $units = Organization::where('parent_id', $org->id)->get();

            if ($units->isEmpty()) {
                $units = collect([$org]);
            }

            foreach ($units as $unit) {
                Invoice::factory()->count(5)->create([
                    'organization_id' => $org->id,
                    'unit_id' => $unit->id,
                ]);
            }
        }
    }
}
