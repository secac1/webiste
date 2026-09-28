<?php
$trx = new Transaksi($conn);
$error = "";

if ($action == 'bayar' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $trx->bayar((int)$_POST['idpesanan'], $_SESSION['iduser'], (int)$_POST['bayar']);
        redirect('index.php?page=transaksi');
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<div class="card">
    <h3>Entri Transaksi Pembayaran</h3>
    <?php if ($error): ?><div class="alert alert-danger">Gagal: <?= e($error); ?></div><?php endif; ?>
    <form method="POST" action="index.php?page=transaksi&action=bayar">
        <div class="form-group">
            <select name="idpesanan" required>
                <option value="">Pilih Pesanan (Belum Bayar)</option>
                <?php foreach ($trx->pesananBelumBayar() as $p): ?>
                    <option value="<?= $p['idpesanan']; ?>">
                        ID: <?= $p['idpesanan']; ?> - <?= e($p['namapelanggan']); ?> (Meja <?= e($p['nomormeja']); ?>) - Rp <?= number_format($p['total']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><input type="number" name="bayar" placeholder="Jumlah Uang Bayar" min="0" required></div>
        <div class="form-group"><button type="submit" class="btn btn-success">Proses Pembayaran</button></div>
    </form>
</div>
<?php
tabel(['ID' => 'idtransaksi', 'Pelanggan' => 'namapelanggan', 'Total' => 'total', 'Bayar' => 'bayar', 'Kembalian' => 'kembalian', 'Tanggal' => 'tanggal_transaksi'],
      $trx->laporan(), ['total', 'bayar', 'kembalian']);