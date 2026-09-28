<?php
$data = (new Transaksi($conn))->laporan();
?>
<h3>Laporan Transaksi Penjualan</h3>
<?php
tabel(['ID' => 'idtransaksi', 'Tanggal' => 'tanggal_transaksi', 'Pelanggan' => 'namapelanggan', 'Meja' => 'nomormeja', 'Kasir' => 'namauser',
       'Total' => 'total', 'Bayar' => 'bayar', 'Kembalian' => 'kembalian'],
      $data, ['total', 'bayar', 'kembalian'], null, 'Tidak ada data transaksi.');