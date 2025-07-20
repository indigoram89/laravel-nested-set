<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('nested-set.table', 'nested_sets'), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->integer('lft')->default(0)->index();
            $table->integer('rgt')->default(0)->index();
            $table->unsignedInteger('depth')->default(0);
            $table->string('name');
            $table->string('slug')->nullable()->index();
            $table->timestamps();
            
            $table->index(['lft', 'rgt']);
            $table->index(['parent_id', 'lft']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('nested-set.table', 'nested_sets'));
    }
};