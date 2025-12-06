<?php
//member_jadwal.php
require_once 'auth_check.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../config/database.php';

$id_member = intval($_GET['id_member'] ?? 0);

/* ============================================================
   AMBIL DATA MEMBER
============================================================ */
$member_info = null;
if ($id_member > 0) {
    $stmt = $conn->prepare("
        SELECT m.*, u.nama AS nama_user, l.nama_lapangan
        FROM member m
        JOIN users u ON m.id_user = u.id_user
        JOIN lapangan l ON m.id_lapangan = l.id_lapangan
        WHERE m.id_member = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id_member);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows) $member_info = $res->fetch_assoc();
    $stmt->close();
}

/* ============================================================
   PROSES SIMPAN JADWAL
============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_member_post = intval($_POST['id_member'] ?? 0);
    $id_lapangan = intval($_POST['id_lapangan'] ?? 0);
    $tanggal_booking = $_POST['tanggal_booking'] ?? '';
    $jam_mulai = $_POST['jam_mulai'] ?? '';
    $jam_selesai = $_POST['jam_selesai'] ?? '';
    $id_detail = intval($_POST['id_detail'] ?? 0);

    if (!$id_member_post || !$id_lapangan || !$tanggal_booking || !$jam_mulai || !$jam_selesai || !$id_detail) {
        $_SESSION['toast_error'] = "❌ Semua kolom wajib diisi atau slot belum dipilih.";
        header("Location: member_jadwal.php?id_member=" . $id_member_post);
        exit;
    }

    $conn->begin_transaction();

    try {
        /* ============================================================
           VALIDASI MASA AKTIF MEMBER
        ============================================================ */
        $stmt = $conn->prepare("
            SELECT tanggal_mulai, tanggal_berakhir 
            FROM member 
            WHERE id_member=? AND status='aktif'
            LIMIT 1
        ");
        $stmt->bind_param("i", $id_member_post);
        $stmt->execute();
        $m = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$m) throw new Exception("⚠️ Member tidak aktif atau tidak ditemukan.");

        if ($tanggal_booking < $m['tanggal_mulai'] || $tanggal_booking > $m['tanggal_berakhir']) {
            throw new Exception("⚠️ Tanggal di luar masa aktif member.");
        }

        /* ============================================================
           VALIDASI PER MINGGU HANYA 1x
        ============================================================ */
        $ts = strtotime($tanggal_booking);
        $week_start = date('Y-m-d', strtotime("monday this week", $ts));
        $week_end   = date('Y-m-d', strtotime("sunday this week", $ts));

        $stmt = $conn->prepare("
            SELECT 1 
            FROM member_jadwal 
            WHERE id_member=? 
              AND tanggal_booking BETWEEN ? AND ?
              AND status='aktif'
            LIMIT 1
        ");
        $stmt->bind_param("iss", $id_member_post, $week_start, $week_end);
        $stmt->execute();
        $cek = $stmt->get_result();
        $stmt->close();

        if ($cek->num_rows) {
            throw new Exception("⚠️ Member sudah punya jadwal di minggu tersebut.");
        }

        /* ============================================================
           VALIDASI SLOT TERSEDIA
        ============================================================ */
        $stmt = $conn->prepare("
            SELECT jd.id_detail, jd.status, 
                   jh.tanggal, jh.id_lapangan,
                   jw.jam_mulai, jw.jam_selesai
            FROM jadwal_detail jd
            JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian
            JOIN jadwal_waktu jw ON jd.id_jadwal_waktu = jw.id_jadwal_waktu
            WHERE jd.id_detail=?
            LIMIT 1
        ");
        $stmt->bind_param("i", $id_detail);
        $stmt->execute();
        $slot = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$slot) throw new Exception("⚠️ Slot tidak ditemukan.");
        if ($slot['status'] !== 'tersedia') throw new Exception("⚠️ Slot sudah dibooking.");
        if ($slot['tanggal'] != $tanggal_booking) throw new Exception("⚠️ Tanggal slot tidak sesuai.");
        if ($slot['id_lapangan'] != $id_lapangan) throw new Exception("⚠️ Lapangan tidak cocok.");

        /* ============================================================
           AMBIL HARGA MEMBER
        ============================================================ */
        $harga = $conn->query("
            SELECT harga_per_jam_member 
            FROM lapangan 
            WHERE id_lapangan={$id_lapangan}
            LIMIT 1
        ")->fetch_assoc()['harga_per_jam_member'] ?? 0;

        if ($harga <= 0) {
            throw new Exception("⚠️ Harga member belum diatur.");
        }

        /* ============================================================
           INSERT member_jadwal
        ============================================================ */
        $stmt = $conn->prepare("
            INSERT INTO member_jadwal
            (id_member, id_lapangan, tanggal_booking, jam_mulai, jam_selesai,
             harga_per_jam_member, id_detail, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'aktif', NOW(), NOW())
        ");
        $stmt->bind_param("iisssdi",
            $id_member_post, $id_lapangan, $tanggal_booking,
            $jam_mulai, $jam_selesai, $harga, $id_detail
        );
        $stmt->execute();
        $last_id = $conn->insert_id;
        $stmt->close();

        /* ============================================================
           UPDATE jadwal_detail + isi id_member_jadwal
        ============================================================ */
        $stmt = $conn->prepare("
            UPDATE jadwal_detail 
            SET status='dibooking', id_member_jadwal=?
            WHERE id_detail=? AND status='tersedia'
            LIMIT 1
        ");
        $stmt->bind_param("ii", $last_id, $id_detail);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            throw new Exception("⚠️ Slot tidak tersedia atau sudah dipakai.");
        }

        $stmt->close();
        $conn->commit();

        $_SESSION['toast_success'] = "✅ Jadwal berhasil ditambahkan!";

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['toast_error'] = $e->getMessage();
    }

    header("Location: member_jadwal.php?id_member=" . $id_member_post);
    exit;
}

/* ============================================================
   DATA TABEL
============================================================ */
$qMembers = $conn->query("
    SELECT m.id_member, u.nama AS nama_user, l.nama_lapangan, m.id_lapangan 
    FROM member m
    JOIN users u ON m.id_user=u.id_user
    JOIN lapangan l ON m.id_lapangan=l.id_lapangan
    WHERE m.status='aktif'
    ORDER BY u.nama
");

$qJadwal = $conn->query("
    SELECT mj.*, u.nama AS nama_user, l.nama_lapangan 
    FROM member_jadwal mj
    JOIN member m ON mj.id_member=m.id_member
    JOIN users u ON m.id_user=u.id_user
    JOIN lapangan l ON mj.id_lapangan=l.id_lapangan
    " . ($id_member ? "WHERE mj.id_member=$id_member" : "") . "
    ORDER BY mj.tanggal_booking DESC
");

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<style>
/* Professional Member Schedule Styling */
:root {
    --primary-gradient: linear-gradient(135deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
    --success-gradient: linear-gradient(135deg, #28a745 0%, #218838 100%);
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

/* Content Header */
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

/* Button Group */
.content-header .btn {
    border-radius: 10px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    letter-spacing: 0.3px;
    transition: all 0.3s ease;
}

.content-header .btn-primary {
    background: var(--primary-gradient);
    border: none;
    box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
}

.content-header .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(33, 150, 243, 0.4);
}

.content-header .btn-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    border: none;
    box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
}

.content-header .btn-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
}

/* Alert Enhancements */
.alert {
    border-radius: 12px;
    border: none;
    padding: 1rem 1.5rem;
    font-size: 0.938rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 1.5rem;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
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

/* Form Elements */
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

.form-control[readonly] {
    background: #e9ecf4;
    color: #5a5c69;
}

/* Select2 Enhancement */
.select2-container--bootstrap4 .select2-selection--single {
    height: 48px !important;
    padding: 0.5rem 1rem !important;
    box-sizing: border-box;
    border: 2px solid #e3e6f0 !important;
    border-radius: 10px !important;
    transition: all 0.3s ease;
}

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

/* Slot Container Enhancement */
#slotContainer {
    padding: 1.5rem;
    background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
    border-radius: 12px;
    border: 2px dashed #d1d3e2;
    margin-top: 1rem;
}

#slotContainer label {
    font-size: 1rem;
    color: #2c3e50;
    margin-bottom: 1rem;
}

/* Slot Buttons Enhancement */
#slotList {
    gap: 0.75rem;
}

#slotList .btn {
    border-radius: 10px;
    padding: 0.75rem 1.25rem;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid #28a745;
    background: #ffffff;
    color: #28a745;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.15);
}

#slotList .btn:hover:not(:disabled) {
    background: var(--success-gradient);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
}

#slotList .btn.active {
    background: var(--success-gradient) !important;
    color: white !important;
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
}

#slotList .btn:disabled {
    background: #e9ecef;
    border-color: #dee2e6;
    color: #6c757d;
    opacity: 0.6;
    cursor: not-allowed;
}

/* Loading Spinner */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
    border-width: 0.15em;
}

/* Button Footer Enhancement */
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
#tblMemberJadwal {
    margin: 0;
}

#tblMemberJadwal thead {
    background: linear-gradient(135deg, #f8f9fc 0%, #f1f3f9 100%);
}

#tblMemberJadwal thead th {
    font-size: 0.813rem;
    font-weight: 700;
    color: #5a5c69;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1rem;
    border-bottom: 2px solid #e3e6f0;
}

#tblMemberJadwal tbody tr {
    transition: all 0.2s ease;
}

#tblMemberJadwal tbody tr:hover {
    background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
    transform: scale(1.01);
    box-shadow: 0 2px 8px rgba(14, 92, 145, 0.1);
}

#tblMemberJadwal tbody td {
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

.badge.bg-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%) !important;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .content-header {
        text-align: center;
    }
    
    .content-header .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .card-primary .card-body {
        padding: 1.5rem;
    }
    
    #slotList .btn {
        width: 100%;
    }
}
</style>

<div class="content-wrapper animate-fade-in">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
      <div class="mb-3 mb-md-0">
          <h1><i class="fas fa-calendar-week me-2"></i> Jadwal Member</h1>
          <p class="text-muted mb-0 mt-2">Kelola jadwal booking member mingguan</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-primary shadow" data-bs-toggle="collapse" data-bs-target="#formTambahJadwal">
          <i class="fas fa-plus-circle me-1"></i> Tambah Jadwal
        </button>
        <a href="member.php" class="btn btn-secondary shadow">
          <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">

      <?php if (!empty($_SESSION['toast_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" id="alert-message">
          <i class="fas fa-exclamation-triangle me-2"></i><?= $_SESSION['toast_error']; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['toast_error']); ?>
      <?php endif; ?>

      <?php if (!empty($_SESSION['toast_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" id="alert-message">
          <i class="fas fa-check-circle me-2"></i><?= $_SESSION['toast_success']; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['toast_success']); ?>
      <?php endif; ?>

      <!-- FORM TAMBAH JADWAL -->
      <div class="collapse" id="formTambahJadwal">
        <div class="card card-primary">
          <div class="card-header text-white">
            <h3 class="card-title mb-0">
              <i class="fas fa-calendar-plus me-2"></i> Tambah Jadwal Member Baru
            </h3>
          </div>
          <form method="POST">
            <div class="card-body row g-4">
              <div class="col-md-4">
                <label for="id_member">Member</label>
                <select name="id_member" id="id_member" class="form-select select2-bootstrap4" required>
                  <option value="">-- Pilih Member --</option>
                  <?php while($m=$qMembers->fetch_assoc()): ?>
                    <option value="<?= $m['id_member'] ?>"
                      data-id-lapangan="<?= $m['id_lapangan'] ?>"
                      data-nama-lapangan="<?= htmlspecialchars($m['nama_lapangan']) ?>"
                      <?= $id_member == $m['id_member'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($m['nama_user']) ?> (<?= htmlspecialchars($m['nama_lapangan']) ?>)
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>

              <div class="col-md-4">
                <label for="nama_lapangan">Lapangan</label>
                <input type="text" class="form-control" id="nama_lapangan" readonly>
                <input type="hidden" name="id_lapangan" id="id_lapangan">
              </div>

              <div class="col-md-4">
                <label for="tanggal_booking">Tanggal Booking</label>
                <input type="date" name="tanggal_booking" id="tanggal_booking" class="form-control" min="<?= date('Y-m-d') ?>" required>
              </div>

              <div class="col-md-12" id="slotContainer" style="display:none;">
                <label>
                  <i class="fas fa-clock me-1"></i> Pilih Slot Jam Tersedia:
                </label>
                <div id="slotList" class="d-flex flex-wrap"></div>
                <input type="hidden" name="jam_mulai" id="jam_mulai">
                <input type="hidden" name="jam_selesai" id="jam_selesai">
                <input type="hidden" name="id_detail" id="id_detail">
              </div>
            </div>
            <div class="card-footer text-end">
              <button type="submit" class="btn btn-success">
                <i class="fas fa-save me-1"></i> Simpan Jadwal
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- TABEL JADWAL -->
      <div class="card shadow-lg border-0">
        <div class="card-header text-white">
          <h3 class="card-title mb-0">
            <i class="fas fa-list me-2"></i> Daftar Jadwal Member Terdaftar
          </h3>
        </div>
        <div class="card-body table-responsive p-0">
          <table id="tblMemberJadwal" class="table table-hover align-middle w-100 mb-0">
            <thead class="text-center">
              <tr>
                <th>No</th>
                <th>Member</th>
                <th>Lapangan</th>
                <th>Tanggal</th>
                <th>Jam</th>
                <th>Status</th>
                <th>Dibuat</th>
              </tr>
            </thead>
            <tbody>
              <?php $no=1; while($r=$qJadwal->fetch_assoc()): ?>
                <tr>
                  <td class="text-center fw-semibold text-muted"><?= $no++ ?></td>
                  <td class="fw-semibold"><?= htmlspecialchars($r['nama_user']) ?></td>
                  <td class="text-center"><?= htmlspecialchars($r['nama_lapangan']) ?></td>
                  <td class="text-center fw-semibold"><?= date('d M Y', strtotime($r['tanggal_booking'])) ?></td>
                  <td class="text-center">
                      <span class="badge bg-primary">
                          <?= substr($r['jam_mulai'],0,5) . ' - ' . substr($r['jam_selesai'],0,5) ?>
                      </span>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-<?= $r['status']=='aktif'?'success':'secondary' ?>">
                        <?= ucfirst($r['status']) ?>
                    </span>
                  </td>
                  <td class="text-center text-muted small"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </section>
</div>

<?php include('../includes/footer.php'); ?>

<script>
$(function() {
  // Initialize Select2
  $('#id_member').select2({ 
    theme: 'bootstrap4', 
    placeholder: "Cari dan Pilih Member", 
    allowClear: true, 
    width: '100%' 
  });
  
  // Initialize DataTable
  // $('#tblMemberJadwal').DataTable({
  //   responsive: true,
  //   language: {
  //     url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
  //   }
  // });
  
  // Auto hide alerts
  const alertElement = $('#alert-message');
  if (alertElement.length) {
    setTimeout(() => alertElement.fadeOut(800), 3000);
  }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const $idMember = $('#id_member');
  const idLapangan = document.getElementById('id_lapangan');
  const namaLapangan = document.getElementById('nama_lapangan');
  const tanggal = document.getElementById('tanggal_booking');
  const slotContainer = document.getElementById('slotContainer');
  const slotList = document.getElementById('slotList');
  const jamMulai = document.getElementById('jam_mulai');
  const jamSelesai = document.getElementById('jam_selesai');
  const idDetailInput = document.getElementById('id_detail');

  // Load slots dari server
  function loadSlots() {
    const idL = idLapangan.value;
    const tgl = tanggal.value;
    slotList.innerHTML = '';
    idDetailInput.value = '';
    jamMulai.value = '';
    jamSelesai.value = '';

    if (!idL || !tgl) { 
      slotContainer.style.display='none'; 
      return; 
    }

    // Loading indicator
    slotList.innerHTML = '<div class="spinner-border spinner-border-sm me-2 text-primary" role="status"></div><span class="text-muted"> Memuat slot tersedia...</span>';

    fetch(`member_jadwal_get_slot.php?id_lapangan=${encodeURIComponent(idL)}&tanggal=${encodeURIComponent(tgl)}`)
      .then(res => res.json())
      .then(data => {
        slotList.innerHTML = '';
        slotContainer.style.display = 'block';

        if (!data || data.status !== 'success') {
          slotList.innerHTML = `<p class="text-danger mb-0"><i class="fas fa-exclamation-circle me-1"></i>${data?.message || 'Respon tidak valid dari server.'}</p>`;
          return;
        }
        
        if (!data.slots.length) {
          slotList.innerHTML = '<p class="text-danger mb-0"><i class="fas fa-calendar-times me-1"></i>Tidak ada slot tersedia untuk tanggal ini.</p>';
          return;
        }

        data.slots.forEach(s => {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'btn';
          btn.innerHTML = `<i class="fas fa-clock me-1"></i>${s.jam_mulai} - ${s.jam_selesai}`;
          btn.dataset.idDetail = s.id_detail;
          btn.dataset.jamMulai = s.jam_mulai;
          btn.dataset.jamSelesai = s.jam_selesai;

          if (s.status !== 'tersedia') {
            btn.disabled = true;
            btn.innerHTML += ' <span class="badge bg-danger ms-1">Booked</span>';
          } else {
            btn.addEventListener('click', () => {
              slotList.querySelectorAll('button').forEach(b => b.classList.remove('active'));
              btn.classList.add('active');
              jamMulai.value = btn.dataset.jamMulai;
              jamSelesai.value = btn.dataset.jamSelesai;
              idDetailInput.value = btn.dataset.idDetail;
            });
          }
          slotList.appendChild(btn);
        });
      })
      .catch(err => {
        slotContainer.style.display='block';
        slotList.innerHTML = `<p class="text-danger mb-0"><i class="fas fa-times-circle me-1"></i>Gagal memuat slot: ${err.message}</p>`;
      });
  }

  // Member selection handler
  $idMember.on('select2:select', function(e) {
    const option = e.params.data.element || this.querySelector('option[value="'+$(this).val()+'"]');
    if (option) {
      idLapangan.value = option.dataset.idLapangan || '';
      namaLapangan.value = option.dataset.namaLapangan || '';
    }
    tanggal.value = '';
    slotList.innerHTML = '';
    slotContainer.style.display = 'none';
  });

  $idMember.on('select2:clear', function() {
    idLapangan.value = '';
    namaLapangan.value = '';
    tanggal.value = '';
    slotList.innerHTML = '';
    slotContainer.style.display = 'none';
  });

  tanggal.addEventListener('change', loadSlots);

  // Auto-select member if provided in URL
  const preIdMember = <?= json_encode($id_member, JSON_NUMERIC_CHECK) ?>;
  if (preIdMember && preIdMember > 0) {
    const collapseEl = document.querySelector('#formTambahJadwal');
    if (collapseEl) {
      new bootstrap.Collapse(collapseEl, { show: true });
    }

    setTimeout(() => {
      $idMember.val(preIdMember).trigger('change');
      const opt = document.querySelector('#id_member option[value="'+preIdMember+'"]');
      if (opt) {
        idLapangan.value = opt.dataset.idLapangan || '';
        namaLapangan.value = opt.dataset.namaLapangan || '';
      }
      if (tanggal.value) loadSlots();
    }, 200);
  } else {
    document.querySelector('[data-bs-target="#formTambahJadwal"]')?.addEventListener('click', () => {
      setTimeout(() => $idMember.select2('open'), 350);
    });
  }
});
</script>