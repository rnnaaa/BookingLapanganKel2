<?php
// pembatalan_fetch.php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $query = mysqli_query($conn, "SELECT * FROM pembatalan_booking WHERE id = '$id'");
    $data = mysqli_fetch_assoc($query);

    if ($data) {
?>
        <form action="pembatalan_proses.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $data['id'] ?>">
            <input type="hidden" name="action" value="approve">

            <div class="row">
                <div class="col-md-12 mb-3">
                    <div class="alert alert-light border shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-receipt mr-1"></i> Rincian Refund</h6>
                            <span class="badge bg-warning text-dark">Pending</span>
                        </div>
                        <hr class="my-2">
                        <div class="row text-sm">
                            <div class="col-6 mb-2">
                                <span class="text-muted d-block" style="font-size:12px;">Nama Pengaju</span>
                                <span class="fw-bold text-dark"><?= htmlspecialchars($data['nama_pengaju']) ?></span>
                            </div>
                            <div class="col-6 mb-2 text-end">
                                <span class="text-muted d-block" style="font-size:12px;">Total Pengembalian</span>
                                <span class="fw-bold text-success" style="font-size:14px;">Rp <?= number_format($data['jumlah_refund'], 0, ',', '.') ?></span>
                            </div>
                            <div class="col-12">
                                <div class="p-2 bg-white rounded border">
                                    <span class="text-muted d-block mb-1" style="font-size:11px;">Rekening Tujuan:</span>
                                    <i class="fas fa-credit-card text-secondary mr-1"></i> 
                                    <strong><?= htmlspecialchars($data['nomor_rekening']) ?></strong> 
                                    <span class="text-muted mx-1">a.n</span> 
                                    <strong><?= htmlspecialchars($data['atas_nama']) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-12 mb-3">
                    <label for="bukti_tf" class="form-label fw-bold small text-uppercase text-muted">
                        Upload Bukti Transfer <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fas fa-upload"></i></span>
                        <input type="file" class="form-control" id="bukti_tf" name="bukti_tf" accept="image/jpeg, image/png, image/jpg" required>
                    </div>
                    <div class="form-text small"><i class="fas fa-info-circle"></i> Format: JPG/PNG. Maksimal 2MB.</div>
                </div>

                <div class="col-md-12 mb-2">
                    <label for="keterangan" class="form-label fw-bold small text-uppercase text-muted">Catatan Admin</label>
                    <textarea class="form-control" name="keterangan" rows="2" placeholder="Tuliskan nomor referensi transaksi atau catatan lain..."><?= htmlspecialchars($data['keterangan']) ?></textarea>
                </div>
            </div>

            <div class="modal-footer bg-light px-0 pb-0 pt-3 border-top-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm" style="background: linear-gradient(90deg, #0e5c91, #2196f3); border:none;">
                    <i class="fas fa-paper-plane mr-1"></i> Proses & Simpan
                </button>
            </div>
        </form>
<?php
    } else {
        echo '<div class="alert alert-danger text-center"><i class="fas fa-exclamation-triangle"></i> Data booking tidak ditemukan.</div>';
    }
}
?>