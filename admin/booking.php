<?php
// booking.php - WALK-IN FIXED (Nama Tidak Berubah-ubah)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// Ambil ID Admin (penanggung jawab)
$admin_id = $_SESSION['id_user'] ?? 0; 

// === PROSES SIMPAN BOOKING WALK-IN ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_walkin_booking') {
    
    $id_lapangan     = intval($_POST['id_lapangan'] ?? 0);
    $tanggal         = $_POST['tanggal'] ?? '';
    $slot_ids        = $_POST['slot_ids'] ?? [];
    $nama_customer   = trim($_POST['nama_customer'] ?? '');
    $no_hp_customer  = trim($_POST['no_hp_customer'] ?? '');

    // Validasi Input
    if (!$id_lapangan || !$tanggal || empty($slot_ids)) {
        $_SESSION['toast_error'] = "⚠️ Data tidak lengkap. Pilih tanggal dan slot jam.";
        header("Location: booking.php");
        exit;
    }
    if (empty($nama_customer)) {
        $_SESSION['toast_error'] = "⚠️ Nama customer wajib diisi.";
        header("Location: booking.php");
        exit;
    }

    $conn->begin_transaction();
    try {
        // -----------------------------------------------------------
        // 1. PERBAIKAN UTAMA: BUAT USER BARU SETIAP TRANSAKSI
        // -----------------------------------------------------------
        // Kita buat username & email unik menggunakan timestamp agar tidak bentrok
        // Ini memastikan setiap booking punya ID User sendiri, jadi namanya tidak tertimpa.
        $unique_code = date('YmdHis') . rand(100, 999);
        $username_w  = "walkin_" . $unique_code;
        $email_w     = "walkin_" . $unique_code . "@local"; // Email dummy unik
        $password    = password_hash('walkin123', PASSWORD_DEFAULT);
        $role_w      = 'user';
        $status_w    = 'aktif';
        
        $stmt = $conn->prepare("INSERT INTO users (nama, username, email, password, no_hp, role, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("sssssss", $nama_customer, $username_w, $email_w, $password, $no_hp_customer, $role_w, $status_w);
        $stmt->execute();
        $user_for_booking_id = $stmt->insert_id;
        $stmt->close();

        // -----------------------------------------------------------
        // 2. PROSES SLOT & HARGA
        // -----------------------------------------------------------
        $stmt = $conn->prepare("SELECT harga_per_jam, nama_lapangan FROM lapangan WHERE id_lapangan = ? LIMIT 1");
        $stmt->bind_param("i", $id_lapangan);
        $stmt->execute();
        $lap = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$lap) throw new Exception("⚠️ Lapangan tidak ditemukan.");
        $harga_per_jam = floatval($lap['harga_per_jam']);

        // Lock & Validasi Slot
        $placeholders = implode(',', array_fill(0, count($slot_ids), '?'));
        $types = str_repeat('i', count($slot_ids));
        $sql = "SELECT jd.id_detail, jd.status, jw.id_jadwal_waktu, jw.jam_mulai, jw.jam_selesai, jh.tanggal 
                FROM jadwal_detail jd
                JOIN jadwal_waktu jw ON jd.id_jadwal_waktu = jw.id_jadwal_waktu
                JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian
                WHERE jd.id_detail IN ($placeholders) FOR UPDATE";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$slot_ids);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (count($rows) !== count($slot_ids)) throw new Exception("⚠️ Data slot tidak valid.");

        usort($rows, function($a, $b) { return strcmp($a['jam_mulai'], $b['jam_mulai']); });

        $total_amount = 0;
        $slot_price_map = [];
        
        foreach ($rows as $i => $r) {
            if ($r['status'] !== 'tersedia') throw new Exception("⚠️ Slot jam {$r['jam_mulai']} sudah diambil.");
            if ($r['tanggal'] !== $tanggal) throw new Exception("⚠️ Tanggal slot tidak cocok.");
            
            // Cek urutan jam
            if ($i > 0) {
                $prev_end = substr($rows[$i-1]['jam_selesai'], 0, 5);
                $curr_start = substr($r['jam_mulai'], 0, 5);
                if ($prev_end !== $curr_start) throw new Exception("⚠️ Slot jam harus berurutan!");
            }

            $durasi = (strtotime($r['jam_selesai']) - strtotime($r['jam_mulai'])) / 3600;
            $harga_slot = $durasi * $harga_per_jam;
            $slot_price_map[$r['id_detail']] = $harga_slot;
            $total_amount += $harga_slot;
        }

        // -----------------------------------------------------------
        // 3. SIMPAN BOOKING
        // -----------------------------------------------------------
        $stmt = $conn->prepare("INSERT INTO booking (id_user, id_lapangan, tipe_booking, tanggal, status, total_amount, payment_status, approved_by, created_at) VALUES (?, ?, 'manual', ?, 'disetujui', ?, 'lunas', ?, NOW())");
        $stmt->bind_param("iisdi", $user_for_booking_id, $id_lapangan, $tanggal, $total_amount, $admin_id);
        $stmt->execute();
        $id_booking = $stmt->insert_id;
        $stmt->close();

        // Update Detail Jadwal
        $stmt_det = $conn->prepare("INSERT INTO detail_booking (id_booking, id_jadwal_waktu, harga) VALUES (?, ?, ?)");
        $stmt_upd = $conn->prepare("UPDATE jadwal_detail SET status='dibooking', id_booking = ? WHERE id_detail = ?"); 

        foreach ($rows as $r) {
            $harga = $slot_price_map[$r['id_detail']];
            $stmt_det->bind_param("iid", $id_booking, $r['id_jadwal_waktu'], $harga);
            $stmt_det->execute();

            $stmt_upd->bind_param("ii", $id_booking, $r['id_detail']);
            $stmt_upd->execute();
        }
        $stmt_det->close();
        $stmt_upd->close();

        // Simpan Pembayaran
        $stmt = $conn->prepare("INSERT INTO pembayaran (booking_id, tipe, amount, method, status_verifikasi, verified_by, verified_at, created_at) VALUES (?, 'Pelunasan', ?, 'cash', 'valid', ?, NOW(), NOW())");
        $stmt->bind_param("idi", $id_booking, $total_amount, $admin_id);
        $stmt->execute();
        $id_pembayaran = $stmt->insert_id;
        $stmt->close();

        // Simpan Keuangan
        $stmt = $conn->prepare("INSERT INTO keuangan (tanggal, jenis, kategori, keterangan, jumlah, sumber, booking_id, pembayaran_id, created_at) VALUES (CURDATE(), 'pemasukan', 'Pelunasan', ?, ?, 'Pelunasan', ?, ?, NOW())");
        $ket = "Walk-in #$id_booking - $nama_customer" . ($no_hp_customer ? " ($no_hp_customer)" : "");
        $stmt->bind_param("sdii", $ket, $total_amount, $id_booking, $id_pembayaran);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $_SESSION['toast_success'] = "✅ Booking Berhasil! Atas nama: <b>$nama_customer</b>";
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['toast_error'] = "Gagal: " . $e->getMessage();
    }

    header("Location: booking.php");
    exit;
}

// === DATA UNTUK TABEL & FORM ===
$sql = "
    SELECT 
      b.id_booking,
      u.nama AS nama_pemesan,
      u.no_hp AS no_hp_pemesan, -- Tambahkan No HP di query
      b.tipe_booking,
      l.nama_lapangan,
      b.tanggal,
      b.total_amount,
      b.status,
      b.payment_status,
      b.created_at,
      GROUP_CONCAT(CONCAT(DATE_FORMAT(jw.jam_mulai, '%H:%i'),'-',DATE_FORMAT(jw.jam_selesai,'%H:%i')) ORDER BY jw.jam_mulai SEPARATOR '<br>') AS jam_booking
    FROM booking b
    LEFT JOIN users u ON b.id_user = u.id_user
    JOIN lapangan l ON b.id_lapangan = l.id_lapangan
    LEFT JOIN detail_booking db ON b.id_booking = db.id_booking
    LEFT JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu
    GROUP BY b.id_booking
    ORDER BY b.created_at DESC
";
$result = $conn->query($sql);

$qLap = $conn->query("SELECT id_lapangan, nama_lapangan, harga_per_jam FROM lapangan WHERE status='aktif' ORDER BY nama_lapangan");

include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper animate__animated animate__fadeIn">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-calendar-check me-2"></i> Data Booking Lapangan</h1>
      <button class="btn btn-primary shadow-sm" data-bs-toggle="collapse" data-bs-target="#formTambahBooking">
        <i class="fas fa-plus-circle"></i> Tambah Booking Walk-In
      </button>
    </div>
  </section>

  <section class="content">
    <?php if (!empty($_SESSION['toast_error'])): ?>
      <div class="alert alert-danger mt-3 alert-dismissible fade show">
        <?= $_SESSION['toast_error']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php unset($_SESSION['toast_error']); ?>
    <?php endif; ?>
    
    <?php if (!empty($_SESSION['toast_success'])): ?>
      <div class="alert alert-success mt-3 alert-dismissible fade show">
        <?= $_SESSION['toast_success']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php unset($_SESSION['toast_success']); ?>
    <?php endif; ?>

    <div class="collapse mt-3" id="formTambahBooking">
      <div class="card card-primary shadow-lg border-0">
        <div class="card-header text-white" style="background: linear-gradient(90deg,#0e5c91,#2196f3);">
          <h3 class="card-title mb-0"><i class="fas fa-user-plus"></i> Booking Walk-In (Pelanggan Datang Langsung)</h3>
        </div>

        <form method="POST" id="formWalkinBooking">
          <input type="hidden" name="action" value="save_walkin_booking">
          <div class="card-body row g-3">
            
            <div class="col-md-6">
              <label class="form-label fw-bold">Nama Customer <span class="text-danger">*</span></label>
              <input type="text" name="nama_customer" class="form-control" placeholder="Masukkan nama customer" required>
              <small class="text-muted">Nama pelanggan yang datang langsung</small>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-bold">No HP Customer (Opsional)</label>
              <input type="text" name="no_hp_customer" class="form-control" placeholder="08xxxxxxxxxx">
              <small class="text-muted">Untuk keperluan konfirmasi/follow-up</small>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-bold">Lapangan <span class="text-danger">*</span></label>
              <select name="id_lapangan" id="id_lapangan" class="form-select" required>
                <option value="">-- Pilih Lapangan --</option>
                <?php while($l = $qLap->fetch_assoc()): ?>
                  <option value="<?= $l['id_lapangan'] ?>" 
                          data-harga="<?= $l['harga_per_jam'] ?>">
                    <?= htmlspecialchars($l['nama_lapangan']) ?> – 
                    Rp <?= number_format($l['harga_per_jam'],0,',','.') ?>/jam
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-bold">Tanggal Main <span class="text-danger">*</span></label>
              <input type="date" name="tanggal" id="tanggal" class="form-control" 
       value="<?= date('Y-m-d') ?>" 
       min="<?= date('Y-m-d') ?>" 
       required>
              <small class="text-muted">Biasanya hari ini untuk walk-in</small>
            </div>

            <div class="col-md-12" id="slotContainer" style="display:none;">
              <label class="form-label fw-bold">Pilih Slot Jam <span class="text-danger">*</span></label>
              <div id="slotList" class="d-flex flex-wrap gap-2"></div>
              <small class="text-muted">Klik beberapa slot yang berurutan (tanpa loncat jam)</small>
            </div>

            <div class="col-md-4 mt-3">
              <label class="form-label fw-bold">Harga per Jam</label>
              <input type="text" id="harga_per_jam_display" class="form-control bg-light" readonly>
            </div>

            <div class="col-md-4 mt-3">
              <label class="form-label fw-bold">Total Pembayaran</label>
              <input type="text" id="total_estimate_display" class="form-control bg-light fw-bold text-primary" readonly>
            </div>

            <div class="col-md-4 mt-3">
              <label class="form-label fw-bold">Metode Bayar</label>
              <input type="text" value="CASH (Langsung Lunas)" class="form-control bg-success text-white fw-bold" readonly>
            </div>
          </div>

          <div class="card-footer text-end bg-light">
            <button type="submit" class="btn btn-success btn-lg">
              <i class="fas fa-check-circle"></i> Simpan & Bayar Langsung
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="card shadow-lg border-0 mt-4">
      <div class="card-header text-white" style="background: linear-gradient(90deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);">
        <h3 class="card-title mb-0"><i class="fas fa-list"></i> Daftar Semua Booking</h3>
      </div>
      <div class="card-body table-responsive">
        <table id="tblBooking" class="table table-bordered table-striped table-hover align-middle w-100">
          <thead class="bg-light text-center">
            <tr>
              <th>No</th>
              <th>Pemesan</th>
              <th>Tipe</th>
              <th>Lapangan</th>
              <th>Tanggal</th>
              <th>Jam</th>
              <th>Total</th>
              <th>Status Booking</th>
              <th>Status Pembayaran</th>
              <th>Dibuat</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php $no=1; while($row = $result->fetch_assoc()): 
              switch ($row['status']) {
                case 'menunggu': $badgeBooking = 'badge bg-warning text-dark'; break;
                case 'disetujui': $badgeBooking = 'badge bg-primary'; break;
                case 'selesai': $badgeBooking = 'badge bg-success'; break;
                case 'ditolak': $badgeBooking = 'badge bg-danger'; break;
                default: $badgeBooking = 'badge bg-secondary'; break;
              }
              switch ($row['payment_status']) {
                case 'belum_bayar': $badgePay = 'badge bg-secondary'; break;
                case 'menunggu_verifikasi': $badgePay = 'badge bg-warning text-dark'; break;
                case 'dp_bayar': $badgePay = 'badge bg-info'; break;
                case 'lunas': $badgePay = 'badge bg-success'; break;
                default: $badgePay = 'badge bg-light text-dark';
              }
              
              // Fix badge tipe - sesuaikan dengan enum tipe_booking
              if ($row['tipe_booking'] == 'member') {
                $badgeTipe = '<span class="badge bg-success"><i class="fas fa-crown"></i> Member</span>';
              } elseif ($row['tipe_booking'] == 'manual') {
                $badgeTipe = '<span class="badge bg-info"><i class="fas fa-walking"></i> Walk-in</span>';
              } else {
                $badgeTipe = '<span class="badge bg-secondary"><i class="fas fa-user"></i> Reguler</span>';
              }
            ?>
              <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['nama_pemesan'] ?? 'Walk-in Customer') ?></td>
                <td class="text-center"><?= $badgeTipe ?></td>
                <td><?= htmlspecialchars($row['nama_lapangan']) ?></td>
                <td class="text-center"><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                <td class="text-center" style="font-size:0.85em;"><?= $row['jam_booking'] ?: '-' ?></td>
                <td class="text-end">Rp <?= number_format($row['total_amount'], 0, ',', '.') ?></td>
                <td class="text-center"><span class="<?= $badgeBooking ?>"><?= ucfirst($row['status']) ?></span></td>
                <td class="text-center"><span class="<?= $badgePay ?>"><?= ucfirst(str_replace('_',' ',$row['payment_status'])) ?></span></td>
                <td class="text-center"><?= date('d-m-Y H:i', strtotime($row['created_at'])) ?></td>
                <td class="text-center">
                  <a href="booking_detail.php?id=<?= $row['id_booking'] ?>" class="btn btn-sm btn-info" title="Lihat Detail">
                    <i class="fas fa-eye"></i>
                  </a>
                </td>
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
document.addEventListener('DOMContentLoaded', () => {
  const idLapangan = document.getElementById('id_lapangan');
  const tanggal = document.getElementById('tanggal');
  const slotContainer = document.getElementById('slotContainer');
  const slotList = document.getElementById('slotList');
  const hargaDisplay = document.getElementById('harga_per_jam_display');
  const totalDisplay = document.getElementById('total_estimate_display');
  const form = document.getElementById('formWalkinBooking');

  let selectedSlots = [];
  let isSubmitting = false;

  // Fungsi Reset Pilihan
  function clearSelection() {
    selectedSlots = [];
    if(slotList) {
        slotList.querySelectorAll('button').forEach(b => b.classList.remove('btn-success','active'));
    }
    form.querySelectorAll("input[name='slot_ids[]']").forEach(e => e.remove());
    form.querySelectorAll("input[name='jw_ids[]']").forEach(e => e.remove());
    totalDisplay.value = '';
  }

  function formatRp(n) {
    return 'Rp ' + (Number(n).toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 0}));
  }

  function timeToMinutes(t) {
    const parts = t.split(':');
    return parseInt(parts[0],10)*60 + parseInt(parts[1],10);
  }

  function getCurrentHarga() {
    const selected = idLapangan.selectedOptions[0];
    if (!selected || !selected.value) return 0;
    return parseFloat(selected.dataset.harga || 0);
  }

  // --- FUNGSI UTAMA LOAD SLOT ---
  function loadSlots() {
    const idL = idLapangan.value;
    const tgl = tanggal.value;
    
    clearSelection();
    slotList.innerHTML = '';
    
    // Sembunyikan kontainer jika data belum lengkap
    if (!idL || !tgl) {
        slotContainer.style.display = 'none';
        return;
    }

    // Tampilkan loading
    slotContainer.style.display = 'block';
    slotList.innerHTML = '<div class="text-primary"><i class="fas fa-spinner fa-spin"></i> Memuat jadwal...</div>';

    // Update tampilan harga per jam
    const harga = getCurrentHarga();
    hargaDisplay.value = harga ? formatRp(harga) : '';

    // Fetch ke booking_get_slot.php
    fetch(`booking_get_slot.php?id_lapangan=${idL}&tanggal=${tgl}`)
      .then(res => res.json())
      .then(data => {
        slotList.innerHTML = ''; // Hapus loading

        if (data.status !== 'success') {
          // Tampilkan pesan error dari PHP (misal: Jadwal belum digenerate)
          slotList.innerHTML = `<div class="alert alert-warning w-100"><i class="fas fa-exclamation-triangle"></i> ${data.message}</div>`;
          return;
        }

        if (!data.slots || data.slots.length === 0) {
           slotList.innerHTML = '<div class="alert alert-info w-100">Tidak ada slot tersedia untuk tanggal ini.</div>';
           return;
        }

        // Render Tombol Slot
        data.slots.forEach(s => {
          const btn = document.createElement('button');
          btn.type = 'button';
          // Styling tombol
          btn.className = 'btn btn-outline-primary m-1 flex-fill text-nowrap';
          btn.style.minWidth = "120px";
          
          btn.dataset.idDetail = s.id_detail;
          btn.dataset.idJw = s.id_jadwal_waktu;
          btn.dataset.jamMulai = s.jam_mulai;
          btn.dataset.jamSelesai = s.jam_selesai;

          if (s.status !== 'tersedia') {
            btn.disabled = true;
            btn.classList.replace('btn-outline-primary', 'btn-secondary');
            
            // Custom pesan berdasarkan status
            if (s.status === 'lewat') {
                 btn.innerHTML = `<i class="fas fa-history"></i> ${s.jam_mulai}`;
                 btn.title = "Waktu sudah berlalu";
                 btn.classList.add('opacity-25'); // Lebih pudar
            } else {
                 btn.innerHTML = `<i class="fas fa-lock"></i> ${s.jam_mulai}`;
                 btn.title = "Sudah dibooking";
            }
          } else {
            btn.innerHTML = `<i class="far fa-clock"></i> ${s.jam_mulai}-${s.jam_selesai}`;
            btn.addEventListener('click', () => toggleSlot(btn));
          }

          slotList.appendChild(btn);
        });
      })
      .catch(err => {
        console.error(err);
        slotList.innerHTML = `<div class="alert alert-danger w-100">Gagal memuat data: ${err.message}</div>`;
      });
  }

  function toggleSlot(btn) {
    const idDetail = btn.dataset.idDetail;
    const idJw = btn.dataset.idJw;
    const jamMulai = btn.dataset.jamMulai;
    const jamSelesai = btn.dataset.jamSelesai;

    const existingIndex = selectedSlots.findIndex(x => x.id_detail == idDetail);
    
    if (existingIndex !== -1) {
      // Unselect
      selectedSlots.splice(existingIndex, 1);
      btn.classList.remove('btn-success','active','text-white');
      btn.classList.add('btn-outline-primary');
    } else {
      // Select
      selectedSlots.push({ id_detail: idDetail, id_jw: idJw, jam_mulai: jamMulai, jam_selesai: jamSelesai });
      btn.classList.remove('btn-outline-primary');
      btn.classList.add('btn-success','active','text-white');
    }

    // Sorting berdasarkan jam
    selectedSlots.sort((a,b) => timeToMinutes(a.jam_mulai) - timeToMinutes(b.jam_mulai));

    // Validasi Slot Berurutan
    let valid = true;
    for (let i=0; i < selectedSlots.length - 1; i++) {
      if (selectedSlots[i].jam_selesai !== selectedSlots[i+1].jam_mulai) {
        valid = false; 
        break;
      }
    }

    if (!valid) {
      alert('⚠️ Peringatan: Slot jam harus berurutan tanpa jeda!');
      // Batalkan select terakhir
      const idx = selectedSlots.findIndex(x => x.id_detail == idDetail);
      if (idx !== -1) selectedSlots.splice(idx, 1);
      btn.classList.remove('btn-success','active','text-white');
      btn.classList.add('btn-outline-primary');
    }

    updateDisplay();
  }

  function updateDisplay() {
    // Bersihkan input hidden lama
    form.querySelectorAll("input[name='slot_ids[]']").forEach(e => e.remove());
    form.querySelectorAll("input[name='jw_ids[]']").forEach(e => e.remove());

    const harga = getCurrentHarga();
    let total = 0;

    selectedSlots.forEach(slt => {
      const sStart = timeToMinutes(slt.jam_mulai);
      const sEnd = timeToMinutes(slt.jam_selesai);
      const dur = (sEnd - sStart)/60; // durasi dalam jam
      total += dur * harga;

      // Buat input hidden baru agar terkirim saat POST
      const in1 = document.createElement('input');
      in1.type = 'hidden'; in1.name = 'slot_ids[]'; in1.value = slt.id_detail;
      form.appendChild(in1);

      const in2 = document.createElement('input');
      in2.type = 'hidden'; in2.name = 'jw_ids[]'; in2.value = slt.id_jw;
      form.appendChild(in2);
    });

    total = Math.round(total * 100) / 100;
    totalDisplay.value = total ? formatRp(total) : '';
  }

  // --- EVENT LISTENERS ---
  
  // 1. Saat Lapangan Berubah -> LOAD SLOTS
  idLapangan.addEventListener('change', () => {
    loadSlots(); // PERBAIKAN: Panggil loadSlots saat lapangan berubah
  });

  // 2. Saat Tanggal Berubah -> LOAD SLOTS
  tanggal.addEventListener('change', () => {
    loadSlots();
  });

  // Initial load (jika halaman direfresh dan browser menyimpan input value)
  if (idLapangan.value && tanggal.value) {
      loadSlots();
  }

  // Submit Form Handling
  form.addEventListener('submit', (e) => {
    if (isSubmitting) {
      e.preventDefault();
      return false;
    }

    const slotInputs = form.querySelectorAll("input[name='slot_ids[]']");
    if (!slotInputs.length) {
      e.preventDefault();
      alert('⚠️ Silakan pilih minimal 1 slot jam.');
      return false;
    }

    const namaCustomer = form.querySelector("input[name='nama_customer']").value.trim();
    if (!namaCustomer) {
      e.preventDefault();
      alert('⚠️ Nama customer wajib diisi.');
      return false;
    }

    if (!confirm('Apakah data sudah benar? Transaksi akan langsung disetujui dan dilunasi.')) {
        e.preventDefault();
        return false;
    }

    isSubmitting = true;
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
      const oriHtml = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
      
      // Restore tombol jika server lambat merespon/gagal (opsional)
      setTimeout(() => {
          isSubmitting = false;
          submitBtn.disabled = false;
          submitBtn.innerHTML = oriHtml;
      }, 15000);
    }
  });
});

// Hilangkan alert otomatis setelah 5 detik
setTimeout(function() {
  let alerts = document.querySelectorAll('.alert-dismissible');
  alerts.forEach(a => {
      // Bootstrap 5 remove
      try {
        let bsAlert = new bootstrap.Alert(a);
        bsAlert.close();
      } catch(e) { a.style.display = 'none'; }
  });
}, 5000);
</script>