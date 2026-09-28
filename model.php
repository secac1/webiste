<?php
abstract class BaseModel {
    protected $conn;

    function __construct(mysqli $conn) { $this->conn = $conn; }

    protected function q($sql, ...$p) {
        $s = $this->conn->prepare($sql);
        if ($p) $s->bind_param(str_repeat('s', count($p)), ...$p);
        $s->execute();
        return $s;
    }

    protected function rows($sql, ...$p) {
        return $this->q($sql, ...$p)->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

abstract class Crud extends BaseModel {
    public $t, $pk, $st;

    function semua()       { return $this->rows("SELECT * FROM $this->t ORDER BY $this->pk"); }
    function tersedia()    { return $this->rows("SELECT * FROM $this->t WHERE $this->st = 'tersedia'"); }
    function cari($id)     { return $this->rows("SELECT * FROM $this->t WHERE $this->pk = ?", $id)[0] ?? null; }
    function hapus($id)    { $this->q("DELETE FROM $this->t WHERE $this->pk = ?", $id); }
    function tambah($d)    { $this->q("INSERT INTO $this->t SET " . $this->set($d), ...array_values($d)); }
    function ubah($id, $d) { $this->q("UPDATE $this->t SET " . $this->set($d) . " WHERE $this->pk = ?", ...array_merge(array_values($d), [$id])); }

    private function set($d) { return implode('=?, ', array_keys($d)) . '=?'; }
}

class Meja extends Crud { public $t = 'meja'; public $pk = 'idmeja'; public $st = 'status'; }
class Menu extends Crud { public $t = 'menu'; public $pk = 'idmenu'; public $st = 'status_menu'; }

class Auth extends BaseModel {
    function login($username, $password) {
        $user = $this->rows("SELECT * FROM users WHERE username = ?", $username)[0] ?? null;
        return ($user && password_verify($password, $user['password'])) ? $user : false;
    }
    static function check()  { return isset($_SESSION['role']); }
    static function logout() { session_destroy(); }
}

// Waiter: buat, lihat, edit, dan batalkan pesanan (hanya yang belum bayar).
// Status 'sudah_bayar' TIDAK pernah diubah di sini - itu wewenang kasir lewat class Transaksi.
class Pesanan extends BaseModel {
    function semua() {
        return $this->rows("SELECT p.idpesanan, pl.namapelanggan, m.nomormeja, mn.namamenu, d.jumlah, p.status_pesanan
                            FROM pesanan p
                            JOIN pelanggan pl ON p.idpelanggan = pl.idpelanggan
                            JOIN meja m ON p.idmeja = m.idmeja
                            LEFT JOIN detail_pesanan d ON d.idpesanan = p.idpesanan
                            LEFT JOIN menu mn ON d.idmenu = mn.idmenu
                            ORDER BY p.idpesanan DESC");
    }

    function cari($id) {
        return $this->rows("SELECT p.idpesanan, p.idmeja, p.status_pesanan,
                                   pl.namapelanggan, pl.jeniskelamin, pl.nohp, pl.alamat,
                                   d.idmenu, d.jumlah
                            FROM pesanan p
                            JOIN pelanggan pl ON p.idpelanggan = pl.idpelanggan
                            LEFT JOIN detail_pesanan d ON d.idpesanan = p.idpesanan
                            WHERE p.idpesanan = ?", $id)[0] ?? null;
    }

    function buat($nama, $jk, $nohp, $alamat, $iduser, $idmeja, $idmenu, $jumlah) {
        if ($jumlah < 1) throw new Exception("Jumlah porsi minimal 1.");

        $this->conn->begin_transaction();
        try {
            $this->q("INSERT INTO pelanggan (namapelanggan, jeniskelamin, nohp, alamat) VALUES (?, ?, ?, ?)", $nama, $jk, $nohp, $alamat);
            $idpelanggan = $this->conn->insert_id;

            $this->q("INSERT INTO pesanan (idpelanggan, iduser, idmeja) VALUES (?, ?, ?)", $idpelanggan, $iduser, $idmeja);
            $idpesanan = $this->conn->insert_id;

            $harga = $this->rows("SELECT harga FROM menu WHERE idmenu = ?", $idmenu)[0]['harga'];
            $this->q("INSERT INTO detail_pesanan (idpesanan, idmenu, jumlah, subtotal) VALUES (?, ?, ?, ?)", $idpesanan, $idmenu, $jumlah, $harga * $jumlah);

            $this->q("UPDATE meja SET status = 'terisi' WHERE idmeja = ?", $idmeja);
            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    function ubah($idpesanan, $nama, $jk, $nohp, $alamat, $idmeja, $idmenu, $jumlah) {
        if ($jumlah < 1) throw new Exception("Jumlah porsi minimal 1.");

        $this->conn->begin_transaction();
        try {
            $p = $this->rows("SELECT idpelanggan, idmeja, status_pesanan FROM pesanan WHERE idpesanan = ? FOR UPDATE", $idpesanan)[0] ?? null;
            if (!$p) throw new Exception("Pesanan tidak ditemukan.");
            if ($p['status_pesanan'] != 'belum_bayar') throw new Exception("Hanya pesanan yang belum dibayar yang bisa diubah.");

            if ((int)$p['idmeja'] != (int)$idmeja) {
                $mj = $this->rows("SELECT status FROM meja WHERE idmeja = ? FOR UPDATE", $idmeja)[0] ?? null;
                if (!$mj || $mj['status'] != 'tersedia') throw new Exception("Meja yang dipilih sedang terisi.");
                $this->q("UPDATE meja SET status = 'tersedia' WHERE idmeja = ?", $p['idmeja']);
                $this->q("UPDATE meja SET status = 'terisi' WHERE idmeja = ?", $idmeja);
                $this->q("UPDATE pesanan SET idmeja = ? WHERE idpesanan = ?", $idmeja, $idpesanan);
            }

            $this->q("UPDATE pelanggan SET namapelanggan = ?, jeniskelamin = ?, nohp = ?, alamat = ? WHERE idpelanggan = ?",
                     $nama, $jk, $nohp, $alamat, $p['idpelanggan']);

            $harga = $this->rows("SELECT harga FROM menu WHERE idmenu = ?", $idmenu)[0]['harga'];
            $this->q("UPDATE detail_pesanan SET idmenu = ?, jumlah = ?, subtotal = ? WHERE idpesanan = ?",
                     $idmenu, $jumlah, $harga * $jumlah, $idpesanan);

            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    function batal($idpesanan) {
        $this->conn->begin_transaction();
        try {
            $p = $this->rows("SELECT idmeja, status_pesanan FROM pesanan WHERE idpesanan = ? FOR UPDATE", $idpesanan)[0] ?? null;
            if (!$p) throw new Exception("Pesanan tidak ditemukan.");
            if ($p['status_pesanan'] != 'belum_bayar') throw new Exception("Hanya pesanan yang belum dibayar yang bisa dibatalkan.");

            $this->q("UPDATE pesanan SET status_pesanan = 'batal' WHERE idpesanan = ?", $idpesanan);
            $this->q("UPDATE meja SET status = 'tersedia' WHERE idmeja = ?", $p['idmeja']);
            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}

class Transaksi extends BaseModel {
    function pesananBelumBayar() {
        return $this->rows("SELECT p.idpesanan, pl.namapelanggan, m.nomormeja,
                                   (SELECT SUM(subtotal) FROM detail_pesanan WHERE idpesanan = p.idpesanan) AS total
                            FROM pesanan p
                            JOIN pelanggan pl ON p.idpelanggan = pl.idpelanggan
                            JOIN meja m ON p.idmeja = m.idmeja
                            WHERE p.status_pesanan = 'belum_bayar'");
    }

    function bayar($idpesanan, $iduser, $bayar) {
        $this->q("CALL sp_proses_transaksi(?, ?, ?)", $idpesanan, $iduser, $bayar)->close();
    }

    function laporan($dari = '', $sampai = '') {
        $filter = ($dari && $sampai) ? "WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?" : "";
        $param  = $filter ? [$dari, $sampai] : [];
        return $this->rows("SELECT t.*, pl.namapelanggan, u.namauser, m.nomormeja
                            FROM transaksi t
                            JOIN pesanan p ON t.idpesanan = p.idpesanan
                            JOIN pelanggan pl ON p.idpelanggan = pl.idpelanggan
                            JOIN meja m ON p.idmeja = m.idmeja
                            JOIN users u ON t.iduser = u.iduser
                            $filter ORDER BY t.tanggal_transaksi DESC, t.idtransaksi DESC", ...$param);
    }
}