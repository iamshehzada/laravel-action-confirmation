<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('action_confirmations', function (Blueprint $table) {
            $table->id();

            $table->string('action');
            $table->morphs('target'); // target_type, target_id

            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('token', 80)->unique();

            $table->text('reason')->nullable();

            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('expires_at');

            $table->timestamps();

            $table->index(['action', 'actor_id']);
            $table->index(['expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_confirmations');
    }
};
