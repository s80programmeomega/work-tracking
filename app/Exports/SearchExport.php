<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export Excel d'une feuille de résultats de recherche.
 * Une instance par type (projets, taches, documents, etc.).
 * Utilisé par SearchController::export() et SearchExportJob.
 */
class SearchExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        private readonly Collection $results,
        private readonly string $type,
    ) {}

    public function title(): string
    {
        return match ($this->type) {
            'projets' => 'Projets',
            'activites' => 'Activités',
            'taches' => 'Tâches',
            'documents' => 'Documents',
            'users' => 'Membres',
            'messages' => 'Messages',
            default => ucfirst($this->type),
        };
    }

    public function headings(): array
    {
        return match ($this->type) {
            'projets' => ['ID', 'Nom', 'Statut', 'Workspace', 'Responsable', 'URL'],
            'activites' => ['ID', 'Nom', 'Projet', 'Workspace', 'Responsable', 'URL'],
            'taches' => ['ID', 'Titre', 'Statut', 'Priorité', 'Projet', 'Workspace', 'Assignés', 'URL'],
            'documents' => ['ID', 'Nom', 'Type', 'Workspace', 'Uploadé par', 'URL'],
            'users' => ['ID', 'Nom complet', 'Email', 'Fonction', 'URL'],
            'messages' => ['ID', 'Équipe', 'Auteur', 'Extrait', 'Workspace', 'URL'],
            default => ['ID', 'Label', 'Extrait', 'URL'],
        };
    }

    public function map($row): array
    {
        $meta = $row['meta'] ?? [];

        return match ($this->type) {
            'projets' => [
                $row['id'] ?? '',
                strip_tags($row['label'] ?? ''),
                $meta['statut'] ?? '',
                $meta['workspace_name'] ?? '',
                $meta['responsable_nom'] ?? '',
                config('app.frontend_url').$row['url'],
            ],
            'activites' => [
                $row['id'] ?? '',
                strip_tags($row['label'] ?? ''),
                $meta['projet_nom'] ?? '',
                $meta['workspace_name'] ?? '',
                $meta['responsable_nom'] ?? '',
                config('app.frontend_url').$row['url'],
            ],
            'taches' => [
                $row['id'] ?? '',
                strip_tags($row['label'] ?? ''),
                $meta['statut'] ?? '',
                $meta['priorite'] ?? '',
                $meta['projet_nom'] ?? '',
                $meta['workspace_name'] ?? '',
                $meta['assignees_noms'] ?? '',
                config('app.frontend_url').$row['url'],
            ],
            'documents' => [
                $row['id'] ?? '',
                strip_tags($row['label'] ?? ''),
                $meta['mime_type'] ?? '',
                $meta['workspace_name'] ?? '',
                $meta['uploader_nom'] ?? '',
                config('app.frontend_url').$row['url'],
            ],
            'users' => [
                $row['id'] ?? '',
                strip_tags($row['label'] ?? ''),
                strip_tags($row['excerpt'] ?? ''),
                $meta['fonction'] ?? '',
                config('app.frontend_url').$row['url'],
            ],
            'messages' => [
                $row['id'] ?? '',
                strip_tags($row['label'] ?? ''),
                $meta['user_nom'] ?? '',
                strip_tags($row['excerpt'] ?? ''),
                $meta['workspace_name'] ?? '',
                config('app.frontend_url').$row['url'],
            ],
            default => [
                $row['id'] ?? '',
                strip_tags($row['label'] ?? ''),
                strip_tags($row['excerpt'] ?? ''),
                config('app.frontend_url').$row['url'],
            ],
        };
    }

    public function collection(): Collection
    {
        return $this->results;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // En-tête : fond bleu marine, texte blanc, gras, centré
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            ],
        ];
    }
}
