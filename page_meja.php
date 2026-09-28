<?php
$m = new Meja($conn); $pg = 'meja'; $judul = 'Meja'; $noun = 'meja';
$f = ['nomormeja' => ['Nomor Meja', 'text', ''], 'kapasitas' => ['Kapasitas', 'number', 1]];   // kolom => [label, type, min]
$opsi = ['tersedia', 'terisi'];
$tulis = true;
require 'crud.php';