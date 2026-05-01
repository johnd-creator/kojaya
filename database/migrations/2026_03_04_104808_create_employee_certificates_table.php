<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->enum('certificate_type', ['SIO_K3', 'TRAINING', 'OTHER']);
            $table->string('certificate_number');
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->string('issuing_authority')->nullable();
            $table->string('document_path')->nullable();
            $table->enum('status', ['VALID', 'EXPIRED', 'REVOKED'])->default('VALID');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('expiry_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_certificates');
    }
};
