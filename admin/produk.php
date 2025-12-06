<?php
// produk.php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

// Ambil semua data produk
$sql = "SELECT * FROM produk ORDER BY created_at DESC";
$query = mysqli_query($conn, $sql);

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<style>
/* Enhanced Professional Styling */
.gradient-blue-primary {
    background: linear-gradient(135deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
}

.gradient-blue-soft {
    background: linear-gradient(135deg, rgba(14, 92, 145, 0.1) 0%, rgba(33, 150, 243, 0.1) 100%);
}

.card-modern {
    border-radius: 15px;
    border: none;
    box-shadow: 0 5px 20px rgba(14, 92, 145, 0.08);
    transition: all 0.3s ease;
}

.card-modern:hover {
    box-shadow: 0 8px 30px rgba(14, 92, 145, 0.15);
    transform: translateY(-2px);
}

.btn-gradient-blue {
    background: linear-gradient(135deg, #0e5c91 0%, #2196f3 100%);
    border: none;
    color: white;
    padding: 10px 24px;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(14, 92, 145, 0.3);
}

.btn-gradient-blue:hover {
    background: linear-gradient(135deg, #0a4770 0%, #1976d2 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(14, 92, 145, 0.4);
    color: white;
}

.card-header-gradient {
    background: linear-gradient(135deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
    color: white;
    border-radius: 15px 15px 0 0 !important;
    padding: 20px 25px;
    border: none;
}

.form-control-modern, .form-select-modern {
    border: 2px solid #e3f2fd;
    border-radius: 8px;
    padding: 12px 16px;
    transition: all 0.3s ease;
}

.form-control-modern:focus, .form-select-modern:focus {
    border-color: #2196f3;
    box-shadow: 0 0 0 0.2rem rgba(33, 150, 243, 0.15);
}

.table-modern thead th {
    background: linear-gradient(135deg, rgba(14, 92, 145, 0.1) 0%, rgba(33, 150, 243, 0.1) 100%);
    color: #0e5c91;
    font-weight: 600;
    border: none;
    padding: 15px;
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

.btn-action-group .btn {
    border-radius: 6px;
    padding: 8px 16px;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-edit-modern {
    background: linear-gradient(135deg, #0e5c91 0%, #2196f3 100%);
    border: none;
    color: white;
}

.btn-edit-modern:hover {
    background: linear-gradient(135deg, #0a4770 0%, #1976d2 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(14, 92, 145, 0.3);
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

.product-image-modern {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 10px;
    border: 2px solid #e3f2fd;
    transition: all 0.3s ease;
}

.product-image-modern:hover {
    transform: scale(1.5);
    box-shadow: 0 8px 20px rgba(14, 92, 145, 0.3);
    cursor: pointer;
}

.collapse-form {
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
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

.form-label-modern {
    font-weight: 600;
    color: #0e5c91;
    margin-bottom: 8px;
}
</style>

<div class="content-wrapper animate__animated animate__fadeIn">
    <section class="content-header">
        <div class="container-fluid">
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="mb-0">
                        <i class="fas fa-boxes icon-gradient-blue me-2"></i>
                        <span style="color: #0e5c91; font-weight: 700;">Manajemen Produk</span>
                    </h1>
                    <button class="btn btn-gradient-blue" data-bs-toggle="collapse" data-bs-target="#formTambahProduk">
                        <i class="fas fa-plus-circle me-2"></i> Tambah Produk
                    </button>
                </div>
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
            unset($_SESSION['success']);
            unset($_SESSION['error']);
            ?>

            <div class="collapse mt-3 collapse-form" id="formTambahProduk">
                <div class="card card-modern">
                    <div class="card-header-gradient">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-plus-circle me-2"></i> Formulir Tambah Produk
                        </h3>
                    </div>
                    <form method="POST" action="produk_proses.php?action=tambah" enctype="multipart/form-data">
                        <div class="card-body p-4">
                            <div class="row g-4">
                                
                                <div class="col-md-6">
                                    <label for="nama_produk" class="form-label form-label-modern">
                                        <i class="fas fa-box me-1"></i> Nama Produk
                                    </label>
                                    <input type="text" class="form-control form-control-modern" id="nama_produk" name="nama_produk" placeholder="Masukkan nama produk" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="kategori" class="form-label form-label-modern">
                                        <i class="fas fa-tags me-1"></i> Kategori
                                    </label>
                                    <input type="text" class="form-control form-control-modern" id="kategori" name="kategori" value="Umum" placeholder="Kategori produk">
                                </div>

                                <div class="col-md-4">
                                    <label for="harga" class="form-label form-label-modern">
                                        <i class="fas fa-money-bill-wave me-1"></i> Harga (Rp)
                                    </label>
                                    <input type="number" class="form-control form-control-modern" id="harga" name="harga" step="0.01" placeholder="0" required>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="status" class="form-label form-label-modern">
                                        <i class="fas fa-toggle-on me-1"></i> Status
                                    </label>
                                    <select name="status" id="status" class="form-select form-control-modern" required>
                                        <option value="aktif">Aktif</option>
                                        <option value="nonaktif">Nonaktif</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label for="foto" class="form-label form-label-modern">
                                        <i class="fas fa-image me-1"></i> Foto Produk
                                    </label>
                                    <input type="file" class="form-control form-control-modern" id="foto" name="foto" accept="image/*">
                                </div>

                                <div class="col-md-12">
                                    <label for="deskripsi" class="form-label form-label-modern">
                                        <i class="fas fa-align-left me-1"></i> Deskripsi
                                    </label>
                                    <textarea class="form-control form-control-modern" id="deskripsi" name="deskripsi" rows="3" placeholder="Deskripsi produk (opsional)"></textarea>
                                </div>

                            </div>
                        </div>
                        <div class="card-footer bg-light p-4 text-end">
                            <button type="button" class="btn btn-secondary me-2" data-bs-toggle="collapse" data-bs-target="#formTambahProduk">
                                <i class="fas fa-times me-1"></i> Batal
                            </button>
                            <button type="submit" class="btn btn-gradient-blue btn-action-loader">
                                <i class="fas fa-save me-1"></i> Simpan Produk
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card card-modern mt-4">
                <div class="card-header-gradient">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i> Daftar Produk
                    </h3>
                </div>

                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table id="example1" class="table table-modern table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-center">No</th>
                                    <th>Nama Produk</th>
                                    <th class="text-center">Kategori</th>
                                    <th class="text-end">Harga</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Foto</th>
                                    <th class="text-center">Dibuat</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php
                                $no = 1;
                                if (!$query) {
                                    echo "<tr><td colspan='8' class='text-center text-danger'>Error Query: " . mysqli_error($conn) . "</td></tr>";
                                } elseif (mysqli_num_rows($query) == 0) {
                                    echo "<tr><td colspan='8' class='text-center'>Tidak ada data produk.</td></tr>";
                                } else {
                                    while ($row = mysqli_fetch_assoc($query)) :
                                        $status_class = $row['status'] === 'aktif' ? 'bg-success' : 'bg-secondary';
                                ?>
                                <tr>
                                    <td class="text-center fw-bold" style="color: #0e5c91;"><?= $no++ ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['nama_produk']) ?></td>
                                    <td class="text-center">
                                        <span class="badge badge-modern" style="background: linear-gradient(135deg, rgba(14, 92, 145, 0.2) 0%, rgba(33, 150, 243, 0.2) 100%); color: #0e5c91;">
                                            <?= htmlspecialchars($row['kategori']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end fw-semibold">Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                                    <td class="text-center">
                                        <span class="badge badge-modern <?= $status_class ?>"><?= ucfirst($row['status']) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($row['foto'])): ?>
                                            <img src="../uploads/produk/<?= $row['foto'] ?>" alt="Foto Produk" class="product-image-modern">
                                        <?php else: ?>
                                            <div style="width: 60px; height: 60px; background: linear-gradient(135deg, rgba(14, 92, 145, 0.1) 0%, rgba(33, 150, 243, 0.1) 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                                <i class="fas fa-image" style="color: #2196f3; font-size: 1.5rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center small text-muted"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>

                                    <td class="text-center">
                                        <div class="btn-group btn-action-group">
                                            <a href="#" 
                                                class="btn btn-sm btn-edit-modern btn-edit-produk" 
                                                data-id="<?= $row['id_produk'] ?>"
                                                title="Edit"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEditProduk"> 
                                                <i class="fas fa-edit me-1"></i> Edit
                                            </a>
                                            <a href="produk_hapus.php?id=<?= $row['id_produk'] ?>" 
                                                class="btn btn-sm btn-danger btn-action-loader ms-1" 
                                                title="Hapus"
                                                data-confirm="Apakah Anda yakin ingin menghapus produk ini?"> 
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

<div class="modal fade" id="modalEditProduk" tabindex="-1" aria-labelledby="modalEditProdukLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content modal-content-modern">
            <div class="modal-header modal-header-gradient">
                <h5 class="modal-title" id="modalEditProdukLabel">
                    <i class="fas fa-edit me-2"></i> Edit Produk
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4" id="modal-body-edit">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Memuat data produk...</p>
                </div>
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

        if (btn.type === 'submit' && btn.closest('#formTambahProduk')) {
             if (!btn.dataset.originalHtml) {
                 btn.dataset.originalHtml = btn.innerHTML;
             }
             btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
             return;
        }

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

    const modalEdit = new bootstrap.Modal(document.getElementById('modalEditProduk'));
    const modalBody = document.getElementById('modal-body-edit');
    const defaultLoader = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Memuat data produk...</p>
        </div>
    `;

    document.body.addEventListener('click', function (e) {
        const editButton = e.target.closest('.btn-edit-produk'); 
        
        if (editButton) {
            e.preventDefault();
            const idProduk = editButton.dataset.id;
            
            modalBody.innerHTML = defaultLoader;
            modalEdit.show();

            $.ajax({
                url: 'produk_fetch.php',
                type: 'GET',
                data: { id: idProduk },
                success: function(response) {
                    modalBody.innerHTML = response;
                },
                error: function() {
                    modalBody.innerHTML = `
                        <div class="alert alert-danger text-center">
                            ❌ Gagal memuat data. Silakan coba lagi.
                        </div>
                    `;
                }
            });
        }
    });

    $(document).on('change', '#foto_baru_edit', function(event) {
        const reader = new FileReader();
        const output = $('#foto_preview_edit');
        const currentPhoto = $('#current_photo_edit');
        
        reader.onload = function(e) {
            output.attr('src', e.target.result).removeClass('d-none');
            currentPhoto.addClass('d-none');
        };
        if (event.target.files[0]) {
            reader.readAsDataURL(event.target.files[0]);
        } else {
            output.attr('src', '#').addClass('d-none');
            currentPhoto.removeClass('d-none');
        }
    });
    
    const formCollapse = document.getElementById('formTambahProduk');
    if (formCollapse) {
        formCollapse.addEventListener('shown.bs.collapse', function () {
            document.getElementById('nama_produk').focus();
        });
    }
});
</script>