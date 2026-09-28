<?php
$pesanan = new Pesanan($conn);
$error = "";
$post  = $_SERVER['REQUEST_METHOD'] == 'POST';
$url   = 'index.php?page=order';

try {
    if ($post && $action == 'tambah') {
        $p = $_POST;
        $pesanan->buat($p['namapelanggan'], $p['jeniskelamin'], $p['nohp'], $p['alamat'], $_SESSION['iduser'], (int)$p['idmeja'], (int)$p['idmenu'], (int)$p['jumlah']);
        redirect($url);
    }
    if ($post && $action == 'update') {
        $p = $_POST;
        $pesanan->ubah((int)$p['idpesanan'], $p['namapelanggan'], $p['jeniskelamin'], $p['nohp'], $p['alamat'], (int)$p['idmeja'], (int)$p['idmenu'], (int)$p['jumlah']);
        redirect($url);
    }
    if ($action == 'batal') {
        $pesanan->batal((int)$_GET['id']);
        redirect($url);
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$edit = null;
if (in_array($action, ['edit', 'update'])) {
    $edit = $pesanan->cari((int)($_GET['id'] ?? $_POST['idpesanan'] ?? 0));
    if ($edit && $edit['status_pesanan'] != 'belum_bayar') $edit = null;
}
?>
<div class="card">
    <h3><?= $edit ? 'Edit Pesanan #' . e($edit['idpesanan']) : 'Entri Order / Pesanan' ?></h3>
    <?php if ($error): ?><div class="alert alert-danger">Gagal: <?= e($error); ?></div><?php endif; ?>
    <form method="POST" action="<?= $url ?>&action=<?= $edit ? 'update' : 'tambah' ?>" class="grid-2">
        <?php if ($edit): ?><input type="hidden" name="idpesanan" value="<?= e($edit['idpesanan']) ?>"><?php endif; ?>
        <div class="form-group"><input type="text" name="namapelanggan" placeholder="Nama Pelanggan" value="<?= e($edit['namapelanggan'] ?? '') ?>" required></div>
        <div class="form-group">
            <select name="jeniskelamin">
                <option value="L" <?= ($edit['jeniskelamin'] ?? '') == 'L' ? 'selected' : '' ?>>Laki-laki</option>
                <option value="P" <?= ($edit['jeniskelamin'] ?? '') == 'P' ? 'selected' : '' ?>>Perempuan</option>
            </select>
        </div>
        <div class="form-group"><input type="text" name="nohp" placeholder="No HP" value="<?= e($edit['nohp'] ?? '') ?>" required></div>
        <div class="form-group"><textarea name="alamat" placeholder="Alamat" required style="height: 40px; resize: none;"><?= e($edit['alamat'] ?? '') ?></textarea></div>
        <div class="form-group">
            <select name="idmeja" required>
                <option value="">Pilih Meja Tersedia</option>
                <?php foreach ((new Meja($conn))->semua() as $m):
                    if ($m['status'] == 'tersedia' || ($edit && $m['idmeja'] == $edit['idmeja'])): ?>
                    <option value="<?= $m['idmeja']; ?>" <?= ($edit && $m['idmeja'] == $edit['idmeja']) ? 'selected' : '' ?>>Meja <?= e($m['nomormeja']); ?> (Kapasitas: <?= e($m['kapasitas']); ?>)</option>
                <?php endif; endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <select name="idmenu" required>
                <option value="">Pilih Menu</option>
                <?php foreach ((new Menu($conn))->semua() as $mn):
                    if ($mn['status_menu'] == 'tersedia' || ($edit && $mn['idmenu'] == $edit['idmenu'])): ?>
                    <option value="<?= $mn['idmenu']; ?>" <?= ($edit && $mn['idmenu'] == $edit['idmenu']) ? 'selected' : '' ?>><?= e($mn['namamenu']); ?> - Rp <?= number_format($mn['harga']); ?></option>
                <?php endif; endforeach; ?>
            </select>
        </div>
        <div class="form-group"><input type="number" name="jumlah" placeholder="Jumlah Porsi" min="1" value="<?= e($edit['jumlah'] ?? '') ?>" required></div>
        <?php if ($edit): ?>
            <div class="form-group"><button type="submit" class="btn btn-primary">Update Pesanan</button></div>
            <div class="form-group"><a href="<?= $url ?>" class="btn btn-secondary">Batal Edit</a></div>
        <?php else: ?>
            <div class="form-group"><button type="submit" class="btn btn-primary">Buat Pesanan</button></div>
        <?php endif; ?>
    </form>
</div>
<?php
tabel(['ID Pesanan' => 'idpesanan', 'Pelanggan' => 'namapelanggan', 'Meja' => 'nomormeja', 'Menu' => 'namamenu', 'Jumlah' => 'jumlah', 'Status' => 'status_pesanan'],
      $pesanan->semua(), [],
      fn($r) => $r['status_pesanan'] == 'belum_bayar'
          ? "<div class=\"btn-aksi-container\">
                <a href=\"$url&action=edit&id={$r['idpesanan']}\" class=\"btn btn-primary btn-aksi\">Edit</a>
                <a href=\"$url&action=batal&id={$r['idpesanan']}\" class=\"btn btn-danger btn-aksi\" onclick=\"return confirm('Yakin ingin membatalkan pesanan ini?')\">Batalkan</a>
             </div>"
          : '<span style="color: gray; font-weight: bold;">Selesai</span>');