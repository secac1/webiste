<?php
ob_start();
session_start();
require_once 'koneksi.php';
require_once 'model.php';

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect($url) { header("Location: $url"); exit; }

function tabel($kolom, $data, $rp = [], $aksi = null, $kosong = '') {
    echo '<table><thead><tr>';
    foreach ($kolom as $h => $k) echo "<th>$h</th>";
    echo ($aksi ? '<th>Aksi</th>' : '') . '</tr></thead><tbody>';
    if (!$data && $kosong) echo '<tr><td colspan="' . count($kolom) . '" style="text-align:center;">' . $kosong . '</td></tr>';
    foreach ($data as $r) {
        echo '<tr>';
        foreach ($kolom as $k) echo '<td>' . (in_array($k, $rp) ? 'Rp ' . number_format($r[$k]) : e($r[$k])) . '</td>';
        echo ($aksi ? '<td>' . $aksi($r) . '</td>' : '') . '</tr>';
    }
    echo '</tbody></table>';
}

$conn = (new Koneksi())->conn;

$akses = [
    'dashboard' => ['admin', 'waiter', 'kasir', 'owner'],
    'meja'      => ['admin'],
    'barang'    => ['admin', 'waiter'],
    'order'     => ['waiter'],
    'transaksi' => ['kasir'],
    'laporan'   => ['waiter', 'kasir', 'owner'],
];

$page   = $_GET['page'] ?? 'login';
$action = $_GET['action'] ?? '';

if ($page == 'logout') { Auth::logout(); redirect('index.php'); }

if ($page == 'login') {
    if (Auth::check()) redirect('index.php?page=dashboard');
    require 'page_login.php';
    exit;
}

if (!Auth::check()) redirect('index.php');

$role = $_SESSION['role'];
if (!isset($akses[$page]) || !in_array($role, $akses[$page])) redirect('index.php?page=dashboard');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Kasir Restoran</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="navbar">
    <div class="navbar-brand">Kasir Restoran</div>
    <div>
        <a href="index.php?page=dashboard" class="btn btn-secondary" style="width: auto;">Dashboard</a>
        <a href="index.php?page=logout" class="btn btn-danger" style="margin-left: 10px; width: auto;">Logout</a>
    </div>
</div>
<div class="container">
<h2><?= e(ucfirst($role)); ?> Restoran</h2>
<?php require "page_$page.php"; ?>
</div>
</body>
</html>