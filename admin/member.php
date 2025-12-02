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

// ✅ (2) PROSES POST MEMBER
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

        $_SESSION['toast_success'] = "Member berhasil diaktifkan! ID: $id_member";
        header("Location: member.php");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['toast_error'] = "ERROR: " . $e->getMessage();
        error_log("Member Activation Error: " . $e->getMessage() . " | MySQL Error: " . $conn->error);
        header("Location: member.php");
        exit;
    }
}

// (3) PERIKSA MEMBER EXPIRED
$conn->query("
    UPDATE member m
    JOIN users u ON m.id_user = u.id_user
    SET m.status='nonaktif', u.role='user'
    WHERE m.tanggal_berakhir < CURDATE() AND m.status='aktif'
");

// ---------------- DATA FORM ----------------
$qUsers = mysqli_query($conn, "SELECT id_user, nama FROM users WHERE role='user' AND status='aktif' ORDER BY nama ASC");

// Ambil daftar lapangan aktif
$qLapangan = mysqli_query($conn, "SELECT id_lapangan, nama_lapangan FROM lapangan WHERE status='aktif' ORDER BY nama_lapangan ASC");

// ---------------- DATA TABLE ----------------
$qMember = mysqli_query($conn, "
    SELECT m.id_member, u.nama, l.nama_lapangan, m.durasi_bulan, m.tanggal_mulai, m.tanggal_berakhir, m.total_bayar, m.status
    FROM member m
    LEFT JOIN users u ON m.id_user = u.id_user
    LEFT JOIN lapangan l ON m.id_lapangan = l.id_lapangan
    ORDER BY m.id_member DESC
");

include('../includes/header.php');
// include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<style>
/* Pastikan semua input/select/select2 punya tinggi yang sama agar rapi (versi A) */
.form-control, .form-select {
    height: 46px;
    padding: .5rem .75rem;
    box-sizing: border-box;
}

.select2-container--bootstrap4 .select2-selection--single {
    height: 46px;
    padding: .25rem .5rem;
    box-sizing: border-box;
}

.select2-container--bootstrap4 .select2-selection__rendered {
    line-height: 46px;
}

.select2-container--bootstrap4 .select2-selection__arrow {
    height: 46px;
}
</style>

<div class="content-wrapper animate__animated animate__fadeIn">

<section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-users mr-2"></i> Data Member</h1>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="collapse" data-bs-target="#formTambah">
            <i class="fas fa-plus-circle"></i> Tambah Member
        </button>
    </div>
</section>

<section class="content">
<?php 
if (!empty($_SESSION['toast_error'])):
    echo '<div class="alert alert-danger mt-3">'.$_SESSION['toast_error'].'</div>';
    unset($_SESSION['toast_error']);
endif;

if (!empty($_SESSION['toast_success'])):
    echo '<div class="alert alert-success mt-3">'.$_SESSION['toast_success'].'</div>';
    unset($_SESSION['toast_success']);
endif;
?>

<!-- FORM (Versi A: 3 kolom stabil) -->
<div class="collapse mt-3" id="formTambah">
    <div class="card card-primary shadow-lg border-0">
        <div class="card-header text-white" style="background: linear-gradient(90deg,#0e5c91,#2196f3);">
            <h3 class="card-title mb-0"><i class="fas fa-user-plus"></i> Aktivasi Member Baru</h3>
        </div>

        <form method="POST" id="formMember" class="needs-validation" novalidate>
            <div class="card-body row g-3">

                <!-- ROW 1: tiga kolom -->
                <div class="col-md-4">
                    <label for="id_user_select">Pilih User</label>
                    <select name="id_user" id="id_user_select" class="form-select select2-bootstrap4" required>
                        <option value="">-- Pilih --</option>
                        <?php while($u=mysqli_fetch_assoc($qUsers)): ?>
                            <option value="<?= $u['id_user'] ?>"><?= htmlspecialchars($u['nama']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <div class="invalid-feedback">Pilih user.</div>
                </div>

                <div class="col-md-4">
                    <label for="id_lapangan">Pilih Lapangan</label>
                    <select name="id_lapangan" id="id_lapangan" class="form-select select2-bootstrap4" required>
                        <option value="">-- Pilih Lapangan --</option>
                        <?php while($l=mysqli_fetch_assoc($qLapangan)): ?>
                            <option value="<?= $l['id_lapangan'] ?>"><?= htmlspecialchars($l['nama_lapangan']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <div class="invalid-feedback">Pilih lapangan.</div>
                </div>

                <div class="col-md-4">
                    <label for="durasi_bulan">Durasi Membership</label>
                    <select name="durasi_bulan" id="durasi_bulan" class="form-select select2-bootstrap4" required>
                        <option value="1">1 Bulan</option>
                        <option value="2">2 Bulan</option>
                        <option value="3">3 Bulan</option>
                    </select>
                    <div class="invalid-feedback">Pilih durasi.</div>
                </div>

                <!-- ROW 2: tiga kolom -->
                <div class="col-md-4">
                    <label for="tanggal_mulai">Tanggal Mulai</label>
                    <input type="date" id="tanggal_mulai" name="tanggal_mulai" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    <div class="invalid-feedback">Isi tanggal mulai.</div>
                </div>

                <div class="col-md-4">
                    <label for="method">Metode Pembayaran</label>
                    <select name="method" id="method" class="form-select select2-bootstrap4" required>
                        <option value="tunai">Tunai</option>
                        <option value="qris">QRIS</option>
                        <option value="bank_transfer">Transfer Bank</option>
                    </select>
                    <div class="invalid-feedback">Pilih metode pembayaran.</div>
                </div>

                <div class="col-md-4">
                    <label for="total_bayar_display">Total Bayar (Asumsi: <?= number_format($harga_per_jam_member,0,',','.') ?> x 4 jam/bln)</label>
                    <input type="text" id="total_bayar_display" class="form-control fw-bold text-end" readonly>
                    <input type="hidden" id="total_bayar_value" name="total_bayar_value">
                </div>

            </div>

            <div class="card-footer text-end">
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- DAFTAR MEMBER -->
<div class="card shadow-lg border-0 mt-4">
    <div class="card-header text-white" style="background: linear-gradient(90deg,#0e5c91,#2196f3);">
        <h3 class="card-title mb-0"><i class="fas fa-list"></i> Daftar Member</h3>
    </div>

    <div class="card-body table-responsive">
        <table id="tblMember" class="table table-bordered table-striped table-hover align-middle w-100">
            <thead class="bg-light text-center">
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Lapangan</th>
                    <th>Durasi</th>
                    <th>Tanggal Mulai</th>
                    <th>Tanggal Berakhir</th>
                    <th>Total Bayar</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php $no=1; while($m=mysqli_fetch_assoc($qMember)): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($m['nama']) ?></td>
                    <td><?= htmlspecialchars($m['nama_lapangan']) ?></td>
                    <td class="text-center"><?= $m['durasi_bulan'] ?> bln</td>
                    <td class="text-center"><?= date('d-m-Y', strtotime($m['tanggal_mulai'])) ?></td>
                    <td class="text-center"><?= date('d-m-Y', strtotime($m['tanggal_berakhir'])) ?></td>
                    <td class="text-end fw-bold">Rp <?= number_format($m['total_bayar'],0,',','.') ?></td>
                    <td class="text-center">
                        <span class="badge bg-<?= $m['status']=='aktif'?'success':'secondary' ?>"><?= ucfirst($m['status']) ?></span>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</section>
</div>

<?php 
// free results and include footer
mysqli_free_result($qLapangan);
mysqli_free_result($qUsers);
mysqli_free_result($qMember);
include('../includes/footer.php'); 
?>

<script>
    // Inisialisasi select2 dan DataTable
    $(document).ready(function() {
        $('#id_user_select, #id_lapangan, #durasi_bulan, #method').select2({
            theme: 'bootstrap4',
            placeholder: "Pilih...",
            width: '100%'
        });

        $('#tblMember').DataTable();

        // Setup hitung awal
        hitungTotal();

        // Validasi bootstrap (optional)
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
    });

    // Hitung total bayar berdasarkan harga per jam (asumsi 4 jam/bln)
    function hitungTotal(){
        let hargaPerJam = <?= json_encode($harga_per_jam_member) ?>;
        let durasi = parseInt($('#durasi_bulan').val()) || 0;
        let total = hargaPerJam * 4 * durasi;
        $('#total_bayar_display').val("Rp " + total.toLocaleString('id-ID'));
        $('#total_bayar_value').val(total);
    }

    // trigger hitung saat durasi berubah
    $('#durasi_bulan').on('change', hitungTotal);
    // juga trigger saat lapangan berubah optionally (if price depends on lapangan you can recalc)
    $('#id_lapangan').on('change', function(){
        // jika nantinya ingin ambil harga berdasarkan lapangan via ajax tambahkan di sini
        hitungTotal();
    });
</script>
