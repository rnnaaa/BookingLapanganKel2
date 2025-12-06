<?php
// keuangan_fetch.php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

// Pastikan hanya bisa diakses via AJAX POST dengan ID
if (!isset($_POST['id'])) {
    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>ID Transaksi tidak ditemukan.</div>';
    exit;
}

$id_keuangan = intval($_POST['id']);

// Ambil Data Lama
$stmt = $conn->prepare("SELECT * FROM keuangan WHERE id_keuangan = ?");
$stmt->bind_param("i", $id_keuangan);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    echo '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Data tidak ditemukan di database.</div>';
    exit;
}

// Cek koneksi booking
$is_system_transaction = !empty($data['booking_id']);
?>

<style>
/* Professional Modal Form Styling */
.modal-content {
    border-radius: 16px;
    border: none;
    box-shadow: 0 10px 40px rgba(14, 92, 145, 0.2);
}

.modal-body {
    padding: 2rem;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fc 100%);
}

.modal-footer {
    background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
    border-top: 2px solid #e3e6f0;
    padding: 1.25rem 2rem;
}

/* Alert Enhancement in Modal */
.modal-body .alert {
    border-radius: 10px;
    border: none;
    padding: 1rem 1.25rem;
    font-size: 0.875rem;
    margin-bottom: 1.5rem;
    background: linear-gradient(135deg, #fff5f5 0%, #ffe3e3 100%);
    border-left: 4px solid #ff6b6b;
}

.modal-body .alert strong {
    color: #dc3545;
    font-weight: 700;
}

.modal-body .alert i {
    color: #dc3545;
    font-size: 1.125rem;
}

/* Form Group Styling */
.modal-body .form-group {
    margin-bottom: 1.25rem;
}

.modal-body label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #5a5c69;
    margin-bottom: 0.5rem;
    letter-spacing: 0.3px;
    display: block;
}

/* Form Controls in Modal */
.modal-body .form-control,
.modal-body .form-select {
    border: 2px solid #e3e6f0;
    border-radius: 10px;
    padding: 0.75rem 1rem;
    font-size: 0.938rem;
    transition: all 0.3s ease;
    background: #ffffff;
}

.modal-body .form-control:focus,
.modal-body .form-select:focus {
    border-color: #2196f3;
    box-shadow: 0 0 0 4px rgba(33, 150, 243, 0.1);
    background: #ffffff;
}

.modal-body .form-control.font-weight-bold {
    font-weight: 600;
    color: #2196f3;
}

.modal-body textarea.form-control {
    resize: vertical;
    min-height: 90px;
}

.modal-body small.text-muted {
    font-size: 0.813rem;
    color: #6c757d;
    margin-top: 0.25rem;
    display: block;
}

/* Button Enhancements in Modal */
.modal-footer .btn-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    border: none;
    border-radius: 10px;
    padding: 0.625rem 1.25rem;
    font-weight: 600;
    letter-spacing: 0.3px;
    transition: all 0.3s ease;
}

.modal-footer .btn-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
}

.modal-footer .btn-primary {
    background: linear-gradient(135deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
    border: none;
    border-radius: 10px;
    padding: 0.625rem 1.5rem;
    font-weight: 600;
    letter-spacing: 0.3px;
    transition: all 0.3s ease;
}

.modal-footer .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(33, 150, 243, 0.4);
}

.modal-footer .btn i {
    margin-right: 0.5rem;
}

/* Row spacing in modal */
.modal-body .row {
    margin-bottom: 0;
}

.modal-body .row > [class*="col-"] {
    padding-left: 0.5rem;
    padding-right: 0.5rem;
}

/* Responsive Modal Adjustments */
@media (max-width: 576px) {
    .modal-body {
        padding: 1.5rem;
    }
    
    .modal-footer {
        padding: 1rem 1.5rem;
    }
    
    .modal-footer .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
</style>

<form action="keuangan_proses.php" method="POST">
    <input type="hidden" name="id_keuangan" value="<?= $data['id_keuangan'] ?>">

    <div class="modal-body">
        <?php if($is_system_transaction): ?>
        <div class="alert text-dark">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Perhatian:</strong> Transaksi ini terhubung dengan <strong>Booking #<?= $data['booking_id'] ?></strong>. 
            Mengubah nominal di sini tidak akan merubah total tagihan pada data booking.
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="modal_tanggal">Tanggal Transaksi</label>
            <input type="date" 
                   name="tanggal" 
                   id="modal_tanggal"
                   class="form-control" 
                   required 
                   value="<?= $data['tanggal'] ?>">
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="modal_jenis">Jenis</label>
                    <select name="jenis" id="modal_jenis" class="form-control" required>
                        <option value="pemasukan" <?= $data['jenis'] == 'pemasukan' ? 'selected' : '' ?>>
                            💰 Pemasukan
                        </option>
                        <option value="pengeluaran" <?= $data['jenis'] == 'pengeluaran' ? 'selected' : '' ?>>
                            💸 Pengeluaran
                        </option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="modal_kategori">Kategori</label>
                    <select name="kategori" id="modal_kategori" class="form-control" required>
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
            <label for="modal_jumlah">Jumlah (Rp)</label>
            <input type="number" 
                   name="jumlah" 
                   id="modal_jumlah"
                   class="form-control font-weight-bold text-primary" 
                   required 
                   value="<?= intval($data['jumlah']) ?>"
                   placeholder="Masukkan nominal">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> Masukkan angka tanpa titik.
            </small>
        </div>

        <div class="form-group mb-0">
            <label for="modal_keterangan">Keterangan</label>
            <textarea name="keterangan" 
                      id="modal_keterangan"
                      class="form-control" 
                      rows="3" 
                      required 
                      placeholder="Jelaskan detail transaksi..."><?= htmlspecialchars($data['keterangan']) ?></textarea>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fas fa-times"></i> Batal
        </button>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Simpan Perubahan
        </button>
    </div>
</form>