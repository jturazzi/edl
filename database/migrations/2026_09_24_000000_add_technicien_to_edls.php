<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edls', function (Blueprint $table) {
            $table->string('technicien_prenom')->nullable()->after('ville');
            $table->string('technicien_nom')->nullable()->after('technicien_prenom');
            $table->string('technicien_email')->nullable()->after('technicien_nom');
        });
    }

    public function down(): void
    {
        Schema::table('edls', function (Blueprint $table) {
            $table->dropColumn(['technicien_prenom', 'technicien_nom', 'technicien_email']);
        });
    }
};
