<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secret_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('secret_id')->constrained()->onDelete('cascade');
            $table->string('environment'); // e.g. development, staging, production
            $table->text('value');
            $table->timestamps();

            $table->unique(['secret_id', 'environment']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secret_values');
    }
};
