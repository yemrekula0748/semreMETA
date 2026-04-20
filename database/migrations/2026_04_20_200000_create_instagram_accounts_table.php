<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('instagram_user_id')->unique();
            $table->string('page_id')->nullable();
            $table->text('access_token');
            $table->string('profile_picture_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_instagram_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('instagram_account_id')->constrained()->onDelete('cascade');
            $table->unique(['user_id', 'instagram_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_instagram_accounts');
        Schema::dropIfExists('instagram_accounts');
    }
};
