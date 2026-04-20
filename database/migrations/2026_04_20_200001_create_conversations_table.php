<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_account_id')->constrained()->onDelete('cascade');
            $table->string('ig_conversation_id')->nullable();
            $table->string('participant_ig_id');
            $table->string('participant_username')->nullable();
            $table->string('participant_name')->nullable();
            $table->string('participant_profile_pic')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->integer('unread_count')->default(0);
            $table->timestamps();
            $table->unique(['instagram_account_id', 'participant_ig_id']);
            $table->index(['instagram_account_id', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
