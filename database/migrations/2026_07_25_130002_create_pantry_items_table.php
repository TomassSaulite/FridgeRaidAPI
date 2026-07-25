<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pantry_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->string('quantity')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'ingredient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pantry_items');
    }
};
