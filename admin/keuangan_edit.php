<?php
// keuangan_edit.php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';
// session_start();

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
// include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper animate__animated animate__fadeIn">
    <section class="content-header">
        <div class="container-fluid">
            <h1><i class="fas fa-edit mr-2"></i> Edit Transaksi Keuangan</h1>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow-lg border-0">
                        <div class="card-header text-white" 
                             style="background: linear-gradient(90deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
                                    box-shadow: inset 0 -2px 8px rgba(0, 0, 0, 0.15);">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-list mr-2"></i> Form Edit Data
                            </h3>
                        </div>
                        <form action="" method="POST">
                            <div class="card-body">
                                
                                <?php if($is_system_transaction): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    <strong>PERINGATAN PROFESIONAL:</strong><br>
                                    Transaksi ini terhubung otomatis dengan <strong>Booking #<?= $data['booking_id'] ?></strong>. 
                                    Mengubah <strong>Jumlah</strong> di sini TIDAK akan mengubah tagihan di data Booking. 
                                    Disarankan hanya mengedit Keterangan atau Tanggal jika ada kesalahan input.
                                </div>
                                <?php endif; ?>

                                <?php if (isset($error)): ?>
                                    <div class="alert alert-danger"><?= $error ?></div>
                                <?php endif; ?>

                                <div class="form-group mb-3">
                                    <label>Tanggal Transaksi</label>
                                    <input type="date" name="tanggal" class="form-control" required 
                                                value="<?= $data['tanggal'] ?>">
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label>Jenis</label>
                                            <select name="jenis" class="form-control" required>
                                                <option value="pemasukan" <?= $data['jenis'] == 'pemasukan' ? 'selected' : '' ?>>Pemasukan (Income)</option>
                                                <option value="pengeluaran" <?= $data['jenis'] == 'pengeluaran' ? 'selected' : '' ?>>Pengeluaran (Expense)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label>Kategori</label>
                                            <select name="kategori" class="form-control" required>
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

                                <div class="form-group mb-3">
                                    <label>Jumlah (Rp)</label>
                                    <input type="number" name="jumlah" class="form-control font-weight-bold text-primary" required 
                                                value="<?= intval($data['jumlah']) ?>">
                                    <small class="text-muted">Masukkan angka saja tanpa titik/koma.</small>
                                </div>

                                <div class="form-group mb-3">
                                    <label>Keterangan</label>
                                    <textarea name="keterangan" class="form-control" rows="3" required><?= htmlspecialchars($data['keterangan']) ?></textarea>
                                </div>
                                
                                <div class="alert alert-light border">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> 
                                        Sistem akan mencatat Anda (<strong><?= $_SESSION['nama'] ?? 'Admin' ?></strong>) sebagai pengedit terakhir data ini pada <strong><?= date('d-m-Y H:i') ?></strong>.
                                    </small>
                                </div>

                            </div>
                            <div class="card-footer text-right">
                                <a href="keuangan.php" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-primary font-weight-bold">
                                    <i class="fas fa-save mr-1"></i> Simpan Perubahan
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