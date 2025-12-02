<?php
// invoice_booking.php - VERSI FINAL (Inklusi info_produk dan perhitungan total)
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

if (!isset($_GET['id'])) die("ID Booking tidak ditemukan");
$id_booking = intval($_GET['id']);

// Ambil data Booking (termasuk info_produk, total_amount, dan dp_amount)
$sql = "SELECT b.*, u.nama AS nama_user, u.no_hp, l.nama_lapangan, l.harga_per_jam 
         FROM booking b
         JOIN users u ON b.id_user = u.id_user
         JOIN lapangan l ON b.id_lapangan = l.id_lapangan
         WHERE b.id_booking = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_booking);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) die("Data tidak ditemukan");

// Ambil Slot & Hitung Subtotal Harga Lapangan Murni
$stmt = $conn->prepare("
    SELECT jw.jam_mulai, jw.jam_selesai, db.harga 
    FROM detail_booking db 
    JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu 
    WHERE db.id_booking = ? ORDER BY jw.jam_mulai ASC");
$stmt->bind_param("i", $id_booking);
$stmt->execute();
$slots = $stmt->get_result();

$total_harga_lapangan = 0;
// Gunakan data_seek(0) dan fetch_assoc untuk iterasi
$slots_data = [];
while ($s = $slots->fetch_assoc()) {
    $total_harga_lapangan += $s['harga'];
    $slots_data[] = $s; // Simpan data slot
}
$slots->data_seek(0); // Reset pointer untuk looping di HTML

// Hitung Biaya Tambahan (Produk/Minuman)
$biaya_tambahan = floatval($data['total_amount']) - $total_harga_lapangan;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?= $id_booking ?></title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 20px auto; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { margin: 0; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 5px; vertical-align: top; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .items-table th, .items-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .items-table th { background-color: #f4f4f4; }
        .total-section { text-align: right; margin-top: 20px; }
        .footer { text-align: center; margin-top: 50px; font-size: 0.8em; color: #666; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        @media print { 
            .no-print { display: none; } 
            body { margin: 0; padding: 20px; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer;">Cetak / Simpan PDF</button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; cursor: pointer;">Tutup</button>
    </div>

    <div class="header">
        <h1>RUSH BADMINTOON ACADEMY</h1>
        <p>Jl. Kalimantan Gg.14, Krajan Timur, Sumber Sari, Kec Sumber Sari,</p>
        <p>Kab Jember, Jawa Timur| Telp: 0812-3456-7890</p>
        <h2>BUKTI BOOKING</h2>
    </div>

    <table class="info-table">
        <tr>
            <td width="15%"><strong>No. Invoice</strong></td>
            <td width="35%">: INV-<?= date('Ymd', strtotime($data['created_at'])) ?>-<?= $id_booking ?></td>
            <td width="15%"><strong>Tanggal Cetak</strong></td>
            <td>: <?= date('d F Y H:i') ?></td>
        </tr>
        <tr>
            <td><strong>Nama Pemesan</strong></td>
            <td>: <?= htmlspecialchars($data['nama_user']) ?></td>
            <td><strong>Status Bayar</strong></td>
            <td>: <?= strtoupper(str_replace('_', ' ', $data['payment_status'])) ?></td>
        </tr>
        <tr>
            <td><strong>No HP</strong></td>
            <td>: <?= htmlspecialchars($data['no_hp'] ?: '-') ?></td>
            <td><strong>Lapangan</strong></td>
            <td>: <?= htmlspecialchars($data['nama_lapangan']) ?></td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 60%;">Keterangan</th>
                <th style="width: 15%;" class="text-right">Harga Satuan</th>
                <th style="width: 20%;" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1; 
            
            // 1. DETAIL SLOT LAPANGAN
            foreach($slots_data as $s): 
                $jam = date('H:i', strtotime($s['jam_mulai'])) . ' - ' . date('H:i', strtotime($s['jam_selesai']));
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td>Sewa Lapangan Tgl <?= date('d/m/Y', strtotime($data['tanggal'])) ?> (Slot <?= $jam ?>)</td>
                <td class="text-right">Rp <?= number_format($s['harga'], 0, ',', '.') ?></td>
                <td class="text-right">Rp <?= number_format($s['harga'], 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>

            <?php 
            // 2. DETAIL TAMBAHAN PRODUK/MINUMAN (Jika ada)
            if($biaya_tambahan > 0 || !empty($data['info_produk'])):
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td>
                    Biaya Tambahan Produk/Minuman 
                    <?php if (!empty($data['info_produk'])): ?>
                        <br><small>Keterangan: **<?= nl2br(htmlspecialchars($data['info_produk'])) ?>**</small>
                    <?php endif; ?>
                </td>
                <td class="text-right">-</td>
                <td class="text-right">Rp <?= number_format($biaya_tambahan, 0, ',', '.') ?></td>
            </tr>
            <?php endif; ?>

            <tr>
                <td colspan="3" class="text-right fw-bold" style="background-color: #f4f4f4;">TOTAL TAGIHAN</td>
                <td class="text-right fw-bold" style="background-color: #f4f4f4;">Rp <?= number_format($data['total_amount'], 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td colspan="3" class="text-right fw-bold">DP / SUDAH DIBAYAR</td>
                <td class="text-right fw-bold">Rp <?= number_format($data['dp_amount'], 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td colspan="3" class="text-right fw-bold" style="background-color: #ffe0b2;">SISA PEMBAYARAN</td>
                <td class="text-right fw-bold" style="background-color: #ffe0b2; color: red;">Rp <?= number_format($data['remaining_amount'], 0, ',', '.') ?></td>
            </tr>
        </tbody>
    </table>

    <div class="total-section">
        <?php if($data['remaining_amount'] <= 0): ?>
            <p style="color: green; font-weight: bold; font-size: 1.2em;">STATUS: LUNAS</p>
        <?php endif; ?>
    </div>

    <div class="footer">
        <p>Terima kasih telah memesan lapangan di Rush Badmintoon Academy.</p>
        <p>Harap simpan bukti ini sebagai tiket masuk.</p>
        <p>Invoice dicetak pada: <?= date('d/m/Y H:i:s') ?></p>
    </div>

</body>
</html>