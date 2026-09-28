<?php
$link = ['meja' => 'Entri Meja', 'barang' => 'Entri Barang', 'order' => 'Entri Pesanan',
         'transaksi' => 'Entri Transaksi Pembayaran', 'laporan' => 'Generate Laporan'];
?>
<div class="alert alert-info">Selamat datang <strong><?= e($_SESSION['namauser']); ?></strong>!</div>
<div class="list-group">
    <?php foreach ($link as $p => $label): if (in_array($role, $akses[$p])): ?>
        <a href="index.php?page=<?= $p ?>" class="list-group-item"><?= $label ?></a>
    <?php endif; endforeach; ?>
</div>