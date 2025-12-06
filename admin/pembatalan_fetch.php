<?php
// pembatalan_fetch.php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $query = mysqli_query($conn, "SELECT * FROM pembatalan_booking WHERE id = '$id'");
    $data = mysqli_fetch_assoc($query);

    if ($data) {
        $tgl_main_fmt = isset($data['tanggal_main']) ? date('d F Y', strtotime($data['tanggal_main'])) : '-';
?>
        <form id="formProsesRefund" action="pembatalan_proses.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $data['id'] ?>">
            <input type="hidden" name="action" id="action_input" value="">

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

                            <div class="col-12 mb-2">
                                <div class="p-2 bg-info bg-opacity-10 rounded border border-info" style="background-color: #e3f2fd;">
                                    <span class="text-muted d-block mb-1" style="font-size:11px;">Item yang dibatalkan:</span>
                                    <i class="fas fa-futbol text-primary mr-1"></i> 
                                    <strong><?= htmlspecialchars($data['nama_lapangan']) ?></strong>
                                    <br>
                                    <small class="text-dark">
                                        <i class="far fa-calendar-alt mr-1"></i> <?= $tgl_main_fmt ?> &nbsp;|&nbsp; 
                                        <i class="far fa-clock mr-1"></i> <?= htmlspecialchars($data['jam_main']) ?>
                                    </small>
                                </div>
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
                        Upload Bukti Transfer <span class="text-danger" id="star_bukti">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fas fa-upload"></i></span>
                        <input type="file" class="form-control" id="bukti_tf" name="bukti_tf" accept="image/jpeg, image/png, image/jpg">
                    </div>
                    <div class="form-text small" id="info_bukti"><i class="fas fa-info-circle"></i> Wajib diisi jika menyetujui refund.</div>
                </div>

                <div class="col-md-12 mb-2">
                    <label for="keterangan" class="form-label fw-bold small text-uppercase text-muted">
                        Catatan Admin <span class="text-danger" id="star_ket" style="display:none;">*</span>
                    </label>
                    <textarea class="form-control" id="keterangan" name="keterangan" rows="2" placeholder="Tuliskan nomor referensi (jika setuju) atau alasan penolakan (jika tolak)..."><?= htmlspecialchars($data['keterangan']) ?></textarea>
                </div>
            </div>

            <div class="modal-footer bg-light px-0 pb-0 pt-3 border-top-0 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                
                <div>
                    <button type="button" onclick="submitForm('reject')" class="btn btn-danger btn-sm px-3 mr-2">
                        <i class="fas fa-times-circle mr-1"></i> Tolak Pengajuan
                    </button>
                    
                    <button type="button" onclick="submitForm('approve')" class="btn btn-success btn-sm px-3 shadow-sm">
                        <i class="fas fa-check-circle mr-1"></i> Setujui Refund
                    </button>
                </div>
            </div>
        </form>

        <script>
        function submitForm(actionType) {
            var form = document.getElementById('formProsesRefund');
            var actionInput = document.getElementById('action_input');
            var buktiInput = document.getElementById('bukti_tf');
            var ketInput = document.getElementById('keterangan');

            // Set tipe aksi
            actionInput.value = actionType;

            if (actionType === 'approve') {
                // Logika Approve: Bukti WAJIB, Keterangan OPSIONAL
                if (buktiInput.files.length === 0) {
                    alert('Mohon upload bukti transfer untuk menyetujui refund.');
                    return; 
                }
                form.submit();

            } else if (actionType === 'reject') {
                // Logika Reject: Bukti OPSIONAL, Keterangan WAJIB
                if (ketInput.value.trim() === "") {
                    alert('Mohon tulis alasan penolakan pada kolom Catatan Admin.');
                    ketInput.focus();
                    return;
                }
                if (confirm('Yakin ingin MENOLAK pengajuan refund ini?')) {
                    form.submit();
                }
            }
        }
        </script>
<?php
    } else {
        echo '<div class="alert alert-danger text-center"><i class="fas fa-exclamation-triangle"></i> Data booking tidak ditemukan.</div>';
    }
}
?>