<?php
require_once 'auth_check.php';
ob_start();
// session_start();
error_reporting(0); 

require_once __DIR__ . '/../config/database.php';
// require_once __DIR__ . '/auth_check.php'; 

include('../includes/header.php');
// include('../includes/topbar.php');
include('../includes/sidebar.php');

// --- PERBAIKAN QUERY ---
// Pastikan tabelnya 'users' (sesuai describe Anda) dan kolomnya 'nama'
$sql = "SELECT p.*, u.nama AS nama_admin, u.username 
        FROM pembatalan_booking p
        LEFT JOIN users u ON p.processed_by = u.id_user 
        ORDER BY p.requested_at DESC";

$query = mysqli_query($conn, $sql);
?>

<style>
    #tableLoader {
        display: none !important;
        opacity: 0 !important;
        visibility: hidden !important;
        pointer-events: none !important;
        z-index: -9999 !important;
    }
    .icon-gradient-blue {
        background: linear-gradient(90deg, #0e5c91, #2196f3);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .btn-close-white { filter: invert(1); }
</style>

<div class="content-wrapper animate__animated animate__fadeIn">
    <section class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <h1><i class="fas fa-undo-alt mr-2 icon-gradient-blue"></i> Data Pembatalan Booking</h1>
            <a href="pembatalan_booking.php" class="btn btn-secondary btn-sm shadow-sm">
                <i class="fas fa-sync-alt"></i> Refresh Data
            </a>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            
            <?php if (isset($_SESSION['toast_success'])): ?>
                <script>$(document).ready(function() { toastr.success("<?= addslashes($_SESSION['toast_success']) ?>"); });</script>
                <?php unset($_SESSION['toast_success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['toast_error'])): ?>
                <script>$(document).ready(function() { toastr.error("<?= addslashes($_SESSION['toast_error']) ?>"); });</script>
                <?php unset($_SESSION['toast_error']); ?>
            <?php endif; ?>

            <div class="card shadow-sm border-0 mt-3">
                <div class="card-header text-white" 
                     style="background: linear-gradient(90deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-list mr-2"></i> Daftar Pengajuan Refund
                    </h3>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="example1" class="table table-bordered table-striped table-hover align-middle">
                            <thead class="text-center bg-light">
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Info Pengaju</th>
                                    <th>Detail Rekening</th>
                                    <th>Jumlah Refund</th>
                                    <th>Status & Proses</th>
                                    <th>Bukti</th>
                                    <th>Tgl Request</th>
                                    <th width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                if (!$query) {
                                    echo "<tr><td colspan='8' class='text-center text-danger'>Error Query: " . mysqli_error($conn) . "</td></tr>";
                                } elseif (mysqli_num_rows($query) == 0) {
                                    echo "<tr><td colspan='8' class='text-center'>Tidak ada data.</td></tr>";
                                } else {
                                    while ($row = mysqli_fetch_assoc($query)) :
                                        // Variabel helper
                                        $nama_pengaju = htmlspecialchars($row['nama_pengaju'] ?? '-');
                                        $nomor_rekening = htmlspecialchars($row['nomor_rekening'] ?? '-');
                                        $atas_nama = htmlspecialchars($row['atas_nama'] ?? '-');
                                        $jml_refund = number_format($row['jumlah_refund'] ?? 0, 0, ',', '.');
                                        $tgl_req = isset($row['requested_at']) ? date('d/m/Y H:i', strtotime($row['requested_at'])) : '-';
                                        
                                        // --- LOGIKA MENAMPILKAN NAMA ADMIN ---
                                        $admin_display = ''; // Default kosong
                                        
                                        // 1. Cek apakah ada nama dari hasil JOIN?
                                        if (!empty($row['nama_admin'])) {
                                            $admin_display = htmlspecialchars($row['nama_admin']);
                                        } 
                                        // 2. Jika nama kosong tapi ada ID di processed_by (misal user terhapus)
                                        elseif (!empty($row['processed_by'])) {
                                            $admin_display = '<span class="text-danger">ID: ' . $row['processed_by'] . ' (N/A)</span>';
                                        }

                                        $tgl_proses = !empty($row['processed_at']) ? date('d/m/y H:i', strtotime($row['processed_at'])) : '';

                                        // Warna Status
                                        $status_class = 'bg-secondary';
                                        if($row['status'] == 'approved') $status_class = 'bg-success';
                                        if($row['status'] == 'rejected') $status_class = 'bg-danger';
                                        if($row['status'] == 'pending')  $status_class = 'bg-warning text-dark';
                                ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td>
                                        <strong><?= $nama_pengaju ?></strong><br>
                                        <small class="text-muted">ID: <?= $row['id_user'] ?></small>
                                    </td>
                                    <td>
                                        <strong><?= $nomor_rekening ?></strong><br>
                                        <small class="text-muted">An. <?= $atas_nama ?></small>
                                    </td>
                                    <td class="text-end text-primary fw-bold">Rp <?= $jml_refund ?></td>
                                    
                                    <td class="text-center">
                                        <span class="badge <?= $status_class ?> badge-pill px-3"><?= ucfirst($row['status']) ?></span>
                                        
                                        <?php if ($row['status'] != 'pending' && !empty($admin_display)): ?>
                                            <div class="mt-2 pt-1 border-top" style="line-height: 1.2;">
                                                <small class="text-muted d-block" style="font-size: 0.75rem;">
                                                    <i class="fas fa-user-check mr-1 text-success"></i> 
                                                    <?= $admin_display ?>
                                                </small>
                                                <?php if($tgl_proses): ?>
                                                <small class="text-muted d-block" style="font-size: 0.7rem;">
                                                    <i class="far fa-clock mr-1"></i> <?= $tgl_proses ?>
                                                </small>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center">
                                        <?php if (!empty($row['bukti_refund'])): ?>
                                            <a href="javascript:void(0);" class="btn-lihat-bukti" data-img="../uploads/bukti_refund/<?= $row['bukti_refund'] ?>">
                                                <img src="../uploads/bukti_refund/<?= $row['bukti_refund'] ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                            </a>
                                        <?php else: ?> - <?php endif; ?>
                                    </td>
                                    
                                    <td class="text-center small"><?= $tgl_req ?></td>
                                    
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="javascript:void(0);" class="btn btn-sm text-white btn-edit-pembatalan" 
                                               data-id="<?= $row['id'] ?>"
                                               data-bs-toggle="modal" 
                                               data-bs-target="#modalProsesPembatalan"
                                               style="background: linear-gradient(90deg, #0e5c91, #2196f3); border: none;"> 
                                               <i class="fas fa-tasks"></i>
                                            </a>
                                            <a href="pembatalan_hapus.php?id=<?= $row['id'] ?>" 
                                               class="btn btn-sm btn-danger btn-action-loader ml-2" 
                                               data-confirm="Hapus data ini?"> 
                                               <i class="fas fa-trash-alt"></i>
                                            </a>
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

<div class="modal fade" id="modalProsesPembatalan" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header text-white" style="background: linear-gradient(90deg,#0e5c91,#2196f3);">
                <h5 class="modal-title"><i class="fas fa-tasks"></i> Proses Refund</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modal-body-proses">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2">Memuat...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalLihatBukti" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header text-white" style="background: linear-gradient(90deg,#0e5c91,#2196f3);">
                <h5 class="modal-title">Bukti Transfer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 text-center bg-light">
                <img src="" id="img-bukti-popup" class="img-fluid" style="max-height: 80vh;">
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    
    // SAFETY NET LOADER
    setTimeout(function() {
        var loader = document.getElementById("tableLoader");
        if(loader) loader.remove();
        var preloader = document.querySelector(".preloader");
        if(preloader) preloader.style.display = "none";
    }, 500);

    // Modal Edit
    $('body').on('click', '.btn-edit-pembatalan', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var body = $('#modal-body-proses');
        body.html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
        $.ajax({
            url: 'pembatalan_fetch.php',
            type: 'GET',
            data: { id: id },
            success: function(res) { body.html(res); },
            error: function() { body.html('Gagal memuat data.'); }
        });
    });

    // Modal Gambar
    $('body').on('click', '.btn-lihat-bukti', function(e) {
        e.preventDefault();
        $('#img-bukti-popup').attr('src', $(this).data('img'));
        new bootstrap.Modal(document.getElementById('modalLihatBukti')).show();
    });

    // Button Hapus
    document.body.addEventListener("click", function (e) {
        var btn = e.target.closest(".btn-action-loader");
        if (!btn) return;
        e.preventDefault();
        if (btn.dataset.confirm && !confirm(btn.dataset.confirm)) return;
        
        if (!btn.dataset.originalHtml) btn.dataset.originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        setTimeout(function() { window.location.href = btn.getAttribute("href"); }, 300);
    });
});
</script>