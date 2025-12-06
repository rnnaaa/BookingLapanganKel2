<?php
// FILE 4: pembatalan_booking.php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

include('../includes/header.php');
include('../includes/sidebar.php');

$sql = "SELECT p.*, u.nama AS nama_admin 
        FROM pembatalan_booking p
        LEFT JOIN users u ON p.processed_by = u.id_user 
        ORDER BY p.requested_at DESC";

$query = mysqli_query($conn, $sql);
?>

<style>
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --danger-gradient: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
}

.content-wrapper {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    padding: 2rem 1rem;
}

.cancellation-table-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.card-header-gradient {
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    padding: 1.5rem;
}

#example1 thead th {
    background: #f8f9fc;
    color: #5a5c69;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    border-bottom: 2px solid #e3e6f0;
}

#example1 tbody tr {
    transition: all 0.2s ease;
    border-bottom: 1px solid #e3e6f0;
}

#example1 tbody tr:hover {
    background: #f8f9fc;
    transform: scale(1.01);
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.info-box {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    border-left: 4px solid;
}

.info-box-primary { border-left-color: #667eea; }
.info-box-success { border-left-color: #11998e; }
.info-box-danger { border-left-color: #ee0979; }
.info-box-info { border-left-color: #4facfe; }

.refund-amount {
    font-size: 1.1rem;
    font-weight: 700;
    color: #11998e;
}

.badge-pill {
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 600;
}

.proof-thumb {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.proof-thumb:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.action-btn-group .btn {
    transition: all 0.2s ease;
}

.action-btn-group .btn:hover {
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .content-wrapper {
        padding: 1rem 0.5rem;
    }
}
</style>

<div class="content-wrapper">
    
    <section class="content-header mb-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="mb-3 mb-md-0">
                    <h1 style="font-weight: 700; color: #2d3748;">
                        <i class="fas fa-undo-alt me-2" style="color: #667eea;"></i>
                        Data Pembatalan Booking
                    </h1>
                    <p class="text-muted mb-0">Kelola pengajuan refund dan pembatalan booking</p>
                </div>
                <a href="pembatalan_booking.php" class="btn btn-outline-primary">
                    <i class="fas fa-sync-alt me-1"></i> Refresh Data
                </a>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            
            <div class="card cancellation-table-card">
                <div class="card-header card-header-gradient text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i> Daftar Pengajuan Refund
                    </h5>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="example1" class="table table-hover align-middle mb-0">
                            <thead class="text-center">
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Info Pengaju</th>
                                    <th>Detail Booking</th>
                                    <th>Detail Rekening</th>
                                    <th>Jumlah Refund</th>
                                    <th>Status & Proses</th>
                                    <th>Bukti</th>
                                    <th>Tgl Request</th>
                                    <th width="10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                if (!$query) {
                                    echo "<tr><td colspan='9' class='text-center text-danger py-4'>Error Query: " . mysqli_error($conn) . "</td></tr>";
                                } elseif (mysqli_num_rows($query) == 0) {
                                    echo "<tr><td colspan='9' class='text-center text-muted py-5'><i class='fas fa-inbox fa-3x mb-3 d-block'></i>Tidak ada data pengajuan</td></tr>";
                                } else {
                                    while ($row = mysqli_fetch_assoc($query)) :
                                        $nama_pengaju = htmlspecialchars($row['nama_pengaju'] ?? '-');
                                        $nomor_rekening = htmlspecialchars($row['nomor_rekening'] ?? '-');
                                        $atas_nama = htmlspecialchars($row['atas_nama'] ?? '-');
                                        $jml_refund = number_format($row['jumlah_refund'] ?? 0, 0, ',', '.');
                                        $tgl_req = isset($row['requested_at']) ? date('d/m/Y H:i', strtotime($row['requested_at'])) : '-';
                                        
                                        $nm_lapangan = htmlspecialchars($row['nama_lapangan'] ?? 'Tanpa Nama');
                                        $tgl_main = isset($row['tanggal_main']) ? date('d M Y', strtotime($row['tanggal_main'])) : '-';
                                        $jam_main = htmlspecialchars($row['jam_main'] ?? '-');

                                        $admin_display = !empty($row['nama_admin']) ? htmlspecialchars($row['nama_admin']) : '';
                                        $tgl_proses = !empty($row['processed_at']) ? date('d/m/y H:i', strtotime($row['processed_at'])) : '';

                                        $status_class = 'bg-secondary';
                                        if($row['status'] == 'approved') $status_class = 'bg-success';
                                        if($row['status'] == 'rejected') $status_class = 'bg-danger';
                                        if($row['status'] == 'pending')  $status_class = 'bg-warning text-dark';
                                ?>
                                <tr>
                                    <td class="text-center fw-semibold"><?= $no++ ?></td>
                                    
                                    <td>
                                        <div class="info-box info-box-primary mb-0">
                                            <strong class="d-block"><?= $nama_pengaju ?></strong>
                                            <small class="text-muted">ID: <?= $row['id_user'] ?></small>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <div class="info-box info-box-info mb-0">
                                            <div class="fw-bold text-primary mb-1">
                                                <i class="fas fa-futbol me-1"></i> <?= $nm_lapangan ?>
                                            </div>
                                            <small class="d-block text-muted">
                                                <i class="far fa-calendar-alt me-1"></i> <?= $tgl_main ?>
                                            </small>
                                            <small class="text-muted">
                                                <i class="far fa-clock me-1"></i> <?= $jam_main ?>
                                            </small>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="info-box info-box-success mb-0">
                                            <strong class="d-block"><?= $nomor_rekening ?></strong>
                                            <small class="text-muted">a.n <?= $atas_nama ?></small>
                                        </div>
                                    </td>

                                    <td class="text-end">
                                        <div class="refund-amount">Rp <?= $jml_refund ?></div>
                                    </td>
                                    
                                    <td class="text-center">
                                        <span class="badge <?= $status_class ?> badge-pill mb-2">
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                        <?php if ($row['status'] != 'pending' && !empty($admin_display)): ?>
                                            <div class="mt-2 pt-2 border-top">
                                                <small class="d-block fw-semibold">
                                                    <i class="fas fa-user-check text-success me-1"></i>
                                                    <?= $admin_display ?>
                                                </small>
                                                <?php if($tgl_proses): ?>
                                                <small class="text-muted d-block">
                                                    <i class="far fa-clock me-1"></i><?= $tgl_proses ?>
                                                </small>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center">
                                        <?php if (!empty($row['bukti_refund'])): ?>
                                            <a href="javascript:void(0);" class="btn-lihat-bukti" 
                                               data-img="../uploads/bukti_refund/<?= $row['bukti_refund'] ?>">
                                                <img src="../uploads/bukti_refund/<?= $row['bukti_refund'] ?>" 
                                                     class="proof-thumb" alt="Bukti">
                                            </a>
                                        <?php else: ?> 
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="text-center small"><?= $tgl_req ?></td>
                                    
                                    <td class="text-center">
                                        <div class="action-btn-group d-flex gap-1">
                                            <button type="button" 
                                               class="btn btn-sm btn-primary flex-fill btn-edit-pembatalan" 
                                               data-id="<?= $row['id'] ?>"
                                               data-bs-toggle="modal" 
                                               data-bs-target="#modalProsesPembatalan"
                                               title="Proses"> 
                                               <i class="fas fa-tasks"></i>
                                            </button>

                                            <button type="button" 
                                                    class="btn btn-sm btn-danger flex-fill btn-delete" 
                                                    data-id="<?= $row['id'] ?>"
                                                    title="Hapus">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; 
                                } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal Proses -->
<div class="modal fade" id="modalProsesPembatalan" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header card-header-gradient text-white">
                <h5 class="modal-title">
                    <i class="fas fa-tasks me-2"></i> Proses Refund
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modal-body-proses">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2 text-muted">Memuat data...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Lihat Bukti -->
<div class="modal fade" id="modalLihatBukti" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header card-header-gradient text-white">
                <h5 class="modal-title">
                    <i class="fas fa-image me-2"></i> Bukti Transfer
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 text-center bg-dark">
                <img src="" id="img-bukti-popup" class="img-fluid" style="max-height: 80vh;">
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
    });

    <?php if (isset($_SESSION['toast_success'])): ?>
        Toast.fire({ icon: 'success', title: '<?= addslashes($_SESSION['toast_success']) ?>' });
        <?php unset($_SESSION['toast_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['toast_error'])): ?>
        Toast.fire({ icon: 'error', title: '<?= addslashes($_SESSION['toast_error']) ?>' });
        <?php unset($_SESSION['toast_error']); ?>
    <?php endif; ?>

    // Delete confirmation
    $('body').on('click', '.btn-delete', function(e) {
        e.preventDefault();
        var id = $(this).data('id');

        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: "Data pengajuan refund akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'pembatalan_hapus.php?id=' + id;
            }
        });
    });

    // Load modal content
    $('body').on('click', '.btn-edit-pembatalan', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var body = $('#modal-body-proses');
        
        body.html('<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Memuat...</p></div>');
        
        $.ajax({
            url: 'pembatalan_fetch.php',
            type: 'GET',
            data: { id: id },
            success: function(res) { body.html(res); },
            error: function() { body.html('<div class="alert alert-danger">Gagal memuat data.</div>'); }
        });
    });

    // View image modal
    $('body').on('click', '.btn-lihat-bukti', function(e) {
        e.preventDefault();
        $('#img-bukti-popup').attr('src', $(this).data('img'));
        new bootstrap.Modal(document.getElementById('modalLihatBukti')).show();
    });
});
</script>

<?php
/* =========================================================================
   FILE 5: pembatalan_fetch.php
   ========================================================================= */
?>

<?php
// FILE 5: pembatalan_fetch.php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $query = mysqli_query($conn, "SELECT * FROM pembatalan_booking WHERE id = '$id'");
    $data = mysqli_fetch_assoc($query);

    if ($data) {
        $tgl_main_fmt = isset($data['tanggal_main']) ? date('d F Y', strtotime($data['tanggal_main'])) : '-';
?>
        <style>
            .refund-summary-card {
                background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                color: white;
                border-radius: 10px;
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .booking-info-box {
                background: #e3f2fd;
                border: 2px solid #2196f3;
                border-radius: 10px;
                padding: 1rem;
            }
            
            .account-info-box {
                background: white;
                border: 2px solid #e0e0e0;
                border-radius: 10px;
                padding: 1rem;
            }
            
            .upload-area {
                border: 2px dashed #667eea;
                border-radius: 10px;
                padding: 1rem;
                transition: all 0.3s ease;
            }
            
            .upload-area:hover {
                background: #f8f9fc;
                border-color: #764ba2;
            }
        </style>

        <form id="formProsesRefund" action="pembatalan_proses.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $data['id'] ?>">
            <input type="hidden" name="action" id="action_input" value="">

            <!-- Summary Card -->
            <div class="refund-summary-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">
                        <i class="fas fa-receipt me-2"></i> Rincian Refund
                    </h6>
                    <span class="badge bg-warning text-dark px-3 py-2">Pending</span>
                </div>
                <hr class="border-white opacity-25 my-3">
                <div class="row">
                    <div class="col-6">
                        <small class="d-block opacity-75 mb-1">Nama Pengaju</small>
                        <div class="fw-bold"><?= htmlspecialchars($data['nama_pengaju']) ?></div>
                    </div>
                    <div class="col-6 text-end">
                        <small class="d-block opacity-75 mb-1">Total Pengembalian</small>
                        <div class="fw-bold fs-5">Rp <?= number_format($data['jumlah_refund'], 0, ',', '.') ?></div>
                    </div>
                </div>
            </div>

            <!-- Booking Info -->
            <div class="booking-info-box mb-3">
                <small class="text-muted d-block mb-2">
                    <i class="fas fa-info-circle me-1"></i> Item yang dibatalkan:
                </small>
                <div class="d-flex align-items-center">
                    <i class="fas fa-futbol text-primary fa-2x me-3"></i>
                    <div>
                        <div class="fw-bold"><?= htmlspecialchars($data['nama_lapangan']) ?></div>
                        <small class="text-muted">
                            <i class="far fa-calendar-alt me-1"></i> <?= $tgl_main_fmt ?>
                            <span class="mx-2">|</span>
                            <i class="far fa-clock me-1"></i> <?= htmlspecialchars($data['jam_main']) ?>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Account Info -->
            <div class="account-info-box mb-4">
                <small class="text-muted d-block mb-2">
                    <i class="fas fa-university me-1"></i> Rekening Tujuan:
                </small>
                <div class="d-flex align-items-center">
                    <i class="fas fa-credit-card text-success fa-2x me-3"></i>
                    <div>
                        <div class="fw-bold"><?= htmlspecialchars($data['nomor_rekening']) ?></div>
                        <small class="text-muted">a.n <?= htmlspecialchars($data['atas_nama']) ?></small>
                    </div>
                </div>
            </div>

            <!-- Upload Bukti -->
            <div class="mb-4">
                <label class="form-label fw-bold">
                    <i class="fas fa-upload me-2"></i>
                    Upload Bukti Transfer <span class="text-danger" id="star_bukti">*</span>
                </label>
                <div class="upload-area">
                    <input type="file" class="form-control" id="bukti_tf" name="bukti_tf" accept="image/*">
                    <small class="form-text text-muted d-block mt-2" id="info_bukti">
                        <i class="fas fa-info-circle me-1"></i>
                        Wajib diisi jika menyetujui refund.
                    </small>
                </div>
            </div>

            <!-- Catatan -->
            <div class="mb-3">
                <label class="form-label fw-bold">
                    <i class="fas fa-comment-alt me-2"></i>
                    Catatan Admin <span class="text-danger" id="star_ket" style="display:none;">*</span>
                </label>
                <textarea class="form-control" id="keterangan" name="keterangan" rows="3" 
                          placeholder="Nomor referensi / Alasan penolakan..."><?= htmlspecialchars($data['keterangan']) ?></textarea>
            </div>

            <!-- Action Buttons -->
            <div class="modal-footer bg-light border-top-0 d-flex justify-content-between px-0 pb-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Batal
                </button>
                
                <div>
                    <button type="button" onclick="submitForm('reject')" class="btn btn-danger me-2">
                        <i class="fas fa-times-circle me-1"></i> Tolak
                    </button>
                    
                    <button type="button" onclick="submitForm('approve')" class="btn btn-success">
                        <i class="fas fa-check-circle me-1"></i> Setujui Refund
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

            actionInput.value = actionType;

            if (actionType === 'approve') {
                if (buktiInput.files.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Perhatian',
                        text: 'Mohon upload bukti transfer untuk menyetujui refund.'
                    });
                    return; 
                }
                form.submit();

            } else if (actionType === 'reject') {
                if (ketInput.value.trim() === "") {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Perhatian',
                        text: 'Mohon tulis alasan penolakan pada kolom Catatan Admin.'
                    });
                    ketInput.focus();
                    return;
                }
                
                Swal.fire({
                    title: 'Konfirmasi Penolakan',
                    text: 'Yakin ingin MENOLAK pengajuan refund ini?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Tolak!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            }
        }
        </script>
<?php
    } else {
        echo '<div class="alert alert-danger text-center"><i class="fas fa-exclamation-triangle me-2"></i> Data tidak ditemukan.</div>';
    }
}
?>