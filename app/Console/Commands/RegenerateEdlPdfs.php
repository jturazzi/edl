<?php

namespace App\Console\Commands;

use App\Models\Edl;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RegenerateEdlPdfs extends Command
{
    protected $signature = 'edl:regenerate-pdfs {--id=* : Limiter à certains EDL} {--all : Inclure les EDL en cours}';

    protected $description = 'Régénère les PDF des EDL terminés avec le gabarit actuel';

    public function handle(): int
    {
        $query = Edl::query()->with('photos', 'user');

        if (! $this->option('all')) {
            $query->where('status', 'complete');
        }
        if ($this->option('id')) {
            $query->whereIn('id', $this->option('id'));
        }

        $ok = $failed = 0;

        foreach ($query->cursor() as $edl) {
            try {
                $pdf     = Pdf::loadHTML(view('edl.pdf', ['edl' => $edl])->render())->setPaper('a4');
                $pdfPath = "edl/{$edl->id}/EDL-{$edl->id}-{$edl->type}-" . now()->format('Ymd_His') . '.pdf';

                Storage::disk('local')->put($pdfPath, $pdf->output());

                $old = $edl->pdf_path;
                $edl->update(['pdf_path' => $pdfPath]);

                if ($old && $old !== $pdfPath) {
                    Storage::disk('local')->delete($old);
                }

                $ok++;
                $this->line("✓ {$edl->numero}");
            } catch (\Throwable $e) {
                $failed++;
                $this->error("✗ {$edl->numero} : {$e->getMessage()}");
            }
        }

        $this->info("{$ok} PDF régénéré(s), {$failed} échec(s).");

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
