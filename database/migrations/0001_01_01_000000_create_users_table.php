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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->nullable()->unique()->index();
            $table->string('name');
            $table->string('nick_name')->nullable();
            $table->string('email')->unique()->index();
            $table->string('password');
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('birthdate')->nullable();
            $table->string('profile_photo')->nullable();
            $table->text('about')->nullable();
            $table->unsignedBigInteger('coins')->default(0);
            $table->unsignedBigInteger('beans')->default(0);
            $table->string('country_code')->foreign()->references('code')->on('countries')->nullOnDelete();
            $table->string('contact_number')->nullable();
            // status active banned or deleted
            $table->enum('status', ['active', 'email_banned','go_live_banned','stream_joining_banned','device_banned','deleted'])->default('active');
            // we have google,facebook,whatsapp and apple as auth providers
            $table->enum('auth_provider', ['email_and_password','google', 'facebook', 'whatsapp',])->default('email_and_password');
            $table->string('auth_provider_id')->nullable();

           
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
