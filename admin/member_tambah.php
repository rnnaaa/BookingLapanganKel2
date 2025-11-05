<?php
ob_start();
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');

// Ambil user yang belum jadi member
$users = mysqli_query($conn, "
  SELECT id_user, nama 
  FROM users 
  WHERE id_user NOT IN (SELECT id_user FROM member)
  ORDER BY nama ASC
");

// Proses tambah member
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $id_user = mysqli_real_escape_string($conn, $_POST['id_user']);
  $jenis = mysqli_real_escape_string($conn, $_POST['jenis_member']);
  $tgl_mulai = mysqli_real_escape_string($conn, $_POST['tgl_mulai']);
  $tgl_akhir = mysqli_real_escape_string($conn, $_POST['tgl_berakhir']);
  $harga = mysqli_real_escape_string($conn, $_POST['harga_member']);
  $status = mysqli_real_escape_string($conn, $_POST['status']);

  // Validasi form
  if (empty($id_user) || empty($jenis) || empty($tgl_mulai) || empty($tgl_akhir) || empty($harga)) {
    $error = "Semua kolom wajib diisi!";
  } else {
    // Cek apakah user sudah jadi member
    $cek = mysqli_query($conn, "SELECT id_member FROM member WHERE id_user = '$id_user'");
    if (mysqli_num_rows($cek) > 0) {
      $error = "Pengguna ini sudah menjadi member!";
    } else {
      // Insert data ke tabel member
      $query = "
        INSERT INTO member (id_user, jenis_member, tgl_mulai, tgl_berakhir, status, created_at)
        VALUES ('$id_user', '$jenis', '$tgl_mulai', '$tgl_akhir', '$status', NOW())
      ";
      $insert = mysqli_query($conn, $query);

      if ($insert) {
        // Update tipe_user pada tabel users menjadi 'member'
        mysqli_query($conn, "UPDATE users SET tipe_user = 'member' WHERE id_user = '$id_user'");

        $_SESSION['toast_success'] = "Member baru berhasil ditambahkan dan tipe user diperbarui!";
        header("Location: member.php");
        exit;
      } else {
        $error = "Gagal menyimpan data member: " . mysqli_error($conn);
      }
    }
  }
}
?>

<div class="content-wrapper animate__animated animate__fadeIn">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-user-plus mr-2"></i> Tambah Member Baru</h1>
      <a href="member.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header bg-info text-white">
          <h3 class="card-title"><i class="fas fa-user-clock mr-2"></i> Form Pendaftaran Member</h3>
        </div>

        <form method="POST">
          <div class="card-body">
            <?php if (!empty($error)): ?>
              <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="id_user">Pilih Pengguna</label>
                <select name="id_user" id="id_user" class="form-control select2" required>
                  <option value="">-- Pilih Pengguna --</option>
                  <?php while ($u = mysqli_fetch_assoc($users)): ?>
                    <option value="<?= $u['id_user'] ?>"><?= htmlspecialchars($u['nama']) ?></option>
                  <?php endwhile; ?>
                </select>
              </div>

              <div class="col-md-6 mb-3">
                <label for="jenis_member">Jenis Member</label>
                <select name="jenis_member" id="jenis_member" class="form-control" required>
                  <option value="Bulanan">Bulanan</option>
                  <option value="3 Bulan">3 Bulan</option>
                  <option value="6 Bulan">6 Bulan</option>
                  <option value="Tahunan">Tahunan</option>
                </select>
              </div>

              <div class="col-md-6 mb-3">
                <label for="tgl_mulai">Tanggal Mulai</label>
                <input type="date" name="tgl_mulai" id="tgl_mulai" class="form-control" required min="<?= date('Y-m-d') ?>">
              </div>

              <div class="col-md-6 mb-3">
                <label for="tgl_berakhir">Tanggal Berakhir</label>
                <input type="date" name="tgl_berakhir" id="tgl_berakhir" class="form-control" readonly>
              </div>

              <div class="col-md-6 mb-3">
                <label for="harga_member">Harga Membership (Rp)</label>
                <input type="number" name="harga_member" id="harga_member" class="form-control" placeholder="Contoh: 300000" required>
              </div>

              <div class="col-md-6 mb-3">
                <label for="status">Status</label>
                <select name="status" id="status" class="form-control" required>
                  <option value="aktif">Aktif</option>
                  <option value="nonaktif">Nonaktif</option>
                </select>
              </div>
            </div>
          </div>

          <div class="card-footer text-right">
            <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</div>

<?php include('../includes/footer.php');
ob_end_flush();
?>

<script>
$(function() {
  $('#jenis_member, #tgl_mulai').on('change', function() {
    const jenis = $('#jenis_member').val();
    const mulai = $('#tgl_mulai').val();
    if (!mulai) return;

    let end = new Date(mulai);
    switch (jenis) {
      case '3 Bulan': end.setMonth(end.getMonth() + 3); break;
      case '6 Bulan': end.setMonth(end.getMonth() + 6); break;
      case 'Tahunan': end.setFullYear(end.getFullYear() + 1); break;
      default: end.setMonth(end.getMonth() + 1);
    }

    const tglBerakhir = end.toISOString().split('T')[0];
    $('#tgl_berakhir').val(tglBerakhir);
  });
});
</script>
