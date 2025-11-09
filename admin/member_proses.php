<?php
require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user = $_POST['id_user'];
    $id_lapangan = $_POST['id_lapangan'];
    $durasi_bulan = intval($_POST['durasi_bulan']); // contoh: 1, 2, 3 bulan
    $tanggal_mulai = $_POST['tanggal_mulai'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];

    // ambil harga per jam member dari tabel lapangan
    $stmt = $pdo->prepare("SELECT harga_per_jam_member FROM lapangan WHERE id_lapangan = ?");
    $stmt->execute([$id_lapangan]);
    $lapangan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lapangan) {
        echo "❌ Lapangan tidak ditemukan.";
        exit;
    }

    $harga_per_jam_member = $lapangan['harga_per_jam_member'];
    $total_bayar = $harga_per_jam_member * 4 * $durasi_bulan; // 4 minggu per bulan
    $tanggal_berakhir = date('Y-m-d', strtotime("+$durasi_bulan month", strtotime($tanggal_mulai)));

    // ===== Simpan ke tabel member =====
    $stmt = $pdo->prepare("INSERT INTO member (id_user, durasi_bulan, tanggal_mulai, tanggal_berakhir, total_bayar, status, created_at, updated_at)
                           VALUES (?, ?, ?, ?, ?, 'aktif', NOW(), NOW())");
    $stmt->execute([$id_user, $durasi_bulan, $tanggal_mulai, $tanggal_berakhir, $total_bayar]);
    $id_member = $pdo->lastInsertId();

    // ===== Simpan ke tabel keuangan =====
    $stmt = $pdo->prepare("INSERT INTO keuangan (tanggal, jenis, kategori, jumlah, sumber)
                           VALUES (CURDATE(), 'pemasukan', 'Member', ?, 'Aktivasi')");
    $stmt->execute([$total_bayar]);

    // ===== Simpan ke tabel member_subscription =====
    // day_of_week diambil dari tanggal_mulai
    $day_of_week = date('l', strtotime($tanggal_mulai)); // misal: Monday, Tuesday, dst

    $stmt = $pdo->prepare("INSERT INTO member_subscription 
        (id_user, id_lapangan, day_of_week, start_date, end_date, months, total_amount, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
    $stmt->execute([
        $id_user,
        $id_lapangan,
        $day_of_week,
        $tanggal_mulai,
        $tanggal_berakhir,
        $durasi_bulan,
        $total_bayar
    ]);

    $id_subscription = $pdo->lastInsertId();

    // ===== Generate jadwal booking otomatis 1x per minggu =====
    for ($i = 0; $i < (4 * $durasi_bulan); $i++) {
        $tanggal_booking = date('Y-m-d', strtotime("+$i week", strtotime($tanggal_mulai)));

        // cek apakah slot sudah terisi
        $cek = $pdo->prepare("SELECT COUNT(*) FROM booking 
                              WHERE id_lapangan = ? 
                              AND tanggal = ? 
                              AND jam_mulai = ? 
                              AND jam_selesai = ?
                              AND status NOT IN ('dibatalkan', 'ditolak')");
        $cek->execute([$id_lapangan, $tanggal_booking, $jam_mulai, $jam_selesai]);
        $terisi = $cek->fetchColumn();

        if ($terisi == 0) {
            // buat booking otomatis tanpa pembayaran
            $stmt = $pdo->prepare("INSERT INTO booking 
                (id_user, id_lapangan, type_booking, tanggal, jam_mulai, jam_selesai, status, total_amount)
                VALUES (?, ?, 'member', ?, ?, ?, 'disetujui', 0)");
            $stmt->execute([$id_user, $id_lapangan, $tanggal_booking, $jam_mulai, $jam_selesai]);
        }
    }

    echo "✅ Member berhasil diaktifkan dan jadwal otomatis dibuat!";
}
?>
