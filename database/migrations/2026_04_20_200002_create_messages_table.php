<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->string('meta_message_id')->unique()->nullable();
            $table->string('from_ig_id');
            $table->string('to_ig_id');
            $table->text('message_text')->nullable();
            $table->string('message_type')->default('text'); // text, image, audio, video, file
            $table->string('media_url')->nullable();
            $table->boolean('is_outgoing')->default(false); // true = bizden gönderilen
            $table->boolean('is_read')->default(false);
            $table->timestamp('sent_at');
            $table->timestamps();
            $table->index(['conversation_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
