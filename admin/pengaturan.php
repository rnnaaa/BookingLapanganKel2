<?php
// 1. Cek Auth & Koneksi
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

// 2. Include Template Utama
include('../includes/header.php');
include('../includes/sidebar.php');

// ============================================================
// LOGIKA PHP: UPDATE & CRUD
// ============================================================

// A. Update Statistik Website
if (isset($_POST['update_config'])) {
    $lapangan = mysqli_real_escape_string($conn, $_POST['total_lapangan']);
    $jam = mysqli_real_escape_string($conn, $_POST['jam_operasional']);
    $dp = mysqli_real_escape_string($conn, $_POST['min_dp_persen']);

    $query = "UPDATE web_config SET total_lapangan='$lapangan', jam_operasional='$jam', min_dp_persen='$dp' WHERE id=1";
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Statistik website berhasil diperbarui!";
        echo "<script>window.location='pengaturan.php';</script>";
        exit;
    }
}

// B. Tambah Fasilitas
if (isset($_POST['tambah_fasilitas'])) {
    $icon = mysqli_real_escape_string($conn, $_POST['icon']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);

    $query = "INSERT INTO fasilitas (icon, nama, deskripsi) VALUES ('$icon', '$nama', '$deskripsi')";
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Fasilitas berhasil ditambahkan!";
        echo "<script>window.location='pengaturan.php';</script>";
        exit;
    }
}

// C. Tambah FAQ (Pertanyaan Umum)
if (isset($_POST['tambah_faq'])) {
    $tanya = mysqli_real_escape_string($conn, $_POST['pertanyaan']);
    $jawab = mysqli_real_escape_string($conn, $_POST['jawaban']);

    $query = "INSERT INTO faq (pertanyaan, jawaban) VALUES ('$tanya', '$jawab')";
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Pertanyaan baru berhasil ditambahkan!";
        echo "<script>window.location='pengaturan.php';</script>";
        exit;
    }
}

// D. Hapus Fasilitas
if (isset($_GET['hapus_fasilitas'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus_fasilitas']);
    mysqli_query($conn, "DELETE FROM fasilitas WHERE id = '$id'");
    $_SESSION['success'] = "Fasilitas berhasil dihapus!";
    echo "<script>window.location='pengaturan.php';</script>";
    exit;
}

// E. Hapus FAQ
if (isset($_GET['hapus_faq'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus_faq']);
    mysqli_query($conn, "DELETE FROM faq WHERE id = '$id'");
    $_SESSION['success'] = "FAQ berhasil dihapus!";
    echo "<script>window.location='pengaturan.php';</script>";
    exit;
}

// Ambil Data untuk Ditampilkan
$config = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM web_config WHERE id=1"));
$fasilitas = mysqli_query($conn, "SELECT * FROM fasilitas ORDER BY id ASC");
$faq_data = mysqli_query($conn, "SELECT * FROM faq ORDER BY id ASC");
?>

<div class="content-wrapper animate__animated animate__fadeIn">
    
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-cogs mr-2 text-primary"></i> Pengaturan Halaman Depan</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active">Pengaturan</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle mr-1"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 mb-4 position-sticky" style="top: 1rem;">
                        <div class="card-header text-white" style="background: linear-gradient(90deg, #0e5c91 0%, #2196f3 100%);">
                            <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i> Info Statistik</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Total Lapangan</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-layer-group"></i></span>
                                        <input type="number" name="total_lapangan" value="<?= $config['total_lapangan'] ?? 4 ?>" class="form-control" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Jam Operasional</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="far fa-clock"></i></span>
                                        <input type="text" name="jam_operasional" value="<?= $config['jam_operasional'] ?? '08-23' ?>" class="form-control" required>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Min DP Event (%)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-percent"></i></span>
                                        <input type="number" name="min_dp_persen" value="<?= $config['min_dp_persen'] ?? 30 ?>" class="form-control" required>
                                    </div>
                                </div>
                                <button type="submit" name="update_config" class="btn btn-primary w-100 fw-bold">
                                    <i class="fas fa-save mr-2"></i> Simpan Statistik
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header text-white" style="background: linear-gradient(90deg, #0e5c91 0%, #2196f3 100%);">
                            <h3 class="card-title"><i class="fas fa-list-ul mr-2"></i> Kelola Fasilitas</h3>
                        </div>
                        <div class="card-body">
                            <div class="card bg-light border mb-4">
                                <div class="card-body p-3">
                                    <form method="POST" class="row g-2 align-items-end">
                                        <div class="col-md-2">
                                            <label class="form-label small fw-bold">Icon</label>
                                            <input type="text" name="icon" class="form-control text-center" placeholder="🕌" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-bold">Nama</label>
                                            <input type="text" name="nama" class="form-control" placeholder="Wifi" required>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small fw-bold">Deskripsi</label>
                                            <input type="text" name="deskripsi" class="form-control" placeholder="Koneksi Cepat" required>
                                        </div>
                                        <div class="col-md-2 d-grid">
                                            <button type="submit" name="tambah_fasilitas" class="btn btn-success text-white">
                                                <i class="fas fa-plus"></i> Add
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle table-sm">
                                    <thead class="bg-secondary text-white text-center">
                                        <tr><th width="10%">Icon</th><th>Nama</th><th>Deskripsi</th><th width="10%">Aksi</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($fasilitas)): ?>
                                        <tr>
                                            <td class="text-center h5"><?= $row['icon'] ?></td>
                                            <td class="fw-bold"><?= htmlspecialchars($row['nama']) ?></td>
                                            <td class="small text-muted"><?= htmlspecialchars($row['deskripsi']) ?></td>
                                            <td class="text-center">
                                                <a href="?hapus_fasilitas=<?= $row['id'] ?>" onclick="return confirm('Hapus fasilitas?')" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0">
                        <div class="card-header text-white" style="background: linear-gradient(90deg, #1874ad 0%, #2196f3 100%);">
                            <h3 class="card-title"><i class="fas fa-question-circle mr-2"></i> Kelola FAQ (Pertanyaan Umum)</h3>
                        </div>
                        <div class="card-body">
                            <div class="card bg-light border mb-4">
                                <div class="card-body p-3">
                                    <h6 class="fw-bold text-primary mb-3"><i class="fas fa-plus-circle mr-1"></i> Tambah Pertanyaan Baru</h6>
                                    <form method="POST">
                                        <div class="mb-2">
                                            <input type="text" name="pertanyaan" class="form-control" placeholder="Tulis Pertanyaan (Contoh: Cara Booking?)" required>
                                        </div>
                                        <div class="mb-2">
                                            <textarea name="jawaban" class="form-control" rows="2" placeholder="Tulis Jawaban di sini..." required></textarea>
                                        </div>
                                        <div class="text-end">
                                            <button type="submit" name="tambah_faq" class="btn btn-sm btn-success px-4 fw-bold">
                                                <i class="fas fa-save mr-1"></i> Simpan FAQ
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="accordion" id="accordionFAQ">
                                <?php 
                                if (mysqli_num_rows($faq_data) > 0) {
                                    while ($faq = mysqli_fetch_assoc($faq_data)): 
                                        $id_faq = $faq['id'];
                                ?>
                                <div class="accordion-item mb-2 border">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed py-2 bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $id_faq ?>">
                                            <span class="fw-bold text-dark me-auto"><?= htmlspecialchars($faq['pertanyaan']) ?></span>
                                            <a href="?hapus_faq=<?= $id_faq ?>" onclick="return confirm('Yakin hapus pertanyaan ini?')" class="btn btn-xs btn-outline-danger ms-2 z-index-front" style="z-index: 10;">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </button>
                                    </h2>
                                    <div id="faq<?= $id_faq ?>" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                                        <div class="accordion-body bg-light text-muted small py-2">
                                            <?= nl2br(htmlspecialchars($faq['jawaban'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php 
                                    endwhile; 
                                } else {
                                    echo '<div class="text-center text-muted py-3">Belum ada pertanyaan umum.</div>';
                                }
                                ?>
                            </div>

                        </div>
                    </div>

                </div> </div> </div> </section>
</div>

<?php include('../includes/footer.php'); ?>

<script>
    // Auto hide alert
    window.setTimeout(function() {
        $(".alert").fadeTo(500, 0).slideUp(500, function(){ $(this).remove(); });
    }, 3000);
</script>