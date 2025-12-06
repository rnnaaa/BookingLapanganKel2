<?php
// keuangan_edit.php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

// Cek ID
if (!isset($_GET['id'])) {
    header("Location: keuangan.php");
    exit;
}

$id_keuangan = intval($_GET['id']);

// Ambil Data Lama
$stmt = $conn->prepare("SELECT * FROM keuangan WHERE id_keuangan = ?");
$stmt->bind_param("i", $id_keuangan);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    $_SESSION['error'] = "Data tidak ditemukan!";
    header("Location: keuangan.php");
    exit;
}

// Cek apakah ini transaksi sistem (dari booking)
$is_system_transaction = !empty($data['booking_id']);

// PROSES SIMPAN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal= $_POST['tanggal'];
    $jenis = $_POST['jenis'];
    $kategori = $_POST['kategori'];
    $jumlah = str_replace('.', '', $_POST['jumlah']); // Hapus format ribuan
    $keterangan = $_POST['keterangan'];
    
    // Ambil ID User yang sedang login (PENTING UNTUK AUDIT)
    $admin_id   = $_SESSION['id_user']; 

    // Query Update dengan Audit Trail (updated_by & updated_at)
    $sql = "UPDATE keuangan 
             SET tanggal = ?, 
                 jenis = ?, 
                 kategori = ?, 
                 jumlah = ?, 
                 keterangan = ?,
                 updated_by = ?, 
                 updated_at = NOW() 
             WHERE id_keuangan = ?";
             
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdsii", $tanggal, $jenis, $kategori, $jumlah, $keterangan, $admin_id, $id_keuangan);

    if ($stmt->execute()) {
        $_SESSION['success'] = "✅ Data keuangan berhasil diperbarui (Tercatat oleh: " . $_SESSION['nama'] . ")";
        header("Location: keuangan.php");
        exit;
    } else {
        $error = "Gagal menyimpan: " . $conn->error;
    }
}

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<style>
/* Professional Edit Form Styling */
:root {
    --primary-gradient: linear-gradient(135deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
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
.content-header h1 {
    font-size: 1.875rem;
    font-weight: 700;
    color: #2c3e50;
    letter-spacing: -0.5px;
    margin-bottom: 0;
}

.content-header h1 i {
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Enhanced Form Card */
.form-card {
    border-radius: 20px;
    border: none;
    box-shadow: var(--card-shadow);
    overflow: hidden;
    transition: all 0.3s ease;
}

.form-card:hover {
    box-shadow: var(--card-hover-shadow);
}

.form-card .card-header {
    background: var(--primary-gradient);
    padding: 1.75rem;
    border: none;
}

.form-card .card-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    letter-spacing: 0.3px;
}

.form-card .card-header i {
    margin-right: 0.75rem;
}

.form-card .card-body {
    padding: 2rem;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fc 100%);
}

/* Form Labels */
.form-card label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #5a5c69;
    margin-bottom: 0.5rem;
    letter-spacing: 0.3px;
    display: block;
}

/* Form Inputs & Selects */
.form-card .form-control,
.form-card .form-select {
    border: 2px solid #e3e6f0;
    border-radius: 10px;
    padding: 0.75rem 1rem;
    font-size: 0.938rem;
    transition: all 0.3s ease;
    background: #ffffff;
}

.form-card .form-control:focus,
.form-card .form-select:focus {
    border-color: #2196f3;
    box-shadow: 0 0 0 4px rgba(33, 150, 243, 0.1);
    background: #ffffff;
}

.form-card .form-control.text-primary {
    color: #2196f3 !important;
    font-weight: 600;
}

.form-card textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

.form-card small.text-muted {
    font-size: 0.813rem;
    color: #6c757d;
    margin-top: 0.25rem;
    display: block;
}

/* Warning Alert Enhancement */
.alert-danger {
    background: linear-gradient(135deg, #fff5f5 0%, #ffe3e3 100%);
    border: 2px solid #ff6b6b;
    border-radius: 12px;
    padding: 1.25rem;
    color: #721c24;
    font-size: 0.938rem;
}

.alert-danger strong {
    color: #dc3545;
    font-weight: 700;
}

.alert-danger i {
    color: #dc3545;
    font-size: 1.125rem;
}

/* Info Alert Enhancement */
.alert-light {
    background: linear-gradient(135deg, #f8f9fc 0%, #e9ecf4 100%);
    border: 2px solid #d1d3e2;
    border-radius: 12px;
    padding: 1rem 1.25rem;
}

.alert-light small {
    color: #5a5c69;
    font-size: 0.875rem;
}

.alert-light i {
    color: #4e73df;
}

/* Form Group Spacing */
.form-group {
    margin-bottom: 1.5rem;
}

/* Card Footer Enhancement */
.form-card .card-footer {
    background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
    border-top: 2px solid #e3e6f0;
    padding: 1.5rem 2rem;
}

/* Button Enhancements */
.btn-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    border: none;
    border-radius: 10px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    letter-spacing: 0.3px;
    transition: all 0.3s ease;
}

.btn-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
}

.btn-primary {
    background: var(--primary-gradient);
    border: none;
    border-radius: 10px;
    padding: 0.75rem 1.75rem;
    font-weight: 600;
    letter-spacing: 0.3px;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(33, 150, 243, 0.4);
}

.btn-primary i,
.btn-secondary i {
    margin-right: 0.5rem;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .form-card .card-body {
        padding: 1.5rem;
    }
    
    .form-card .card-footer {
        padding: 1rem 1.5rem;
    }
    
    .content-header h1 {
        font-size: 1.5rem;
    }
}
</style>

<div class="content-wrapper animate-fade-in">
    <section class="content-header">
        <div class="container-fluid mb-4">
            <h1><i class="fas fa-edit me-2"></i> Edit Transaksi Keuangan</h1>
            <p class="text-muted mb-0 mt-2">Perbarui informasi transaksi keuangan</p>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10">
                    <div class="card form-card">
                        <div class="card-header text-white">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-file-invoice-dollar"></i> Form Edit Data Transaksi
                            </h3>
                        </div>
                        <form action="" method="POST">
                            <div class="card-body">
                                
                                <?php if($is_system_transaction): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>PERINGATAN SISTEM:</strong><br>
                                    Transaksi ini terhubung otomatis dengan <strong>Booking #<?= $data['booking_id'] ?></strong>. 
                                    Mengubah <strong>Jumlah</strong> di sini TIDAK akan mengubah tagihan di data Booking. 
                                    Disarankan hanya mengedit Keterangan atau Tanggal jika ada kesalahan input.
                                </div>
                                <?php endif; ?>

                                <?php if (isset($error)): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-times-circle me-2"></i> <?= $error ?>
                                    </div>
                                <?php endif; ?>

                                <div class="form-group">
                                    <label for="tanggal">Tanggal Transaksi</label>
                                    <input type="date" 
                                           name="tanggal" 
                                           id="tanggal"
                                           class="form-control" 
                                           required 
                                           value="<?= $data['tanggal'] ?>">
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="jenis">Jenis Transaksi</label>
                                            <select name="jenis" 
                                                    id="jenis"
                                                    class="form-select" 
                                                    required>
                                                <option value="pemasukan" <?= $data['jenis'] == 'pemasukan' ? 'selected' : '' ?>>
                                                    💰 Pemasukan (Income)
                                                </option>
                                                <option value="pengeluaran" <?= $data['jenis'] == 'pengeluaran' ? 'selected' : '' ?>>
                                                    💸 Pengeluaran (Expense)
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="kategori">Kategori</label>
                                            <select name="kategori" 
                                                    id="kategori"
                                                    class="form-select" 
                                                    required>
                                                <option value="Pelunasan" <?= $data['kategori'] == 'Pelunasan' ? 'selected' : '' ?>>Pelunasan Sewa</option>
                                                <option value="DP" <?= $data['kategori'] == 'DP' ? 'selected' : '' ?>>Down Payment (DP)</option>
                                                <option value="Pembayaran Online" <?= $data['kategori'] == 'Pembayaran Online' ? 'selected' : '' ?>>Pembayaran Online</option>
                                                <option value="Operasional" <?= $data['kategori'] == 'Operasional' ? 'selected' : '' ?>>Biaya Operasional</option>
                                                <option value="Maintenance" <?= $data['kategori'] == 'Maintenance' ? 'selected' : '' ?>>Perawatan Lapangan</option>
                                                <option value="Gaji" <?= $data['kategori'] == 'Gaji' ? 'selected' : '' ?>>Gaji Karyawan</option>
                                                <option value="Lainnya" <?= $data['kategori'] == 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="jumlah">Jumlah (Rp)</label>
                                    <input type="number" 
                                           name="jumlah" 
                                           id="jumlah"
                                           class="form-control font-weight-bold text-primary" 
                                           required 
                                           value="<?= intval($data['jumlah']) ?>"
                                           placeholder="Masukkan nominal">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> Masukkan angka saja tanpa titik/koma.
                                    </small>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="keterangan">Keterangan Detail</label>
                                    <textarea name="keterangan" 
                                              id="keterangan"
                                              class="form-control" 
                                              rows="4" 
                                              required 
                                              placeholder="Jelaskan detail transaksi..."><?= htmlspecialchars($data['keterangan']) ?></textarea>
                                </div>
                                
                                <div class="alert alert-light border">
                                    <small>
                                        <i class="fas fa-user-shield"></i> 
                                        <strong>Audit Trail:</strong> Sistem akan mencatat Anda (<strong><?= $_SESSION['nama'] ?? 'Admin' ?></strong>) sebagai pengedit terakhir data ini pada <strong><?= date('d M Y, H:i') ?> WIB</strong>.
                                    </small>
                                </div>

                            </div>
                            <div class="card-footer text-end">
                                <a href="keuangan.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Batal
                                </a>
                                <button type="submit" class="btn btn-primary ms-2">
                                    <i class="fas fa-save"></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include('../includes/footer.php'); ?>