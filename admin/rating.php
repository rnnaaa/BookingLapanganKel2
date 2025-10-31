<?php
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<!-- ================== CONTENT WRAPPER ================== -->
<div class="content-wrapper animate__animated animate__fadeIn">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-star text-warning mr-2"></i> Manajemen Rating</h1>
      <button id="btnTambah" class="btn btn-primary shadow-sm">
        <i class="fas fa-plus-circle"></i> Tambah Rating
      </button>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header bg-info text-white">
          <h3 class="card-title"><i class="fas fa-list mr-2"></i> Data Rating Pengguna</h3>
        </div>
        <div class="card-body">
          <table id="tblRating" class="table table-bordered table-striped table-hover">
            <thead class="text-center bg-light">
              <tr>
                <th>No</th>
                <th>Nama User</th>
                <th>Lapangan</th>
                <th>Nilai</th>
                <th>Komentar</th>
                <th>Dibuat</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody id="ratingData"></tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include('../includes/footer.php'); ?>

<!-- ================== RATING SCRIPT ================== -->
<script>
$(document).ready(function() {

  // 🌀 Load Data Rating via AJAX
  function loadData() {
    $.ajax({
      url: 'rating_action.php',
      type: 'GET',
      data: { action: 'read' },
      beforeSend: function() {
        $('#ratingData').html(`
          <tr>
            <td colspan="7" class="text-center text-muted p-4">
              <i class="fas fa-spinner fa-spin"></i> Memuat data...
            </td>
          </tr>
        `);
      },
      success: function(response) {
        $('#ratingData').hide().html(response).fadeIn(400);

        // Tunggu tabel ter-render dulu, biar DataTables dari footer bisa aktif otomatis
        setTimeout(() => {
          if (!$.fn.DataTable.isDataTable('#tblRating')) {
            $('#tblRating').DataTable();
          }
        }, 500);
      },
      error: function() {
        toastr.error("Gagal memuat data rating.");
      }
    });
  }

  // Load saat halaman dibuka
  loadData();

  // ➕ Tambah Rating
  $('#btnTambah').on('click', function() {
    Swal.fire({
      title: 'Tambah Rating Baru',
      html: `
        <select id="user" class="swal2-input">
          <option value="">Pilih User</option>
          <?php
          $u = mysqli_query($conn, "SELECT id_user, nama FROM users ORDER BY nama ASC");
          while($row = mysqli_fetch_assoc($u)) echo "<option value='{$row['id_user']}'>{$row['nama']}</option>";
          ?>
        </select>
        <select id="lapangan" class="swal2-input">
          <option value="">Pilih Lapangan</option>
          <?php
          $l = mysqli_query($conn, "SELECT id_lapangan, nama_lapangan FROM lapangan ORDER BY nama_lapangan ASC");
          while($row = mysqli_fetch_assoc($l)) echo "<option value='{$row['id_lapangan']}'>{$row['nama_lapangan']}</option>";
          ?>
        </select>
        <select id="nilai" class="swal2-input">
          <option value="">Pilih Nilai</option>
          <option value="1">1 ⭐</option>
          <option value="2">2 ⭐⭐</option>
          <option value="3">3 ⭐⭐⭐</option>
          <option value="4">4 ⭐⭐⭐⭐</option>
          <option value="5">5 ⭐⭐⭐⭐⭐</option>
        </select>
        <textarea id="komentar" class="swal2-textarea" placeholder="Tulis komentar..."></textarea>
      `,
      confirmButtonText: 'Simpan',
      showCancelButton: true,
      cancelButtonText: 'Batal',
      focusConfirm: false,
      preConfirm: () => {
        const user = $('#user').val(),
              lapangan = $('#lapangan').val(),
              nilai = $('#nilai').val(),
              komentar = $('#komentar').val();
        if (!user || !lapangan || !nilai) {
          Swal.showValidationMessage('Semua field wajib diisi!');
          return false;
        }
        $.post('rating_action.php', {
          action: 'create',
          user_id: user,
          lapangan_id: lapangan,
          nilai: nilai,
          komentar: komentar
        }, function(res) {
          toastr.success(res);
          loadData();
        }).fail(() => {
          toastr.error("Terjadi kesalahan saat menambah rating.");
        });
      }
    });
  });

  // 🗑️ Hapus Rating
  $(document).on('click', '.btn-delete', function(e) {
    e.preventDefault();
    const id = $(this).data('id');

    Swal.fire({
      title: 'Yakin ingin menghapus rating ini?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Ya, hapus!',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        $.post('rating_action.php', { action: 'delete', id: id }, function(res) {
          toastr.success(res);
          loadData();
        }).fail(() => {
          toastr.error("Gagal menghapus rating.");
        });
      }
    });
  });
});
</script>
