<?php

namespace App\Http\Controllers;

use App\Models\Edl;
use App\Models\EdlPhoto;
use App\Mail\EdlCompleteMail;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EdlController extends Controller
{
    // ═══════════════════════════════════════════════════════════════
    // API JSON — utilisées par le frontend Vue.js
    // ═══════════════════════════════════════════════════════════════

    /**
     * Liste paginée des EDL (JSON).
     */
    public function apiIndex(Request $request)
    {
        $query = Edl::with('user', 'category')->latest();

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $edls = $query->paginate(20);

        return response()->json($edls);
    }

    /**
     * Création d'un EDL (JSON).
     */
    public function apiStore(Request $request)
    {
        $request->validate([
            'adresse'          => 'required|string|max:255',
            'ville'            => 'required|string|max:100',
            'type'             => 'required|in:entrant,sortant',
            'locataire_nom'    => 'nullable|string|max:100',
            'locataire_prenom' => 'nullable|string|max:100',
            'locataire_email'  => 'nullable|email|max:150',
            'category_id'      => 'nullable|exists:categories,id',
        ]);

        $edl = Edl::create([
            'type'             => $request->type,
            'adresse'          => $request->adresse,
            'ville'            => $request->ville,
            'locataire_nom'    => $request->locataire_nom,
            'locataire_prenom' => $request->locataire_prenom,
            'locataire_email'  => $request->locataire_email,
            'category_id'      => $request->category_id,
            'date_edl'         => now(),
            'status'           => 'en_cours',
            'user_id'          => auth()->id(),
        ]);

        return response()->json($edl, 201);
    }

    /**
     * Détail d'un EDL (JSON).
     */
    public function apiShow(Edl $edl)
    {
        $edl->load('user', 'photos');

        return response()->json($edl);
    }

    /**
     * Sauvegarde des données du formulaire (JSON).
     */
    public function saveSurvey(Request $request, Edl $edl)
    {
        $request->validate(['survey_data' => 'required|string']);

        $edl->update(['survey_data' => json_decode($request->survey_data, true)]);

        return response()->json(['success' => true]);
    }

    /**
     * Upload d'une photo (JSON).
     */
    public function uploadPhoto(Request $request, Edl $edl)
    {
        $request->validate([
            'photo'        => 'required|image|max:8192',
            'question_key' => 'required|string',
            'room'         => 'required|string',
        ]);

        $path = $request->file('photo')->store("edl/{$edl->id}/photos", 'local');

        $photo = EdlPhoto::create([
            'edl_id'       => $edl->id,
            'question_key' => $request->question_key,
            'room'         => $request->room,
            'photo_path'   => $path,
        ]);

        return response()->json([
            'success'  => true,
            'photo_id' => $photo->id,
            'url'      => "/edl/photos/{$photo->id}",
        ]);
    }

    /**
     * Liste des photos d'un EDL (JSON).
     */
    public function listPhotos(Edl $edl)
    {
        $photos = $edl->photos->map(fn ($p) => [
            'id'   => $p->id,
            'room' => $p->room,
            'url'  => "/edl/photos/{$p->id}",
        ]);

        return response()->json($photos);
    }

    /**
     * Finalisation + génération PDF (JSON).
     */
    public function apiFinalize(Request $request, Edl $edl)
    {
        $request->validate(['signature' => 'required|string']);

        $edl->update([
            'signature' => $request->signature,
            'status'    => 'complete',
        ]);

        $pdfView = view('edl.pdf', ['edl' => $edl->fresh(['photos'])])->render();
        $pdf     = Pdf::loadHTML($pdfView)->setPaper('a4');
        $pdfName = "EDL-{$edl->id}-{$edl->type}-" . now()->format('Ymd_His') . '.pdf';
        $pdfPath = "edl/{$edl->id}/{$pdfName}";
        Storage::disk('local')->put($pdfPath, $pdf->output());

        $edl->update(['pdf_path' => $pdfPath]);

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
     * Envoi du PDF par email (JSON).
     */
    public function sendEmail(Request $request, Edl $edl)
    {
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
    // FICHIERS — routes classiques
    // ═══════════════════════════════════════════════════════════════

    /**
     * Affichage d'une photo (fichier).
     */
    public function showPhoto(EdlPhoto $photo)
    {
        $path = Storage::disk('local')->path($photo->photo_path);

        return response()->file($path);
    }

    /**
     * Téléchargement du PDF (régénère si nécessaire).
     */
    public function downloadPdf(Edl $edl)
    {
        $this->ensurePdf($edl);

        return response()->download(Storage::disk('local')->path($edl->pdf_path));
    }

    /**
     * Lecture du PDF dans le navigateur (inline).
     */
    public function viewPdf(Edl $edl)
    {
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
