<?php
// saran.php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

include('../includes/header.php');
include('../includes/sidebar.php');

// Ambil semua data saran
$sql = "SELECT * FROM saran ORDER BY created_at DESC";
$query = mysqli_query($conn, $sql);
?>

<style>
/* Enhanced Professional Styling for Saran Page */
.gradient-blue-primary {
    background: linear-gradient(135deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
}

.card-modern {
    border-radius: 15px;
    border: none;
    box-shadow: 0 5px 20px rgba(14, 92, 145, 0.08);
    transition: all 0.3s ease;
}

.card-modern:hover {
    box-shadow: 0 8px 30px rgba(14, 92, 145, 0.15);
}

.card-header-gradient {
    background: linear-gradient(135deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
    color: white;
    border-radius: 15px 15px 0 0 !important;
    padding: 20px 25px;
    border: none;
}

.page-header {
    background: linear-gradient(135deg, rgba(14, 92, 145, 0.05) 0%, rgba(33, 150, 243, 0.05) 100%);
    padding: 25px;
    border-radius: 15px;
    margin-bottom: 25px;
}

.icon-gradient-blue {
    background: linear-gradient(135deg, #0e5c91, #2196f3);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-size: 1.8rem;
}

.table-modern thead th {
    background: linear-gradient(135deg, rgba(14, 92, 145, 0.1) 0%, rgba(33, 150, 243, 0.1) 100%);
    color: #0e5c91;
    font-weight: 600;
    border: none;
    padding: 15px;
    vertical-align: middle;
}

.table-modern tbody tr {
    transition: all 0.3s ease;
}

.table-modern tbody tr:hover {
    background: linear-gradient(135deg, rgba(14, 92, 145, 0.05) 0%, rgba(33, 150, 243, 0.05) 100%);
    transform: scale(1.01);
}

.badge-modern {
    padding: 6px 14px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 0.85rem;
}

.badge-anonim-yes {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    color: white;
}

.badge-anonim-no {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.badge-kategori {
    background: linear-gradient(135deg, rgba(14, 92, 145, 0.2) 0%, rgba(33, 150, 243, 0.2) 100%);
    color: #0e5c91;
    border: 1px solid rgba(14, 92, 145, 0.3);
}

.rating-stars {
    color: #ffc107;
    font-size: 1rem;
}

.btn-detail-modern {
    background: linear-gradient(135deg, #0e5c91 0%, #2196f3 100%);
    border: none;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-detail-modern:hover {
    background: linear-gradient(135deg, #0a4770 0%, #1976d2 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(14, 92, 145, 0.3);
    color: white;
}

.modal-content-modern {
    border-radius: 15px;
    border: none;
    overflow: hidden;
}

.modal-header-gradient {
    background: linear-gradient(135deg, #0e5c91 0%, #2196f3 100%);
    border: none;
    padding: 20px 25px;
}

.message-preview {
    color: #495057;
    font-size: 0.9rem;
    line-height: 1.5;
}
</style>

<div class="content-wrapper animate__animated animate__fadeIn">
    <section class="content-header">
        <div class="container-fluid">
            <div class="page-header">
                <h1 class="mb-0">
                    <i class="fas fa-comment-alt icon-gradient-blue me-2"></i>
                    <span style="color: #0e5c91; font-weight: 700;">Data Saran & Masukan</span>
                </h1>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            
            <?php 
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

            <div class="card card-modern">
                <div class="card-header-gradient">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i> Daftar Feedback Pengguna
                    </h3>
                </div>

                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table id="example1" class="table table-modern table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 60px;">No</th>
                                    <th class="text-center" style="width: 140px;">Tanggal</th>
                                    <th style="width: 180px;">Nama Pengirim</th>
                                    <th class="text-center" style="width: 140px;">Kategori</th>
                                    <th class="text-center" style="width: 120px;">Rating</th>
                                    <th>Pesan Singkat</th>
                                    <th class="text-center" style="width: 100px;">Anonim?</th>
                                    <th class="text-center" style="width: 180px;">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php
                                $no = 1;
                                if (!$query) {
                                    echo "<tr><td colspan='8' class='text-center text-danger'>Error Query: " . mysqli_error($conn) . "</td></tr>";
                                } elseif (mysqli_num_rows($query) == 0) {
                                    echo "<tr><td colspan='8' class='text-center py-5'>
                                            <i class='fas fa-inbox fa-3x text-muted mb-3 d-block'></i>
                                            <span class='text-muted'>Tidak ada data saran/masukan.</span>
                                          </td></tr>";
                                } else {
                                    while ($row = mysqli_fetch_assoc($query)) :
                                        $is_anonim = $row['is_anonim'] == 1;
                                        $nama_tampil = $is_anonim ? 'Anonim' : htmlspecialchars($row['nama']);
                                        $pesan_singkat = (strlen($row['pesan']) > 60) ? substr($row['pesan'], 0, 57) . '...' : $row['pesan'];
                                ?>
                                <tr>
                                    <td class="text-center fw-bold" style="color: #0e5c91;"><?= $no++ ?></td>
                                    <td class="text-center small text-muted">
                                        <i class="fas fa-calendar-alt me-1" style="color: #2196f3;"></i>
                                        <?= date('d/m/Y', strtotime($row['created_at'])) ?>
                                        <br>
                                        <span style="font-size: 0.8rem;"><?= date('H:i', strtotime($row['created_at'])) ?></span>
                                    </td>
                                    <td class="fw-semibold">
                                        <i class="fas fa-user-circle me-2" style="color: #2196f3;"></i>
                                        <?= $nama_tampil ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-modern badge-kategori">
                                            <?= htmlspecialchars($row['kategori']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="rating-stars">
                                            <?php for ($i = 0; $i < $row['rating']; $i++): ?>
                                                <i class="fas fa-star"></i>
                                            <?php endfor; ?>
                                            <?php for ($i = $row['rating']; $i < 5; $i++): ?>
                                                <i class="far fa-star"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <small class="text-muted d-block mt-1"><?= $row['rating'] ?>/5</small>
                                    </td>
                                    <td class="message-preview"><?= htmlspecialchars($pesan_singkat) ?></td>
                                    <td class="text-center">
                                        <?php if ($is_anonim): ?>
                                            <span class="badge badge-modern badge-anonim-yes">
                                                <i class="fas fa-mask me-1"></i> Ya
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-modern badge-anonim-no">
                                                <i class="fas fa-user-check me-1"></i> Tidak
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="#" 
                                                class="btn btn-sm btn-detail-modern btn-detail-saran" 
                                                data-id="<?= $row['id_saran'] ?>"
                                                title="Lihat Detail"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalDetailSaran"> 
                                                <i class="fas fa-eye me-1"></i> Detail
                                            </a>
                                            <a href="saran_hapus.php?id=<?= $row['id_saran'] ?>" 
                                                class="btn btn-sm btn-danger btn-action-loader ms-1" 
                                                title="Hapus"
                                                data-confirm="Apakah Anda yakin ingin menghapus saran ini?"> 
                                                <i class="fas fa-trash-alt me-1"></i> Hapus
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
        <div class="modal-content modal-content-modern">
            <div class="modal-header modal-header-gradient">
                <h5 class="modal-title" id="modalDetailSaranLabel">
                    <i class="fas fa-info-circle me-2"></i> Detail Saran Pengguna
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4" id="modal-body-detail-saran">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Memuat data detail...</p>
                </div>
            </div>
            
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

<script>
document.addEventListener("DOMContentLoaded", function () {

    document.body.addEventListener("click", function (e) {
        const btn = e.target.closest(".btn-action-loader");
        if (!btn) return;

        e.preventDefault();
        const targetUrl = btn.getAttribute("href");
        const confirmMsg = btn.getAttribute("data-confirm") || null;

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

    const modalBodyDetail = document.getElementById('modal-body-detail-saran');
    const defaultLoader = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Memuat data detail...</p>
        </div>
    `;

    document.body.addEventListener('click', function (e) {
        const detailButton = e.target.closest('.btn-detail-saran');
        
        if (detailButton) {
            e.preventDefault();
            const idSaran = detailButton.dataset.id;
            
            modalBodyDetail.innerHTML = defaultLoader;

            $.ajax({
                url: 'saran_fetch_detail.php',
                type: 'GET',
                data: { id: idSaran },
                success: function(response) {
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

    setTimeout(function () {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>