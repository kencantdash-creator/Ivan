<?php
ob_start(); // lets pages redirect after the header is included
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/model.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$page = basename($_SERVER['PHP_SELF']);
$nav = [
    'index.php'       => ['Dashboard', 'fa-gauge-high'],
    'readings.php'    => ['Sensor readings', 'fa-droplet'],
    'alerts.php'      => ['Alerts', 'fa-bell'],
    'subscribers.php' => ['Residents', 'fa-users'],
];
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'FloodWatch') ?> | FloodWatch Basey</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>body{font-family:'IBM Plex Sans',system-ui,sans-serif}</style>
<?php if (!empty($refresh)): ?><meta http-equiv="refresh" content="<?= (int)$refresh ?>"><?php endif; ?>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen">
<header class="bg-slate-900 text-white">
  <div class="max-w-6xl mx-auto px-4 h-14 flex items-center justify-between">
    <a href="index.php" class="flex items-center gap-2 font-semibold">
      <i class="fa-solid fa-water text-sky-400"></i> FloodWatch <span class="text-slate-400 font-normal hidden sm:inline">LGU Basey</span>
    </a>
    <button id="menuBtn" class="md:hidden p-2 rounded hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400" aria-label="Toggle menu">
      <i class="fa-solid fa-bars"></i>
    </button>
    <nav class="hidden md:flex gap-1">
      <?php foreach ($nav as $file => [$label, $icon]): ?>
        <a href="<?= $file ?>" class="px-3 py-2 rounded text-sm flex items-center gap-2 <?= $page === $file ? 'bg-sky-700 text-white' : 'text-slate-300 hover:bg-slate-800' ?>">
          <i class="fa-solid <?= $icon ?>"></i><?= $label ?>
        </a>
      <?php endforeach; ?>
    </nav>
  </div>
  <nav id="mobileNav" class="hidden md:hidden border-t border-slate-800 px-4 py-2">
    <?php foreach ($nav as $file => [$label, $icon]): ?>
      <a href="<?= $file ?>" class="flex items-center gap-3 px-2 py-3 text-sm <?= $page === $file ? 'text-sky-400' : 'text-slate-300' ?>">
        <i class="fa-solid <?= $icon ?> w-5 text-center"></i><?= $label ?>
      </a>
    <?php endforeach; ?>
  </nav>
</header>
<main class="max-w-6xl mx-auto px-4 py-6">
<?php if ($flash): ?>
  <div class="mb-4 flex items-start gap-3 bg-white border-l-4 border-sky-700 rounded p-3 text-sm">
    <i class="fa-solid fa-circle-check text-sky-700 mt-0.5"></i><span><?= e($flash) ?></span>
  </div>
<?php endif; ?>
