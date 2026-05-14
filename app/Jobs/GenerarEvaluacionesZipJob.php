<?php

namespace App\Jobs;

use App\Models\Ciclos;
use App\Models\Entry;
use App\Models\Sections;
use App\Models\AnswerFullView;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use ZipArchive;

class GenerarEvaluacionesZipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    private $download;
    private $nombre;

    public function __construct($download, $nombre)
    {
        $this->download = $download;
        $this->nombre = $nombre;
    }

    public function handle(): void
    {

        $this->download->update([
            'status' => 'processing'
        ]);

        try {
            $ciclo = Ciclos::whereJsonContains('sistemas', 'investigacion')
                ->latest()
                ->first();

            if (!$ciclo) {
                Log::warning('No existe ciclo activo');
                return;
            }

            $query = AnswerFullView::with('user')
                ->where('ciclo_id', $ciclo->id)
                ->where('section_title', 'Proyectos de Investigación')
                ->where('pregunta', 'Folio')
                ->get();

            if ($query->isEmpty()) {
                Log::warning('No hay evaluaciones');
                return;
            }

            $fileName = $this->nombre;

            $zipPath = storage_path("app/public/{$fileName}");

            $zip = new ZipArchive();

            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                Log::error('No se pudo crear ZIP');
                return;
            }

            foreach ($query as $proyecto) {

                $entry = Entry::with(['answers.question'])
                    ->find($proyecto->id_evaluacion);


                if (!$entry || $entry->obtenerCalificacionDeEvaluacion() > 60) {
                    continue;
                }

                Log::info("{$entry->obtenerCalificacionDeEvaluacion()}");

                $firstAnswer = $entry->answers->first();

                if (!$firstAnswer || !$firstAnswer->question) {
                    continue;
                }

                $idSeccion = $firstAnswer->question->section_id;

                $seccion = Sections::with([
                    'questions' => fn($q) => $q->orderBy('sort_order')
                ])->find($idSeccion);

                if (!$seccion) {
                    continue;
                }

                $answersMap = $entry->answers->keyBy('question_id');

                $pdf = Pdf::loadView('asignaciones.partials.pdf', [
                    'entry' => $entry,
                    'seccion' => $seccion,
                    'answersMap' => $answersMap,
                ]);

                $pdfContent = $pdf->output();

                $nombre = $this->sanitizeFilename($proyecto->respuesta);

                $zip->addFromString(
                    "evaluacion_{$nombre}.pdf",
                    $pdfContent
                );
            }

            $zip->close();

            Log::info("ZIP generado correctamente: {$fileName}");
            $this->download->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {

            $this->download->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function sanitizeFilename(string $filename): string
    {
        $invalid = ['\\', '/', ':', '*', '?', '"', '<', '>', '|'];

        $safe = str_replace($invalid, '_', $filename);

        $safe = trim($safe);

        return substr($safe, 0, 255);
    }
}
