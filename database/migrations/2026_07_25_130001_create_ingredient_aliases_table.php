<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredient_aliases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->string('alias');
            $table->timestamps();
            $table->index('alias');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_aliases');
    }
};
