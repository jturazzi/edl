<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suppression de la fonctionnalité « catégories ».
 *
 * ⚠ Les catégories existantes et leur rattachement aux EDL sont perdus :
 * down() recrée la structure, pas les données.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edls', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::dropIfExists('categories');
    }

    public function down(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color')->default('#6366f1');
            $table->timestamps();
        });

        Schema::table('edls', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('user_id')->constrained('categories')->nullOnDelete();
        });
    }
};
