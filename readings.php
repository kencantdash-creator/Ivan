<?php
$title = 'Sensor readings';
require 'includes/header.php';

$brgys = $pdo->query('SELECT * FROM barangays ORDER BY name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bid   = (int)($_POST['barangay_id'] ?? 0);
    $rain  = filter_var($_POST['rainfall'] ?? '', FILTER_VALIDATE_FLOAT);
    $river = filter_var($_POST['river'] ?? '', FILTER_VALIDATE_FLOAT);
    $soil  = filter_var($_POST['soil'] ?? '', FILTER_VALIDATE_INT);
    $tide  = filter_var($_POST['tide'] ?? '', FILTER_VALIDATE_FLOAT);

    $stmt = $pdo->prepare('SELECT name FROM barangays WHERE id = ?'); $stmt->execute([$bid]);
    $brgy = $stmt->fetchColumn();

    if (!$brgy) $errors[] = 'Choose a barangay.';
    if ($rain === false || $rain < 0 || $rain > 999) $errors[] = 'Rainfall must be 0 to 999 mm.';
    if ($river === false || $river < 0 || $river > 20) $errors[] = 'River level must be 0 to 20 m.';
    if ($soil === false || $soil < 0 || $soil > 100) $errors[] = 'Soil saturation must be 0 to 100%.';
    if ($tide === false || $tide < 0 || $tide > 9) $errors[] = 'Tide level must be 0 to 9 m.';

    if (!$errors) {
        $res = predict_flood($rain, $river, $soil, $tide);
        $pdo->prepare('INSERT INTO readings (barangay_id, rainfall_mm, river_level_m, soil_saturation, tide_level_m, probability, risk_level, created_at)
                       VALUES (?,?,?,?,?,?,?,NOW())')
            ->execute([$bid, $rain, $river, $soil, $tide, $res['probability'], $res['level']]);

        $msg = 'Reading saved. Prediction: ' . $res['level'] . ' (' . round($res['probability'] * 100) . '%).';
        if (in_array($res['level'], ['High', 'Critical'], true)) {
            $n = send_alert($pdo, $bid, $res['level'], level_advice($res['level'], $brgy), true, true, $res['level'] === 'Critical', 'auto');
            $msg .= " Alert sent automatically to $n resident" . ($n === 1 ? '' : 's') . '.';
        }
        flash($msg);
        header('Location: readings.php'); exit;
    }
}

$history = $pdo->query('SELECT r.*, b.name FROM readings r JOIN barangays b ON b.id = r.barangay_id ORDER BY r.id DESC LIMIT 15')->fetchAll();
$input = 'w-full border border-slate-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-600 focus:border-sky-600';
?>
<h1 class="text-2xl font-semibold mb-1">Sensor readings</h1>
<p class="text-sm text-slate-500 mb-4">Enter data from the rain gauge and river sensor. The AI model predicts flood probability and sends alerts when risk is High or Critical.</p>

<?php if ($errors): ?>
  <div class="mb-4 bg-red-50 border-l-4 border-red-700 rounded p-3 text-sm text-red-800">
    <p class="font-semibold mb-1"><i class="fa-solid fa-circle-xmark mr-1"></i>Fix these and try again:</p>
    <ul class="list-disc ml-6"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<div class="grid lg:grid-cols-3 gap-6">
  <form method="post" class="bg-white border border-slate-200 rounded p-4 space-y-4 h-fit">
    <div>
      <label class="block text-sm font-medium mb-1" for="barangay_id">Barangay</label>
      <select id="barangay_id" name="barangay_id" class="<?= $input ?>" required>
        <option value="">Select barangay</option>
        <?php foreach ($brgys as $b): ?><option value="<?= $b['id'] ?>" <?= ($_POST['barangay_id'] ?? '') == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1" for="rainfall"><i class="fa-solid fa-cloud-rain text-sky-700 mr-1"></i>Rainfall, last 24 hours (mm)</label>
      <input id="rainfall" name="rainfall" type="number" step="0.1" min="0" max="999" class="<?= $input ?>" value="<?= e($_POST['rainfall'] ?? '') ?>" required>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1" for="river"><i class="fa-solid fa-water text-sky-700 mr-1"></i>River level (m)</label>
      <input id="river" name="river" type="number" step="0.01" min="0" max="20" class="<?= $input ?>" value="<?= e($_POST['river'] ?? '') ?>" required>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1" for="soil"><i class="fa-solid fa-seedling text-sky-700 mr-1"></i>Soil saturation (%)</label>
      <input id="soil" name="soil" type="number" step="1" min="0" max="100" class="<?= $input ?>" value="<?= e($_POST['soil'] ?? '') ?>" required>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1" for="tide"><i class="fa-solid fa-wave-square text-sky-700 mr-1"></i>Tide level (m)</label>
      <input id="tide" name="tide" type="number" step="0.01" min="0" max="9" class="<?= $input ?>" value="<?= e($_POST['tide'] ?? '') ?>" required>
    </div>
    <button class="w-full bg-sky-700 hover:bg-sky-800 text-white text-sm font-medium rounded px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-700">
      <i class="fa-solid fa-brain mr-2"></i>Run prediction
    </button>
  </form>

  <section class="lg:col-span-2 bg-white border border-slate-200 rounded">
    <h2 class="px-4 py-3 border-b border-slate-200 font-semibold flex items-center gap-2"><i class="fa-solid fa-clock-rotate-left text-sky-700"></i>Latest readings</h2>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left"><tr>
          <th class="px-4 py-2 font-medium">Barangay</th><th class="px-4 py-2 font-medium">Rain</th><th class="px-4 py-2 font-medium">River</th>
          <th class="px-4 py-2 font-medium">Soil</th><th class="px-4 py-2 font-medium">Tide</th><th class="px-4 py-2 font-medium">Result</th><th class="px-4 py-2 font-medium">Time</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
          <?php if (!$history): ?><tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">No readings yet. Submit the form to run the first prediction.</td></tr><?php endif; ?>
          <?php foreach ($history as $h): ?>
            <tr>
              <td class="px-4 py-3 font-medium whitespace-nowrap"><?= e($h['name']) ?></td>
              <td class="px-4 py-3 whitespace-nowrap"><?= $h['rainfall_mm'] ?> mm</td>
              <td class="px-4 py-3 whitespace-nowrap"><?= $h['river_level_m'] ?> m</td>
              <td class="px-4 py-3"><?= $h['soil_saturation'] ?>%</td>
              <td class="px-4 py-3 whitespace-nowrap"><?= $h['tide_level_m'] ?> m</td>
              <td class="px-4 py-3 whitespace-nowrap"><span class="px-2 py-1 rounded text-xs font-semibold <?= level_classes($h['risk_level']) ?>"><?= $h['risk_level'] ?> <?= round($h['probability'] * 100) ?>%</span></td>
              <td class="px-4 py-3 text-slate-500 whitespace-nowrap"><?= date('M j, g:i A', strtotime($h['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
<?php require 'includes/footer.php'; ?>
