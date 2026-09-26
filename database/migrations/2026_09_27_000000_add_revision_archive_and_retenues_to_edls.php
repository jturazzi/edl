<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edls', function (Blueprint $table) {
            // Numéro de révision des réponses : détecte deux appareils qui modifient le même EDL
            $table->unsignedInteger('survey_rev')->default(0)->after('survey_data');
            // EDL archivé (masqué des listes, conservé)
            $table->timestamp('archived_at')->nullable()->after('status');
            // Retenues estimées sur le dépôt de garantie (sortant) : [{label, amount}]
            $table->text('retenues')->nullable()->after('steps');
        });
    }

    public function down(): void
    {
        Schema::table('edls', function (Blueprint $table) {
            $table->dropColumn(['survey_rev', 'archived_at', 'retenues']);
        });
    }
};
