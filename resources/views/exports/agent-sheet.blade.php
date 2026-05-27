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
    .score-grid { display: table; width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .score-row { display: table-row; }
    .score-cell { display: table-cell; padding: 6px 8px; border: 1px solid #e5e7eb; font-size: 10px; vertical-align: middle; }
    .score-cell.label { background: #f9fafb; font-weight: bold; width: 35%; }
    .score-cell.value { text-align: right; width: 15%; font-weight: bold; color: #1d4ed8; }
    .score-cell.bar-cell { width: 50%; padding: 6px 8px; }
    .bar-bg { background: #e5e7eb; height: 8px; border-radius: 4px; overflow: hidden; }
    .bar-fill { height: 8px; border-radius: 4px; background: #1d4ed8; }
    table { width: 100%; border-collapse: collapse; font-size: 10px; }
    th { background: #f3f4f6; text-align: left; padding: 6px 8px; border: 1px solid #d1d5db; font-weight: bold; font-size: 9px; text-transform: uppercase; }
    td { padding: 5px 8px; border: 1px solid #e5e7eb; vertical-align: top; }
    tr:nth-child(even) td { background: #f9fafb; }
    .badge { display: inline-block; padding: 1px 6px; border-radius: 10px; font-size: 9px; font-weight: bold; }
    .badge-blue { background: #dbeafe; color: #1d4ed8; }
    .badge-green { background: #d1fae5; color: #065f46; }
    .badge-red { background: #fee2e2; color: #991b1b; }
    .badge-amber { background: #fef3c7; color: #92400e; }
    .total-score { text-align: center; padding: 16px; background: #eff6ff; border: 2px solid #1d4ed8; border-radius: 6px; margin-bottom: 18px; margin: 0 24px 18px; }
    .total-score .score-num { font-size: 36px; font-weight: bold; color: #1d4ed8; }
    .total-score .score-label { font-size: 11px; color: #6b7280; margin-top: 2px; }
    .footer { margin-top: 24px; padding: 10px 24px; font-size: 9px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
</style>
</head>
<body>

<div class="header">
    <h1>Fiche d'évaluation — {{ $user['nom'] ?? $user['nom_complet'] ?? '—' }}</h1>
    <div class="meta">
        Période : {{ \Carbon\Carbon::parse($start)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($end)->format('d/m/Y') }}
        &nbsp;|&nbsp; Généré le : {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

{{-- Score global --}}
<div class="total-score">
    <div class="score-num">{{ number_format($sheet['score_global'] ?? 0, 1) }}</div>
    <div class="score-label">Score global / 100</div>
</div>

{{-- Critères --}}
<div class="section" style="margin-top:18px">
    <div class="section-title">Détail par critère</div>
    <div class="score-grid">
        @foreach($criteria as $key => $label)
            @php
                $criterion = $sheet['criteria'][$key] ?? [];
                $raw = is_array($criterion) ? (float)($criterion['raw'] ?? 0) : (float)$criterion;
                $pct = round($raw * 100, 1);
            @endphp
            <div class="score-row">
                <div class="score-cell label">{{ $label }}</div>
                <div class="score-cell value">{{ number_format($pct, 1) }}</div>
                <div class="score-cell bar-cell">
                    <div class="bar-bg"><div class="bar-fill" style="width:{{ min(100, $pct) }}%"></div></div>
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- Tâches dirigées --}}
@if(!empty($sheet['directed_tasks']))
<div class="section">
    <div class="section-title">Tâches en tant que responsable</div>
    <table>
        <thead>
            <tr>
                <th>Titre</th>
                <th>Statut</th>
                <th>Priorité</th>
                <th>Avancement</th>
                <th>Échéance</th>
            </tr>
        </thead>
        <tbody>
            @foreach(array_slice($sheet['directed_tasks'], 0, 30) as $t)
            <tr>
                <td>{{ $t['titre'] ?? '—' }}</td>
                <td><span class="badge badge-blue">{{ $t['statut'] ?? '—' }}</span></td>
                <td>{{ $t['priorite'] ?? '—' }}</td>
                <td>{{ $t['taux_realisation'] ?? 0 }}%</td>
                <td>{{ isset($t['echeance']) ? \Carbon\Carbon::parse($t['echeance'])->format('d/m/Y') : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Tâches assignées --}}
@if(!empty($sheet['assignee_tasks']))
<div class="section">
    <div class="section-title">Tâches en tant qu'intervenant</div>
    <table>
        <thead>
            <tr>
                <th>Titre</th>
                <th>Statut</th>
                <th>Avancement</th>
                <th>Échéance</th>
            </tr>
        </thead>
        <tbody>
            @foreach(array_slice($sheet['assignee_tasks'], 0, 30) as $t)
            <tr>
                <td>{{ $t['titre'] ?? '—' }}</td>
                <td><span class="badge badge-blue">{{ $t['statut'] ?? '—' }}</span></td>
                <td>{{ $t['taux_realisation'] ?? 0 }}%</td>
                <td>{{ isset($t['echeance']) ? \Carbon\Carbon::parse($t['echeance'])->format('d/m/Y') : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Alertes --}}
@if(!empty($sheet['unjustified_alert']) || !empty($sheet['escalades_abusives']))
<div class="section">
    <div class="section-title">Alertes</div>
    @if(!empty($sheet['unjustified_alert']))
        <p style="color:#991b1b;font-size:10px;margin-bottom:4px">⚠ Retours injustifiés détectés sur la période.</p>
    @endif
    @if(!empty($sheet['escalades_abusives']))
        <p style="color:#991b1b;font-size:10px">⚠ Escalades abusives signalées.</p>
    @endif
</div>
@endif

<div class="footer">
    Work Tracking — Export confidentiel — {{ config('app.name') }}
</div>

</body>
</html>
