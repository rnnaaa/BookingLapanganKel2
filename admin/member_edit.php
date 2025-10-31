<?php
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');

if (!isset($_GET['id'])) {
  header("Location: member.php");
  exit;
}

$id = $_GET['id'];

// Ambil data member
$member = mysqli_query($conn, "SELECT * FROM member WHERE id_member = '$id'");
if (mysqli_num_rows($member) == 0) {
  echo "<script>alert('Data member tidak ditemukan!');window.location='member.php';</script>";
  exit;
}
$m = mysqli_fetch_assoc($member);

// Ambil data user
$users = mysqli_query($conn, "SELECT id_user, nama FROM users WHERE role = 'user' ORDER BY nama ASC");

// Ambil data lapangan
$lapangan = mysqli_query($conn, "SELECT id_lapangan, nama_lapangan FROM lapangan ORDER BY id_lapangan ASC");

// Ambil jadwal mingguan member
$jadwal = mysqli_query($conn, "
  SELECT * FROM member_jadwal mj
  JOIN lapangan l ON mj.id_lapangan = l.id_lapangan
  WHERE mj.id_member = '$id'
");
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-edit mr-2"></i> Edit Data Member</h1>
      <a href="member.php" class="btn btn-secondary shadow-sm">
        <i class="fas fa-arrow-left"></i> Kembali
      </a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <form action="member_edit_proses.php" method="POST" id="formEdit">
        <input type="hidden" name="id_member" value="<?= $id ?>">
        <div class="card shadow-lg border-0">
          <div class="card-header bg-warning text-white">
            <h3 class="card-title"><i class="fas fa-user-edit mr-2"></i> Formulir Edit Member</h3>
          </div>

          <div class="card-body">
            <div class="row">
              <!-- Nama User -->
              <div class="col-md-6">
                <div class="form-group">
                  <label>Nama User <span class="text-danger">*</span></label>
                  <select name="id_user" class="form-control select2" required>
                    <option value="">-- Pilih User --</option>
                    <?php while ($u = mysqli_fetch_assoc($users)): ?>
                      <option value="<?= $u['id_user'] ?>" <?= $m['id_user'] == $u['id_user'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['nama']) ?>
                      </option>
                    <?php endwhile; ?>
                  </select>
                </div>
              </div>

              <!-- Jenis Member -->
              <div class="col-md-6">
                <div class="form-group">
                  <label>Jenis Member <span class="text-danger">*</span></label>
                  <select name="jenis_member" id="jenis_member" class="form-control" required>
                    <?php
                    $options = ['1 Bulan', '3 Bulan', '6 Bulan', '12 Bulan'];
                    foreach ($options as $opt):
                      $sel = $opt == $m['jenis_member'] ? 'selected' : '';
                      echo "<option value='$opt' $sel>$opt</option>";
                    endforeach;
                    ?>
                  </select>
                </div>
              </div>

              <!-- Tanggal Mulai -->
              <div class="col-md-6">
                <div class="form-group">
                  <label>Tanggal Mulai <span class="text-danger">*</span></label>
                  <input type="date" name="tgl_mulai" id="tgl_mulai" value="<?= $m['tgl_mulai'] ?>" class="form-control" required>
                </div>
              </div>

              <!-- Tanggal Berakhir -->
              <div class="col-md-6">
                <div class="form-group">
                  <label>Tanggal Berakhir</label>
                  <input type="date" name="tgl_berakhir" id="tgl_berakhir" value="<?= $m['tgl_berakhir'] ?>" class="form-control" readonly>
                </div>
              </div>

              <!-- Status -->
              <div class="col-md-6">
                <div class="form-group">
                  <label>Status</label>
                  <select name="status" class="form-control">
                    <option value="aktif" <?= $m['status']=='aktif'?'selected':'' ?>>Aktif</option>
                    <option value="nonaktif" <?= $m['status']=='nonaktif'?'selected':'' ?>>Nonaktif</option>
                  </select>
                </div>
              </div>

              <!-- Keterangan -->
              <div class="col-md-6">
                <div class="form-group">
                  <label>Keterangan</label>
                  <textarea name="keterangan" class="form-control"><?= htmlspecialchars($m['keterangan']) ?></textarea>
                </div>
              </div>
            </div>

            <hr>

            <!-- Jadwal Mingguan -->
            <h5 class="mt-3"><i class="fas fa-calendar-week mr-2"></i> Jadwal Tetap Mingguan</h5>
            <small class="text-muted">Klik <b>Tambah Jadwal</b> untuk menambah jadwal baru.</small>
            
            <table class="table table-bordered mt-3" id="tblJadwal">
              <thead class="bg-light text-center">
                <tr>
                  <th>Hari</th>
                  <th>Jam Mulai</th>
                  <th>Jam Selesai</th>
                  <th>Lapangan</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($j = mysqli_fetch_assoc($jadwal)): ?>
                  <tr>
                    <td>
                      <select name="hari[]" class="form-control">
                        <?php
                        $hari = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
                        foreach ($hari as $h):
                          $sel = $h == $j['hari'] ? 'selected' : '';
                          echo "<option $sel>$h</option>";
                        endforeach;
                        ?>
                      </select>
                    </td>
                    <td><input type="time" name="jam_mulai[]" value="<?= substr($j['jam_mulai'],0,5) ?>" class="form-control" required></td>
                    <td><input type="time" name="jam_selesai[]" value="<?= substr($j['jam_selesai'],0,5) ?>" class="form-control" required></td>
                    <td>
                      <select name="id_lapangan[]" class="form-control" required>
                        <?php
                        mysqli_data_seek($lapangan, 0);
                        while ($l = mysqli_fetch_assoc($lapangan)):
                          $sel = $l['id_lapangan'] == $j['id_lapangan'] ? 'selected' : '';
                          echo "<option value='{$l['id_lapangan']}' $sel>{$l['nama_lapangan']}</option>";
                        endwhile;
                        ?>
                      </select>
                    </td>
                    <td class="text-center">
                      <button type="button" class="btn btn-sm btn-danger removeRow"><i class="fas fa-trash"></i></button>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>

            <button type="button" class="btn btn-sm btn-success" id="addRow">
              <i class="fas fa-plus"></i> Tambah Jadwal
            </button>
          </div>

          <div class="card-footer text-right">
            <button type="submit" class="btn btn-warning">
              <i class="fas fa-save"></i> Simpan Perubahan
            </button>
            <a href="member.php" class="btn btn-secondary">Batal</a>
          </div>
        </div>
      </form>
    </div>
  </section>
</div>

<?php include('../includes/footer.php'); ?>

<!-- ================== JAVASCRIPT SECTION ================== -->
<script>
$(document).ready(function() {
  // Hitung otomatis tanggal berakhir
  $('#tgl_mulai, #jenis_member').on('change', function() {
    const start = $('#tgl_mulai').val();
    const jenis = $('#jenis_member').val();
    if (!start || !jenis) return;

    const durasi = {'1 Bulan':1,'3 Bulan':3,'6 Bulan':6,'12 Bulan':12}[jenis];
    const tgl = new Date(start);
    tgl.setMonth(tgl.getMonth() + durasi);
    $('#tgl_berakhir').val(tgl.toISOString().split('T')[0]);
  });

  // Tambah baris jadwal baru
  $("#addRow").click(function() {
    const row = `
      <tr>
        <td>
          <select name="hari[]" class="form-control" required>
            <option value="">-- Hari --</option>
            <option>Senin</option><option>Selasa</option><option>Rabu</option>
            <option>Kamis</option><option>Jumat</option><option>Sabtu</option><option>Minggu</option>
          </select>
        </td>
        <td><input type="time" name="jam_mulai[]" class="form-control" required></td>
        <td><input type="time" name="jam_selesai[]" class="form-control" required></td>
        <td>
          <select name="id_lapangan[]" class="form-control" required>
            <?php mysqli_data_seek($lapangan, 0); while ($l = mysqli_fetch_assoc($lapangan)): ?>
              <option value="<?= $l['id_lapangan'] ?>"><?= htmlspecialchars($l['nama_lapangan']) ?></option>
            <?php endwhile; ?>
          </select>
        </td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-danger removeRow"><i class="fas fa-trash"></i></button></td>
      </tr>`;
    $("#tblJadwal tbody").append(row);
  });

  // Hapus baris jadwal
  $(document).on('click', '.removeRow', function() {
    $(this).closest('tr').remove();
  });

  // Validasi form
  $("#formEdit").on("submit", function(e) {
    if ($("#tblJadwal tbody tr").length === 0) {
      e.preventDefault();
      toastr.warning("Minimal harus ada 1 jadwal tetap mingguan!");
    }
  });
});
</script>
