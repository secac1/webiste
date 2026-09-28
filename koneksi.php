<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

class Koneksi {
    public $conn;

    function __construct() {
        $this->conn = new mysqli("localhost", "root", "", "db_restoran");
    }
}
