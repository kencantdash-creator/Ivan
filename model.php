<?php

const MODEL_BIAS = -7.0;
const MODEL_WEIGHTS = [
    'rainfall'   => 0.022, 
    'river'      => 0.90,
    'soil'       => 0.02,
    'tide'       => 0.80,
];

function predict_flood(float $rain, float $river, float $soil, float $tide): array
{
    $z = MODEL_BIAS
        + MODEL_WEIGHTS['rainfall'] * $rain
        + MODEL_WEIGHTS['river'] * $river
        + MODEL_WEIGHTS['soil'] * $soil
        + MODEL_WEIGHTS['tide'] * $tide;
    $p = 1 / (1 + exp(-$z));
    return ['probability' => round($p, 4), 'level' => risk_level($p)];
}

function risk_level(float $p): string
{
    if ($p >= 0.75) return 'Critical';
    if ($p >= 0.50) return 'High';
    if ($p >= 0.25) return 'Moderate';
    return 'Low';
}

function level_classes(string $level): string
{
    return match ($level) {
        'Critical' => 'bg-red-700 text-white',
        'High'     => 'bg-orange-600 text-white',
        'Moderate' => 'bg-amber-400 text-slate-900',
        'Low'      => 'bg-emerald-600 text-white',
        default    => 'bg-slate-200 text-slate-700',
    };
}

function level_bar(string $level): string
{
    return match ($level) {
        'Critical' => 'bg-red-700', 'High' => 'bg-orange-600',
        'Moderate' => 'bg-amber-400', default => 'bg-emerald-600',
    };
}

function level_advice(string $level, string $brgy): string
{
    return match ($level) {
        'Critical' => "CRITICAL flood warning for Brgy. $brgy. Evacuate to the nearest evacuation center now.",
        'High'     => "HIGH flood risk in Brgy. $brgy. Prepare go-bags and move valuables and livestock to higher ground.",
        'Moderate' => "Moderate flood risk in Brgy. $brgy. Stay alert and monitor official updates.",
        default    => "Brgy. $brgy is at low flood risk. No action needed.",
    };
}


function send_alert(PDO $pdo, int $brgyId, string $level, string $message, bool $sms, bool $app, bool $siren, string $source): int
{
    $q = $pdo->prepare('SELECT COUNT(*) FROM subscribers WHERE barangay_id = ?');
    $q->execute([$brgyId]);
    $recipients = $sms ? (int)$q->fetchColumn() : 0;


    $pdo->prepare('INSERT INTO alerts (barangay_id, level, message, via_sms, via_app, via_siren, recipients, source, created_at)
                   VALUES (?,?,?,?,?,?,?,?,NOW())')
        ->execute([$brgyId, $level, $message, (int)$sms, (int)$app, (int)$siren, $recipients, $source]);
    return $recipients;
}

function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function flash(string $msg): void { if (session_status() === PHP_SESSION_NONE) session_start(); $_SESSION['flash'] = $msg; }
