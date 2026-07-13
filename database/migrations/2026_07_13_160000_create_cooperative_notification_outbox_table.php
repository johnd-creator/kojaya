<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cooperative_notification_outbox', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('deduplication_key', 120);
            $table->json('payload');
            $table->string('status', 20)->default('PENDING');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('available_at');
            $table->timestamp('delivered_at')->nullable();
            $table->string('last_error', 120)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'deduplication_key'], 'notification_outbox_user_dedup_unique');
            $table->index('status', 'notification_outbox_status_index');
            $table->index('available_at', 'notification_outbox_available_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cooperative_notification_outbox');
    }
};
