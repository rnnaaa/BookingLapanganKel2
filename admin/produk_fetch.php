<?php
// produk_fetch.php
require_once 'auth_check.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

$id_produk = $_GET['id'] ?? null;

if (!is_numeric($id_produk)) {
    http_response_code(400);
    echo '<div class="alert alert-danger text-center">ID Produk tidak valid.</div>';
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM produk WHERE id_produk = ?");
    $stmt->bind_param("i", $id_produk);
    $stmt->execute();
    $result = $stmt->get_result();
    $produk = $result->fetch_assoc();
    $stmt->close();

    if (!$produk) {
        http_response_code(404);
        echo '<div class="alert alert-warning text-center">Produk tidak ditemukan.</div>';
        exit;
    }

    $foto_path = !empty($produk['foto']) ? '../uploads/produk/' . $produk['foto'] : '../assets/img/placeholder.png';
    
?>
<style>
.form-label-edit {
    font-weight: 600;
    color: #0e5c91;
    margin-bottom: 8px;
    font-size: 0.95rem;
}

.form-control-edit, .form-select-edit {
    border: 2px solid #e3f2fd;
    border-radius: 8px;
    padding: 12px 16px;
    transition: all 0.3s ease;
}

.form-control-edit:focus, .form-select-edit:focus {
    border-color: #2196f3;
    box-shadow: 0 0 0 0.2rem rgba(33, 150, 243, 0.15);
}

.photo-preview-container {
    background: linear-gradient(135deg, rgba(14, 92, 145, 0.05) 0%, rgba(33, 150, 243, 0.05) 100%);
    border-radius: 15px;
    padding: 25px;
    text-align: center;
}

.photo-current, .photo-preview {
    max-width: 180px;
    max-height: 180px;
    object-fit: cover;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(14, 92, 145, 0.2);
    transition: all 0.3s ease;
}

.photo-current:hover, .photo-preview:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(14, 92, 145, 0.3);
}

.photo-label {
    color: #0e5c91;
    font-weight: 600;
    margin-bottom: 15px;
    font-size: 0.95rem;
}

.btn-submit-edit {
    background: linear-gradient(135deg, #f39c12 0%, #f1c40f 100%);
    border: none;
    color: white;
    padding: 10px 24px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(243, 156, 18, 0.3);
}

.btn-submit-edit:hover {
    background: linear-gradient(135deg, #e67e22 0%, #f39c12 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(243, 156, 18, 0.4);
    color: white;
}

.icon-label {
    color: #2196f3;
    margin-right: 6px;
}
</style>

<form method="POST" action="produk_edit_proses.php?action=edit" enctype="multipart/form-data">
    <input type="hidden" name="id_produk" value="<?= htmlspecialchars($produk['id_produk']) ?>">
    
    <div class="row g-4">
        
        <div class="col-md-6">
            <label for="nama_produk_edit" class="form-label form-label-edit">
                <i class="fas fa-box icon-label"></i> Nama Produk
            </label>
            <input type="text" class="form-control form-control-edit" id="nama_produk_edit" name="nama_produk" value="<?= htmlspecialchars($produk['nama_produk']) ?>" required>
        </div>

        <div class="col-md-6">
            <label for="kategori_edit" class="form-label form-label-edit">
                <i class="fas fa-tags icon-label"></i> Kategori
            </label>
            <input type="text" class="form-control form-control-edit" id="kategori_edit" name="kategori" value="<?= htmlspecialchars($produk['kategori']) ?>">
        </div>

        <div class="col-md-4">
            <label for="harga_edit" class="form-label form-label-edit">
                <i class="fas fa-money-bill-wave icon-label"></i> Harga (Rp)
            </label>
            <input type="number" class="form-control form-control-edit" id="harga_edit" name="harga" step="0.01" value="<?= htmlspecialchars($produk['harga']) ?>" required>
        </div>
        
        <div class="col-md-4">
            <label for="status_edit" class="form-label form-label-edit">
                <i class="fas fa-toggle-on icon-label"></i> Status
            </label>
            <select name="status" id="status_edit" class="form-select form-control-edit" required>
                <option value="aktif" <?= $produk['status'] == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                <option value="nonaktif" <?= $produk['status'] == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
            </select>
        </div>

        <div class="col-md-4">
            <label for="foto_baru_edit" class="form-label form-label-edit">
                <i class="fas fa-image icon-label"></i> Ganti Foto
            </label>
            <input type="file" class="form-control form-control-edit" id="foto_baru_edit" name="foto" accept="image/*">
            <input type="hidden" name="foto_lama" value="<?= htmlspecialchars($produk['foto'] ?? '') ?>">
        </div>

        <div class="col-md-12">
            <label for="deskripsi_edit" class="form-label form-label-edit">
                <i class="fas fa-align-left icon-label"></i> Deskripsi
            </label>
            <textarea class="form-control form-control-edit" id="deskripsi_edit" name="deskripsi" rows="3"><?= htmlspecialchars($produk['deskripsi']) ?></textarea>
        </div>

        <div class="col-12">
            <div class="photo-preview-container">
                <p class="photo-label">
                    <i class="fas fa-image me-2"></i> Preview Foto Produk
                </p>
                <img src="<?= $foto_path ?>" id="current_photo_edit" alt="Foto Produk Saat Ini" class="photo-current">
                
                <img src="#" id="foto_preview_edit" class="d-none photo-preview mt-3" alt="Preview Foto Baru">
                
                <p class="mt-3 mb-0 text-muted small">
                    <i class="fas fa-info-circle me-1"></i> Pilih foto baru untuk melihat preview
                </p>
            </div>
        </div>

    </div>
    
    <div class="modal-footer mt-4 pt-4 border-top">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i> Batal
        </button>
        <button type="submit" class="btn btn-submit-edit btn-submit-edit-loader">
            <i class="fas fa-save me-1"></i> Perbarui Produk
        </button>
    </div>
</form>

<script>
// Handle form submit loader
$(document).on('submit', 'form[action*="produk_edit_proses"]', function() {
    const btn = $(this).find('.btn-submit-edit-loader');
    btn.prop('disabled', true);
    btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Memperbarui...');
});
</script>
<?php
} catch (Exception $e) {
    http_response_code(500);
    echo '<div class="alert alert-danger text-center">Kesalahan Server: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>