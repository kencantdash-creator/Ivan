<?php
$title = 'Residents';
require 'includes/header.php';
$brgys = $pdo->query('SELECT * FROM barangays ORDER BY name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $pdo->prepare('DELETE FROM subscribers WHERE id = ?')->execute([(int)$_POST['delete']]);
        flash('Resident removed.'); header('Location: subscribers.php'); exit;
    }
    $name = trim($_POST['name'] ?? '');
    $phone = preg_replace('/[\s-]/', '', $_POST['phone'] ?? '');
    $bid = (int)($_POST['barangay_id'] ?? 0);
    if ($name === '' || mb_strlen($name) > 100) $errors[] = 'Enter the resident name.';
    if (!preg_match('/^(09\d{9}|\+639\d{9})$/', $phone)) $errors[] = 'Enter a valid mobile number, like 09171234567.';
    if (!$bid) $errors[] = 'Choose a barangay.';
    if (!$errors) {
        $pdo->prepare('INSERT INTO subscribers (name, phone, barangay_id, created_at) VALUES (?,?,?,NOW())')->execute([$name, $phone, $bid]);
        flash('Resident registered for SMS alerts.'); header('Location: subscribers.php'); exit;
    }
}

$list = $pdo->query('SELECT s.*, b.name AS brgy FROM subscribers s JOIN barangays b ON b.id = s.barangay_id ORDER BY s.id DESC')->fetchAll();
$input = 'w-full border border-slate-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-600 focus:border-sky-600';
?>
<h1 class="text-2xl font-semibold mb-1">Residents</h1>
<p class="text-sm text-slate-500 mb-4">Register mobile numbers to receive SMS flood alerts for their barangay.</p>

<?php if ($errors): ?>
  <div class="mb-4 bg-red-50 border-l-4 border-red-700 rounded p-3 text-sm text-red-800">
    <p class="font-semibold mb-1"><i class="fa-solid fa-circle-xmark mr-1"></i>Fix these and try again:</p>
    <ul class="list-disc ml-6"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<div class="grid lg:grid-cols-3 gap-6">
  <form method="post" class="bg-white border border-slate-200 rounded p-4 space-y-4 h-fit">
    <div><label class="block text-sm font-medium mb-1" for="name">Full name</label>
      <input id="name" name="name" class="<?= $input ?>" value="<?= e($_POST['name'] ?? '') ?>" required></div>
    <div><label class="block text-sm font-medium mb-1" for="phone">Mobile number</label>
      <input id="phone" name="phone" inputmode="tel" placeholder="09171234567" class="<?= $input ?>" value="<?= e($_POST['phone'] ?? '') ?>" required></div>
    <div><label class="block text-sm font-medium mb-1" for="barangay_id">Barangay</label>
      <select id="barangay_id" name="barangay_id" class="<?= $input ?>" required>
        <option value="">Select barangay</option>
        <?php foreach ($brgys as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
      </select></div>
    <button class="w-full bg-sky-700 hover:bg-sky-800 text-white text-sm font-medium rounded px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-700">
      <i class="fa-solid fa-user-plus mr-2"></i>Register resident
    </button>
  </form>

  <section class="lg:col-span-2 bg-white border border-slate-200 rounded">
    <h2 class="px-4 py-3 border-b border-slate-200 font-semibold flex items-center gap-2"><i class="fa-solid fa-address-book text-sky-700"></i>Registered residents (<?= count($list) ?>)</h2>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left"><tr><th class="px-4 py-2 font-medium">Name</th><th class="px-4 py-2 font-medium">Mobile</th><th class="px-4 py-2 font-medium">Barangay</th><th class="px-4 py-2"></th></tr></thead>
        <tbody class="divide-y divide-slate-100">
          <?php if (!$list): ?><tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">No residents registered. Use the form to add the first one.</td></tr><?php endif; ?>
          <?php foreach ($list as $s): ?>
            <tr>
              <td class="px-4 py-3 font-medium"><?= e($s['name']) ?></td>
              <td class="px-4 py-3 whitespace-nowrap"><?= e($s['phone']) ?></td>
              <td class="px-4 py-3"><?= e($s['brgy']) ?></td>
              <td class="px-4 py-3 text-right">
                <form method="post" onsubmit="return confirm('Remove this resident?')">
                  <button name="delete" value="<?= $s['id'] ?>" class="text-red-700 hover:text-red-900 p-1" aria-label="Remove <?= e($s['name']) ?>"><i class="fa-solid fa-trash"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
<?php require 'includes/footer.php'; ?>
