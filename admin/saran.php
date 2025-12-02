<?php
// saran.php
require_once 'auth_check.php';
// session_start();
require_once __DIR__ . '/../config/database.php';
// Anda mungkin perlu otentikasi (auth_check) di sini
// require_once __DIR__ . '/auth_check.php'; 

include('../includes/header.php');
// include('../includes/topbar.php');
include('../includes/sidebar.php');

// Ambil semua data saran
$sql = "SELECT * FROM saran ORDER BY created_at DESC";
$query = mysqli_query($conn, $sql);
?>

<div class="content-wrapper animate__animated animate__fadeIn">
    <section class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <h1><i class="fas fa-comment-alt mr-2 icon-gradient-blue"></i> Data Saran & Masukan</h1>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            
            <?php 
            // Cek dan tampilkan notifikasi toast (Jika Anda menggunakan Toastr.js)
            if (isset($_SESSION['toast_success'])) {
                echo '<script>
                        $(document).ready(function() {
                            toastr.success("' . addslashes($_SESSION['toast_success']) . '");
                        });
                    </script>';
                unset($_SESSION['toast_success']);
            }
            if (isset($_SESSION['toast_error'])) {
                echo '<script>
                        $(document).ready(function() {
                            toastr.error("' . addslashes($_SESSION['toast_error']) . '");
                        });
                    </script>';
                unset($_SESSION['toast_error']);
            }
            // Fallback untuk alert biasa (Jika Anda tidak menggunakan Toastr.js)
            if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; 
            if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <div class="card shadow-sm border-0 mt-3">
                <div class="card-header text-white" 
                        style="background: linear-gradient(90deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
                                box-shadow: inset 0 -2px 8px rgba(0, 0, 0, 0.15);">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-list mr-2"></i> Daftar Feedback Pengguna
                    </h3>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="example1" class="table table-bordered table-striped table-hover align-middle">
                            <thead class="text-center bg-light">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Nama Pengirim</th>
                                    <th>Kategori</th>
                                    <th>Rating</th>
                                    <th>Pesan Singkat</th>
                                    <th>Anonim?</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php
                                $no = 1;
                                if (!$query) {
                                    echo "<tr><td colspan='8' class='text-center text-danger'>Error Query: " . mysqli_error($conn) . "</td></tr>";
                                } elseif (mysqli_num_rows($query) == 0) {
                                    echo "<tr><td colspan='8' class='text-center'>Tidak ada data saran/masukan.</td></tr>";
                                } else {
                                    while ($row = mysqli_fetch_assoc($query)) :
                                        $is_anonim = $row['is_anonim'] == 1 ? '<span class="badge bg-secondary">Ya</span>' : '<span class="badge bg-success">Tidak</span>';
                                        $nama_tampil = $row['is_anonim'] == 1 ? 'Anonim' : htmlspecialchars($row['nama']);
                                        // Potong pesan agar tidak terlalu panjang di tabel
                                        $pesan_singkat = (strlen($row['pesan']) > 50) ? substr($row['pesan'], 0, 47) . '...' : $row['pesan'];
                                ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td class="text-center small"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                    <td><?= $nama_tampil ?></td>
                                    <td class="text-center"><?= htmlspecialchars($row['kategori']) ?></td>
                                    <td class="text-center text-warning">
                                        <?php for ($i = 0; $i < $row['rating']; $i++): ?>
                                            <i class="fas fa-star"></i>
                                        <?php endfor; ?>
                                    </td>
                                    <td><?= htmlspecialchars($pesan_singkat) ?></td>
                                    <td class="text-center"><?= $is_anonim ?></td>

                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="#" 
                                                class="btn btn-sm text-white btn-detail-saran" 
                                                data-id="<?= $row['id_saran'] ?>"
                                                title="Lihat Detail"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalDetailSaran"
                                                style="background: linear-gradient(90deg, #0e5c91, #2196f3); border: none;"> 
                                                <i class="fas fa-eye"></i> Detail
                                            </a>
                                            <a href="saran_hapus.php?id=<?= $row['id_saran'] ?>" 
                                                class="btn btn-sm btn-danger btn-action-loader ml-2" 
                                                title="Hapus"
                                                data-confirm="Apakah Anda yakin ingin menghapus saran ini?"> 
                                                <i class="fas fa-trash-alt"></i> Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile; 
                                } 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            </div>
    </section>
</div>

<div class="modal fade" id="modalDetailSaran" tabindex="-1" aria-labelledby="modalDetailSaranLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white" style="background: linear-gradient(90deg,#0e5c91,#2196f3) !important;">
                <h5 class="modal-title" id="modalDetailSaranLabel"><i class="fas fa-info-circle"></i> Detail Saran Pengguna</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body" id="modal-body-detail-saran">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat data detail...</p>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<?php include('../includes/footer.php'); ?>

<style>
.icon-gradient-blue {
    background: linear-gradient(90deg, #0e5c91, #2196f3);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}
.bg-gradient-primary {
    background: linear-gradient(90deg,#0e5c91,#2196f3);
}
.btn-close-white {
    filter: invert(1);
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function () {

    // --- LOGIC btn-action-loader (untuk tombol hapus/navigasi) ---
    // Logika ini sama dengan di produk.php
    document.body.addEventListener("click", function (e) {
        const btn = e.target.closest(".btn-action-loader");
        if (!btn) return;

        e.preventDefault();

        const targetUrl = btn.getAttribute("href");
        const confirmMsg = btn.getAttribute("data-confirm") || null;

        // Konfirmasi (untuk tombol hapus)
        if (confirmMsg && !confirm(confirmMsg)) {
            return;
        }

        if (!btn.dataset.originalHtml) {
            btn.dataset.originalHtml = btn.innerHTML;
        }

        btn.style.pointerEvents = "none";
        btn.style.opacity = "0.6";
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        setTimeout(() => {
            window.location.href = targetUrl;
        }, 300);

        setTimeout(() => {
            btn.style.pointerEvents = "auto";
            btn.style.opacity = "1";
            btn.innerHTML = btn.dataset.originalHtml;
        }, 6000);
    });
    // --- END LOGIC btn-action-loader ---


    // --- LOGIC MODAL DETAIL SARAN (AJAX) ---
    const modalBodyDetail = document.getElementById('modal-body-detail-saran');
    const defaultLoader = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Memuat data detail...</p>
        </div>
    `;

    document.body.addEventListener('click', function (e) {
        const detailButton = e.target.closest('.btn-detail-saran');
        
        if (detailButton) {
            e.preventDefault();
            const idSaran = detailButton.dataset.id;
            
            // 1. Reset Modal Body dan tampilkan Loader
            modalBodyDetail.innerHTML = defaultLoader;
            // Modal akan otomatis show karena data-bs-toggle/target sudah di set di tombol Aksi

            // 2. Lakukan permintaan AJAX
            $.ajax({
                url: 'saran_fetch_detail.php', // Pastikan file ini berada di lokasi yang sama
                type: 'GET',
                data: { id: idSaran },
                success: function(response) {
                    // Isi konten modal dengan detail yang terformat
                    modalBodyDetail.innerHTML = response;
                },
                error: function() {
                    modalBodyDetail.innerHTML = `
                        <div class="alert alert-danger text-center">
                            ❌ Gagal memuat data detail. Silakan coba lagi.
                        </div>
                    `;
                }
            });
        }
    });
    // --- END LOGIC MODAL DETAIL SARAN ---


    // Auto hide alert
    setTimeout(function () {
        $('.alert').fadeOut('slow');
    }, 5000);

});
</script>