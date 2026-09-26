<?php

namespace App\Http\Controllers;

use App\Models\Edl;
use App\Models\EdlPhoto;
use App\Mail\EdlCompleteMail;
use App\Services\ActivityLogger;
use App\Services\EdlComparison;
use App\Services\EdlStructure;
use App\Rules\SignatureImage;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EdlController extends Controller
{
    // ═══════════════════════════════════════════════════════════════
    // API JSON - utilisées par le frontend Vue.js
    // ═══════════════════════════════════════════════════════════════

    /** Un EDL terminé (signé) est figé : ses réponses, ses pièces et ses photos ne changent plus. */
    private function ensureEditable(Edl $edl): void
    {
        abort_if($edl->isLocked(), 422, "Cet EDL est finalisé et signé : il n'est plus modifiable.");
    }

    /**
     * Liste paginée des EDL (JSON), avec recherche, filtres et tri.
     */
    public function apiIndex(Request $request)
    {
        return response()->json($this->filteredQuery($request)->paginate(20));
    }

    /**
     * Valeurs proposées par les filtres de l'historique (techniciens des EDL visibles).
     */
    public function apiFilters(Request $request)
    {
        $techniciens = Edl::query()
            ->whereNotNull('technicien_email')
            ->select('technicien_prenom', 'technicien_nom', 'technicien_email')
            ->distinct()
            ->get()
            ->map(fn ($e) => [
                'email' => $e->technicien_email,
                'name'  => trim("{$e->technicien_prenom} {$e->technicien_nom}") ?: $e->technicien_email,
            ])
            ->unique('email')
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return response()->json(['techniciens' => $techniciens]);
    }

    /**
     * Export CSV (Excel FR : séparateur « ; », UTF-8 avec BOM) des EDL correspondant aux filtres.
     */
    public function apiExport(Request $request)
    {
        $query = $this->filteredQuery($request);
        $filename = 'edl-export-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['N° EDL', 'Type', 'Statut', 'Adresse', 'Ville', 'Locataire', 'E-mail locataire', 'Technicien', 'E-mail technicien', 'Date', 'Locataire absent'], ';');

            $query->chunk(500, function ($edls) use ($out) {
                foreach ($edls as $edl) {
                    fputcsv($out, [
                        $edl->numero,
                        $edl->type === 'entrant' ? 'Entrant' : 'Sortant',
                        $edl->status === 'complete' ? 'Terminé' : 'En cours',
                        $edl->adresse,
                        $edl->ville,
                        $edl->locataire_full_name,
                        $edl->locataire_email,
                        $edl->agent_name,
                        $edl->technicien_email,
                        optional($edl->date_edl ?? $edl->created_at)->format('d/m/Y H:i'),
                        $edl->locataire_absent ? 'Oui' : 'Non',
                    ], ';');
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Requête commune à la liste et à l'export : périmètre de l'utilisateur, filtres, recherche, tri.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Edl>
     */
    private function filteredQuery(Request $request)
    {
        $query = Edl::with('user');

        // Archivés : masqués par défaut ; `archived=1` (administrateurs) n'affiche qu'eux
        if ($request->boolean('archived') && $request->user()->isAdmin()) {
            $query->whereNotNull('archived_at');
        } else {
            $query->notArchived();
        }

        if (in_array($request->query('type'), ['entrant', 'sortant'], true)) {
            $query->where('type', $request->query('type'));
        }

        if (in_array($request->query('status'), ['en_cours', 'complete'], true)) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('technicien')) {
            $query->where('technicien_email', $request->query('technicien'));
        }

        // Période : bornes incluses (aaaa-mm-jj), sur la date de l'EDL
        foreach (['from' => '>=', 'to' => '<='] as $param => $operator) {
            $value = (string) $request->query($param, '');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $query->whereDate('date_edl', $operator, $value);
            }
        }

        foreach (preg_split('/\s+/', trim((string) $request->query('q', '')), -1, PREG_SPLIT_NO_EMPTY) as $term) {
            $this->applySearchTerm($query, $term);
        }

        $sort = $request->query('sort');
        if (in_array($sort, ['date_edl', 'adresse', 'ville', 'type', 'status'], true)) {
            $query->orderBy($sort, $request->query('dir') === 'asc' ? 'asc' : 'desc')->orderByDesc('id');
        } else {
            $query->latest()->orderByDesc('id');
        }

        return $query;
    }

    /**
     * Filtre la requête : chaque mot doit correspondre à l'adresse, la ville,
     * le technicien, le locataire ou la date de l'EDL.
     */
    private function applySearchTerm($query, string $term): void
    {
        $like = '%' . addcslashes($term, '%_\\') . '%';

        $query->where(function ($q) use ($like, $term) {
            foreach (['adresse', 'ville', 'technicien_prenom', 'technicien_nom', 'technicien_email', 'locataire_prenom', 'locataire_nom'] as $col) {
                $q->orWhere($col, 'like', $like);
            }

            // N° EDL : « EDL-000123 », « 000123 » ou « 123 » (= id en base)
            if (preg_match('/^(?:EDL-?)?(\d+)$/i', $term, $m) && strlen($m[1]) <= 9) {
                $q->orWhere('id', (int) $m[1]);
            }

            $q->orWhereHas('user', fn ($u) => $u->where('firstname', 'like', $like)->orWhere('lastname', 'like', $like));

            // Date : jj/mm/aaaa, mm/aaaa, aaaa ou aaaa-mm-jj
            if (preg_match('#^(?:(\d{1,2})/)?(?:(\d{1,2})/)?(\d{4})$#', $term, $m)) {
                [$day, $month, $year] = $m[2] !== '' ? [$m[1], $m[2], $m[3]] : ['', $m[1], $m[3]];
                $q->orWhere(function ($d) use ($day, $month, $year) {
                    $d->whereYear('date_edl', $year);
                    if ($month !== '') {
                        $d->whereMonth('date_edl', (int) $month);
                    }
                    if ($day !== '') {
                        $d->whereDay('date_edl', (int) $day);
                    }
                });
            } elseif (preg_match('#^\d{4}-\d{2}(-\d{2})?$#', $term)) {
                $q->orWhere('date_edl', 'like', $term . '%');
            }
        });
    }

    /**
     * Création d'un EDL (JSON).
     */
    public function apiStore(Request $request)
    {
        $request->validate([
            'adresse'          => 'required|string|max:255',
            'ville'            => 'required|string|max:100',
            'technicien_prenom' => 'required|string|max:100',
            'technicien_nom'   => 'required|string|max:100',
            'technicien_email' => 'required|email|max:150',
            'type'             => 'required|in:entrant,sortant',
            'locataire_nom'    => 'nullable|string|max:100',
            'locataire_prenom' => 'nullable|string|max:100',
            'locataire_email'  => 'nullable|email|max:150',
            'steps'            => 'nullable|array',
            'steps.*'          => ['string', Rule::in(EdlStructure::keys())],
        ]);

        $edl = Edl::create([
            'type'             => $request->type,
            'adresse'          => $request->adresse,
            'ville'            => $request->ville,
            'technicien_prenom' => $request->technicien_prenom,
            'technicien_nom'   => $request->technicien_nom,
            'technicien_email' => $request->technicien_email,
            'locataire_nom'    => $request->locataire_nom,
            'locataire_prenom' => $request->locataire_prenom,
            'locataire_email'  => $request->locataire_email,
            'steps'            => EdlStructure::normalize($request->input('steps')),
            'date_edl'         => now(),
            'status'           => 'en_cours',
            'user_id'          => auth()->id(),
        ]);

        return response()->json($edl, 201);
    }

    /**
     * Crée l'EDL sortant à partir d'un entrant : reprend adresse, locataire,
     * catégorie et données saisies ; seul le technicien est à renseigner.
     */
    public function apiCreateSortant(Request $request, Edl $edl)
    {
        Gate::authorize('view', $edl);
        abort_unless($edl->type === 'entrant', 422, "Seul un EDL entrant peut servir de base à un sortant.");

        $request->validate([
            'technicien_prenom' => 'required|string|max:100',
            'technicien_nom'    => 'required|string|max:100',
            'technicien_email'  => 'required|email|max:150',
        ]);

        $sortant = Edl::create([
            'type'              => 'sortant',
            'adresse'           => $edl->adresse,
            'ville'             => $edl->ville,
            'locataire_nom'     => $edl->locataire_nom,
            'locataire_prenom'  => $edl->locataire_prenom,
            'locataire_email'   => $edl->locataire_email,
            'entrant_id'        => $edl->id,
            'steps'             => $edl->steps,
            'survey_data'       => $edl->survey_data,
            'technicien_prenom' => $request->technicien_prenom,
            'technicien_nom'    => $request->technicien_nom,
            'technicien_email'  => $request->technicien_email,
            'date_edl'          => now(),
            'status'            => 'en_cours',
            'user_id'           => auth()->id(),
        ]);

        return response()->json($sortant, 201);
    }

    /**
     * Duplique un EDL pour le même logement (nouveau locataire, nouvelle visite…).
     * Reprend adresse et pièces ; les réponses sont copiées sur demande ; ni photos ni signatures.
     */
    public function apiDuplicate(Request $request, Edl $edl)
    {
        Gate::authorize('view', $edl);

        $data = $request->validate([
            'type'              => 'required|in:entrant,sortant',
            'keep_tenant'       => 'sometimes|boolean',
            'keep_survey'       => 'sometimes|boolean',
            'technicien_prenom' => 'required|string|max:100',
            'technicien_nom'    => 'required|string|max:100',
            'technicien_email'  => 'required|email|max:150',
        ]);

        // Un sortant garde le lien vers l'entrant d'origine pour la comparaison
        $entrantId = null;
        if ($data['type'] === 'sortant') {
            $entrantId = $edl->type === 'entrant' ? $edl->id : $edl->entrant_id;
        }

        $keepTenant = $request->boolean('keep_tenant');

        $copy = Edl::create([
            'type'              => $data['type'],
            'adresse'           => $edl->adresse,
            'ville'             => $edl->ville,
            'locataire_nom'     => $keepTenant ? $edl->locataire_nom : null,
            'locataire_prenom'  => $keepTenant ? $edl->locataire_prenom : null,
            'locataire_email'   => $keepTenant ? $edl->locataire_email : null,
            'entrant_id'        => $entrantId,
            'steps'             => $edl->steps,
            'survey_data'       => $request->boolean('keep_survey') ? $edl->survey_data : null,
            'technicien_prenom' => $data['technicien_prenom'],
            'technicien_nom'    => $data['technicien_nom'],
            'technicien_email'  => $data['technicien_email'],
            'date_edl'          => now(),
            'status'            => 'en_cours',
            'user_id'           => auth()->id(),
        ]);

        ActivityLogger::edlDuplicated($copy->id, [
            'adresse' => $copy->adresse,
            'ville'   => $copy->ville,
            'type'    => $copy->type,
            'source'  => $edl->id,
        ]);

        return response()->json($copy, 201);
    }

    /**
     * Archive un EDL : il disparaît des listes et du tableau de bord mais reste consultable.
     */
    public function apiArchive(Edl $edl)
    {
        Gate::authorize('archive', $edl);

        if ($edl->archived_at === null) {
            $edl->update(['archived_at' => now()]);
            ActivityLogger::edlArchived($edl->id, ['adresse' => $edl->adresse, 'ville' => $edl->ville, 'type' => $edl->type]);
        }

        return response()->json(['success' => true, 'archived_at' => $edl->archived_at]);
    }

    public function apiUnarchive(Edl $edl)
    {
        Gate::authorize('archive', $edl);

        if ($edl->archived_at !== null) {
            $edl->update(['archived_at' => null]);
            ActivityLogger::edlUnarchived($edl->id, ['adresse' => $edl->adresse, 'ville' => $edl->ville, 'type' => $edl->type]);
        }

        return response()->json(['success' => true, 'archived_at' => null]);
    }

    /**
     * Retenues estimées sur le dépôt de garantie d'un EDL sortant (lignes libellé + montant).
     */
    public function updateRetenues(Request $request, Edl $edl)
    {
        Gate::authorize('update', $edl);
        abort_unless($edl->type === 'sortant', 422, 'Les retenues ne concernent que les EDL sortants.');

        $data = $request->validate([
            'retenues'          => 'present|array|max:100',
            'retenues.*.label'  => 'required|string|max:255',
            'retenues.*.amount' => 'required|numeric|min:0|max:1000000',
        ]);

        $lines = collect($data['retenues'])
            ->map(fn ($l) => ['label' => trim($l['label']), 'amount' => round((float) $l['amount'], 2)])
            ->values()
            ->all();

        $edl->update(['retenues' => $lines ?: null]);

        return response()->json(['success' => true, 'retenues' => $lines, 'total' => round(array_sum(array_column($lines, 'amount')), 2)]);
    }

    /**
     * Détail d'un EDL (JSON).
     */
    public function apiShow(Edl $edl)
    {
        Gate::authorize('view', $edl);
        $edl->load('user', 'photos', 'entrant');
        $edl->setAttribute('can_edit', Gate::allows('update', $edl));

        return response()->json($edl);
    }

    /**
     * Comparaison d'un EDL sortant avec son entrant (JSON).
     */
    public function apiComparison(Edl $edl, EdlComparison $comparison)
    {
        Gate::authorize('view', $edl);
        abort_unless($edl->type === 'sortant', 422, "Seul un EDL sortant peut être comparé à un entrant.");

        $entrant = $edl->entrant;
        abort_if($entrant === null, 404, "Aucun EDL entrant n'est associé à ce sortant.");

        return response()->json([...$comparison->build($entrant, $edl), 'retenues' => $edl->retenues ?? [], 'editable' => Gate::allows('update', $edl)]);
    }

    /**
     * Modifie les pièces / étapes affichées dans le formulaire (JSON).
     */
    public function updateSteps(Request $request, Edl $edl)
    {
        Gate::authorize('update', $edl);
        $this->ensureEditable($edl);

        $data = $request->validate([
            'steps'   => 'required|array|min:1',
            'steps.*' => ['string', Rule::in(EdlStructure::keys())],
        ]);

        $edl->update(['steps' => EdlStructure::normalize($data['steps'])]);

        return response()->json(['success' => true, 'steps' => $edl->steps]);
    }

    /**
     * Sauvegarde des données du formulaire (JSON).
     *
     * `base_rev` = révision des réponses connue du client. Si elle diffère de celle du serveur, un autre
     * appareil (ou onglet) a modifié l'EDL entre-temps : 409 avec la version serveur, sauf si `force`.
     */
    public function saveSurvey(Request $request, Edl $edl)
    {
        Gate::authorize('update', $edl);
        $this->ensureEditable($edl);
        $request->validate([
            'survey_data' => 'required|string',
            'base_rev'    => 'nullable|integer|min:0',
            'force'       => 'sometimes|boolean',
        ]);

        // Un emoji coupé en deux (surrogate UTF-16 isolé) rend le JSON illisible pour PHP : on le remplace
        $json = preg_replace('/\\\\ud[89ab][0-9a-f]{2}(?!\\\\ud[c-f][0-9a-f]{2})|(?<!\\\\ud[89ab][0-9a-f]{2})\\\\ud[c-f][0-9a-f]{2}/i', '\\\\ufffd', $request->survey_data) ?? $request->survey_data;
        $surveyData = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($surveyData)) {
            Log::warning('survey_data invalide', ['edl' => $edl->id, 'error' => json_last_error_msg(), 'length' => strlen($request->survey_data)]);

            return response()->json(['success' => false, 'message' => 'survey_data invalide (JSON malformé).'], 422);
        }

        if ($request->filled('base_rev') && (int) $request->base_rev !== $edl->survey_rev && ! $request->boolean('force')) {
            return response()->json([
                'success'     => false,
                'conflict'    => true,
                'message'     => 'Cet EDL a été modifié depuis un autre appareil.',
                'survey_data' => $edl->survey_data,
                'survey_rev'  => $edl->survey_rev,
            ], 409);
        }

        // La révision n'avance que si les réponses ont réellement changé
        if ($edl->survey_data != $surveyData) {
            $edl->survey_rev++;
            $edl->survey_data = $surveyData;
            $edl->save();
        }

        return response()->json(['success' => true, 'survey_rev' => $edl->survey_rev]);
    }

    /**
     * Upload d'une photo (JSON).
     */
    public function uploadPhoto(Request $request, Edl $edl)
    {
        Gate::authorize('update', $edl);
        $this->ensureEditable($edl);
        $request->validate([
            'photo'        => 'required|image|max:8192',
            'question_key' => 'required|string',
            'room'         => 'required|string',
            'caption'      => 'nullable|string|max:255',
        ]);

        $path = $request->file('photo')->store("edl/{$edl->id}/photos", 'local');

        $photo = EdlPhoto::create([
            'edl_id'       => $edl->id,
            'question_key' => $request->question_key,
            'room'         => $request->room,
            'photo_path'   => $path,
            'caption'      => $request->caption,
        ]);

        return response()->json([
            'success'      => true,
            'photo_id'     => $photo->id,
            'url'          => "/edl/photos/{$photo->id}",
            'question_key' => $photo->question_key,
            'caption'      => $photo->caption,
        ]);
    }

    /**
     * Mise à jour de la légende d'une photo (JSON).
     */
    public function updatePhoto(Request $request, EdlPhoto $photo)
    {
        Gate::authorize('update', $photo->edl);
        $this->ensureEditable($photo->edl);

        $data = $request->validate(['caption' => 'nullable|string|max:255']);
        $photo->update(['caption' => $data['caption'] !== null && trim($data['caption']) !== '' ? trim($data['caption']) : null]);

        return response()->json(['success' => true, 'caption' => $photo->caption]);
    }

    /**
     * Suppression d'une photo (JSON).
     */
    public function destroyPhoto(EdlPhoto $photo)
    {
        Gate::authorize('update', $photo->edl);
        $this->ensureEditable($photo->edl);

        Storage::disk('local')->delete($photo->photo_path);
        $photo->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Liste des photos d'un EDL (JSON).
     */
    public function listPhotos(Edl $edl)
    {
        Gate::authorize('view', $edl);
        $photos = $edl->photos->map(fn ($p) => [
            'id'           => $p->id,
            'room'         => $p->room,
            'question_key' => $p->question_key,
            'caption'      => $p->caption,
            'url'          => "/edl/photos/{$p->id}",
        ]);

        return response()->json($photos);
    }

    /**
     * Finalisation + génération PDF (JSON).
     */
    public function apiFinalize(Request $request, Edl $edl)
    {
        Gate::authorize('update', $edl);
        $this->ensureEditable($edl);
        $request->validate([
            'signature_technicien' => ['required', 'string', new SignatureImage],
            'locataire_absent'     => 'sometimes|boolean',
            'signature'            => ['required_unless:locataire_absent,true,1', 'nullable', 'string', new SignatureImage],
        ]);

        $absent = $request->boolean('locataire_absent');

        $edl->update([
            'signature_technicien' => $request->signature_technicien,
            'signature'            => $absent ? null : $request->signature,
            'locataire_absent'     => $absent,
            'status'               => 'complete',
            'signed_at'            => now(),
        ]);

        $pdfView = view('edl.pdf', ['edl' => $edl->fresh(['photos'])])->render();
        $pdf     = Pdf::loadHTML($pdfView)->setPaper('a4');
        $pdfName = "EDL-{$edl->id}-{$edl->type}-" . now()->format('Ymd_His') . '.pdf';
        $pdfPath = "edl/{$edl->id}/{$pdfName}";
        $output  = $pdf->output();
        Storage::disk('local')->put($pdfPath, $output);

        $edl->update(['pdf_path' => $pdfPath, 'pdf_hash' => hash('sha256', $output)]);

        ActivityLogger::edlCompleted($edl->id, [
            'adresse'          => $edl->adresse,
            'ville'            => $edl->ville,
            'type'             => $edl->type,
            'locataire'        => trim($edl->locataire_prenom . ' ' . $edl->locataire_nom) ?: null,
            'locataire_email'  => $edl->locataire_email,
        ]);

        return response()->json(['success' => true, 'edl_id' => $edl->id]);
    }

    /**
     * Intégrité du PDF : compare l'empreinte SHA-256 enregistrée à la validation avec celle du fichier stocké.
     */
    public function pdfIntegrity(Edl $edl)
    {
        Gate::authorize('view', $edl);
        abort_unless($edl->isLocked(), 422, "L'EDL n'est pas encore finalisé.");

        $exists = $edl->pdf_path && Storage::disk('local')->exists($edl->pdf_path);
        $current = $exists ? hash_file('sha256', Storage::disk('local')->path($edl->pdf_path)) : null;

        return response()->json([
            'hash'      => $edl->pdf_hash,
            'signed_at' => $edl->signed_at,
            'file'      => $exists,
            // null : EDL validé avant l'introduction de l'empreinte, rien à comparer
            'valid'     => $edl->pdf_hash === null ? null : ($current !== null && hash_equals($edl->pdf_hash, $current)),
        ]);
    }

    /**
     * Tous les EDL d'un même logement (adresse + ville), du plus récent au plus ancien.
     */
    public function logementEdls(Request $request)
    {
        $data = $request->validate([
            'adresse' => 'required|string|max:255',
            'ville'   => 'nullable|string|max:100',
        ]);

        // Comparaison faite en PHP (minuscules Unicode) : LOWER() de SQLite ignore les accents.
        // Un préfiltre SQL sur le mot le plus long de l'adresse limite les lignes chargées.
        $norm = fn (?string $v) => preg_replace('/\s+/u', ' ', mb_strtolower(trim((string) $v)));
        $word = collect(preg_split('/[\s,]+/u', $data['adresse'], -1, PREG_SPLIT_NO_EMPTY))->sortByDesc(fn ($w) => mb_strlen($w))->first();

        $edls = Edl::notArchived()
            ->where('adresse', 'like', '%' . addcslashes((string) $word, '%_\\') . '%')
            ->with('user')
            ->select(['id', 'user_id', 'type', 'status', 'adresse', 'ville', 'entrant_id', 'locataire_nom', 'locataire_prenom',
                'technicien_prenom', 'technicien_nom', 'date_edl', 'signed_at', 'created_at', 'updated_at'])
            ->orderByDesc('date_edl')->orderByDesc('id')
            ->get()
            ->filter(fn (Edl $e) => $norm($e->adresse) === $norm($data['adresse']) && $norm($e->ville) === $norm($data['ville'] ?? ''))
            ->values();

        return response()->json(['adresse' => $data['adresse'], 'ville' => $data['ville'] ?? '', 'edls' => $edls]);
    }

    /**
     * Envoi du PDF par email (JSON).
     */
    public function sendEmail(Request $request, Edl $edl)
    {
        Gate::authorize('view', $edl);
        $request->validate([
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'required|email',
        ]);

        if (! $edl->pdf_path || ! Storage::disk('local')->exists($edl->pdf_path)) {
            return response()->json(['success' => false, 'message' => 'PDF introuvable. Veuillez d\'abord finaliser l\'EDL.'], 422);
        }

        $edl->load('user');

        foreach ($request->recipients as $email) {
            Mail::to($email)->send(new EdlCompleteMail($edl));
        }

        return response()->json([
            'success' => true,
            'message' => 'Email envoyé à ' . count($request->recipients) . ' destinataire(s).',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // FICHIERS - routes classiques
    // ═══════════════════════════════════════════════════════════════

    /**
     * Affichage d'une photo (fichier).
     */
    public function showPhoto(EdlPhoto $photo)
    {
        Gate::authorize('view', $photo->edl);
        $path = Storage::disk('local')->path($photo->photo_path);

        return response()->file($path);
    }

    /**
     * Téléchargement du PDF (régénère si nécessaire).
     */
    public function downloadPdf(Edl $edl)
    {
        Gate::authorize('view', $edl);
        $this->ensurePdf($edl);

        return response()->download(Storage::disk('local')->path($edl->pdf_path));
    }

    /**
     * Lecture du PDF dans le navigateur (inline).
     */
    public function viewPdf(Edl $edl)
    {
        Gate::authorize('view', $edl);
        $this->ensurePdf($edl);

        return response()->file(Storage::disk('local')->path($edl->pdf_path), [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($edl->pdf_path) . '"',
        ]);
    }

    /**
     * Génère le PDF si absent et met à jour le modèle.
     */
    private function ensurePdf(Edl $edl): void
    {
        if (! $edl->pdf_path || ! Storage::disk('local')->exists($edl->pdf_path)) {
            $edl->load('photos');
            $pdfView = view('edl.pdf', ['edl' => $edl])->render();
            $pdf     = Pdf::loadHTML($pdfView)->setPaper('a4');
            $pdfName = "EDL-{$edl->id}-{$edl->type}-" . now()->format('Ymd_His') . '.pdf';
            $pdfPath = "edl/{$edl->id}/{$pdfName}";
            Storage::disk('local')->put($pdfPath, $pdf->output());
            $edl->update(['pdf_path' => $pdfPath]);
        }
    }

    /**
     * Suppression d'un EDL et de ses photos (JSON).
     */
    public function apiDestroy(Edl $edl)
    {
        Gate::authorize('delete', $edl);
        $logDetails = [
            'adresse' => $edl->adresse,
            'ville'   => $edl->ville,
            'type'    => $edl->type,
            'status'  => $edl->status,
        ];
        $edlId = $edl->id;

        // Supprimer toutes les photos stockées
        foreach ($edl->photos as $photo) {
            Storage::disk('local')->delete($photo->photo_path);
        }

        // Supprimer le PDF
        if ($edl->pdf_path) {
            Storage::disk('local')->delete($edl->pdf_path);
        }

        $edl->delete();

        ActivityLogger::edlDeleted($edlId, $logDetails);

        return response()->json(['success' => true]);
    }
}
