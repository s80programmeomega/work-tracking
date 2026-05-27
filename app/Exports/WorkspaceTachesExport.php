<?php

declare(strict_types=1);

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkspaceTachesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(private readonly Collection $taches) {}

    public function title(): string
    {
        return 'Tâches';
    }

    public function headings(): array
    {
        return [
            'Projet',
            'Activité',
            'Titre',
            'Statut',
            'Priorité',
            'Responsable',
            'Intervenants',
            'Avancement (%)',
            'Échéance',
            'Sous-tâches',
        ];
    }

    public function map($tache): array
    {
        return [
            $tache->activite?->projet?->nom ?? '—',
            $tache->activite?->nom ?? '—',
            $tache->titre,
            $this->statutLabel($tache->statut),
            $this->prioriteLabel($tache->priorite),
            $tache->responsable?->nom ?? '—',
            $tache->assignees->map(fn ($u) => $u->nom)->implode(', ') ?: '—',
            $tache->taux_realisation ?? 0,
            $tache->echeance ? Carbon::parse($tache->echeance)->format('d/m/Y') : '—',
            $tache->sous_taches_count ?? 0,
        ];
    }

    public function collection(): Collection
    {
        return $this->taches;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    private function statutLabel(string $statut): string
    {
        return match ($statut) {
            'a_faire' => 'À faire',
            'en_cours' => 'En cours',
            'termine' => 'Terminé',
            'en_retard' => 'En retard',
            'a_refaire' => 'À refaire',
            default => $statut,
        };
    }

    private function prioriteLabel(?string $priorite): string
    {
        return match ($priorite) {
            'faible' => 'Faible',
            'moyenne' => 'Moyenne',
            'elevee' => 'Élevée',
            'critique' => 'Critique',
            default => $priorite ?? '—',
        };
    }
}
