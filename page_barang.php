<?php
$m = new Menu($conn); $pg = 'barang'; $judul = 'Barang'; $noun = 'menu';
$f = ['namamenu' => ['Nama Menu', 'text', ''], 'harga' => ['Harga', 'number', 0]];   
$opsi = ['tersedia', 'habis'];
$tulis = true;   
require 'crud.php';