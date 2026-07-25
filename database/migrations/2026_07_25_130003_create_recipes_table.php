<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('ease_tier', ['lazy', 'normal', 'ambitious'])->default('normal');
            $table->unsignedInteger('prep_minutes')->nullable();
            $table->unsignedInteger('cook_minutes')->nullable();
            $table->unsignedInteger('servings')->nullable();
            $table->longText('instructions');
            $table->string('source_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
