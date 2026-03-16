<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secret_value_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('secret_value_id')->constrained()->onDelete('cascade');
            $table->text('value');
            $table->foreignId('changed_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['secret_value_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secret_value_histories');
    }
};
