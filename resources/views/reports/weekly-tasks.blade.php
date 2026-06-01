<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }
    .header { background: #1d4ed8; color: #fff; padding: 20px 24px; margin-bottom: 20px; }
    .header h1 { font-size: 18px; font-weight: bold; }
    .header .meta { font-size: 10px; margin-top: 4px; opacity: 0.85; }
    .section { margin: 0 24px 18px; }
    .section-title { font-size: 13px; font-weight: bold; color: #1d4ed8; border-bottom: 2px solid #1d4ed8; padding-bottom: 4px; margin-bottom: 10px; }
    .stats-grid { display: table; width: 100%; border-collapse: collapse; margin-bottom: 18px; }
    .stats-row { display: table-row; }
    .stats-cell { display: table-cell; width: 16.6%; padding: 10px 8px; border: 1px solid #e5e7eb; text-align: center; vertical-align: middle; }
    .stats-cell .num { font-size: 22px; font-weight: bold; color: #1d4ed8; }
    .stats-cell .lbl { font-size: 9px; color: #6b7280; margin-top: 2px; text-transform: uppercase; }
    table { width: 100%; border-collapse: collapse; font-size: 10px; }
    th { background: #f3f4f6; text-align: left; padding: 6px 8px; border: 1px solid #d1d5db; font-weight: bold; font-size: 9px; text-transform: uppercase; }
    td { padding: 5px 8px; border: 1px solid #e5e7eb; vertical-align: top; }
    tr:nth-child(even) td { background: #f9fafb; }
    .badge { display: inline-block; padding: 1px 6px; border-radius: 10px; font-size: 9px; font-weight: bold; }
    .badge-blue  { background: #dbeafe; color: #1d4ed8; }
    .badge-green { background: #d1fae5; color: #065f46; }
    .badge-red   { background: #fee2e2; color: #991b1b; }
    .badge-amber { background: #fef3c7; color: #92400e; }
    .activite-block { margin-bottom: 16px; }
    .activite-name { font-size: 11px; font-weight: bold; color: #374151; background: #f3f4f6; padding: 5px 8px; border-left: 3px solid #1d4ed8; margin-bottom: 4px; }
    .activite-projet { font-size: 9px; color: #9ca3af; }
    .footer { margin-top: 24px; padding: 10px 24px; font-size: 9px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
</style>
</head>
<body>

<div class="header">
    <h1>Rapport hebdomadaire — {{ $user['nom'] ?? '—' }}</h1>
    <div class="meta">
        Semaine {{ $report['week_number'] ?? '—' }} / {{ $report['year'] ?? '—' }}
        &nbsp;|&nbsp;
        Du {{ isset($report['week_dates']['start']) ? \Carbon\Carbon::parse($report['week_dates']['start'])->format('d/m/Y') : '—' }}
        au {{ isset($report['week_dates']['end']) ? \Carbon\Carbon::parse($report['week_dates']['end'])->format('d/m/Y') : '—' }}
        &nbsp;|&nbsp; Généré le : {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

{{-- Statistiques globales --}}
<div class="section">
    <div class="section-title">Synthèse</div>
    @php $stats = $report['statistics'] ?? []; @endphp
    <div class="stats-grid">
        <div class="stats-row">
            <div class="stats-cell"><div class="num">{{ $stats['total'] ?? 0 }}</div><div class="lbl">Total</div></div>
            <div class="stats-cell"><div class="num">{{ $stats['completed'] ?? 0 }}</div><div class="lbl">Terminées</div></div>
            <div class="stats-cell"><div class="num">{{ $stats['in_progress'] ?? 0 }}</div><div class="lbl">En cours</div></div>
            <div class="stats-cell"><div class="num">{{ $stats['pending'] ?? 0 }}</div><div class="lbl">À faire</div></div>
            <div class="stats-cell"><div class="num">{{ $stats['overdue'] ?? 0 }}</div><div class="lbl">En retard</div></div>
            <div class="stats-cell"><div class="num">{{ number_format($stats['completion_rate'] ?? 0, 0) }}%</div><div class="lbl">Avancement</div></div>
        </div>
    </div>
</div>

{{-- Tâches groupées par activité --}}
@if(!empty($report['tasks_by_activite']))
<div class="section">
    <div class="section-title">Tâches par activité</div>

    @foreach($report['tasks_by_activite'] as $group)
    @php $activite = $group['activite'] ?? []; $tasks = $group['tasks'] ?? []; @endphp
    <div class="activite-block">
        <div class="activite-name">
            {{ $activite['nom'] ?? '—' }}
            @if(!empty($activite['projet_nom']))
                <span class="activite-projet"> — {{ $activite['projet_nom'] }}</span>
            @endif
            &nbsp;&nbsp;<span style="font-size:9px;color:#6b7280">({{ $group['completed'] ?? 0 }}/{{ $group['count'] ?? 0 }} terminées)</span>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:35%">Titre</th>
                    <th style="width:12%">Statut</th>
                    <th style="width:10%">Priorité</th>
                    <th style="width:10%">Avancement</th>
                    <th style="width:13%">Échéance</th>
                    <th style="width:10%">Val. N1</th>
                    <th style="width:10%">Val. N2</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tasks as $t)
                @php
                    $statut = is_object($t) ? $t->statut?->value ?? $t->statut : ($t['statut'] ?? '');
                    $priorite = is_object($t) ? $t->priorite?->value ?? $t->priorite : ($t['priorite'] ?? '');
                    $taux = is_object($t) ? ($t->taux_realisation ?? 0) : ($t['taux_realisation'] ?? 0);
                    $echeance = is_object($t) ? ($t->echeance ?? null) : ($t['echeance'] ?? null);
                    $valN1 = is_object($t) ? ($t->validated_n1_at ?? null) : ($t['validated_n1_at'] ?? null);
                    $valN2 = is_object($t) ? ($t->validated_n2_at ?? null) : ($t['validated_n2_at'] ?? null);
                    $titre = is_object($t) ? ($t->titre ?? '—') : ($t['titre'] ?? '—');

                    $badgeStatut = match($statut) {
                        'termine'  => 'badge-green',
                        'en_cours' => 'badge-blue',
                        default    => 'badge-amber',
                    };
                    $labelStatut = match($statut) {
                        'a_faire'  => 'À faire',
                        'en_cours' => 'En cours',
                        'termine'  => 'Terminé',
                        default    => $statut,
                    };
                    $labelPriorite = match($priorite) {
                        'faible'   => 'Faible',
                        'moyenne'  => 'Moyenne',
                        'elevee'   => 'Élevée',
                        'critique' => 'Critique',
                        default    => $priorite,
                    };
                @endphp
                <tr>
                    <td>{{ $titre }}</td>
                    <td><span class="badge {{ $badgeStatut }}">{{ $labelStatut }}</span></td>
                    <td>{{ $labelPriorite }}</td>
                    <td>{{ $taux }}%</td>
                    <td>{{ $echeance ? \Carbon\Carbon::parse($echeance)->format('d/m/Y') : '—' }}</td>
                    <td>{{ $valN1 ? '✔' : '—' }}</td>
                    <td>{{ $valN2 ? '✔' : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
</div>
@else
<div class="section">
    <p style="color:#6b7280;font-size:10px">Aucune tâche trouvée pour cette semaine.</p>
</div>
@endif

<div class="footer">
    Work Tracking — Export confidentiel — {{ config('app.name') }} — Semaine {{ $report['week_number'] ?? '—' }}/{{ $report['year'] ?? '—' }}
</div>

</body>
</html>
