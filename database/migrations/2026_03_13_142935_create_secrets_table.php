<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secrets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('key');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['key', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secrets');
    }
};
