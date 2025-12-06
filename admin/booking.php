<?php
// booking.php - PROFESSIONAL VERSION
require_once 'auth_check.php';

if (isset($_SESSION['error']) && (stripos($_SESSION['error'], 'habis') !== false || stripos($_SESSION['error'], 'login') !== false)) {
    unset($_SESSION['error']);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../config/database.php';

$admin_id = $_SESSION['id_user'] ?? 0;

// Auto Update Status
try {
    $sqlAutoUpdate = "
        UPDATE booking b
        JOIN (
            SELECT db.id_booking, MAX(jw.jam_selesai) as jam_terakhir
            FROM detail_booking db
            JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu
            GROUP BY db.id_booking
        ) as t ON b.id_booking = t.id_booking
        SET b.status = 'selesai'
        WHERE b.status = 'disetujui' 
        AND (
            b.tanggal < CURDATE() 
            OR (b.tanggal = CURDATE() AND t.jam_terakhir < CURTIME())
        );
    ";
    $conn->query($sqlAutoUpdate);
} catch (Exception $e) {}

// SAVE WALK-IN BOOKING
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_walkin_booking') {
    
    $id_lapangan     = intval($_POST['id_lapangan'] ?? 0);
    $tanggal         = $_POST['tanggal'] ?? '';
    $slot_ids        = $_POST['slot_ids'] ?? [];
    $nama_customer   = trim($_POST['nama_customer'] ?? '');
    $no_hp_customer  = trim($_POST['no_hp_customer'] ?? '');
    $produk_qty      = $_POST['produk_qty'] ?? [];

    if (!$id_lapangan || !$tanggal || empty($slot_ids)) {
        $_SESSION['swal_type'] = 'warning';
        $_SESSION['swal_message'] = '⚠️ Data tidak lengkap. Pilih tanggal dan slot jam.';
        header("Location: booking.php");
        exit;
    }
    if (empty($nama_customer)) {
        $_SESSION['swal_type'] = 'warning';
        $_SESSION['swal_message'] = '⚠️ Nama customer wajib diisi.';
        header("Location: booking.php");
        exit;
    }

    $conn->begin_transaction();
    try {
        $unique_code = date('YmdHis') . rand(100, 999);
        $username_w  = "walkin_" . $unique_code;
        $email_w     = "walkin_" . $unique_code . "@local";
        $password    = password_hash('walkin123', PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (nama, username, email, password, no_hp, role, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'user', 'aktif', NOW(), NOW())");
        $stmt->bind_param("sssss", $nama_customer, $username_w, $email_w, $password, $no_hp_customer);
        $stmt->execute();
        $user_for_booking_id = $stmt->insert_id;
        $stmt->close();

        $stmt = $conn->prepare("SELECT harga_per_jam FROM lapangan WHERE id_lapangan = ? LIMIT 1");
        $stmt->bind_param("i", $id_lapangan);
        $stmt->execute();
        $lap = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$lap) throw new Exception("Lapangan tidak ditemukan.");
        $harga_per_jam = floatval($lap['harga_per_jam']);

        $placeholders = implode(',', array_fill(0, count($slot_ids), '?'));
        $types = str_repeat('i', count($slot_ids));
        $sql = "SELECT jd.id_detail, jd.status, jw.id_jadwal_waktu, jw.jam_mulai, jw.jam_selesai, jh.tanggal 
                FROM jadwal_detail jd
                JOIN jadwal_waktu jw ON jd.id_jadwal_waktu = jw.id_jadwal_waktu
                JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian
                WHERE jd.id_detail IN ($placeholders) FOR UPDATE";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$slot_ids);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (count($rows) !== count($slot_ids)) throw new Exception("Data slot tidak valid/sudah berubah.");
        usort($rows, function($a, $b) { return strcmp($a['jam_mulai'], $b['jam_mulai']); });

        $total_slot_price = 0;
        $slot_price_map = [];
        
        foreach ($rows as $i => $r) {
            if ($r['status'] !== 'tersedia') throw new Exception("Slot jam {$r['jam_mulai']} sudah diambil orang lain.");
            if ($r['tanggal'] !== $tanggal) throw new Exception("Tanggal slot error.");
            if ($i > 0) {
                if (substr($rows[$i-1]['jam_selesai'], 0, 5) !== substr($r['jam_mulai'], 0, 5)) 
                    throw new Exception("Slot jam harus berurutan!");
            }
            $durasi = (strtotime($r['jam_selesai']) - strtotime($r['jam_mulai'])) / 3600;
            $harga_slot = $durasi * $harga_per_jam;
            $slot_price_map[$r['id_detail']] = $harga_slot;
            $total_slot_price += $harga_slot;
        }

        $total_produk_price = 0;
        $list_produk_beli = [];
        if (!empty($produk_qty)) {
            $ids_prod = array_keys($produk_qty);
            $ids_prod_str = implode(',', array_map('intval', $ids_prod));
            
            if(!empty($ids_prod_str)){
                $qP = $conn->query("SELECT id_produk, nama_produk, harga FROM produk WHERE id_produk IN ($ids_prod_str)");
                while($p = $qP->fetch_assoc()) {
                    $qty = intval($produk_qty[$p['id_produk']] ?? 0);
                    if ($qty > 0) {
                        $subtotal = $qty * floatval($p['harga']);
                        $total_produk_price += $subtotal;
                        $list_produk_beli[] = $p['nama_produk'] . " (" . $qty . "x)";
                    }
                }
            }
        }
        $info_produk_str = !empty($list_produk_beli) ? implode(", ", $list_produk_beli) : "";
        $grand_total = $total_slot_price + $total_produk_price;

        $stmt = $conn->prepare("INSERT INTO booking (id_user, id_lapangan, tipe_booking, tanggal, status, total_amount, payment_status, info_produk, approved_by, created_at) VALUES (?, ?, 'manual', ?, 'disetujui', ?, 'lunas', ?, ?, NOW())");
        $stmt->bind_param("iisdsi", $user_for_booking_id, $id_lapangan, $tanggal, $grand_total, $info_produk_str, $admin_id);
        $stmt->execute();
        $id_booking = $stmt->insert_id;
        $stmt->close();

        $stmt_det = $conn->prepare("INSERT INTO detail_booking (id_booking, id_jadwal_waktu, harga) VALUES (?, ?, ?)");
        $stmt_upd = $conn->prepare("UPDATE jadwal_detail SET status='dibooking', id_booking = ? WHERE id_detail = ?");
        foreach ($rows as $r) {
            $harga = $slot_price_map[$r['id_detail']];
            $stmt_det->bind_param("iid", $id_booking, $r['id_jadwal_waktu'], $harga);
            $stmt_det->execute();
            $stmt_upd->bind_param("ii", $id_booking, $r['id_detail']);
            $stmt_upd->execute();
        }
        $stmt_det->close();
        $stmt_upd->close();

        $stmt = $conn->prepare("INSERT INTO pembayaran (booking_id, tipe, amount, method, status_verifikasi, verified_by, verified_at, created_at) VALUES (?, 'Pelunasan', ?, 'cash', 'valid', ?, NOW(), NOW())");
        $stmt->bind_param("idi", $id_booking, $grand_total, $admin_id);
        $stmt->execute();
        $id_pembayaran = $stmt->insert_id;
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO keuangan (tanggal, jenis, kategori, keterangan, jumlah, sumber, booking_id, pembayaran_id, created_at) VALUES (CURDATE(), 'pemasukan', 'Pelunasan', ?, ?, 'Pelunasan', ?, ?, NOW())");
        $ket = "Walk-in #$id_booking - $nama_customer";
        if ($info_produk_str) $ket .= " [+ $info_produk_str]";
        $stmt->bind_param("sdii", $ket, $grand_total, $id_booking, $id_pembayaran);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $_SESSION['swal_type'] = 'success';
        $_SESSION['swal_message'] = "✅ Booking Berhasil! Atas nama: $nama_customer";
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['swal_type'] = 'error';
        $_SESSION['swal_message'] = "❌ Gagal: " . $e->getMessage();
    }

    header("Location: booking.php");
    exit;
}

// FILTER
$filter_start = $_GET['start_date'] ?? date('Y-m-01');
$filter_end   = $_GET['end_date'] ?? date('Y-m-t');
$filter_status = $_GET['status'] ?? 'all';

// STATISTICS
$qTotal = $conn->query("SELECT COUNT(*) as total FROM booking WHERE tanggal BETWEEN '$filter_start' AND '$filter_end'");
$statTotal = $qTotal->fetch_assoc()['total'];

$qSuccess = $conn->query("SELECT COUNT(*) as total FROM booking WHERE status IN ('disetujui', 'selesai') AND tanggal BETWEEN '$filter_start' AND '$filter_end'");
$statSuccess = $qSuccess->fetch_assoc()['total'];

$qRevenue = $conn->query("SELECT SUM(total_amount) as total FROM booking WHERE payment_status IN ('lunas','dp_bayar') AND tanggal BETWEEN '$filter_start' AND '$filter_end'");
$statRevenue = $qRevenue->fetch_assoc()['total'] ?? 0;

$qLap = $conn->query("SELECT id_lapangan, nama_lapangan, harga_per_jam FROM lapangan WHERE status='aktif' ORDER BY nama_lapangan");
$qProd = $conn->query("SELECT * FROM produk WHERE status='aktif' ORDER BY nama_produk ASC");

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --shadow-sm: 0 2px 4px rgba(0,0,0,0.08);
    --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
    --shadow-lg: 0 10px 25px rgba(0,0,0,0.15);
}

.content-wrapper {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    padding: 2rem 1rem;
}

.stats-card {
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s ease;
    border: none;
    position: relative;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 120px;
    height: 120px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    transform: translate(40%, -40%);
}

.stats-icon {
    width: 70px;
    height: 70px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 15px;
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
}

.filter-card, .form-card {
    background: white;
    border-radius: 15px;
    box-shadow: var(--shadow-sm);
    border: 1px solid rgba(0,0,0,0.05);
}

.table-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: var(--shadow-md);
}

.table-card-header {
    background: var(--primary-gradient);
    padding: 1.5rem;
}

#tblBooking thead th {
    background: #f8f9fc;
    color: #5a5c69;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e3e6f0;
    padding: 1rem 0.75rem;
}

#tblBooking tbody tr {
    transition: all 0.2s ease;
    border-bottom: 1px solid #e3e6f0;
}

#tblBooking tbody tr:hover {
    background: #f8f9fc;
    transform: scale(1.005);
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.badge {
    padding: 0.5rem 0.75rem;
    font-weight: 600;
    border-radius: 8px;
    font-size: 0.75rem;
}

.badge.bg-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
}

.badge.bg-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
}

.badge.bg-info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
}

.btn {
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
    padding: 0.6rem 1.2rem;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-primary {
    background: var(--primary-gradient);
    border: none;
}

.btn-success {
    background: var(--success-gradient);
    border: none;
}

.slot-button {
    min-width: 100px;
    padding: 0.5rem 1rem;
    border-radius: 10px;
    transition: all 0.3s ease;
    border: 2px solid #667eea;
}

.slot-button:hover {
    transform: scale(1.05);
    box-shadow: var(--shadow-sm);
}

.slot-button.active {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
    border-color: #11998e;
}

@keyframes slideInDown {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.alert {
    border-radius: 12px;
    border: none;
    box-shadow: var(--shadow-sm);
    animation: slideInDown 0.5s ease;
}

@media (max-width: 768px) {
    .content-wrapper {
        padding: 1rem 0.5rem;
    }
    
    .stats-card {
        margin-bottom: 1rem;
    }
}
</style>

<div class="content-wrapper">
  
  <section class="content-header mb-4">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div class="mb-3 mb-md-0">
          <h1 class="mb-2" style="font-weight: 700; color: #2d3748;">
            <i class="fas fa-calendar-check me-2" style="color: #667eea;"></i> 
            Manajemen Booking
          </h1>
          <p class="text-muted mb-0">
            <i class="far fa-calendar-alt me-2"></i>
            Kelola jadwal dan lihat statistik periode <strong><?= date('d M Y', strtotime($filter_start)) ?> - <?= date('d M Y', strtotime($filter_end)) ?></strong>
          </p>
        </div>
        <button onclick="window.location.reload()" class="btn btn-outline-primary">
          <i class="fas fa-sync-alt me-1"></i> Refresh
        </button>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      
      <!-- Stats Cards -->
      <div class="row g-4 mb-4">
        <div class="col-lg-4 col-md-6">
          <div class="card stats-card h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body text-white p-4">
              <div class="d-flex align-items-center">
                <div class="stats-icon me-3">
                  <i class="fas fa-calendar-alt fa-2x"></i>
                </div>
                <div class="flex-grow-1">
                  <p class="mb-1 opacity-90" style="font-size: 0.875rem; font-weight: 500;">Total Booking</p>
                  <h2 class="mb-0 fw-bold"><?= $statTotal ?></h2>
                  <small class="opacity-75">
                    <i class="fas fa-chart-line me-1"></i>Periode Ini
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4 col-md-6">
          <div class="card stats-card h-100" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
            <div class="card-body text-white p-4">
              <div class="d-flex align-items-center">
                <div class="stats-icon me-3">
                  <i class="fas fa-check-circle fa-2x"></i>
                </div>
                <div class="flex-grow-1">
                  <p class="mb-1 opacity-90" style="font-size: 0.875rem; font-weight: 500;">Booking Sukses</p>
                  <h2 class="mb-0 fw-bold"><?= $statSuccess ?></h2>
                  <small class="opacity-75">
                    <i class="fas fa-thumbs-up me-1"></i>Disetujui & Selesai
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4 col-md-12">
          <div class="card stats-card h-100" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="card-body text-white p-4">
              <div class="d-flex align-items-center">
                <div class="stats-icon me-3">
                  <i class="fas fa-wallet fa-2x"></i>
                </div>
                <div class="flex-grow-1">
                  <p class="mb-1 opacity-90" style="font-size: 0.875rem; font-weight: 500;">Total Pendapatan</p>
                  <h2 class="mb-0 fw-bold">Rp <?= number_format($statRevenue, 0, ',', '.') ?></h2>
                  <small class="opacity-75">
                    <i class="fas fa-money-bill-wave me-1"></i>Lunas & DP
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filter Card -->
      <div class="card filter-card mb-4">
        <div class="card-body p-4">
          <form method="GET" action="booking.php" class="row g-3 align-items-end">
            <div class="col-lg-3 col-md-6">
              <label class="form-label fw-semibold text-secondary mb-2">
                <i class="far fa-calendar-alt me-1"></i> Dari Tanggal
              </label>
              <input type="date" name="start_date" class="form-control" value="<?= $filter_start ?>">
            </div>
            <div class="col-lg-3 col-md-6">
              <label class="form-label fw-semibold text-secondary mb-2">
                <i class="far fa-calendar-check me-1"></i> Sampai Tanggal
              </label>
              <input type="date" name="end_date" class="form-control" value="<?= $filter_end ?>">
            </div>
            <div class="col-lg-2 col-md-6">
              <label class="form-label fw-semibold text-secondary mb-2">
                <i class="fas fa-filter me-1"></i> Status
              </label>
              <select name="status" class="form-select">
                <option value="all" <?= $filter_status == 'all' ? 'selected' : '' ?>>Semua</option>
                <option value="menunggu" <?= $filter_status == 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                <option value="belum lunas" <?= $filter_status == 'belum lunas' ? 'selected' : '' ?>>Belum Lunas</option>
                <option value="disetujui" <?= $filter_status == 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                <option value="selesai" <?= $filter_status == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                <option value="ditolak" <?= $filter_status == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                <option value="dibatalkan" <?= $filter_status == 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
              </select>
            </div>
            <div class="col-lg-2 col-md-6">
              <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-search me-1"></i> Terapkan
              </button>
            </div>
            <div class="col-lg-2 col-md-12">
              <button type="button" class="btn btn-success w-100" data-bs-toggle="collapse" data-bs-target="#formTambahBooking">
                <i class="fas fa-plus me-1"></i> Booking Baru
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Form Walk-In -->
      <div class="collapse mb-4" id="formTambahBooking">
        <div class="card form-card">
          <div class="card-header text-white" style="background: var(--primary-gradient); padding: 1.5rem;">
            <h3 class="card-title mb-0 fw-bold">
              <i class="fas fa-user-plus me-2"></i> Form Booking Walk-In
            </h3>
          </div>

          <form method="POST" id="formWalkinBooking">
            <input type="hidden" name="action" value="save_walkin_booking">
            <div class="card-body p-4">
              
              <div class="row g-4">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">
                    <i class="fas fa-user me-1"></i> Nama Customer <span class="text-danger">*</span>
                  </label>
                  <input type="text" name="nama_customer" class="form-control" placeholder="Masukkan nama customer" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">
                    <i class="fas fa-phone me-1"></i> No HP Customer
                  </label>
                  <input type="text" name="no_hp_customer" class="form-control" placeholder="08xxxxxxxxxx">
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold">
                    <i class="fas fa-futbol me-1"></i> Lapangan <span class="text-danger">*</span>
                  </label>
                  <select name="id_lapangan" id="id_lapangan" class="form-select" required>
                    <option value="">-- Pilih Lapangan --</option>
                    <?php $qLap->data_seek(0); ?>
                    <?php while($l = $qLap->fetch_assoc()): ?>
                      <option value="<?= $l['id_lapangan'] ?>" data-harga="<?= $l['harga_per_jam'] ?>">
                        <?= htmlspecialchars($l['nama_lapangan']) ?> – Rp <?= number_format($l['harga_per_jam'],0,',','.') ?>/jam
                      </option>
                    <?php endwhile; ?>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold">
                    <i class="far fa-calendar-alt me-1"></i> Tanggal Main <span class="text-danger">*</span>
                  </label>
                  <input type="date" name="tanggal" id="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="col-md-12" id="slotContainer" style="display:none;">
                  <label class="form-label fw-semibold">
                    <i class="far fa-clock me-1"></i> Pilih Slot Jam <span class="text-danger">*</span>
                  </label>
                  <div id="slotList" class="d-flex flex-wrap gap-2 p-3 bg-light rounded"></div>
                </div>

                <div class="col-md-12">
                  <div class="card border-0" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="card-header border-0" style="background: transparent;">
                      <h5 class="card-title mb-0 text-white fw-bold">
                        <i class="fas fa-box-open me-2"></i> Tambah Produk / F&B (Opsional)
                      </h5>
                    </div>
                    <div class="card-body bg-white rounded-bottom p-3">
                      <div class="row g-2" style="max-height: 200px; overflow-y: auto;">
                        <?php if($qProd->num_rows > 0): ?>
                          <?php while($p = $qProd->fetch_assoc()): ?>
                            <div class="col-md-4 col-sm-6">
                              <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light w-50 text-truncate fw-semibold" title="<?= htmlspecialchars($p['nama_produk']) ?>">
                                  <?= htmlspecialchars($p['nama_produk']) ?>
                                </span>
                                <span class="input-group-text bg-white fw-bold">
                                  <?= number_format($p['harga'],0,',','.') ?>
                                </span>
                                <input type="number" name="produk_qty[<?= $p['id_produk'] ?>]" class="form-control produk-input text-center" min="0" placeholder="0" data-harga="<?= $p['harga'] ?>">
                              </div>
                            </div>
                          <?php endwhile; ?>
                        <?php else: ?>
                          <div class="col-12 text-center text-muted">Belum ada produk.</div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-semibold text-primary">
                    <i class="fas fa-futbol me-1"></i> Total Lapangan
                  </label>
                  <input type="text" id="subtotal_lapangan" class="form-control form-control-lg bg-light fw-bold" readonly>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-semibold text-info">
                    <i class="fas fa-box me-1"></i> Total Produk
                  </label>
                  <input type="text" id="subtotal_produk" class="form-control form-control-lg bg-light fw-bold" readonly>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-semibold text-success">
                    <i class="fas fa-money-bill-wave me-1"></i> GRAND TOTAL
                  </label>
                  <input type="text" id="grand_total_display" class="form-control form-control-lg fw-bold text-white" style="background: var(--success-gradient); font-size: 1.3rem;" readonly>
                </div>
              </div>
            </div>

            <div class="card-footer bg-light text-end p-4">
              <button type="submit" class="btn btn-success btn-lg px-5">
                <i class="fas fa-check-circle me-2"></i> Simpan & Bayar
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Table -->
      <div class="card table-card">
        <div class="table-card-header">
          <h3 class="card-title mb-0 text-white fw-bold">
            <i class="fas fa-list me-2"></i> Daftar Booking
          </h3>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table id="tblBooking" class="table table-hover align-middle w-100 mb-0">
              <thead class="text-center">
                <tr>
                  <th style="width: 5%">No</th>
                  <th style="width: 15%">Pemesan</th>
                  <th style="width: 20%">Info Produk</th>
                  <th style="width: 10%">Lapangan</th>
                  <th style="width: 10%">Tanggal</th>
                  <th style="width: 15%">Jam</th>
                  <th style="width: 12%">Total</th>
                  <th style="width: 8%">Status</th>
                  <th style="width: 5%">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $whereClause = "WHERE b.tanggal BETWEEN '$filter_start' AND '$filter_end'";
                if ($filter_status !== 'all') {
                    $whereClause .= " AND b.status = '$filter_status'";
                }

                $sql = "
                    SELECT 
                      b.id_booking, b.tanggal, b.total_amount, b.status, b.info_produk, b.tipe_booking,
                      u.nama AS nama_pemesan, l.nama_lapangan
                    FROM booking b
                    LEFT JOIN users u ON b.id_user = u.id_user
                    LEFT JOIN lapangan l ON b.id_lapangan = l.id_lapangan
                    $whereClause
                    ORDER BY b.tanggal DESC, b.created_at DESC
                ";
                
                $result = $conn->query($sql);
                
                if (!$result) {
                    echo '</tbody></table><div class="alert alert-danger m-3">Error Database</div><table class="d-none"><tbody>';
                } elseif ($result->num_rows > 0) {
                    $no = 1;
                    while($row = $result->fetch_assoc()): 
                        $badgeBooking = match($row['status']) {
                            'disetujui', 'selesai' => 'badge bg-success',
                            'menunggu' => 'badge bg-warning',
                            'belum lunas' => 'badge bg-secondary',
                            'dibatalkan', 'ditolak' => 'badge bg-danger',
                            default => 'badge bg-secondary'
                        };

                        $id_b = $row['id_booking'];
                        $qJam = $conn->query("
                            SELECT jw.jam_mulai, jw.jam_selesai 
                            FROM detail_booking db 
                            JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu 
                            WHERE db.id_booking = '$id_b' 
                            ORDER BY jw.jam_mulai ASC
                        ");
                        
                        $jam_list = [];
                        while($j = $qJam->fetch_assoc()) {
                            $jam_str = date('H:i', strtotime($j['jam_mulai'])) . '-' . date('H:i', strtotime($j['jam_selesai']));
                            $jam_list[] = '<span class="badge bg-light text-dark border shadow-sm mb-1" style="font-size:0.8rem;">' . $jam_str . '</span>';
                        }
                        $jam_display = !empty($jam_list) ? implode(' ', $jam_list) : '<span class="text-muted">-</span>';
                ?>
                  <tr>
                    <td class="text-center fw-semibold"><?= $no++ ?></td>
                    <td>
                      <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama_pemesan'] ?? 'Guest') ?></div>
                      <?php if($row['tipe_booking'] == 'manual'): ?>
                        <span class="badge bg-info" style="font-size: 0.7rem;">Walk-in</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if(!empty($row['info_produk'])): ?>
                        <small class="text-dark"><?= htmlspecialchars($row['info_produk']) ?></small>
                      <?php else: ?>
                        <span class="text-muted text-center d-block small">-</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center fw-semibold text-primary"><?= htmlspecialchars($row['nama_lapangan'] ?? '-') ?></td>
                    <td class="text-center"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                    <td class="text-center">
                      <div class="d-flex flex-column align-items-center">
                        <?= $jam_display ?>
                      </div>
                    </td>
                    <td class="text-end fw-bold">Rp <?= number_format($row['total_amount'], 0, ',', '.') ?></td>
                    <td class="text-center">
                      <span class="<?= $badgeBooking ?>"><?= ucfirst($row['status']) ?></span>
                    </td>
                    <td class="text-center">
                      <a href="booking_detail.php?id=<?= $row['id_booking'] ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i>
                      </a>
                    </td>
                  </tr>
                <?php 
                    endwhile; 
                } else {
                    echo '<tr><td colspan="9" class="text-center py-5 text-muted"><i class="fas fa-inbox fa-3x mb-3 d-block"></i>Tidak ada data booking</td></tr>';
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include('../includes/footer.php'); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const Toast = Swal.mixin({
        toast: true, position: 'top-end', showConfirmButton: false, timer: 4000, timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    <?php if(isset($_SESSION['swal_type']) && isset($_SESSION['swal_message'])): ?>
        Toast.fire({ icon: '<?= $_SESSION['swal_type'] ?>', title: '<?= $_SESSION['swal_message'] ?>' });
        <?php unset($_SESSION['swal_type']); unset($_SESSION['swal_message']); ?>
    <?php endif; ?>
});

// FORM LOGIC
document.addEventListener('DOMContentLoaded', () => {
  const idLapangan = document.getElementById('id_lapangan');
  const tanggal = document.getElementById('tanggal');
  const slotContainer = document.getElementById('slotContainer');
  const slotList = document.getElementById('slotList');
  const subtotalLapDisplay = document.getElementById('subtotal_lapangan');
  const subtotalProdDisplay = document.getElementById('subtotal_produk');
  const grandTotalDisplay = document.getElementById('grand_total_display');
  const productInputs = document.querySelectorAll('.produk-input');
  const form = document.getElementById('formWalkinBooking');
  let selectedSlots = [];

  function formatRp(n) {
    return 'Rp ' + (Number(n).toLocaleString('id-ID', {minimumFractionDigits: 0}));
  }
  
  function timeToMinutes(t) {
    const p = t.split(':'); 
    return parseInt(p[0])*60 + parseInt(p[1]);
  }
  
  function getCurrentHargaLap() {
    const s = idLapangan.selectedOptions[0];
    return s ? parseFloat(s.dataset.harga || 0) : 0;
  }

  function calculateTotal() {
      const hargaLap = getCurrentHargaLap();
      let totalLap = 0;
      selectedSlots.forEach(slt => {
          const dur = (timeToMinutes(slt.jam_selesai) - timeToMinutes(slt.jam_mulai))/60; 
          totalLap += dur * hargaLap;
      });

      let totalProd = 0;
      productInputs.forEach(inp => {
          const qty = parseInt(inp.value) || 0;
          const price = parseFloat(inp.dataset.harga) || 0;
          if(qty > 0) totalProd += (qty * price);
      });

      subtotalLapDisplay.value = formatRp(totalLap);
      subtotalProdDisplay.value = formatRp(totalProd);
      grandTotalDisplay.value = formatRp(totalLap + totalProd);
      updateHiddenInputs();
  }

  function loadSlots() {
    const idL = idLapangan.value;
    const tgl = tanggal.value;
    selectedSlots = [];
    form.querySelectorAll("input[name='slot_ids[]']").forEach(e => e.remove());
    slotList.innerHTML = '';
    
    if (!idL || !tgl) { 
      slotContainer.style.display = 'none'; 
      return; 
    }
    slotContainer.style.display = 'block';
    slotList.innerHTML = '<div class="spinner-border spinner-border-sm text-primary"></div> Memuat...';

    fetch(`booking_get_slot.php?id_lapangan=${idL}&tanggal=${tgl}`)
      .then(res => res.json())
      .then(data => {
        slotList.innerHTML = ''; 
        if (data.status !== 'success' || !data.slots.length) {
           slotList.innerHTML = '<div class="alert alert-info py-2 px-3 mb-0">Tidak ada slot tersedia</div>';
           return;
        }

        data.slots.forEach(s => {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'btn slot-button';
          btn.dataset.idDetail = s.id_detail;
          btn.dataset.idJw = s.id_jadwal_waktu;
          btn.dataset.jamMulai = s.jam_mulai;
          btn.dataset.jamSelesai = s.jam_selesai;

          if (s.status !== 'tersedia') {
            btn.disabled = true;
            btn.className = 'btn btn-secondary opacity-50';
            btn.innerHTML = `<i class="fas fa-lock me-1"></i> ${s.jam_mulai}`;
          } else {
            btn.innerHTML = `<i class="far fa-clock me-1"></i> ${s.jam_mulai}-${s.jam_selesai}`;
            btn.addEventListener('click', () => toggleSlot(btn));
          }
          slotList.appendChild(btn);
        });
        calculateTotal();
      })
      .catch(err => {
          slotList.innerHTML = '<span class="text-danger">Gagal memuat slot</span>';
      });
  }

  function toggleSlot(btn) {
    const idDetail = btn.dataset.idDetail;
    const idx = selectedSlots.findIndex(x => x.id_detail == idDetail);
    
    if (idx !== -1) {
      selectedSlots.splice(idx, 1);
      btn.classList.remove('active');
    } else {
      selectedSlots.push({
          id_detail: idDetail, 
          id_jw: btn.dataset.idJw, 
          jam_mulai: btn.dataset.jamMulai, 
          jam_selesai: btn.dataset.jamSelesai
      });
      btn.classList.add('active');
    }
    selectedSlots.sort((a,b) => timeToMinutes(a.jam_mulai) - timeToMinutes(b.jam_mulai));
    calculateTotal();
  }

  function updateHiddenInputs() {
    form.querySelectorAll("input[name='slot_ids[]']").forEach(e => e.remove());
    selectedSlots.forEach(slt => {
      const in1 = document.createElement('input');
      in1.type = 'hidden'; 
      in1.name = 'slot_ids[]'; 
      in1.value = slt.id_detail;
      form.appendChild(in1);
    });
  }

  idLapangan.addEventListener('change', loadSlots);
  tanggal.addEventListener('change', loadSlots);
  productInputs.forEach(input => {
      input.addEventListener('input', calculateTotal);
  });

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    if (selectedSlots.length === 0) {
      Swal.fire({ 
        icon: 'warning', 
        title: 'Perhatian', 
        text: 'Pilih minimal 1 slot jam!' 
      });
      return;
    }
    
    Swal.fire({
        title: 'Konfirmasi Pembayaran',
        text: "Transaksi akan langsung berstatus LUNAS.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#11998e',
        cancelButtonColor: '#d33',
        confirmButtonText: '<i class="fas fa-check me-2"></i>Ya, Proses!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            const btn = form.querySelector('button[type="submit"]');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Memproses...';
            btn.disabled = true;
            form.submit();
        }
    });
  });
});
</script>