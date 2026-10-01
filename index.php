<?php
$title = 'Dashboard'; $refresh = 30;
require 'includes/header.php';

$rows = $pdo->query('SELECT b.id, b.name, r.rainfall_mm, r.river_level_m, r.soil_saturation, r.tide_level_m,
        r.probability, r.risk_level, r.created_at
    FROM barangays b
    LEFT JOIN readings r ON r.id = (SELECT MAX(id) FROM readings WHERE barangay_id = b.id)
    ORDER BY r.probability DESC, b.name')->fetchAll();

$counts = ['Critical' => 0, 'High' => 0, 'Moderate' => 0, 'Low' => 0];
foreach ($rows as $r) if ($r['risk_level']) $counts[$r['risk_level']]++;
$subs = (int)$pdo->query('SELECT COUNT(*) FROM subscribers')->fetchColumn();
$recent = $pdo->query('SELECT a.*, b.name FROM alerts a JOIN barangays b ON b.id = a.barangay_id ORDER BY a.id DESC LIMIT 5')->fetchAll();
$cards = [
    ['Critical', $counts['Critical'], 'fa-triangle-exclamation', 'text-red-700'],
    ['High', $counts['High'], 'fa-circle-exclamation', 'text-orange-600'],
    ['Moderate', $counts['Moderate'], 'fa-circle-info', 'text-amber-500'],
    ['Low', $counts['Low'], 'fa-shield-halved', 'text-emerald-600'],
];
?>
<div class="flex flex-wrap items-end justify-between gap-2 mb-4">
  <div>
    <h1 class="text-2xl font-semibold">Flood risk monitor</h1>
    <p class="text-sm text-slate-500">Latest AI prediction per barangay. Page refreshes every 30 seconds.</p>
  </div>
  <p class="text-sm text-slate-500"><i class="fa-regular fa-clock mr-1"></i><?= date('M j, Y g:i A') ?></p>
</div>

<section class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
  <?php foreach ($cards as [$label, $n, $icon, $color]): ?>
    <div class="bg-white border border-slate-200 rounded p-4 flex items-center gap-3">
      <i class="fa-solid <?= $icon ?> text-2xl <?= $color ?>"></i>
      <div><p class="text-2xl font-semibold leading-none"><?= $n ?></p><p class="text-sm text-slate-500 mt-1"><?= $label ?> risk</p></div>
    </div>
  <?php endforeach; ?>
</section>

<div class="grid lg:grid-cols-3 gap-6">
  <section class="lg:col-span-2 bg-white border border-slate-200 rounded">
    <h2 class="px-4 py-3 border-b border-slate-200 font-semibold flex items-center gap-2"><i class="fa-solid fa-map-location-dot text-sky-700"></i>Barangay status</h2>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
          <tr><th class="px-4 py-2 font-medium">Barangay</th><th class="px-4 py-2 font-medium">Risk</th>
              <th class="px-4 py-2 font-medium">Probability</th><th class="px-4 py-2 font-medium">Rain</th>
              <th class="px-4 py-2 font-medium">River</th><th class="px-4 py-2 font-medium">Updated</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        <?php foreach ($rows as $r): $pct = round(($r['probability'] ?? 0) * 100); ?>
          <tr>
            <td class="px-4 py-3 font-medium whitespace-nowrap"><?= e($r['name']) ?></td>
            <?php if ($r['risk_level']): ?>
              <td class="px-4 py-3"><span class="px-2 py-1 rounded text-xs font-semibold <?= level_classes($r['risk_level']) ?>"><?= $r['risk_level'] ?></span></td>
              <td class="px-4 py-3 min-w-[120px]">
                <div class="flex items-center gap-2"><div class="flex-1 h-2 bg-slate-200 rounded"><div class="h-2 rounded <?= level_bar($r['risk_level']) ?>" style="width:<?= $pct ?>%"></div></div><span class="w-10 text-right"><?= $pct ?>%</span></div>
              </td>
              <td class="px-4 py-3 whitespace-nowrap"><?= $r['rainfall_mm'] ?> mm</td>
              <td class="px-4 py-3 whitespace-nowrap"><?= $r['river_level_m'] ?> m</td>
              <td class="px-4 py-3 text-slate-500 whitespace-nowrap"><?= date('M j, g:i A', strtotime($r['created_at'])) ?></td>
            <?php else: ?>
              <td colspan="5" class="px-4 py-3 text-slate-400">No reading yet. <a class="text-sky-700 underline" href="readings.php">Add a reading</a></td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <aside class="space-y-6">
    <section class="bg-white border border-slate-200 rounded">
      <h2 class="px-4 py-3 border-b border-slate-200 font-semibold flex items-center gap-2"><i class="fa-solid fa-bell text-sky-700"></i>Recent alerts</h2>
      <?php if (!$recent): ?>
        <p class="p-4 text-sm text-slate-500">No alerts sent yet. Alerts are created automatically when a reading is High or Critical.</p>
      <?php endif; ?>
      <ul class="divide-y divide-slate-100">
        <?php foreach ($recent as $a): ?>
          <li class="p-4 text-sm">
            <div class="flex items-center justify-between mb-1">
              <span class="px-2 py-0.5 rounded text-xs font-semibold <?= level_classes($a['level']) ?>"><?= $a['level'] ?></span>
              <span class="text-xs text-slate-500"><?= date('M j, g:i A', strtotime($a['created_at'])) ?></span>
            </div>
            <p><?= e($a['message']) ?></p>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
    <section class="bg-white border border-slate-200 rounded p-4 text-sm">
      <p class="font-semibold flex items-center gap-2 mb-1"><i class="fa-solid fa-mobile-screen text-sky-700"></i>Registered residents</p>
      <p class="text-slate-600"><?= $subs ?> resident<?= $subs === 1 ? '' : 's' ?> will receive SMS alerts.</p>
    </section>
  </aside>
</div>
<?php require 'includes/footer.php'; ?>
