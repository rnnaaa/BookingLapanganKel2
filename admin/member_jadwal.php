<?php
//member_jadwal.php
require_once 'auth_check.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);
// session_start();
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../config/database.php';

$id_member = intval($_GET['id_member'] ?? 0);

/* ============================================================
   AMBIL DATA MEMBER
============================================================ */
$member_info = null;
if ($id_member > 0) {
    $stmt = $conn->prepare("
        SELECT m.*, u.nama AS nama_user, l.nama_lapangan
        FROM member m
        JOIN users u ON m.id_user = u.id_user
        JOIN lapangan l ON m.id_lapangan = l.id_lapangan
        WHERE m.id_member = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id_member);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows) $member_info = $res->fetch_assoc();
    $stmt->close();
}

/* ============================================================
   PROSES SIMPAN JADWAL
============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_member_post = intval($_POST['id_member'] ?? 0);
    $id_lapangan = intval($_POST['id_lapangan'] ?? 0);
    $tanggal_booking = $_POST['tanggal_booking'] ?? '';
    $jam_mulai = $_POST['jam_mulai'] ?? '';
    $jam_selesai = $_POST['jam_selesai'] ?? '';
    $id_detail = intval($_POST['id_detail'] ?? 0);

    if (!$id_member_post || !$id_lapangan || !$tanggal_booking || !$jam_mulai || !$jam_selesai || !$id_detail) {
        $_SESSION['toast_error'] = "❌ Semua kolom wajib diisi atau slot belum dipilih.";
        header("Location: member_jadwal.php?id_member=" . $id_member_post);
        exit;
    }

    $conn->begin_transaction();

    try {
        /* ============================================================
           VALIDASI MASA AKTIF MEMBER
        ============================================================ */
        $stmt = $conn->prepare("
            SELECT tanggal_mulai, tanggal_berakhir 
            FROM member 
            WHERE id_member=? AND status='aktif'
            LIMIT 1
        ");
        $stmt->bind_param("i", $id_member_post);
        $stmt->execute();
        $m = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$m) throw new Exception("⚠️ Member tidak aktif atau tidak ditemukan.");

        if ($tanggal_booking < $m['tanggal_mulai'] || $tanggal_booking > $m['tanggal_berakhir']) {
            throw new Exception("⚠️ Tanggal di luar masa aktif member.");
        }

        /* ============================================================
           VALIDASI PER MINGGU HANYA 1x
        ============================================================ */
        $ts = strtotime($tanggal_booking);
        $week_start = date('Y-m-d', strtotime("monday this week", $ts));
        $week_end   = date('Y-m-d', strtotime("sunday this week", $ts));

        $stmt = $conn->prepare("
            SELECT 1 
            FROM member_jadwal 
            WHERE id_member=? 
              AND tanggal_booking BETWEEN ? AND ?
              AND status='aktif'
            LIMIT 1
        ");
        $stmt->bind_param("iss", $id_member_post, $week_start, $week_end);
        $stmt->execute();
        $cek = $stmt->get_result();
        $stmt->close();

        if ($cek->num_rows) {
            throw new Exception("⚠️ Member sudah punya jadwal di minggu tersebut.");
        }

        /* ============================================================
           VALIDASI SLOT TERSEDIA
        ============================================================ */
        $stmt = $conn->prepare("
            SELECT jd.id_detail, jd.status, 
                   jh.tanggal, jh.id_lapangan,
                   jw.jam_mulai, jw.jam_selesai
            FROM jadwal_detail jd
            JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian
            JOIN jadwal_waktu jw ON jd.id_jadwal_waktu = jw.id_jadwal_waktu
            WHERE jd.id_detail=?
            LIMIT 1
        ");
        $stmt->bind_param("i", $id_detail);
        $stmt->execute();
        $slot = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$slot) throw new Exception("⚠️ Slot tidak ditemukan.");
        if ($slot['status'] !== 'tersedia') throw new Exception("⚠️ Slot sudah dibooking.");
        if ($slot['tanggal'] != $tanggal_booking) throw new Exception("⚠️ Tanggal slot tidak sesuai.");
        if ($slot['id_lapangan'] != $id_lapangan) throw new Exception("⚠️ Lapangan tidak cocok.");

        /* ============================================================
           AMBIL HARGA MEMBER
        ============================================================ */
        $harga = $conn->query("
            SELECT harga_per_jam_member 
            FROM lapangan 
            WHERE id_lapangan={$id_lapangan}
            LIMIT 1
        ")->fetch_assoc()['harga_per_jam_member'] ?? 0;

        if ($harga <= 0) {
            throw new Exception("⚠️ Harga member belum diatur.");
        }

        /* ============================================================
           INSERT member_jadwal
        ============================================================ */
        $stmt = $conn->prepare("
            INSERT INTO member_jadwal
            (id_member, id_lapangan, tanggal_booking, jam_mulai, jam_selesai,
             harga_per_jam_member, id_detail, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'aktif', NOW(), NOW())
        ");
        $stmt->bind_param("iisssdi",
            $id_member_post, $id_lapangan, $tanggal_booking,
            $jam_mulai, $jam_selesai, $harga, $id_detail
        );
        $stmt->execute();
        $last_id = $conn->insert_id;
        $stmt->close();

        /* ============================================================
           UPDATE jadwal_detail + isi id_member_jadwal
        ============================================================ */
        $stmt = $conn->prepare("
            UPDATE jadwal_detail 
            SET status='dibooking', id_member_jadwal=?
            WHERE id_detail=? AND status='tersedia'
            LIMIT 1
        ");
        $stmt->bind_param("ii", $last_id, $id_detail);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            throw new Exception("⚠️ Slot tidak tersedia atau sudah dipakai.");
        }

        $stmt->close();
        $conn->commit();

        $_SESSION['toast_success'] = "✅ Jadwal berhasil ditambahkan!";

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['toast_error'] = $e->getMessage();
    }

    header("Location: member_jadwal.php?id_member=" . $id_member_post);
    exit;
}

/* ============================================================
   DATA TABEL
============================================================ */
$qMembers = $conn->query("
    SELECT m.id_member, u.nama AS nama_user, l.nama_lapangan, m.id_lapangan 
    FROM member m
    JOIN users u ON m.id_user=u.id_user
    JOIN lapangan l ON m.id_lapangan=l.id_lapangan
    WHERE m.status='aktif'
    ORDER BY u.nama
");

$qJadwal = $conn->query("
    SELECT mj.*, u.nama AS nama_user, l.nama_lapangan 
    FROM member_jadwal mj
    JOIN member m ON mj.id_member=m.id_member
    JOIN users u ON m.id_user=u.id_user
    JOIN lapangan l ON mj.id_lapangan=l.id_lapangan
    " . ($id_member ? "WHERE mj.id_member=$id_member" : "") . "
    ORDER BY mj.tanggal_booking DESC
");

include('../includes/header.php');
// include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper animate__animated animate__fadeIn">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-calendar-week me-2"></i> Jadwal Member</h1>
      <div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="collapse" data-bs-target="#formTambahJadwal">
          <i class="fas fa-plus-circle"></i> Tambah Jadwal
        </button>
        <a href="member.php" class="btn btn-secondary shadow-sm">
          <i class="fas fa-arrow-left"></i> Kembali
        </a>
      </div>
    </div>
  </section>

  <section class="content">

    <?php if (!empty($_SESSION['toast_error'])): ?>
      <div class="alert alert-danger mt-3" id="alert-message">
        <?= $_SESSION['toast_error']; ?>
      </div>
      <?php unset($_SESSION['toast_error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['toast_success'])): ?>
      <div class="alert alert-success mt-3" id="alert-message">
        <?= $_SESSION['toast_success']; ?>
      </div>
      <?php unset($_SESSION['toast_success']); ?>
    <?php endif; ?>

    <div class="collapse mt-3" id="formTambahJadwal">
      <div class="card card-primary shadow-lg border-0">
        <div class="card-header text-white" style="background: linear-gradient(90deg,#0e5c91,#2196f3);">
          <h3 class="card-title mb-0"><i class="fas fa-plus-circle"></i> Tambah Jadwal Member</h3>
        </div>
        <form method="POST">
          <div class="card-body row g-3">
            <div class="col-md-4">
              <label>Member</label>
              <select name="id_member" id="id_member" class="form-select select2-bootstrap4" required>
                <option value="">-- Pilih Member --</option>
                <?php while($m=$qMembers->fetch_assoc()): ?>
                  <option value="<?= $m['id_member'] ?>"
                    data-id-lapangan="<?= $m['id_lapangan'] ?>"
                    data-nama-lapangan="<?= htmlspecialchars($m['nama_lapangan']) ?>"
                    <?= $id_member == $m['id_member'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($m['nama_user']) ?> (<?= htmlspecialchars($m['nama_lapangan']) ?>)
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="col-md-4">
              <label>Lapangan</label>
              <input type="text" class="form-control" id="nama_lapangan" readonly>
              <input type="hidden" name="id_lapangan" id="id_lapangan">
            </div>

            <div class="col-md-4">
              <label>Tanggal Booking</label>
              <input type="date" name="tanggal_booking" id="tanggal_booking" class="form-control" min="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="col-md-12" id="slotContainer" style="display:none;">
              <label>Pilih Slot Jam:</label>
              <div id="slotList" class="d-flex flex-wrap gap-2"></div>
              <input type="hidden" name="jam_mulai" id="jam_mulai">
              <input type="hidden" name="jam_selesai" id="jam_selesai">
              <input type="hidden" name="id_detail" id="id_detail">
            </div>
          </div>
          <div class="card-footer text-end">
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card shadow-lg border-0 mt-4">
      <div class="card-header text-white" style="background: linear-gradient(90deg,#0e5c91,#2196f3);">
        <h3 class="card-title mb-0"><i class="fas fa-list"></i> Daftar Jadwal Member</h3>
      </div>
      <div class="card-body table-responsive">
        <table id="tblMemberJadwal" class="table table-bordered table-striped table-hover align-middle w-100">
          <thead class="bg-light text-center">
            <tr>
              <th>No</th>
              <th>Member</th>
              <th>Lapangan</th>
              <th>Tanggal</th>
              <th>Jam</th>
              <th>Status</th>
              <th>Dibuat</th>
            </tr>
          </thead>
          <tbody>
            <?php $no=1; while($r=$qJadwal->fetch_assoc()): ?>
              <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($r['nama_user']) ?></td>
                <td class="text-center"><?= htmlspecialchars($r['nama_lapangan']) ?></td>
                <td class="text-center"><?= date('d-m-Y', strtotime($r['tanggal_booking'])) ?></td>
                <td class="text-center"><?= substr($r['jam_mulai'],0,5) . ' - ' . substr($r['jam_selesai'],0,5) ?></td>
                <td class="text-center">
                  <span class="badge bg-<?= $r['status']=='aktif'?'success':'secondary' ?>"><?= ucfirst($r['status']) ?></span>
                </td>
                <td class="text-center"><?= date('d-m-Y', strtotime($r['created_at'])) ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

  </section>
</div>

<?php include('../includes/footer.php'); ?>

<script>
$(function() {
  $('#id_member').select2({ theme: 'bootstrap4', placeholder: "Cari dan Pilih Member", allowClear: true, width: '100%' });
  // $('#tblMemberJadwal').DataTable();
  const alertElement = $('#alert-message');
  if (alertElement.length) setTimeout(() => alertElement.fadeOut(800), 2000);
});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const $idMember = $('#id_member');
  const idLapangan = document.getElementById('id_lapangan');
  const namaLapangan = document.getElementById('nama_lapangan');
  const tanggal = document.getElementById('tanggal_booking');
  const slotContainer = document.getElementById('slotContainer');
  const slotList = document.getElementById('slotList');
  const jamMulai = document.getElementById('jam_mulai');
  const jamSelesai = document.getElementById('jam_selesai');
  const idDetailInput = document.getElementById('id_detail');

  // Load slots dari server
  function loadSlots() {
    const idL = idLapangan.value;
    const tgl = tanggal.value;
    slotList.innerHTML = '';
    idDetailInput.value = '';
    jamMulai.value = '';
    jamSelesai.value = '';

    if (!idL || !tgl) { slotContainer.style.display='none'; return; }

    // tambahkan loading sederhana
    slotList.innerHTML = '<div class="spinner-border spinner-border-sm me-2" role="status"></div> Memuat...';

    fetch(`member_jadwal_get_slot.php?id_lapangan=${encodeURIComponent(idL)}&tanggal=${encodeURIComponent(tgl)}`)
      .then(res => res.json())
      .then(data => {
        slotList.innerHTML = '';
        slotContainer.style.display = 'block';

        if (!data) {
          slotList.innerHTML = '<p class="text-danger">Respon tidak valid dari server.</p>';
          return;
        }
        if (data.status !== 'success') {
          slotList.innerHTML = `<p class="text-danger">${data.message}</p>`;
          return;
        }
        if (!data.slots.length) {
          slotList.innerHTML = '<p class="text-danger">Tidak ada slot tersedia.</p>';
          return;
        }

        data.slots.forEach(s => {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'btn btn-outline-success btn-sm me-2 mb-2';
          btn.textContent = `${s.jam_mulai} - ${s.jam_selesai}`;
          btn.dataset.idDetail = s.id_detail;
          btn.dataset.jamMulai = s.jam_mulai;
          btn.dataset.jamSelesai = s.jam_selesai;

          if (s.status !== 'tersedia') {
            btn.disabled = true;
            btn.classList.remove('btn-outline-success');
            btn.classList.add('btn-secondary');
            btn.textContent += ' (Booked)';
          } else {
            btn.addEventListener('click', () => {
              // clear selection
              slotList.querySelectorAll('button').forEach(b => b.classList.remove('btn-success','active'));
              btn.classList.add('btn-success','active');
              jamMulai.value = btn.dataset.jamMulai;
              jamSelesai.value = btn.dataset.jamSelesai;
              idDetailInput.value = btn.dataset.idDetail;
            });
          }
          slotList.appendChild(btn);
        });
      })
      .catch(err => {
        slotContainer.style.display='block';
        slotList.innerHTML = `<p class="text-danger">Gagal memuat slot: ${err.message}</p>`;
      });
  }

  // ketika user memilih member lewat select2
  $idMember.on('select2:select', function(e) {
    const option = e.params.data.element ? e.params.data.element : null;
    if (!option) {
      // fallback: gunakan value untuk cari option
      const val = $(this).val();
      const opt = this.querySelector('option[value="'+val+'"]');
      if (opt) {
        idLapangan.value = opt.dataset.idLapangan || '';
        namaLapangan.value = opt.dataset.namaLapangan || '';
      }
    } else {
      idLapangan.value = option.dataset.idLapangan || '';
      namaLapangan.value = option.dataset.namaLapangan || '';
    }
    // reset tanggal & slot ketika ganti member
    tanggal.value = '';
    slotList.innerHTML = '';
    slotContainer.style.display = 'none';
  });

  // juga handle ketika member dikosongkan (clear)
  $idMember.on('select2:clear', function() {
    idLapangan.value = '';
    namaLapangan.value = '';
    tanggal.value = '';
    slotList.innerHTML = '';
    slotContainer.style.display = 'none';
  });

  // ketika tanggal berubah -> load slot sesuai id_lapangan & tanggal
  tanggal.addEventListener('change', loadSlots);

  // Jika URL menyediakan id_member, auto open form dan preselect member (butuh setTimeout agar select2 siap)
  const preIdMember = <?= json_encode($id_member, JSON_NUMERIC_CHECK) ?>;
  if (preIdMember && preIdMember > 0) {
    // buka collapse
    const collapseEl = document.querySelector('#formTambahJadwal');
    if (collapseEl) {
      new bootstrap.Collapse(collapseEl, { show: true });
    }

    // pastikan select2 siap, lalu set value & trigger select2:select
    setTimeout(() => {
      $idMember.val(preIdMember).trigger('change');

      // manually fire select2:select behaviour to populate lapangan & nama
      const opt = document.querySelector('#id_member option[value="'+preIdMember+'"]');
      if (opt) {
        idLapangan.value = opt.dataset.idLapangan || '';
        namaLapangan.value = opt.dataset.namaLapangan || '';
      }

      // jika tanggal sudah diisi (mis. dari form state) maka load slots
      if (tanggal.value) {
        loadSlots();
      }
    }, 200);
  } else {
    // jika user memilih manual dari tombol "Tambah Jadwal" — kita ingin fokus ke select member
    document.querySelector('[data-bs-target="#formTambahJadwal"]').addEventListener('click', () => {
      setTimeout(() => $idMember.select2('open'), 350);
    });
  }

  // jika user sudah memilih member lalu memilih tanggal, atau sebaliknya — loadSlots akan menampilkan slot
});
</script>
