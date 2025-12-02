<?php
// produk_fetch.php (Handle AJAX untuk memuat data produk ke modal edit)
require_once 'auth_check.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
// session_start();
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

    // Tentukan path foto. Gunakan placeholder jika foto kosong atau tidak ada.
    $foto_path = !empty($produk['foto']) ? '../uploads/produk/' . $produk['foto'] : '../assets/img/placeholder.png'; // Ganti dengan path placeholder Anda
    
?>
<form method="POST" action="produk_edit_proses.php?action=edit" enctype="multipart/form-data">
    <input type="hidden" name="id_produk" value="<?= htmlspecialchars($produk['id_produk']) ?>">
    <div class="card-body row g-3">
        
        <div class="col-md-6">
            <label for="nama_produk_edit" class="form-label">Nama Produk</label>
            <input type="text" class="form-control" id="nama_produk_edit" name="nama_produk" value="<?= htmlspecialchars($produk['nama_produk']) ?>" required>
        </div>

        <div class="col-md-6">
            <label for="kategori_edit" class="form-label">Kategori</label>
            <input type="text" class="form-control" id="kategori_edit" name="kategori" value="<?= htmlspecialchars($produk['kategori']) ?>">
        </div>

        <div class="col-md-4">
            <label for="harga_edit" class="form-label">Harga (Rp)</label>
            <input type="number" class="form-control" id="harga_edit" name="harga" step="0.01" value="<?= htmlspecialchars($produk['harga']) ?>" required>
        </div>
        
        <div class="col-md-4">
            <label for="status_edit" class="form-label">Status</label>
            <select name="status" id="status_edit" class="form-select" required>
                <option value="aktif" <?= $produk['status'] == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                <option value="nonaktif" <?= $produk['status'] == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
            </select>
        </div>

        <div class="col-md-4">
            <label for="foto_baru_edit" class="form-label">Ganti Foto Produk</label>
            <input type="file" class="form-control" id="foto_baru_edit" name="foto" accept="image/*">
            <input type="hidden" name="foto_lama" value="<?= htmlspecialchars($produk['foto'] ?? '') ?>">
        </div>

        <div class="col-md-12 mb-3">
            <label for="deskripsi_edit" class="form-label">Deskripsi</label>
            <textarea class="form-control" id="deskripsi_edit" name="deskripsi" rows="3"><?= htmlspecialchars($produk['deskripsi']) ?></textarea>
        </div>

        <div class="col-12 text-center">
            <p class="mb-1 text-muted">Foto Saat Ini:</p>
            <img src="<?= $foto_path ?>" id="current_photo_edit" alt="Foto Produk Saat Ini" 
                 style="max-width: 150px; max-height: 150px; object-fit: cover; border: 1px solid #ccc; padding: 5px;">
            
            <img src="#" id="foto_preview_edit" class="d-none mt-2" alt="Preview Foto Baru" 
                 style="max-width: 150px; max-height: 150px; object-fit: cover; border: 2px solid #28a745; padding: 5px;">
        </div>

    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-warning btn-submit-edit-loader"><i class="fas fa-save"></i> Perbarui Produk</button>
    </div>
</form>
<?php
} catch (Exception $e) {
    http_response_code(500);
    echo '<div class="alert alert-danger text-center">Kesalahan Server: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>