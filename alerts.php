<?php
$title = 'Alerts';
require 'includes/header.php';
$brgys = $pdo->query('SELECT * FROM barangays ORDER BY name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bid = (int)($_POST['barangay_id'] ?? 0);
    $level = $_POST['level'] ?? '';
    $msg = trim($_POST['message'] ?? '');
    $sms = isset($_POST['sms']); $app = isset($_POST['app']); $siren = isset($_POST['siren']);
    if (!$bid) $errors[] = 'Choose a barangay.';
    if (!in_array($level, ['Moderate', 'High', 'Critical'], true)) $errors[] = 'Choose an alert level.';
    if ($msg === '' || mb_strlen($msg) > 255) $errors[] = 'Message is required and must be 255 characters or less.';
    if (!$sms && !$app && !$siren) $errors[] = 'Select at least one channel.';
    if (!$errors) {
        $n = send_alert($pdo, $bid, $level, $msg, $sms, $app, $siren, 'manual');
        flash("Alert sent. $n resident" . ($n === 1 ? '' : 's') . ' notified by SMS.');
        header('Location: alerts.php'); exit;
    }
}

$log = $pdo->query('SELECT a.*, b.name FROM alerts a JOIN barangays b ON b.id = a.barangay_id ORDER BY a.id DESC LIMIT 30')->fetchAll();
$input = 'w-full border border-slate-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-600 focus:border-sky-600';
?>
<h1 class="text-2xl font-semibold mb-1">Alerts</h1>
<p class="text-sm text-slate-500 mb-4">Send a manual warning or review every alert the system has issued.</p>

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
        <?php foreach ($brgys as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1" for="level">Alert level</label>
      <select id="level" name="level" class="<?= $input ?>" required>
        <option>Moderate</option><option selected>High</option><option>Critical</option>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1" for="message">Message</label>
      <textarea id="message" name="message" rows="3" maxlength="255" class="<?= $input ?>" required></textarea>
    </div>
    <fieldset>
      <legend class="block text-sm font-medium mb-2">Send through</legend>
      <label class="flex items-center gap-2 text-sm mb-2"><input type="checkbox" name="sms" checked class="accent-sky-700"><i class="fa-solid fa-message text-sky-700 w-4"></i>SMS</label>
      <label class="flex items-center gap-2 text-sm mb-2"><input type="checkbox" name="app" checked class="accent-sky-700"><i class="fa-solid fa-mobile-screen text-sky-700 w-4"></i>App notification</label>
      <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="siren" class="accent-sky-700"><i class="fa-solid fa-volume-high text-sky-700 w-4"></i>Siren</label>
    </fieldset>
    <button class="w-full bg-red-700 hover:bg-red-800 text-white text-sm font-medium rounded px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-700">
      <i class="fa-solid fa-paper-plane mr-2"></i>Send alert
    </button>
  </form>

  <section class="lg:col-span-2 bg-white border border-slate-200 rounded">
    <h2 class="px-4 py-3 border-b border-slate-200 font-semibold flex items-center gap-2"><i class="fa-solid fa-list text-sky-700"></i>Alert log</h2>
    <?php if (!$log): ?><p class="p-6 text-center text-sm text-slate-500">No alerts yet.</p><?php endif; ?>
    <ul class="divide-y divide-slate-100">
      <?php foreach ($log as $a): ?>
        <li class="p-4 text-sm">
          <div class="flex flex-wrap items-center gap-2 mb-1">
            <span class="px-2 py-0.5 rounded text-xs font-semibold <?= level_classes($a['level']) ?>"><?= $a['level'] ?></span>
            <span class="font-medium">Brgy. <?= e($a['name']) ?></span>
            <span class="text-xs text-slate-500 ml-auto"><?= date('M j, Y g:i A', strtotime($a['created_at'])) ?></span>
          </div>
          <p class="mb-2"><?= e($a['message']) ?></p>
          <div class="flex flex-wrap gap-3 text-xs text-slate-600">
            <?php if ($a['via_sms']): ?><span><i class="fa-solid fa-message mr-1"></i>SMS to <?= $a['recipients'] ?></span><?php endif; ?>
            <?php if ($a['via_app']): ?><span><i class="fa-solid fa-mobile-screen mr-1"></i>App</span><?php endif; ?>
            <?php if ($a['via_siren']): ?><span><i class="fa-solid fa-volume-high mr-1"></i>Siren</span><?php endif; ?>
            <span><i class="fa-solid fa-<?= $a['source'] === 'auto' ? 'robot' : 'user' ?> mr-1"></i><?= $a['source'] === 'auto' ? 'Automatic' : 'Manual' ?></span>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
</div>
<?php require 'includes/footer.php'; ?>
