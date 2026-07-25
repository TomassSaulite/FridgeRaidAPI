<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_tags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->string('tag');
            $table->timestamps();
            $table->index('tag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_tags');
    }
};
