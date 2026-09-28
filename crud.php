<?php
$pk  = $m->pk;
$st  = $m->st;
$url = "index.php?page=$pg";
$post = $_SERVER['REQUEST_METHOD'] == 'POST';
$d = array_intersect_key($_POST, $f);

if ($tulis && $post && $action == 'tambah') { $m->tambah($d); redirect($url); }
if ($tulis && $post && $action == 'update') { $m->ubah($_POST[$pk], $d + [$st => $_POST[$st]]); redirect($url); }
if ($tulis && $action == 'hapus') { try { $m->hapus((int)$_GET['id']); } catch (Throwable $e) {} redirect($url); }

$edit  = ($tulis && $action == 'edit') ? $m->cari((int)$_GET['id']) : null;
$kolom = ['ID' => $pk];
foreach ($f as $k => $v) $kolom[$v[0]] = $k;
$kolom['Status'] = $st;
?>
<div class="card">
    <h3><?= $edit ? "Edit $judul" : "Entri $judul" ?></h3>
    <?php if ($tulis): ?>
    <form method="POST" action="<?= $url ?>&action=<?= $edit ? 'update' : 'tambah' ?>" class="grid-2">
        <?php if ($edit): ?><input type="hidden" name="<?= $pk ?>" value="<?= e($edit[$pk]) ?>"><?php endif; ?>
        <?php foreach ($f as $k => [$label, $tipe, $min]): ?>
            <div class="form-group"><input type="<?= $tipe ?>" name="<?= $k ?>" placeholder="<?= $label ?>" value="<?= e($edit[$k] ?? '') ?>" <?= $min !== '' ? "min=\"$min\"" : '' ?> required></div>
        <?php endforeach; ?>
        <?php if ($edit): ?>
            <div class="form-group">
                <select name="<?= $st ?>">
                    <?php foreach ($opsi as $o): ?><option value="<?= $o ?>" <?= $edit[$st] == $o ? 'selected' : '' ?>><?= ucfirst($o) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><button type="submit" class="btn btn-primary">Update</button></div>
            <div class="form-group"><a href="<?= $url ?>" class="btn btn-secondary">Batal</a></div>
        <?php else: ?>
            <div class="form-group"><button type="submit" class="btn btn-success">Simpan <?= ucfirst($noun) ?></button></div>
        <?php endif; ?>
    </form>
    <?php else: ?>
        <p>Anda hanya dapat melihat daftar <?= $noun ?>.</p>
    <?php endif; ?>
</div>
<?php
tabel($kolom, $m->semua(), ['harga'], $tulis ? fn($r) => "<div class=\"btn-aksi-container\">
    <a href=\"$url&action=edit&id={$r[$pk]}\" class=\"btn btn-primary btn-aksi\">Edit</a>
    <a href=\"$url&action=hapus&id={$r[$pk]}\" class=\"btn btn-danger btn-aksi\" onclick=\"return confirm('Yakin ingin menghapus $noun ini?')\">Hapus</a>
</div>" : null);