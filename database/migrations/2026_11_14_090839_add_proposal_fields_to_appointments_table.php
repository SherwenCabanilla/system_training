<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->json('requested_details')->nullable();
            $table->uuid('proposal_token')->nullable();
            $table->dateTime('proposal_expires_at')->nullable();
            $table->index(['status', 'proposal_expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['status', 'proposal_expires_at']);
            $table->dropColumn(['requested_details', 'proposal_token', 'proposal_expires_at']);
        });
    }
};
