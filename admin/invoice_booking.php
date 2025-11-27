<?php
// invoice_booking.php
require_once __DIR__ . '/../config/database.php';

if (!isset($_GET['id'])) die("ID Booking tidak ditemukan");
$id_booking = intval($_GET['id']);

// Ambil data
$sql = "SELECT b.*, u.nama AS nama_user, u.no_hp, l.nama_lapangan, l.harga_per_jam 
        FROM booking b
        JOIN users u ON b.id_user = u.id_user
        JOIN lapangan l ON b.id_lapangan = l.id_lapangan
        WHERE b.id_booking = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_booking);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) die("Data tidak ditemukan");

// Ambil Slot
$stmt = $conn->prepare("
    SELECT jw.jam_mulai, jw.jam_selesai, db.harga 
    FROM detail_booking db 
    JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu 
    WHERE db.id_booking = ? ORDER BY jw.jam_mulai ASC");
$stmt->bind_param("i", $id_booking);
$stmt->execute();
$slots = $stmt->get_result();
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
            <td width="15%"><strong>No</strong></td>
            <td width="35%">: INV-<?= date('Ymd', strtotime($data['created_at'])) ?>-<?= $id_booking ?></td>
            <td width="15%"><strong>Tanggal</strong></td>
            <td>: <?= date('d F Y H:i', strtotime($data['created_at'])) ?></td>
        </tr>
        <tr>
            <td><strong>Nama</strong></td>
            <td>: <?= htmlspecialchars($data['nama_user']) ?></td>
            <td><strong>Status</strong></td>
            <td>: <?= strtoupper($data['payment_status']) ?></td>
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
                <th>No</th>
                <th>Keterangan / Slot Jam</th>
                <th style="text-align: right;">Harga</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1; 
            $subtotal = 0;
            while($s = $slots->fetch_assoc()): 
                $jam = date('H:i', strtotime($s['jam_mulai'])) . ' - ' . date('H:i', strtotime($s['jam_selesai']));
                $subtotal += $s['harga'];
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td>Sewa Lapangan (<?= $jam ?>) - <?= date('d/m/Y', strtotime($data['tanggal'])) ?></td>
                <td style="text-align: right;">Rp <?= number_format($s['harga'], 0, ',', '.') ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="total-section">
        <h3>Total Tagihan: Rp <?= number_format($subtotal, 0, ',', '.') ?></h3>
        <?php if($data['remaining_amount'] > 0): ?>
            <p style="color: red;">Sisa Pembayaran: Rp <?= number_format($data['remaining_amount'], 0, ',', '.') ?></p>
        <?php else: ?>
            <p style="color: green; font-weight: bold;">LUNAS</p>
        <?php endif; ?>
    </div>

    <div class="footer">
        <p>Terima kasih telah memesan lapangan di Rush Badmintoon Academy.</p>
        <p>Harap simpan bukti ini sebagai tiket masuk.</p>
    </div>

</body>
</html>