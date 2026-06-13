<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cooperative_members', function (Blueprint $table) {
            $table->decimal('credit_limit', 15, 2)->default(0)->after('status');
            $table->decimal('outstanding_balance', 15, 2)->default(0)->after('credit_limit');
            $table->unsignedSmallInteger('credit_term_days')->default(30)->after('outstanding_balance');
            $table->string('credit_tier', 30)->default('REGULAR')->after('credit_term_days');
        });

        Schema::create('pos_member_credit_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_member_id')->constrained('cooperative_members')->cascadeOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference_no', 80)->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('paid_at');
            $table->string('notes', 500)->nullable();
            $table->timestamps();
            $table->index(['cooperative_member_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_member_credit_payments');
        Schema::table('cooperative_members', function (Blueprint $table) {
            $table->dropColumn(['credit_limit', 'outstanding_balance', 'credit_term_days', 'credit_tier']);
        });
    }
};
