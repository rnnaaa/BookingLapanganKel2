<?php
// booking.php - FINAL: DASHBOARD FILTER & AUTO UPDATE STATUS
require_once 'auth_check.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

$admin_id = $_SESSION['id_user'] ?? 0; 

// =================================================================================
// 0. AUTO UPDATE STATUS: DISETUJUI -> SELESAI
// =================================================================================
// Jika tanggal < hari ini, ATAU (tanggal = hari ini DAN jam_selesai < jam sekarang)
// Maka ubah status dari 'disetujui' menjadi 'selesai'
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
} catch (Exception $e) {
    // Silent error agar tidak mengganggu UI, bisa di log jika perlu
}

// =================================================================================
// 1. BAGIAN LOGIKA SIMPAN (WALK-IN BOOKING)
// =================================================================================
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
        // A. BUAT USER BARU (GUEST/WALKIN)
        $unique_code = date('YmdHis') . rand(100, 999);
        $username_w  = "walkin_" . $unique_code;
        $email_w     = "walkin_" . $unique_code . "@local"; 
        $password    = password_hash('walkin123', PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (nama, username, email, password, no_hp, role, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'user', 'aktif', NOW(), NOW())");
        $stmt->bind_param("sssss", $nama_customer, $username_w, $email_w, $password, $no_hp_customer);
        $stmt->execute();
        $user_for_booking_id = $stmt->insert_id;
        $stmt->close();

        // B. HITUNG HARGA LAPANGAN
        $stmt = $conn->prepare("SELECT harga_per_jam FROM lapangan WHERE id_lapangan = ? LIMIT 1");
        $stmt->bind_param("i", $id_lapangan);
        $stmt->execute();
        $lap = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$lap) throw new Exception("Lapangan tidak ditemukan.");
        $harga_per_jam = floatval($lap['harga_per_jam']);

        // Lock rows for update
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

        // C. HITUNG PRODUK
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

        // D. INSERT BOOKING (Status Payment Langsung Lunas untuk Walk-in)
        $stmt = $conn->prepare("INSERT INTO booking (id_user, id_lapangan, tipe_booking, tanggal, status, total_amount, payment_status, info_produk, approved_by, created_at) VALUES (?, ?, 'manual', ?, 'disetujui', ?, 'lunas', ?, ?, NOW())");
        $stmt->bind_param("iisdsi", $user_for_booking_id, $id_lapangan, $tanggal, $grand_total, $info_produk_str, $admin_id);
        $stmt->execute();
        $id_booking = $stmt->insert_id;
        $stmt->close();

        // E. Detail & Update Status Jadwal
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

        // F. Pembayaran
        $stmt = $conn->prepare("INSERT INTO pembayaran (booking_id, tipe, amount, method, status_verifikasi, verified_by, verified_at, created_at) VALUES (?, 'Pelunasan', ?, 'cash', 'valid', ?, NOW(), NOW())");
        $stmt->bind_param("idi", $id_booking, $grand_total, $admin_id);
        $stmt->execute();
        $id_pembayaran = $stmt->insert_id;
        $stmt->close();

        // G. Keuangan
        $stmt = $conn->prepare("INSERT INTO keuangan (tanggal, jenis, kategori, keterangan, jumlah, sumber, booking_id, pembayaran_id, created_at) VALUES (CURDATE(), 'pemasukan', 'Pelunasan', ?, ?, 'Pelunasan', ?, ?, NOW())");
        $ket = "Walk-in #$id_booking - $nama_customer";
        if ($info_produk_str) $ket .= " [+ $info_produk_str]";
        $stmt->bind_param("sdii", $ket, $grand_total, $id_booking, $id_pembayaran);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $_SESSION['swal_type'] = 'success';
        $_SESSION['swal_message'] = "Booking Berhasil! Atas nama: $nama_customer";
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['swal_type'] = 'error';
        $_SESSION['swal_message'] = "Gagal: " . $e->getMessage();
    }

    header("Location: booking.php");
    exit;
}

// =================================================================================
// 2. BAGIAN STATISTIK & FILTER (DATA REAL MENGIKUTI FILTER)
// =================================================================================

// --- LOGIKA FILTER ---
$filter_start = $_GET['start_date'] ?? date('Y-m-01'); // Default awal bulan ini
$filter_end   = $_GET['end_date'] ?? date('Y-m-t');    // Default akhir bulan ini
$filter_status = $_GET['status'] ?? 'all';

// --- QUERY STATISTIK (DASHBOARD) ---
// 1. Total Booking (Dalam Range Tanggal Ini)
$qTotal = $conn->query("SELECT COUNT(*) as total FROM booking WHERE tanggal BETWEEN '$filter_start' AND '$filter_end'");
$statTotal = $qTotal->fetch_assoc()['total'];

// 2. Booking Sukses (Disetujui/Selesai dalam Range Ini)
$qSuccess = $conn->query("SELECT COUNT(*) as total FROM booking WHERE status IN ('disetujui', 'selesai') AND tanggal BETWEEN '$filter_start' AND '$filter_end'");
$statSuccess = $qSuccess->fetch_assoc()['total'];

// 3. Pendapatan (Hanya yang status payment lunas/dp_bayar dalam Range Ini)
$qRevenue = $conn->query("SELECT SUM(total_amount) as total FROM booking WHERE payment_status IN ('lunas','dp_bayar') AND tanggal BETWEEN '$filter_start' AND '$filter_end'");
$statRevenue = $qRevenue->fetch_assoc()['total'] ?? 0;

// =================================================================================
// 3. PERSIAPAN DATA FORM DROPDOWN
// =================================================================================
$qLap = $conn->query("SELECT id_lapangan, nama_lapangan, harga_per_jam FROM lapangan WHERE status='aktif' ORDER BY nama_lapangan");
$qProd = $conn->query("SELECT * FROM produk WHERE status='aktif' ORDER BY nama_produk ASC");

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="content-wrapper animate__animated animate__fadeIn">
  
  <section class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="mb-2"><i class="fas fa-calendar-check me-2"></i> Manajemen Booking</h1>
            
            <button onclick="window.location.reload()" class="btn btn-secondary btn-sm shadow-sm">
                <i class="fas fa-sync-alt"></i> Refresh Data
            </button>
        </div>
        <p class="text-muted">Kelola jadwal, data booking, dan lihat statistik berdasarkan periode.</p>
    </div>
  </section>

  <section class="content">
    
    <div class="row g-3 mb-4">
        <div class="col-md-4 col-sm-6">
            <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #0e5c91 0%, #1e88e5 100%); color: white;">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-white bg-opacity-25 rounded-circle p-3 me-3">
                        <i class="fas fa-calendar-alt fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 opacity-75">Total Booking (Periode Ini)</h6>
                        <h2 class="mb-0 fw-bold"><?= $statTotal ?></h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #0e5c91 0%, #1e88e5 100%); color: white;">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-white bg-opacity-25 rounded-circle p-3 me-3">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 opacity-75">Booking Sukses (Periode Ini)</h6>
                        <h2 class="mb-0 fw-bold"><?= $statSuccess ?></h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-12">
            <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #0e5c91 0%, #1e88e5 100%); color: white;">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-white bg-opacity-25 rounded-circle p-3 me-3">
                        <i class="fas fa-wallet fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 opacity-75">Pendapatan (Periode Ini)</h6>
                        <h3 class="mb-0 fw-bold">Rp <?= number_format($statRevenue, 0, ',', '.') ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="booking.php" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1 fw-bold">Tanggal Mulai</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $filter_start ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1 fw-bold">Tanggal Akhir</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $filter_end ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1 fw-bold">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="all" <?= $filter_status == 'all' ? 'selected' : '' ?>>Semua</option>
                        <option value="menunggu" <?= $filter_status == 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                        <option value="belum lunas" <?= $filter_status == 'belum lunas' ? 'selected' : '' ?>>Belum Lunas</option>
                        <option value="disetujui" <?= $filter_status == 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                        <option value="selesai" <?= $filter_status == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                        <option value="ditolak" <?= $filter_status == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                        <option value="dibatalkan" <?= $filter_status == 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i> Filter</button>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="button" class="btn btn-sm text-white shadow-sm" 
                            style="background: linear-gradient(90deg, #0e5c91, #2196f3); border: none;"
                            data-bs-toggle="collapse" data-bs-target="#formTambahBooking">
                        <i class="fas fa-plus me-1"></i> Booking Baru
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="collapse mb-4" id="formTambahBooking">
      <div class="card card-primary shadow-lg border-0">
        <div class="card-header text-white" style="background: linear-gradient(90deg,#0e5c91,#2196f3);">
          <h3 class="card-title mb-0"><i class="fas fa-user-plus"></i> Form Booking Walk-In</h3>
        </div>

        <form method="POST" id="formWalkinBooking">
          <input type="hidden" name="action" value="save_walkin_booking">
          <div class="card-body row g-3">
            
            <div class="col-md-6">
              <label class="form-label fw-bold">Nama Customer <span class="text-danger">*</span></label>
              <input type="text" name="nama_customer" class="form-control" placeholder="Nama customer" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">No HP Customer</label>
              <input type="text" name="no_hp_customer" class="form-control" placeholder="08xxxxxxxxxx">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-bold">Lapangan <span class="text-danger">*</span></label>
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
              <label class="form-label fw-bold">Tanggal Main <span class="text-danger">*</span></label>
              <input type="date" name="tanggal" id="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="col-md-12" id="slotContainer" style="display:none;">
              <label class="form-label fw-bold">Pilih Slot Jam <span class="text-danger">*</span></label>
              <div id="slotList" class="d-flex flex-wrap gap-2"></div>
            </div>

            <div class="col-md-12 mt-4">
              <div class="card border-info">
                  <div class="card-header bg-info text-white">
                      <h5 class="card-title mb-0 text-white small"><i class="fas fa-box-open"></i> Tambah Produk / Makanan / Minuman</h5>
                  </div>
                  <div class="card-body p-2">
                      <div class="row g-2" style="max-height: 200px; overflow-y: auto;">
                        <?php if($qProd->num_rows > 0): ?>
                            <?php while($p = $qProd->fetch_assoc()): ?>
                                <div class="col-md-4 col-sm-6">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light w-50 text-truncate" title="<?= htmlspecialchars($p['nama_produk']) ?>">
                                            <?= htmlspecialchars($p['nama_produk']) ?>
                                        </span>
                                        <span class="input-group-text bg-white">
                                            Rp<?= number_format($p['harga'],0,',','.') ?>
                                        </span>
                                        <input type="number" name="produk_qty[<?= $p['id_produk'] ?>]" class="form-control produk-input text-center" min="0" placeholder="0" data-harga="<?= $p['harga'] ?>">
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12 text-center text-muted">Belum ada data produk.</div>
                        <?php endif; ?>
                      </div>
                  </div>
              </div>
            </div>

            <div class="col-md-4 mt-3">
              <label class="form-label fw-bold">Total Lapangan</label>
              <input type="text" id="subtotal_lapangan" class="form-control bg-light" readonly>
            </div>
            <div class="col-md-4 mt-3">
              <label class="form-label fw-bold">Total Produk</label>
              <input type="text" id="subtotal_produk" class="form-control bg-light" readonly>
            </div>
            <div class="col-md-4 mt-3">
              <label class="form-label fw-bold text-primary">GRAND TOTAL</label>
              <input type="text" id="grand_total_display" class="form-control fw-bold text-white bg-success" style="font-size: 1.2rem;" readonly>
            </div>
          </div>

          <div class="card-footer text-end bg-light">
            <button type="submit" class="btn btn-success btn-lg shadow-sm">
              <i class="fas fa-save me-1"></i> Simpan & Bayar
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="card shadow-lg border-0">
      <div class="card-header text-white" style="background: linear-gradient(90deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);">
        <h3 class="card-title mb-0"><i class="fas fa-list me-2"></i> Daftar Booking (<?= date('d/m/Y', strtotime($filter_start)) ?> - <?= date('d/m/Y', strtotime($filter_end)) ?>)</h3>
      </div>
      <div class="card-body table-responsive p-0">
        <table id="tblBooking" class="table table-bordered table-striped table-hover align-middle w-100 mb-0">
          <thead class="bg-light text-center">
            <tr>
              <th style="width: 5%">No</th>
              <th style="width: 15%">Pemesan</th>
              <th style="width: 25%">Info Produk</th> 
              <th style="width: 10%">Lapangan</th>
              <th style="width: 10%">Tanggal</th>
              <th style="width: 10%">Jam</th>
              <th style="width: 15%">Total</th>
              <th style="width: 5%">Status</th>
              <th style="width: 5%">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            // 4.1 QUERY DATA DENGAN FILTER TANGGAL
            $whereClause = "WHERE b.tanggal BETWEEN '$filter_start' AND '$filter_end'";
            
            if ($filter_status !== 'all') {
                $whereClause .= " AND b.status = '$filter_status'";
            }

            $sql = "
                SELECT 
                  b.id_booking, 
                  b.tanggal, 
                  b.total_amount, 
                  b.status, 
                  b.info_produk, 
                  b.tipe_booking,
                  u.nama AS nama_pemesan,
                  l.nama_lapangan
                FROM booking b
                LEFT JOIN users u ON b.id_user = u.id_user
                LEFT JOIN lapangan l ON b.id_lapangan = l.id_lapangan
                $whereClause
                ORDER BY b.tanggal DESC, b.created_at DESC
            ";
            
            $result = $conn->query($sql);
            
            if (!$result) {
                 echo '</tbody></table><div class="alert alert-danger m-3">Error Database: '.$conn->error.'</div><table class="d-none"><tbody>';
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

                    // AMBIL JAM (Looping Manual)
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
                        $jam_list[] = '<span class="badge bg-white text-dark border border-secondary border-opacity-25 fw-normal mb-1 shadow-sm" style="font-size:0.85rem;">' . $jam_str . '</span>';
                    }
                    $jam_display = !empty($jam_list) ? implode(' ', $jam_list) : '<span class="text-muted">-</span>';
            ?>
              <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama_pemesan'] ?? 'Guest/Hapus') ?></div>
                    <?php if($row['tipe_booking'] == 'manual'): ?>
                        <span class="badge bg-info text-white" style="font-size: 0.7rem;">Walk-in</span>
                    <?php endif; ?>
                </td>
                <td class="align-middle">
                    <?php if(!empty($row['info_produk'])): ?>
                        <small class="text-dark d-block" style="line-height: 1.4;">
                            <?= htmlspecialchars($row['info_produk']) ?>
                        </small>
                    <?php else: ?>
                        <span class="text-muted text-center d-block small">-</span>
                    <?php endif; ?>
                </td>
                <td class="text-center fw-semibold"><?= htmlspecialchars($row['nama_lapangan'] ?? '-') ?></td>
                <td class="text-center"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                
                <td class="text-center" style="vertical-align: middle;">
                    <div class="d-flex flex-column align-items-center">
                        <?= $jam_display ?>
                    </div>
                </td>
                
                <td class="text-end fw-bold text-nowrap">
                    Rp <?= number_format($row['total_amount'], 0, ',', '.') ?>
                </td>
                
                <td class="text-center"><span class="<?= $badgeBooking ?>"><?= ucfirst($row['status']) ?></span></td>
                
                <td class="text-center">
                  <a href="booking_detail.php?id=<?= $row['id_booking'] ?>" 
                     class="btn btn-sm text-white shadow-sm" 
                     style="background: linear-gradient(90deg, #0e5c91, #2196f3); border: none;">
                     <i class="fas fa-eye"></i> Detail
                  </a>
                </td>
              </tr>
            <?php 
                endwhile; 
            } else {
                echo '<tr><td colspan="9" class="text-center py-4 text-muted">Tidak ada data booking pada periode ini.</td></tr>';
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>

<?php include('../includes/footer.php'); ?>

<script>
// --- TOAST NOTIFICATION ---
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

// --- LOGIKA FORM BOOKING (CLIENT SIDE) ---
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
    const p = t.split(':'); return parseInt(p[0])*60 + parseInt(p[1]);
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
    
    if (!idL || !tgl) { slotContainer.style.display = 'none'; return; }
    slotContainer.style.display = 'block';
    slotList.innerHTML = '<div class="spinner-border spinner-border-sm text-primary"></div> Memuat...';

    // Pastikan booking_get_slot.php tersedia dan mengembalikan JSON
    fetch(`booking_get_slot.php?id_lapangan=${idL}&tanggal=${tgl}`)
      .then(res => res.json())
      .then(data => {
        slotList.innerHTML = ''; 
        if (data.status !== 'success' || !data.slots.length) {
           slotList.innerHTML = '<div class="alert alert-info py-1 px-3">Tidak ada slot.</div>';
           return;
        }

        data.slots.forEach(s => {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'btn btn-outline-primary m-1 flex-fill';
          btn.style.minWidth = "100px";
          btn.dataset.idDetail = s.id_detail;
          btn.dataset.idJw = s.id_jadwal_waktu;
          btn.dataset.jamMulai = s.jam_mulai;
          btn.dataset.jamSelesai = s.jam_selesai;

          if (s.status !== 'tersedia') {
            btn.disabled = true;
            btn.className = 'btn btn-secondary m-1 flex-fill opacity-50';
            btn.innerHTML = `<i class="fas fa-lock"></i> ${s.jam_mulai}`;
          } else {
            btn.innerHTML = `${s.jam_mulai}-${s.jam_selesai}`;
            btn.addEventListener('click', () => toggleSlot(btn));
          }
          slotList.appendChild(btn);
        });
        calculateTotal();
      })
      .catch(err => {
          slotList.innerHTML = '<span class="text-danger">Gagal memuat slot.</span>';
          console.error(err);
      });
  }

  function toggleSlot(btn) {
    const idDetail = btn.dataset.idDetail;
    const idx = selectedSlots.findIndex(x => x.id_detail == idDetail);
    
    if (idx !== -1) {
      selectedSlots.splice(idx, 1);
      btn.classList.remove('btn-success','active','text-white');
      btn.classList.add('btn-outline-primary');
    } else {
      selectedSlots.push({
          id_detail: idDetail, id_jw: btn.dataset.idJw, 
          jam_mulai: btn.dataset.jamMulai, jam_selesai: btn.dataset.jamSelesai
      });
      btn.classList.remove('btn-outline-primary');
      btn.classList.add('btn-success','active','text-white');
    }
    selectedSlots.sort((a,b) => timeToMinutes(a.jam_mulai) - timeToMinutes(b.jam_mulai));
    calculateTotal();
  }

  function updateHiddenInputs() {
    form.querySelectorAll("input[name='slot_ids[]']").forEach(e => e.remove());
    selectedSlots.forEach(slt => {
      const in1 = document.createElement('input');
      in1.type = 'hidden'; in1.name = 'slot_ids[]'; in1.value = slt.id_detail;
      form.appendChild(in1);
    });
  }

  idLapangan.addEventListener('change', loadSlots);
  tanggal.addEventListener('change', loadSlots);
  productInputs.forEach(input => {
      input.addEventListener('input', calculateTotal);
      input.addEventListener('change', calculateTotal);
  });

  if(idLapangan.value && tanggal.value) loadSlots();

  form.addEventListener('submit', (e) => {
    e.preventDefault();

    if (selectedSlots.length === 0) {
      Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih minimal 1 slot jam!' });
      return false;
    }
    
    Swal.fire({
        title: 'Konfirmasi Pembayaran',
        text: "Pastikan data sudah benar. Transaksi akan langsung berstatus LUNAS.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Proses!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            const btn = form.querySelector('button[type="submit"]');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            btn.disabled = true;
            form.submit();
        }
    });
  });
});
</script> 