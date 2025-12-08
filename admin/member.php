<?php
require_once 'auth_check.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Jakarta');

// --- (1) AMBIL DATA HARGA MEMBER UNTUK JAVASCRIPT ---
$harga_per_jam_member = 0;
if ($qHarga = mysqli_query($conn, "SELECT harga_per_jam_member FROM lapangan WHERE status='aktif' LIMIT 1")) {
    if ($h = mysqli_fetch_assoc($qHarga)) {
        $harga_per_jam_member = floatval($h['harga_per_jam_member']);
    }
    mysqli_free_result($qHarga);
}

// ✅ (2) PROSES VALIDASI MEMBER (PENDING → AKTIF)
if (isset($_GET['action']) && $_GET['action'] === 'validate' && isset($_GET['id'])) {
    try {
        $id_member = intval($_GET['id']);
        
        $conn->begin_transaction();
        
        // Cek apakah member ada dan statusnya pending
        $stmt = $conn->prepare("SELECT m.id_member, m.id_user, m.id_lapangan, m.total_bayar, m.tanggal_mulai, l.nama_lapangan 
                                 FROM member m 
                                 LEFT JOIN lapangan l ON m.id_lapangan = l.id_lapangan 
                                 WHERE m.id_member = ? AND m.status = 'pending'");
        $stmt->bind_param("i", $id_member);
        $stmt->execute();
        $member = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$member) {
            throw new Exception("Member tidak ditemukan atau sudah divalidasi.");
        }
        
        // Update status member menjadi aktif
        $stmt = $conn->prepare("UPDATE member SET status='aktif', updated_at=NOW() WHERE id_member=?");
        $stmt->bind_param("i", $id_member);
        $stmt->execute();
        $stmt->close();
        
        // Update role user menjadi 'member'
        $stmt = $conn->prepare("UPDATE users SET role='member', updated_at=NOW() WHERE id_user=?");
        $stmt->bind_param("i", $member['id_user']);
        $stmt->execute();
        $stmt->close();
        
        // Update status member_jadwal menjadi aktif
        $stmt = $conn->prepare("UPDATE member_jadwal SET status='aktif', updated_at=NOW() WHERE id_member=?");
        $stmt->bind_param("i", $id_member);
        $stmt->execute();
        $stmt->close();
        
        // Update status booking yang terkait dengan member ini menjadi 'disetujui'
        // Cari booking berdasarkan id_user, id_lapangan, dan tanggal yang sesuai
        $stmt = $conn->prepare("
            UPDATE booking 
            SET status='disetujui', 
                payment_status='lunas',
                approved_by=?,
                approved_at=NOW(),
                updated_at=NOW() 
            WHERE id_user=? 
            AND id_lapangan=? 
            AND tanggal=?
            AND tipe_booking='member'
            AND status IN ('menunggu', 'belum lunas')
        ");
        $admin_id = $_SESSION['id_user'] ?? 1; // ID admin yang melakukan validasi
        $stmt->bind_param("iiis", $admin_id, $member['id_user'], $member['id_lapangan'], $member['tanggal_mulai']);
        $stmt->execute();
        $affected_bookings = $stmt->affected_rows;
        $stmt->close();
        
        // Insert keuangan otomatis
        $stmt = $conn->prepare("
            INSERT INTO keuangan 
            (tanggal, jenis, kategori, keterangan, jumlah, sumber, booking_id, created_at, updated_at)
            VALUES (CURDATE(), 'pemasukan', 'Membership', ?, ?, 'Pelunasan', NULL, NOW(), NOW())
        ");
        $ket = "Pembayaran Member ID $id_member untuk Lapangan: " . $member['nama_lapangan'];
        $stmt->bind_param("sd", $ket, $member['total_bayar']);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        
        $booking_msg = $affected_bookings > 0 ? " & $affected_bookings booking disetujui" : "";
        $_SESSION['toast_success'] = "✅ Member berhasil divalidasi dan diaktifkan! ID: $id_member" . $booking_msg;
        header("Location: member.php");
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['toast_error'] = "❌ ERROR: " . $e->getMessage();
        error_log("Member Validation Error: " . $e->getMessage());
        header("Location: member.php");
        exit;
    }
}

// ✅ (3) PROSES POST MEMBER
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id_user = intval($_POST['id_user']);
        $id_lapangan = intval($_POST['id_lapangan']);
        $durasi_bulan = intval($_POST['durasi_bulan']);
        $tanggal_mulai = $_POST['tanggal_mulai'] ?? null;
        $method = $_POST['method'] ?? '';
        $total_bayar = floatval($_POST['total_bayar_value'] ?? 0);

        if (!$id_user || !$id_lapangan || !$durasi_bulan || !$tanggal_mulai || $total_bayar <= 0) {
            throw new Exception("Data tidak lengkap atau Total Bayar belum terhitung/tidak valid!");
        }

        $conn->begin_transaction();

        // Ambil validasi lapangan
        $stmt = $conn->prepare("SELECT nama_lapangan FROM lapangan WHERE id_lapangan=? AND status='aktif'");
        $stmt->bind_param("i", $id_lapangan);
        $stmt->execute();
        $cekLap = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$cekLap) throw new Exception("Lapangan tidak ditemukan atau nonaktif.");

        // Hitung tanggal berakhir
        $tanggal_berakhir = date('Y-m-d', strtotime("+$durasi_bulan month", strtotime($tanggal_mulai)));

        // Insert ke member
        $stmt = $conn->prepare("
            INSERT INTO member 
            (id_user, id_lapangan, durasi_bulan, tanggal_mulai, tanggal_berakhir, total_bayar, method, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'aktif', NOW(), NOW())
        ");
        $stmt->bind_param("iiissds", $id_user, $id_lapangan, $durasi_bulan, $tanggal_mulai, $tanggal_berakhir, $total_bayar, $method);
        $stmt->execute();
        $id_member = $conn->insert_id;
        $stmt->close();

        if (!$id_member) throw new Exception("Gagal menyimpan data member.");

        // Update role user menjadi 'member'
        $stmt = $conn->prepare("UPDATE users SET role='member', updated_at=NOW() WHERE id_user=?");
        $stmt->bind_param("i", $id_user);
        $stmt->execute();
        $stmt->close();

        // Insert keuangan otomatis
        $stmt = $conn->prepare("
            INSERT INTO keuangan 
            (tanggal, jenis, kategori, keterangan, jumlah, sumber, booking_id, created_at, updated_at)
            VALUES (CURDATE(), 'pemasukan', 'Membership', ?, ?, 'Pelunasan', NULL, NOW(), NOW())
        ");
        $ket = "Pembayaran Member ID $id_member untuk Lapangan: " . $cekLap['nama_lapangan'];
        $stmt->bind_param("sd", $ket, $total_bayar);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        $_SESSION['toast_success'] = "✅ Member berhasil diaktifkan! ID: $id_member";
        header("Location: member.php");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['toast_error'] = "❌ ERROR: " . $e->getMessage();
        error_log("Member Activation Error: " . $e->getMessage() . " | MySQL Error: " . $conn->error);
        header("Location: member.php");
        exit;
    }
}

// (4) PERIKSA MEMBER EXPIRED
$conn->query("
    UPDATE member m
    JOIN users u ON m.id_user = u.id_user
    SET m.status='nonaktif', u.role='user'
    WHERE m.tanggal_berakhir < CURDATE() AND m.status='aktif'
");

// Update member_jadwal yang expired
$conn->query("
    UPDATE member_jadwal mj
    JOIN member m ON mj.id_member = m.id_member
    SET mj.status='nonaktif'
    WHERE m.status='nonaktif' AND mj.status='aktif'
");

// ---------------- DATA FORM ----------------
$qUsers = mysqli_query($conn, "SELECT id_user, nama FROM users WHERE role='user' AND status='aktif' ORDER BY nama ASC");

// Ambil daftar lapangan aktif
$qLapangan = mysqli_query($conn, "SELECT id_lapangan, nama_lapangan FROM lapangan WHERE status='aktif' ORDER BY nama_lapangan ASC");

// ---------------- DATA TABLE ----------------
$qMember = mysqli_query($conn, "
    SELECT m.id_member, u.nama, l.nama_lapangan, m.durasi_bulan, m.tanggal_mulai, m.tanggal_berakhir, m.total_bayar, m.status, m.bukti_pembayaran, m.method
    FROM member m
    LEFT JOIN users u ON m.id_user = u.id_user
    LEFT JOIN lapangan l ON m.id_lapangan = l.id_lapangan
    ORDER BY 
        CASE 
            WHEN m.status = 'pending' THEN 1
            WHEN m.status = 'aktif' THEN 2
            ELSE 3
        END,
        m.id_member DESC
");

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<style>
/* Professional Member Management Styling */
:root {
    --primary-gradient: linear-gradient(135deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
    --success-gradient: linear-gradient(135deg, #28a745 0%, #218838 100%);
    --warning-gradient: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    --card-shadow: 0 4px 20px rgba(14, 92, 145, 0.15);
    --card-hover-shadow: 0 8px 30px rgba(14, 92, 145, 0.25);
}

.animate-fade-in {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Content Header Enhancement */
.content-header {
    margin-bottom: 2rem;
}

.content-header h1 {
    font-size: 1.875rem;
    font-weight: 700;
    color: #2c3e50;
    letter-spacing: -0.5px;
    margin: 0;
}

.content-header h1 i {
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.content-header .btn-primary {
    background: var(--primary-gradient);
    border: none;
    border-radius: 10px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    letter-spacing: 0.3px;
    box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
    transition: all 0.3s ease;
}

.content-header .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(33, 150, 243, 0.4);
}

/* Alert Enhancements */
.alert {
    border-radius: 12px;
    border: none;
    padding: 1rem 1.5rem;
    font-size: 0.938rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 1.5rem;
}

.alert-success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
}

.alert-danger {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    color: #721c24;
}

/* Form Card Enhancement */
.card-primary {
    border-radius: 20px;
    border: none;
    box-shadow: var(--card-shadow);
    overflow: hidden;
    margin-bottom: 2rem;
    transition: all 0.3s ease;
}

.card-primary:hover {
    box-shadow: var(--card-hover-shadow);
}

.card-primary .card-header {
    background: var(--primary-gradient);
    padding: 1.75rem;
    border: none;
}

.card-primary .card-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    letter-spacing: 0.3px;
}

.card-primary .card-body {
    padding: 2rem;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fc 100%);
}

/* Form Elements Consistent Height */
.form-control, .form-select {
    height: 48px;
    padding: 0.75rem 1rem;
    box-sizing: border-box;
    border: 2px solid #e3e6f0;
    border-radius: 10px;
    font-size: 0.938rem;
    transition: all 0.3s ease;
    background: #ffffff;
}

.form-control:focus, .form-select:focus {
    border-color: #2196f3;
    box-shadow: 0 0 0 4px rgba(33, 150, 243, 0.1);
    background: #ffffff;
}

/* Select2 Bootstrap 4 Height Match */
.select2-container--bootstrap4 .select2-selection--single {
    height: 48px !important;
    padding: 0.5rem 1rem !important;
    box-sizing: border-box;
    border: 2px solid #e3e6f0 !important;
    border-radius: 10px !important;
    transition: all 0.3s ease;
}

.select2-container--bootstrap4 .select2-selection--single:focus,
.select2-container--bootstrap4.select2-container--focus .select2-selection--single {
    border-color: #2196f3 !important;
    box-shadow: 0 0 0 4px rgba(33, 150, 243, 0.1) !important;
}

.select2-container--bootstrap4 .select2-selection__rendered {
    line-height: 48px !important;
    padding-left: 0 !important;
    color: #495057;
}

.select2-container--bootstrap4 .select2-selection__arrow {
    height: 46px !important;
}

/* Form Labels */
label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #5a5c69;
    margin-bottom: 0.5rem;
    letter-spacing: 0.3px;
}

/* Button Enhancements */
.btn-success {
    background: var(--success-gradient);
    border: none;
    border-radius: 10px;
    padding: 0.75rem 1.75rem;
    font-weight: 600;
    letter-spacing: 0.3px;
    transition: all 0.3s ease;
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
}

.btn-success:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.btn-warning {
    background: var(--warning-gradient);
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-weight: 600;
    color: #fff;
    transition: all 0.3s ease;
}

.btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
    color: #fff;
}

.card-footer {
    background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
    border-top: 2px solid #e3e6f0;
    padding: 1.5rem 2rem;
}

/* Table Card Enhancement */
.card {
    border-radius: 20px;
    border: none;
    box-shadow: var(--card-shadow);
    overflow: hidden;
}

.card .card-header {
    background: var(--primary-gradient);
    padding: 1.75rem;
    border: none;
}

.card .card-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    letter-spacing: 0.3px;
}

/* Enhanced Table Styling */
#tblMember {
    margin: 0;
}

#tblMember thead {
    background: linear-gradient(135deg, #f8f9fc 0%, #f1f3f9 100%);
}

#tblMember thead th {
    font-size: 0.813rem;
    font-weight: 700;
    color: #5a5c69;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1rem;
    border-bottom: 2px solid #e3e6f0;
}

#tblMember tbody tr {
    transition: all 0.2s ease;
}

#tblMember tbody tr:hover {
    background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
    transform: scale(1.01);
    box-shadow: 0 2px 8px rgba(14, 92, 145, 0.1);
}

#tblMember tbody td {
    padding: 1rem;
    vertical-align: middle;
    font-size: 0.875rem;
    border-bottom: 1px solid #f1f3f9;
}

/* Badge Enhancements */
.badge {
    padding: 0.5rem 0.875rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 8px;
    letter-spacing: 0.3px;
}

.badge.bg-success {
    background: var(--success-gradient) !important;
}

.badge.bg-warning {
    background: var(--warning-gradient) !important;
}

.badge.bg-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%) !important;
}

/* Highlight for pending row */
tr.pending-row {
    background: linear-gradient(135deg, #fff9e6 0%, #fffbf0 100%) !important;
}

/* Input Group Enhancement */
.input-group .form-control {
    border-left: none;
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

.input-group-text {
    background: linear-gradient(135deg, #f1f3f9 0%, #e9ecf4 100%);
    border: 2px solid #e3e6f0;
    border-right: none;
    color: #4e73df;
    border-top-left-radius: 10px;
    border-bottom-left-radius: 10px;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .card-primary .card-body {
        padding: 1.5rem;
    }
    
    .content-header h1 {
        font-size: 1.5rem;
    }
    
    .form-control, .form-select,
    .select2-container--bootstrap4 .select2-selection--single {
        height: 44px !important;
    }
}

/* Modal Image Styling */
.modal-image {
    cursor: pointer;
    transition: transform 0.2s ease;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.modal-image:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.modal-content {
    border-radius: 20px;
    border: none;
    overflow: hidden;
}

.modal-header {
    background: var(--primary-gradient);
    color: white;
    border: none;
    padding: 1.5rem;
}

.modal-body {
    padding: 2rem;
    text-align: center;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fc 100%);
}

.modal-body img {
    max-width: 100%;
    height: auto;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.btn-close {
    filter: brightness(0) invert(1);
}
</style>

<div class="content-wrapper animate-fade-in">

<section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
        <div class="mb-3 mb-md-0">
            <h1><i class="fas fa-users me-2"></i> Data Member</h1>
            <p class="text-muted mb-0 mt-2">Kelola membership dan aktivasi anggota</p>
        </div>
        <button class="btn btn-primary shadow" data-bs-toggle="collapse" data-bs-target="#formTambah">
            <i class="fas fa-plus-circle me-1"></i> Tambah Member
        </button>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        
        <?php 
        if (!empty($_SESSION['toast_error'])):
            echo '<div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i>'.$_SESSION['toast_error'].'
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>';
            unset($_SESSION['toast_error']);
        endif;

        if (!empty($_SESSION['toast_success'])):
            echo '<div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>'.$_SESSION['toast_success'].'
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>';
            unset($_SESSION['toast_success']);
        endif;
        ?>

        <!-- FORM TAMBAH MEMBER -->
        <div class="collapse" id="formTambah">
            <div class="card card-primary">
                <div class="card-header text-white">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-user-plus me-2"></i> Aktivasi Member Baru
                    </h3>
                </div>

                <form method="POST" id="formMember" class="needs-validation" novalidate>
                    <div class="card-body row g-4">

                        <!-- ROW 1: tiga kolom -->
                        <div class="col-md-4">
                            <label for="id_user_select">Pilih User</label>
                            <select name="id_user" id="id_user_select" class="form-select select2-bootstrap4" required>
                                <option value="">-- Pilih User --</option>
                                <?php while($u=mysqli_fetch_assoc($qUsers)): ?>
                                    <option value="<?= $u['id_user'] ?>"><?= htmlspecialchars($u['nama']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <div class="invalid-feedback">Pilih user terlebih dahulu.</div>
                        </div>

                        <div class="col-md-4">
                            <label for="id_lapangan">Pilih Lapangan</label>
                            <select name="id_lapangan" id="id_lapangan" class="form-select select2-bootstrap4" required>
                                <option value="">-- Pilih Lapangan --</option>
                                <?php while($l=mysqli_fetch_assoc($qLapangan)): ?>
                                    <option value="<?= $l['id_lapangan'] ?>"><?= htmlspecialchars($l['nama_lapangan']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <div class="invalid-feedback">Pilih lapangan terlebih dahulu.</div>
                        </div>

                        <div class="col-md-4">
                            <label for="durasi_bulan">Durasi Membership</label>
                            <select name="durasi_bulan" id="durasi_bulan" class="form-select select2-bootstrap4" required>
                                <option value="1">1 Bulan</option>
                                <option value="2">2 Bulan</option>
                                <option value="3">3 Bulan</option>
                            </select>
                            <div class="invalid-feedback">Pilih durasi membership.</div>
                        </div>

                        <!-- ROW 2: tiga kolom -->
                        <div class="col-md-4">
                            <label for="tanggal_mulai">Tanggal Mulai</label>
                            <input type="date" 
                                   id="tanggal_mulai" 
                                   name="tanggal_mulai" 
                                   class="form-control" 
                                   value="<?= date('Y-m-d') ?>" 
                                   required>
                            <div class="invalid-feedback">Pilih tanggal mulai.</div>
                        </div>

                        <div class="col-md-4">
                            <label for="method">Metode Pembayaran</label>
                            <select name="method" id="method" class="form-select select2-bootstrap4" required>
                                <option value="tunai">💵 Tunai</option>
                                <option value="qris">📱 QRIS</option>
                                <option value="bank_transfer">🏦 Transfer Bank</option>
                            </select>
                            <div class="invalid-feedback">Pilih metode pembayaran.</div>
                        </div>

                        <div class="col-md-4">
                            <label for="total_bayar_display">Total Bayar</label>
                            <input type="text" 
                                   id="total_bayar_display" 
                                   class="form-control fw-bold text-end" 
                                   readonly 
                                   style="background: #e9ecf4; color: #2196f3;">
                            <input type="hidden" id="total_bayar_value" name="total_bayar_value">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> Perhitungan: Rp <?= number_format($harga_per_jam_member,0,',','.') ?> × 4 jam/bulan
                            </small>
                        </div>

                    </div>

                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i> Simpan & Aktivasi
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- DAFTAR MEMBER -->
        <div class="card shadow-lg border-0">
            <div class="card-header text-white">
                <h3 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i> Daftar Member Terdaftar
                </h3>
            </div>

            <div class="card-body table-responsive p-0">
                <table id="tblMember" class="table table-hover align-middle w-100 mb-0">
                    <thead class="text-center">
                        <tr>
                            <th>No</th>
                            <th>Nama Member</th>
                            <th>Lapangan</th>
                            <th>Durasi</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Berakhir</th>
                            <th>Total Bayar</th>
                            <th>Metode</th>
                            <th>Bukti Pembayaran</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no=1; while($m=mysqli_fetch_assoc($qMember)): ?>
                        <tr class="<?= $m['status']=='pending' ? 'pending-row' : '' ?>">
                            <td class="text-center fw-semibold text-muted"><?= $no++ ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($m['nama']) ?></td>
                            <td class="text-center"><?= htmlspecialchars($m['nama_lapangan']) ?></td>
                            <td class="text-center"><span class="badge bg-primary"><?= $m['durasi_bulan'] ?> Bulan</span></td>
                            <td class="text-center"><?= date('d M Y', strtotime($m['tanggal_mulai'])) ?></td>
                            <td class="text-center"><?= date('d M Y', strtotime($m['tanggal_berakhir'])) ?></td>
                            <td class="text-end fw-bold text-success">Rp <?= number_format($m['total_bayar'],0,',','.') ?></td>
                            <td class="text-center">
                                <?php 
                                $method_icon = [
                                    'tunai' => '💵',
                                    'qris' => '📱',
                                    'bank_transfer' => '🏦'
                                ];
                                $method_label = [
                                    'tunai' => 'Tunai',
                                    'qris' => 'QRIS',
                                    'bank_transfer' => 'Transfer'
                                ];
                                echo $method_icon[$m['method']] ?? '';
                                echo ' ' . ($method_label[$m['method']] ?? ucfirst($m['method']));
                                ?>
                            </td>
                            <td class="text-center">
                                <?php if(!empty($m['bukti_pembayaran']) && file_exists('../uploads/bukti_pembayaran/' . $m['bukti_pembayaran'])): ?>
                                    <img 
                                        src="../uploads/bukti_pembayaran/<?= htmlspecialchars($m['bukti_pembayaran']) ?>" 
                                        class="modal-image" 
                                        width="60" 
                                        height="60" 
                                        style="object-fit: cover; cursor: pointer;"
                                        onclick="showImageModal('<?= htmlspecialchars($m['bukti_pembayaran']) ?>', '<?= htmlspecialchars($m['nama']) ?>')"
                                        title="Klik untuk memperbesar">
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-<?= $m['status']=='aktif'?'success':($m['status']=='pending'?'warning':'secondary') ?>">
                                    <?= ucfirst($m['status']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if($m['status']=='pending' || $m['status']=='aktif'): ?>
                                    <button 
                                        class="btn btn-<?= $m['status']=='pending'?'warning':'success' ?> btn-sm"
                                        onclick="validateMember(<?= $m['id_member'] ?>)"
                                        title="<?= $m['status']=='pending'?'Validasi & Aktifkan Member':'Member Sudah Aktif' ?>"
                                        <?= $m['status']=='aktif'?'disabled':'' ?>>
                                        <i class="fas fa-<?= $m['status']=='pending'?'check-circle':'check-double' ?> me-1"></i> 
                                        <?= $m['status']=='pending'?'Validasi':'Aktif' ?>
                                    </button>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>
</div>

<!-- Modal untuk Menampilkan Gambar Bukti Pembayaran -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">
                    <i class="fas fa-receipt me-2"></i> Bukti Pembayaran
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3 fw-semibold" id="memberName"></p>
                <img id="modalImage" src="" alt="Bukti Pembayaran" class="img-fluid">
            </div>
            <div class="modal-footer">
                <a id="downloadImage" href="" download class="btn btn-primary">
                    <i class="fas fa-download me-1"></i> Download
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<?php 
// free results and include footer
mysqli_free_result($qLapangan);
mysqli_free_result($qUsers);
mysqli_free_result($qMember);
include('../includes/footer.php'); 
?>

<script>
// Function untuk validasi member - HARUS DI LUAR $(document).ready()
function validateMember(id) {
    console.log('validateMember called with id:', id);
    
    // Cek apakah Swal tersedia
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 tidak tersedia!');
        // Fallback ke confirm native browser
        if (confirm('Validasi member ini?\n\nMember akan diaktifkan dan dicatat di keuangan.')) {
            window.location.href = `member.php?action=validate&id=${id}`;
        }
        return;
    }
    
    Swal.fire({
        title: 'Validasi Member?',
        text: 'Member akan diaktifkan dan dicatat di keuangan',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-check-circle me-1"></i> Ya, Validasi!',
        cancelButtonText: '<i class="fas fa-times me-1"></i> Batal',
        customClass: {
            confirmButton: 'btn btn-warning',
            cancelButton: 'btn btn-secondary'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Tampilkan loading
            Swal.fire({
                title: 'Memproses...',
                text: 'Sedang memvalidasi member',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Redirect ke URL validasi
            window.location.href = `member.php?action=validate&id=${id}`;
        }
    });
}

// Hitung total bayar berdasarkan harga per jam (asumsi 4 jam/bln)
function hitungTotal(){
    let hargaPerJam = <?= json_encode($harga_per_jam_member) ?>;
    let durasi = parseInt($('#durasi_bulan').val()) || 0;
    let total = hargaPerJam * 4 * durasi;
    $('#total_bayar_display').val("Rp " + total.toLocaleString('id-ID'));
    $('#total_bayar_value').val(total);
}

// Function untuk menampilkan modal gambar bukti pembayaran
function showImageModal(imageName, memberName) {
    console.log('showImageModal called:', imageName, memberName);
    
    const imagePath = '../uploads/bukti_pembayaran/' + imageName;
    
    // Set gambar dan informasi member
    document.getElementById('modalImage').src = imagePath;
    document.getElementById('memberName').textContent = 'Member: ' + memberName;
    document.getElementById('downloadImage').href = imagePath;
    document.getElementById('downloadImage').download = 'bukti_pembayaran_' + imageName;
    
    // Tampilkan modal menggunakan Bootstrap 5
    const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
    imageModal.show();
}

$(document).ready(function() {
    console.log('jQuery ready');
    console.log('SweetAlert2 available:', typeof Swal !== 'undefined');
    
    // Inisialisasi select2 dengan tema bootstrap4
    $('#id_user_select, #id_lapangan, #durasi_bulan, #method').select2({
        theme: 'bootstrap4',
        placeholder: "Pilih...",
        width: '100%',
        allowClear: true
    });

    // Inisialisasi DataTable
    // $('#tblMember').DataTable({
    //     responsive: true,
    //     language: {
    //         url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
    //     }
    // });

    // Setup hitung awal
    hitungTotal();

    // Validasi bootstrap
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()

    // Auto hide alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // trigger hitung saat durasi berubah
    $('#durasi_bulan').on('change', hitungTotal);
    
    // juga trigger saat lapangan berubah (jika nantinya harga berbeda per lapangan)
    $('#id_lapangan').on('change', function(){
        hitungTotal();
    });
});
</script>