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
        Schema::create('ingredient_substitutions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('substitute_ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->float('confidence');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['ingredient_id', 'substitute_ingredient_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_substitutions');
    }
};
