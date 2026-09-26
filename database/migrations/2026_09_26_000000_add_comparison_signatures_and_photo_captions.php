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
            // Lien sortant → entrant (comparaison des états)
            $table->foreignId('entrant_id')->nullable()->after('category_id')->constrained('edls')->nullOnDelete();
            // Signature du technicien (`signature` reste celle du locataire)
            $table->longText('signature_technicien')->nullable()->after('signature');
            // Locataire absent ou dans l'impossibilité de signer
            $table->boolean('locataire_absent')->default(false)->after('signature_technicien');
        });

        Schema::table('edl_photos', function (Blueprint $table) {
            $table->string('caption', 255)->nullable()->after('photo_path');
        });

        // Rattache les sortants existants à leur entrant le plus probable :
        // même logement, entrant créé avant le sortant, le plus récent.
        $sortants = DB::table('edls')->where('type', 'sortant')->whereNull('entrant_id')->get(['id', 'adresse', 'ville', 'created_at']);
        foreach ($sortants as $sortant) {
            $entrantId = DB::table('edls')
                ->where('type', 'entrant')
                ->where('adresse', $sortant->adresse)
                ->where('ville', $sortant->ville)
                ->where('created_at', '<=', $sortant->created_at)
                ->orderByDesc('created_at')
                ->value('id');

            if ($entrantId) {
                DB::table('edls')->where('id', $sortant->id)->update(['entrant_id' => $entrantId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('edl_photos', function (Blueprint $table) {
            $table->dropColumn('caption');
        });

        Schema::table('edls', function (Blueprint $table) {
            $table->dropConstrainedForeignId('entrant_id');
            $table->dropColumn(['signature_technicien', 'locataire_absent']);
        });
    }
};
