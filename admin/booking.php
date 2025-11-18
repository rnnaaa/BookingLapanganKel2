<?php
// booking.php - FINAL ANTI-DUPLIKASI VERSION
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../config/database.php';

// === PROSES SIMPAN BOOKING MANUAL (ADMIN) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_manual_booking') {
    $id_user_input   = intval($_POST['id_user'] ?? 0); 
    $id_lapangan     = intval($_POST['id_lapangan'] ?? 0);
    $tanggal         = $_POST['tanggal'] ?? '';
    $slot_ids        = $_POST['slot_ids'] ?? [];
    $jw_ids          = $_POST['jw_ids'] ?? [];

    if (!$id_lapangan || !$tanggal || empty($slot_ids) || !is_array($slot_ids)) {
        $_SESSION['toast_error'] = "⚠️ Semua kolom wajib diisi dan slot harus dipilih.";
        header("Location: booking.php");
        exit;
    }

    $slot_ids = array_map('intval', $slot_ids);
    $jw_ids   = array_map('intval', $jw_ids);

    if (count($slot_ids) !== count($jw_ids)) {
        $_SESSION['toast_error'] = "⚠️ Data slot tidak valid.";
        header("Location: booking.php");
        exit;
    }

    $conn->begin_transaction();
    try {
        // Handle walk-in atau user terdaftar
        $is_walkin = ($id_user_input === 0);
        $user_for_booking_id = $id_user_input;

        if ($is_walkin) {
            $stmt = $conn->prepare("SELECT id_user FROM users WHERE username = 'walkin' LIMIT 1");
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc() ?? null;
            $stmt->close();

            if ($row) {
                $user_for_booking_id = intval($row['id_user']);
            } else {
                $stmt = $conn->prepare("INSERT INTO users (nama, username, email, password, role, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $nama_w = "Walk-in Customer";
                $username_w = "walkin";
                $email_w = "walkin@local";
                $password_hash = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
                $role_w = 'user';
                $status_w = 'aktif';
                $stmt->bind_param("ssssss", $nama_w, $username_w, $email_w, $password_hash, $role_w, $status_w);
                $stmt->execute();
                $user_for_booking_id = $stmt->insert_id;
                $stmt->close();

                if (!$user_for_booking_id) {
                    throw new Exception("⚠️ Gagal membuat user walk-in.");
                }
            }
        } else {
            $stmt = $conn->prepare("SELECT id_user, role, nama FROM users WHERE id_user = ? LIMIT 1");
            $stmt->bind_param("i", $id_user_input);
            $stmt->execute();
            $u = $stmt->get_result()->fetch_assoc() ?? null;
            $stmt->close();
            if (!$u) throw new Exception("⚠️ Pengguna tidak ditemukan.");
            $user_for_booking_id = intval($u['id_user']);
        }

        // Ambil harga lapangan
        $stmt = $conn->prepare("SELECT harga_per_jam, harga_per_jam_member, nama_lapangan FROM lapangan WHERE id_lapangan = ? LIMIT 1");
        $stmt->bind_param("i", $id_lapangan);
        $stmt->execute();
        $lap = $stmt->get_result()->fetch_assoc() ?? null;
        $stmt->close();
        if (!$lap) throw new Exception("⚠️ Lapangan tidak ditemukan.");

        // Tentukan harga berdasarkan tipe user
        $tipe_booking = 'reguler';
        if (!$is_walkin && $u['role'] === 'member') {
            $tipe_booking = 'member';
            $harga_per_jam = floatval($lap['harga_per_jam_member'] > 0 ? $lap['harga_per_jam_member'] : $lap['harga_per_jam']);
        } else {
            $harga_per_jam = floatval($lap['harga_per_jam']);
        }

        // Validasi slot dengan FOR UPDATE untuk lock
        $placeholders = implode(',', array_fill(0, count($slot_ids), '?'));
        $types = str_repeat('i', count($slot_ids));
        $sql = "
            SELECT jd.id_detail, jd.status, jw.id_jadwal_waktu, jw.jam_mulai, jw.jam_selesai, jh.tanggal, jh.id_lapangan
            FROM jadwal_detail jd
            JOIN jadwal_waktu jw ON jd.id_jadwal_waktu = jw.id_jadwal_waktu
            JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian
            WHERE jd.id_detail IN ($placeholders)
            FOR UPDATE
        ";
        $stmt = $conn->prepare($sql);
        
        $bind_names = [$types];
        for ($i = 0; $i < count($slot_ids); $i++) {
            $bind_name = 'bind' . $i;
            $$bind_name = $slot_ids[$i];
            $bind_names[] = &$$bind_name;
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);

        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        $stmt->close();

        if (count($rows) !== count($slot_ids)) {
            throw new Exception("⚠️ Beberapa slot tidak ditemukan atau tidak valid.");
        }

        foreach ($rows as $r) {
            if ($r['status'] !== 'tersedia') throw new Exception("⚠️ Slot sudah dibooking: " . $r['jam_mulai'] . " - " . $r['jam_selesai']);
            if ($r['tanggal'] !== $tanggal) throw new Exception("⚠️ Slot tanggal tidak cocok.");
            if (intval($r['id_lapangan']) !== $id_lapangan) throw new Exception("⚠️ Slot lapangan tidak cocok.");
        }

        usort($rows, function($a, $b) {
            return strcmp($a['jam_mulai'], $b['jam_mulai']);
        });

        for ($i = 0; $i < count($rows) - 1; $i++) {
            $prev_end = substr($rows[$i]['jam_selesai'], 0, 5);
            $next_start = substr($rows[$i+1]['jam_mulai'], 0, 5);
            if ($prev_end !== $next_start) {
                throw new Exception("⚠️ Slot harus berurutan tanpa loncat jam.");
            }
        }

        // Hitung total
        $total_amount = 0.0;
        $slot_price_map = [];
        foreach ($rows as $r) {
            $startTs = strtotime($r['jam_mulai']);
            $endTs   = strtotime($r['jam_selesai']);
            if ($endTs <= $startTs) throw new Exception("⚠️ Waktu slot tidak valid.");
            $durHours = ($endTs - $startTs) / 3600.0;
            $harga_slot = round($harga_per_jam * $durHours, 2);
            $slot_price_map[intval($r['id_detail'])] = $harga_slot;
            $total_amount += $harga_slot;
        }
        $total_amount = round($total_amount, 2);

        // Tentukan pembayaran & status
        if ($is_walkin) {
            $dp_amount = 0.00;
            $remaining_amount = 0.00;
            $payment_status = 'lunas';
            $booking_status = 'disetujui';
        } else {
            $dp_amount = round($total_amount * 0.30, 2);
            $remaining_amount = round($total_amount - $dp_amount, 2);
            $payment_status = 'belum_bayar';
            $booking_status = 'menunggu';
        }

        // INSERT BOOKING
        $stmt = $conn->prepare("
            INSERT INTO booking
            (id_user, id_lapangan, tipe_booking, tanggal, status, dp_amount, total_amount, remaining_amount, payment_status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->bind_param("iisssddds",
            $user_for_booking_id,
            $id_lapangan,
            $tipe_booking,
            $tanggal,
            $booking_status,
            $dp_amount,
            $total_amount,
            $remaining_amount,
            $payment_status
        );
        $stmt->execute();
        $id_booking = $stmt->insert_id;
        $stmt->close();

        if (!$id_booking) throw new Exception("⚠️ Gagal membuat booking.");

        // INSERT DETAIL_BOOKING & UPDATE JADWAL_DETAIL
        foreach ($rows as $r) {
            $id_detail = intval($r['id_detail']);
            $id_jw = intval($r['id_jadwal_waktu']);
            $harga_detail = $slot_price_map[$id_detail] ?? 0.00;

            $stmt = $conn->prepare("INSERT INTO detail_booking (id_booking, id_jadwal_waktu, harga, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iid", $id_booking, $id_jw, $harga_detail);
            $stmt->execute();
            $stmt->close();

            $stmt2 = $conn->prepare("UPDATE jadwal_detail SET status='dibooking', id_booking = ? WHERE id_detail = ? AND status = 'tersedia' LIMIT 1");
            $stmt2->bind_param("ii", $id_booking, $id_detail);
            $stmt2->execute();
            if ($stmt2->affected_rows === 0) {
                throw new Exception("⚠️ Slot (ID: {$id_detail}) sudah diambil orang lain.");
            }
            $stmt2->close();
        }

        // === INSERT PEMBAYARAN & KEUANGAN (ANTI-DUPLIKASI) ===
        if ($is_walkin) {
            // 1. Insert ke pembayaran (walk-in langsung valid)
            $stmt = $conn->prepare("
                INSERT INTO pembayaran
                (booking_id, tipe, bukti_pembayaran, amount, method, status_verifikasi, verified_by, verified_at, tanggal_upload, created_at)
                VALUES (?, 'Pelunasan', NULL, ?, 'cash', 'valid', ?, NOW(), NOW(), NOW())
            ");
            $adminId = intval($_SESSION['id_user'] ?? 0);
            $stmt->bind_param("idi", $id_booking, $total_amount, $adminId);
            $stmt->execute();
            $id_pembayaran = $stmt->insert_id;
            $stmt->close();

            if (!$id_pembayaran) {
                throw new Exception("⚠️ Gagal membuat data pembayaran.");
            }

            // 2. CEK DUPLIKASI DI KEUANGAN dengan lock berdasarkan pembayaran_id (paling akurat)
            $stmt_lock = $conn->prepare("
                SELECT id_keuangan FROM keuangan
                WHERE pembayaran_id = ?
                FOR UPDATE
            ");
            $stmt_lock->bind_param("i", $id_pembayaran);
            $stmt_lock->execute();
            $res_lock = $stmt_lock->get_result();
            $existing_keuangan = $res_lock->fetch_assoc();
            $stmt_lock->close();

            // tambahan fallback: jika tidak ada row dengan pembayaran_id, cek juga apakah ada row "mirip"
            // (mis. row lama tanpa pembayaran_id tetapi dengan booking_id + kategori + jumlah sama)
            if (!$existing_keuangan) {
                $stmt_lock2 = $conn->prepare("
                    SELECT id_keuangan FROM keuangan
                    WHERE pembayaran_id IS NULL
                      AND booking_id = ?
                      AND kategori = 'Pelunasan'
                      AND ABS(jumlah - ?) < 0.01
                    FOR UPDATE
                ");
                $stmt_lock2->bind_param("id", $id_booking, $total_amount);
                $stmt_lock2->execute();
                $res_lock2 = $stmt_lock2->get_result();
                $existing_keuangan = $res_lock2->fetch_assoc();
                $stmt_lock2->close();
            }

            // 3. HANYA INSERT JIKA BELUM ADA
            if (!$existing_keuangan) {
                $tanggal_keu = date('Y-m-d');
                $ket = "Pelunasan walk-in booking #{$id_booking} - {$lap['nama_lapangan']}";

                $stmt = $conn->prepare("
                    INSERT INTO keuangan (tanggal, jenis, kategori, keterangan, jumlah, sumber, booking_id, pembayaran_id, created_at)
                    VALUES (?, 'pemasukan', ?, ?, ?, ?, ?, ?, NOW())
                ");
                // params: tanggal(s), kategori(s), keterangan(s), jumlah(d), sumber(s), booking_id(i), pembayaran_id(i)
                $sumber = 'Pelunasan';
                $stmt->bind_param("sssdsii", $tanggal_keu, $sumber, $ket, $total_amount, $sumber, $id_booking, $id_pembayaran);

                // Nota: ada kemungkinan constraint UNIQUE (pembayaran_id atau booking_id) tetap memicu duplicate-key
                // jika proses lain secara nyaris bersamaan membuat row. Tangani dengan try/catch pada eksekusi.
                try {
                    $stmt->execute();
                } catch (mysqli_sql_exception $me) {
                    // Jika duplicate key (errno 1062), abaikan karena row sudah ada
                    if ($me->getCode() == 1062) {
                        // ignore duplicate insert caused by race condition
                    } else {
                        throw $me;
                    }
                }
                $stmt->close();
            }
        } else {
            // User reguler/member - hanya insert pembayaran (menunggu verifikasi)
            $stmt = $conn->prepare("
                INSERT INTO pembayaran
                (booking_id, tipe, bukti_pembayaran, amount, method, status_verifikasi, tanggal_upload, created_at)
                VALUES (?, 'DP', NULL, ?, 'transfer', 'menunggu', NOW(), NOW())
            ");
            $stmt->bind_param("id", $id_booking, $dp_amount);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        $_SESSION['toast_success'] = "✅ Booking berhasil dibuat (ID: {$id_booking}). " . 
            ($is_walkin ? "Walk-in langsung lunas & disetujui." : "Menunggu user upload bukti DP.");
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['toast_error'] = $e->getMessage();
    }

    header("Location: booking.php");
    exit;
}

// === DATA UNTUK TABEL & FORM ===
$sql = "
    SELECT 
      b.id_booking,
      u.nama AS nama_pemesan,
      COALESCE(b.tipe_booking, 'reguler') AS tipe_booking,
      l.nama_lapangan,
      b.tanggal,
      b.total_amount,
      b.status,
      b.payment_status,
      b.created_at,
      COALESCE(
        GROUP_CONCAT(
          CONCAT(DATE_FORMAT(jw.jam_mulai, '%H:%i'),' - ',DATE_FORMAT(jw.jam_selesai,'%H:%i')) 
          ORDER BY jw.jam_mulai SEPARATOR '<br>'
        ),
        '-'
      ) AS jam_booking
    FROM booking b
    LEFT JOIN users u ON b.id_user = u.id_user
    JOIN lapangan l ON b.id_lapangan = l.id_lapangan
    LEFT JOIN detail_booking db ON b.id_booking = db.id_booking
    LEFT JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu
    GROUP BY b.id_booking
    ORDER BY b.tanggal DESC, b.created_at DESC
";
$result = $conn->query($sql);

$qUsers = $conn->query("SELECT id_user, nama, role, email FROM users WHERE role IN ('user','member') AND username != 'walkin' ORDER BY nama");
$qLap = $conn->query("SELECT id_lapangan, nama_lapangan, harga_per_jam, harga_per_jam_member FROM lapangan WHERE status='aktif' ORDER BY nama_lapangan");

include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper animate__animated animate__fadeIn">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-calendar-check me-2"></i> Data Booking Lapangan</h1>
      <button class="btn btn-primary shadow-sm" data-bs-toggle="collapse" data-bs-target="#formTambahBooking">
        <i class="fas fa-plus-circle"></i> Tambah Booking Manual
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
          <h3 class="card-title mb-0"><i class="fas fa-plus-circle"></i> Tambah Booking Manual</h3>
        </div>

        <form method="POST" id="formManualBooking">
          <input type="hidden" name="action" value="save_manual_booking">
          <div class="card-body row g-3">
            <div class="col-md-4">
              <label>Pemesan (User/Member) – kosongkan untuk walk-in</label>
              <select name="id_user" id="id_user" class="form-select select2-bootstrap4">
                <option value="">-- Walk-in (Bayar Langsung) --</option>
                <?php while($u = $qUsers->fetch_assoc()): ?>
                  <option value="<?= $u['id_user'] ?>" data-role="<?= $u['role'] ?>">
                    <?= htmlspecialchars($u['nama']) ?> (<?= $u['role'] ?>)
                  </option>
                <?php endwhile; ?>
              </select>
              <small class="text-muted">Walk-in = pelunasan cash langsung disetujui</small>
            </div>

            <div class="col-md-4">
              <label>Lapangan</label>
              <select name="id_lapangan" id="id_lapangan" class="form-select" required>
                <option value="">-- Pilih Lapangan --</option>
                <?php while($l = $qLap->fetch_assoc()): ?>
                  <option value="<?= $l['id_lapangan'] ?>" 
                          data-harga="<?= $l['harga_per_jam'] ?>"
                          data-harga-member="<?= $l['harga_per_jam_member'] ?>">
                    <?= htmlspecialchars($l['nama_lapangan']) ?> – 
                    Reguler: Rp <?= number_format($l['harga_per_jam'],0,',','.') ?>/jam
                    <?php if($l['harga_per_jam_member'] > 0): ?>
                      | Member: Rp <?= number_format($l['harga_per_jam_member'],0,',','.') ?>/jam
                    <?php endif; ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="col-md-4">
              <label>Tanggal Booking</label>
              <input type="date" name="tanggal" id="tanggal" class="form-control" min="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="col-md-12" id="slotContainer" style="display:none;">
              <label>Pilih Slot Jam (klik beberapa; harus berurutan)</label>
              <div id="slotList" class="d-flex flex-wrap gap-2"></div>
            </div>

            <div class="col-md-4 mt-2">
              <label>Harga per Jam</label>
              <input type="text" id="harga_per_jam_display" class="form-control" readonly>
            </div>

            <div class="col-md-4 mt-2">
              <label>Total Estimasi</label>
              <input type="text" id="total_estimate_display" class="form-control" readonly>
            </div>

            <div class="col-md-4 mt-2">
              <label>DP (30%)</label>
              <input type="text" id="dp_estimate_display" class="form-control" readonly>
              <small class="text-muted">Walk-in = Rp 0 (langsung lunas)</small>
            </div>
          </div>

          <div class="card-footer text-end">
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan Booking</button>
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
              $badgeTipe = ($row['tipe_booking'] == 'member') ? '<span class="badge bg-success">Member</span>' : '<span class="badge bg-secondary">Reguler</span>';
            ?>
              <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['nama_pemesan'] ?? 'Walk-in') ?></td>
                <td class="text-center"><?= $badgeTipe ?></td>
                <td><?= htmlspecialchars($row['nama_lapangan']) ?></td>
                <td class="text-center"><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                <td class="text-center" style="font-size:0.85em;"><?= $row['jam_booking'] ?: '-' ?></td>
                <td class="text-end">Rp <?= number_format($row['total_amount'], 0, ',', '.') ?></td>
                <td class="text-center"><span class="<?= $badgeBooking ?>"><?= ucfirst($row['status']) ?></span></td>
                <td class="text-center"><span class="<?= $badgePay ?>"><?= ucfirst(str_replace('_',' ',$row['payment_status'])) ?></span></td>
                <td class="text-center"><?= date('d-m-Y H:i', strtotime($row['created_at'])) ?></td>
                <td class="text-center">
                  <a href="booking_detail.php?id=<?= $row['id_booking'] ?>" class="btn btn-sm btn-info" title="Detail"><i class="fas fa-info-circle"></i></a>
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
  const idUser = document.getElementById('id_user');
  const tanggal = document.getElementById('tanggal');
  const slotContainer = document.getElementById('slotContainer');
  const slotList = document.getElementById('slotList');
  const hargaDisplay = document.getElementById('harga_per_jam_display');
  const totalDisplay = document.getElementById('total_estimate_display');
  const dpDisplay = document.getElementById('dp_estimate_display');
  const form = document.getElementById('formManualBooking');

  let selectedSlots = [];
  let isSubmitting = false;

  function clearSelection() {
    selectedSlots = [];
    slotList.querySelectorAll('button').forEach(b => b.classList.remove('btn-success','active'));
    form.querySelectorAll("input[name='slot_ids[]']").forEach(e => e.remove());
    form.querySelectorAll("input[name='jw_ids[]']").forEach(e => e.remove());
    totalDisplay.value = '';
    dpDisplay.value = '';
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
    if (!selected) return 0;
    
    const userId = idUser.value;
    const userRole = userId ? idUser.selectedOptions[0]?.dataset.role : null;
    
    if (userRole === 'member') {
      const hargaMember = parseFloat(selected.dataset.hargaMember || 0);
      return hargaMember > 0 ? hargaMember : parseFloat(selected.dataset.harga || 0);
    }
    return parseFloat(selected.dataset.harga || 0);
  }

  function loadSlots() {
    const idL = idLapangan.value;
    const tgl = tanggal.value;
    clearSelection();
    slotList.innerHTML = '';
    slotContainer.style.display = 'none';
    if (!idL || !tgl) return;

    slotList.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div> Memuat...';
    slotContainer.style.display = 'block';

    const harga = getCurrentHarga();
    hargaDisplay.value = harga ? formatRp(harga) : '';

    fetch(`booking_get_slot.php?id_lapangan=${idL}&tanggal=${tgl}`)
      .then(res => res.json())
      .then(data => {
        slotList.innerHTML = '';
        if (data.status !== 'success' || !data.slots.length) {
          slotList.innerHTML = '<p class="text-danger">Tidak ada slot tersedia (synchronize jadwal terlebih dahulu).</p>';
          return;
        }

        data.slots.forEach(s => {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'btn btn-outline-success btn-sm me-2 mb-2';
          btn.textContent = `${s.jam_mulai} - ${s.jam_selesai}`; 
          btn.dataset.idDetail = s.id_detail;
          btn.dataset.idJw = s.id_jadwal_waktu;
          btn.dataset.jamMulai = s.jam_mulai;
          btn.dataset.jamSelesai = s.jam_selesai;

          if (s.status !== 'tersedia') {
            btn.disabled = true;
            btn.classList.remove('btn-outline-success');
            btn.classList.add('btn-secondary');
            btn.textContent += ' (Booked)';
          } else {
            btn.addEventListener('click', () => toggleSlot(btn));
          }

          slotList.appendChild(btn);
        });
      })
      .catch(err => {
        slotList.innerHTML = `<p class="text-danger">Gagal memuat slot: ${err.message}</p>`;
      });
  }

  function toggleSlot(btn) {
    const idDetail = btn.dataset.idDetail;
    const idJw = btn.dataset.idJw;
    const jamMulai = btn.dataset.jamMulai;
    const jamSelesai = btn.dataset.jamSelesai;

    const existingIndex = selectedSlots.findIndex(x => x.id_detail == idDetail);
    if (existingIndex !== -1) {
      selectedSlots.splice(existingIndex, 1);
      btn.classList.remove('btn-success','active');
    } else {
      selectedSlots.push({ id_detail: idDetail, id_jw: idJw, jam_mulai: jamMulai, jam_selesai: jamSelesai });
      btn.classList.add('btn-success','active');
    }

    selectedSlots.sort((a,b) => timeToMinutes(a.jam_mulai) - timeToMinutes(b.jam_mulai));

    let valid = true;
    for (let i=0; i < selectedSlots.length - 1; i++) {
      if (selectedSlots[i].jam_selesai !== selectedSlots[i+1].jam_mulai) {
        valid = false;
        break;
      }
    }

    if (!valid) {
      alert('Slot harus berurutan tanpa loncat jam!');
      const idx = selectedSlots.findIndex(x => x.id_detail == idDetail);
      if (idx !== -1) selectedSlots.splice(idx, 1);
      btn.classList.remove('btn-success','active');
    }

    updateDisplay();
  }

  function updateDisplay() {
    form.querySelectorAll("input[name='slot_ids[]']").forEach(e => e.remove());
    form.querySelectorAll("input[name='jw_ids[]']").forEach(e => e.remove());

    const harga = getCurrentHarga();
    let total = 0;

    selectedSlots.forEach(slt => {
      const sStart = timeToMinutes(slt.jam_mulai);
      const sEnd = timeToMinutes(slt.jam_selesai);
      const dur = (sEnd - sStart)/60;
      total += dur * harga;

      const in1 = document.createElement('input');
      in1.type = 'hidden'; in1.name = 'slot_ids[]'; in1.value = slt.id_detail;
      form.appendChild(in1);

      const in2 = document.createElement('input');
      in2.type = 'hidden'; in2.name = 'jw_ids[]'; in2.value = slt.id_jw;
      form.appendChild(in2);
    });

    total = Math.round(total * 100) / 100;
    totalDisplay.value = total ? formatRp(total) : '';

    const userId = idUser.value;
    if (userId && userId !== '') {
      const dp = Math.round(total * 0.30 * 100) / 100;
      dpDisplay.value = formatRp(dp);
    } else {
      dpDisplay.value = formatRp(0);
    }
  }

  idLapangan.addEventListener('change', () => {
    clearSelection();
    const harga = getCurrentHarga();
    hargaDisplay.value = harga ? formatRp(harga) : '';
  });

  idUser.addEventListener('change', () => {
    const harga = getCurrentHarga();
    hargaDisplay.value = harga ? formatRp(harga) : '';
    updateDisplay();
  });

  tanggal.addEventListener('change', loadSlots);

  form.addEventListener('submit', (e) => {
    if (isSubmitting) {
      e.preventDefault();
      alert('⏳ Sedang memproses... Mohon tunggu.');
      return false;
    }

    const slotInputs = form.querySelectorAll("input[name='slot_ids[]']");
    if (!slotInputs.length) {
      e.preventDefault();
      alert('Pilih slot jam terlebih dahulu.');
      return false;
    }

    // Set flag dan disable button
    isSubmitting = true;
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
    }

    // Safety: enable kembali setelah 10 detik jika masih di halaman ini
    setTimeout(() => {
      isSubmitting = false;
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Simpan Booking';
      }
    }, 10000);
  });
});

setTimeout(function() {
  $('.alert').fadeOut('slow');
}, 5000);
</script>