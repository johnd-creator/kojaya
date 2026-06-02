<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_onboarding_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_member_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamp('profile_completed_at')->nullable();
            $table->timestamp('kyc_uploaded_at')->nullable();
            $table->timestamp('first_savings_paid_at')->nullable();
            $table->timestamp('loan_intro_seen_at')->nullable();
            $table->timestamp('reward_intro_seen_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_onboarding_progress');
    }
};
