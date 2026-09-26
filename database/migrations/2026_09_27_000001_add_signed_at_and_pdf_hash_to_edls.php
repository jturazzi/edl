<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edls', function (Blueprint $table) {
            // Horodatage exact de la validation (signature) : `updated_at` bouge à chaque modification
            $table->timestamp('signed_at')->nullable()->after('locataire_absent');
            // Empreinte SHA-256 du PDF généré à la validation : prouve qu'il n'a pas été modifié ensuite
            $table->string('pdf_hash', 64)->nullable()->after('pdf_path');
        });

        // EDL déjà terminés : meilleure approximation disponible
        DB::table('edls')->where('status', 'complete')->whereNull('signed_at')->update(['signed_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('edls', function (Blueprint $table) {
            $table->dropColumn(['signed_at', 'pdf_hash']);
        });
    }
};
