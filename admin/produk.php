<?php
// produk.php
session_start();
require_once __DIR__ . '/../config/database.php';
// Pastikan file config/database.php sudah terhubung ke $conn

// Ambil semua data produk
$sql = "SELECT * FROM produk ORDER BY created_at DESC";
$query = mysqli_query($conn, $sql);

// Anda mungkin perlu auth_check di sini
// require_once __DIR__ . '/auth_check.php'; 

include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper animate__animated animate__fadeIn">
    <section class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <h1><i class="fas fa-boxes mr-2 icon-gradient-blue"></i> Manajemen Produk</h1>
            
            <button class="btn btn-primary shadow-sm" data-bs-toggle="collapse" data-bs-target="#formTambahProduk">
                <i class="fas fa-plus-circle"></i> Tambah Produk Baru
            </button>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            
            <?php 
            // Cek dan tampilkan toast
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
            // Hapus session success/error lama untuk menghindari konflik
            unset($_SESSION['success']);
            unset($_SESSION['error']);
            ?>
            <div class="collapse mt-3" id="formTambahProduk">
                <div class="card card-primary shadow-lg border-0">
                    <div class="card-header text-white" style="background: linear-gradient(90deg,#0e5c91,#2196f3);">
                        <h3 class="card-title mb-0"><i class="fas fa-plus-circle"></i> Formulir Tambah Produk</h3>
                    </div>
                    <form method="POST" action="produk_proses.php?action=tambah" enctype="multipart/form-data">
                        <div class="card-body row g-3">
                            
                            <div class="col-md-6">
                                <label for="nama_produk" class="form-label">Nama Produk</label>
                                <input type="text" class="form-control" id="nama_produk" name="nama_produk" required>
                            </div>

                            <div class="col-md-6">
                                <label for="kategori" class="form-label">Kategori</label>
                                <input type="text" class="form-control" id="kategori" name="kategori" value="Umum">
                            </div>

                            <div class="col-md-4">
                                <label for="harga" class="form-label">Harga (Rp)</label>
                                <input type="number" class="form-control" id="harga" name="harga" step="0.01" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select" required>
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Nonaktif</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="foto" class="form-label">Foto Produk</label>
                                <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                            </div>

                            <div class="col-md-12">
                                <label for="deskripsi" class="form-label">Deskripsi</label>
                                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"></textarea>
                            </div>

                        </div>
                        <div class="card-footer text-end">
                            <button type="button" class="btn btn-secondary me-2" data-bs-toggle="collapse" data-bs-target="#formTambahProduk">Batal</button>
                            <button type="submit" class="btn btn-success btn-action-loader"><i class="fas fa-save"></i> Simpan Produk</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-header text-white" 
                        style="background: linear-gradient(90deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
                                box-shadow: inset 0 -2px 8px rgba(0, 0, 0, 0.15);">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-list mr-2"></i> Data Produk
                    </h3>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="example1" class="table table-bordered table-striped table-hover align-middle">
                            <thead class="text-center bg-light">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Produk</th>
                                    <th>Kategori</th>
                                    <th>Harga</th>
                                    <th>Status</th>
                                    <th>Foto</th>
                                    <th>Dibuat</th>
                                    <th>Aksi</th>
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
                                        $status_class = $row['status'] === 'aktif' ? 'bg-success' : 'bg-danger';
                                ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                                    <td class="text-center"><?= htmlspecialchars($row['kategori']) ?></td>
                                    <td class="text-end">Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                                    <td class="text-center">
                                        <span class="badge <?= $status_class ?>"><?= ucfirst($row['status']) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($row['foto'])): ?>
                                            <img src="../uploads/produk/<?= $row['foto'] ?>" alt="Foto Produk" style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center small"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>

                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="#" 
                                                class="btn btn-sm text-white btn-edit-produk" 
                                                data-id="<?= $row['id_produk'] ?>"
                                                title="Edit"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEditProduk"
                                                style="background: linear-gradient(90deg, #0e5c91, #2196f3); border: none;"> 
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="produk_hapus.php?id=<?= $row['id_produk'] ?>" 
                                                class="btn btn-sm btn-danger btn-action-loader ml-2" 
                                                title="Hapus"
                                                data-confirm="Apakah Anda yakin ingin menghapus produk ini? Tindakan ini tidak dapat dibatalkan."> 
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

<div class="modal fade" id="modalEditProduk" tabindex="-1" aria-labelledby="modalEditProdukLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white" style="background: linear-gradient(90deg,#0e5c91,#2196f3) !important;">
                <h5 class="modal-title" id="modalEditProdukLabel"><i class="fas fa-edit"></i> Edit Produk</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body" id="modal-body-edit">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat data produk...</p>
                </div>
            </div>
            
        </div>
    </div>
</div>
<?php include('../includes/footer.php'); ?>

<style>
.bg-gradient-primary {
    background: linear-gradient(90deg,#0e5c91,#2196f3);
}
.btn-close-white {
    filter: invert(1);
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function () {

    // --- LOGIC btn-action-loader (untuk hapus dan submit form tambah) ---
    document.body.addEventListener("click", function (e) {
        const btn = e.target.closest(".btn-action-loader");
        if (!btn) return;

        // Khusus untuk tombol submit form, kita hanya ingin menampilkan spinner sesaat
        if (btn.type === 'submit' && btn.closest('#formTambahProduk')) {
             if (!btn.dataset.originalHtml) {
                 btn.dataset.originalHtml = btn.innerHTML;
             }
             btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
             return; // Biarkan form submit
        }

        e.preventDefault();

        const targetUrl = btn.getAttribute("href");
        const confirmMsg = btn.getAttribute("data-confirm") || null;

        // Konfirmasi (untuk tombol hapus)
        if (confirmMsg && !confirm(confirmMsg)) {
            return;
        }

        // Simpan isi tombol asli
        if (!btn.dataset.originalHtml) {
            btn.dataset.originalHtml = btn.innerHTML;
        }

        // Disable tombol dan tampilkan spinner
        btn.style.pointerEvents = "none";
        btn.style.opacity = "0.6";
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        // Navigasi
        setTimeout(() => {
            window.location.href = targetUrl;
        }, 300);

        // SAFEGUARD: kalau gagal pindah halaman
        setTimeout(() => {
            btn.style.pointerEvents = "auto";
            btn.style.opacity = "1";
            btn.innerHTML = btn.dataset.originalHtml;
        }, 6000);
    });
    // --- END LOGIC btn-action-loader ---


    // --- LOGIC MODAL EDIT PRODUK ---
    const modalEdit = new bootstrap.Modal(document.getElementById('modalEditProduk'));
    const modalBody = document.getElementById('modal-body-edit');
    const defaultLoader = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Memuat data produk...</p>
        </div>
    `;

    document.body.addEventListener('click', function (e) {
        const editButton = e.target.closest('.btn-edit-produk'); 
        
        if (editButton) {
            e.preventDefault();
            const idProduk = editButton.dataset.id;
            
            // 1. Reset Modal Body dan tampilkan Loader
            modalBody.innerHTML = defaultLoader;
            modalEdit.show();

            // 2. Lakukan permintaan AJAX
            $.ajax({
                url: 'produk_fetch.php', // File untuk mengambil data
                type: 'GET',
                data: { id: idProduk },
                success: function(response) {
                    // Isi konten modal dengan form yang terisi data
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

    // Event listener untuk preview foto di dalam modal yang dimuat secara dinamis
    $(document).on('change', '#foto_baru_edit', function(event) {
        const reader = new FileReader();
        const output = $('#foto_preview_edit');
        const currentPhoto = $('#current_photo_edit');
        
        reader.onload = function(e) {
            output.attr('src', e.target.result).removeClass('d-none');
            currentPhoto.addClass('d-none'); // Sembunyikan foto lama
        };
        if (event.target.files[0]) {
            reader.readAsDataURL(event.target.files[0]);
        } else {
            // Jika file dibatalkan, tampilkan lagi foto lama
            output.attr('src', '#').addClass('d-none');
            currentPhoto.removeClass('d-none');
        }
    });
    
    // Tambahan untuk Collapse (agar lebih smooth)
    const formCollapse = document.getElementById('formTambahProduk');
    if (formCollapse) {
        formCollapse.addEventListener('shown.bs.collapse', function () {
            // Fokus ke input pertama saat form terbuka
            document.getElementById('nama_produk').focus();
        });
    }
    
});
</script>