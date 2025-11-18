<?php
declare(strict_types=1);

/*
 member.php (final revised)
 - MySQL (PDO). Sesuaikan DB config di bawah.
 - Endpoints:
   GET  ?action=availability&lapangan={id}&ym={YYYY-MM}  -> JSON booked slots
   POST ?action=submit_member   -> proses pendaftaran + insert member & jadwal
*/

// ---------------- DB CONFIG - EDIT INI SESUAI SERVERMU ----------------
$DB_HOST = '127.0.0.1';
$DB_NAME = 'bookinglapanganb2'; // ganti sesuai db mu
$DB_USER = 'root';
$DB_PASS = '';
$DB_CHAR = 'utf8mb4';
// ----------------------------------------------------------------------

/* ---------- Utility / DB ---------- */
class DB {
    private PDO $pdo;
    public function __construct($host, $db, $user, $pass, $charset='utf8mb4') {
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $opt = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        $this->pdo = new PDO($dsn, $user, $pass, $opt);
    }
    public function pdo(): PDO { return $this->pdo; }
}

function ensureStatusEnumHasPending(PDO $pdo) {
    // optional helper: attempt to ALTER if missing (best to run manually)
    // We'll not auto-alter here to avoid permission issues.
}

/* week helpers */
function getWeekCountOfMonth(int $year, int $month): int {
    $first = new DateTimeImmutable("$year-$month-01");
    $last = $first->modify('last day of this month');
    $startWeekday = (int)$first->format('w'); // 0..6
    $days = (int)$last->format('d');
    return (int)ceil(($startWeekday + $days) / 7);
}

function getWeekIndexInMonthFromDate(string $ymd): int {
    $d = new DateTimeImmutable($ymd);
    $first = $d->modify('first day of this month');
    $offset = (int)$first->format('w');
    return (int)floor(((int)$d->format('d') + $offset - 1) / 7);
}

function rupiah(int $n): string { return 'Rp ' . number_format($n,0,',','.'); }

/* ---------- Service ---------- */
class MemberService {
    private PDO $pdo;
    private string $uploadDir;
    private array $prices = [1=>100000,2=>200000,3=>300000];

    public function __construct(PDO $pdo, string $uploadDir) {
        $this->pdo = $pdo;
        $this->uploadDir = rtrim($uploadDir, '/');
        if (!is_dir($this->uploadDir)) @mkdir($this->uploadDir, 0755, true);
    }

    /**
     * Check availability for a given lapangan + year-month.
     * Returns array: [ 'YYYY-MM-DD' => ['HH:MM','HH:MM', ...], ... ]
     */
    public function getBookedSlotsForMonth(int $lapanganId, string $ym): array {
        // expects $ym = "YYYY-MM"
        [$y,$m] = explode('-', $ym);
        $start = "$ym-01";
        $end = (new DateTimeImmutable($start))->modify('last day of this month')->format('Y-m-d');

        $sql = "SELECT tanggal_booking, jam_mulai FROM member_jadwal
                WHERE id_lapangan = :lapangan
                AND tanggal_booking BETWEEN :start AND :end
                AND status IN ('pending','aktif')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':lapangan'=>$lapanganId, ':start'=>$start, ':end'=>$end]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $res = [];
        foreach ($rows as $r) {
            $d = $r['tanggal_booking'];
            $t = substr($r['jam_mulai'],0,5);
            if (!isset($res[$d])) $res[$d] = [];
            $res[$d][] = $t;
        }
        return $res;
    }

    /**
     * Submit member + jadwal (REVISED FOR FLEXIBLE SYSTEM)
     * Sistem fleksibel: minimal 2 tanggal per bulan, bebas minggu ke berapa
     * Minggu ke-5 opsional
     */
    public function store(array $input, array $files): array {
        $errors = [];

        $name = trim((string)($input['name'] ?? ''));
        $emailRaw = trim((string)($input['email'] ?? ''));
        if ($emailRaw !== '' && strpos($emailRaw, '@') === false) $emailRaw .= '@gmail.com';
        $email = $emailRaw;
        $paket = (int)($input['paket'] ?? 0);
        $start_month = trim((string)($input['start_month'] ?? '')); // YYYY-MM
        $court = (int)($input['court'] ?? 0);
        $selected_dates_raw = $input['selected_dates'] ?? '[]';
        $selected_dates = json_decode((string)$selected_dates_raw, true);
        $payment_method = trim((string)($input['payment_method'] ?? ''));
        $bank_from_name = trim((string)($input['bank_from_name'] ?? ''));
        $bank_from_number = trim((string)($input['bank_from_number'] ?? ''));
        $transfer_amount = trim((string)($input['transfer_amount'] ?? ''));

        // basic validations
        if ($name === '') $errors[] = 'Nama wajib diisi.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email wajib dan harus valid.';
        if (!in_array($paket, [1,2,3])) $errors[] = 'Pilih paket yang valid.';
        if ($start_month === '' || !preg_match('/^\d{4}-\d{2}$/', $start_month)) $errors[] = 'Pilih bulan mulai (format YYYY-MM).';
        if (!is_array($selected_dates) || empty($selected_dates)) $errors[] = 'Pilih minimal 2 tanggal per bulan.';
        if (empty($court)) $errors[] = 'Pilih lapangan.';
        if (!in_array($payment_method, ['qris','bca','mandiri'])) $errors[] = 'Pilih metode pembayaran.';
        if ($transfer_amount === '' || !preg_match('/^\d+$/', $transfer_amount)) $errors[] = 'Nominal harus angka.';
        if ($bank_from_number !== '' && !preg_match('/^\d+$/', $bank_from_number)) $errors[] = 'Nomor rekening harus angka.';

        // parse selected_dates into normalized array of ['date'=>'YYYY-MM-DD','time'=>'HH:MM']
        $parsedSlots = [];
        foreach ($selected_dates as $item) {
            if (is_string($item)) {
                // accept "YYYY-MM-DD HH:MM" or "YYYY-MM-DD"
                $parts = preg_split('/\s+/', trim($item));
                $date = $parts[0] ?? '';
                $time = $parts[1] ?? null;
                if ($time === null) $time = '18:00'; // default 18:00 if not provided
            } elseif (is_array($item)) {
                $date = $item['date'] ?? '';
                $time = $item['time'] ?? '18:00';
            } else {
                continue;
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { $errors[] = "Format tanggal tidak valid: $date"; continue; }
            if (!preg_match('/^\d{2}:\d{2}$/', $time)) { $errors[] = "Format jam tidak valid: $time"; continue; }
            $parsedSlots[] = ['date'=>$date, 'time'=>$time];
        }

        if (!empty($errors)) return ['success'=>false, 'errors'=>$errors];

        // REVISI: Validasi sistem fleksibel - minimal 2 tanggal per bulan
        [$y0,$m0] = explode('-', $start_month);
        $y0 = (int)$y0; $m0 = (int)$m0;
        $monthsCount = max(1, $paket);
        
        for ($i=0;$i<$monthsCount;$i++) {
            $inspect = (new DateTimeImmutable("$y0-$m0-01"))->modify("+$i month");
            $year = (int)$inspect->format('Y');
            $month = (int)$inspect->format('n');
            $monthName = $inspect->format('F Y');

            // Hitung berapa tanggal yang dipilih untuk bulan ini
            $datesInMonth = [];
            foreach ($parsedSlots as $ps) {
                $d = $ps['date'];
                $dt = strtotime($d);
                if ($dt === false) continue;
                if (date('Y-m', $dt) === sprintf('%04d-%02d', $year, $month)) {
                    $datesInMonth[] = $d;
                }
            }
            
            // REVISI: Minimal 2 tanggal per bulan
            $selectedCount = count($datesInMonth);
            if ($selectedCount < 2) {
                $errors[] = "Tanggal belum lengkap untuk bulan $monthName (harus pilih minimal 2 tanggal, baru memilih $selectedCount).";
            }
        }

        if (!empty($errors)) return ['success'=>false, 'errors'=>$errors];

        // check availability for every parsed slot: same lapangan & same date & same jam must be free
        $conflicts = [];
        $checkStmt = $this->pdo->prepare("SELECT id_member_jadwal FROM member_jadwal
            WHERE id_lapangan = :lap AND tanggal_booking = :tgl AND jam_mulai = :jm
            AND status IN ('pending','aktif') LIMIT 1");
        foreach ($parsedSlots as $ps) {
            $checkStmt->execute([':lap'=>$court, ':tgl'=>$ps['date'], ':jm'=>$ps['time']]);
            if ($checkStmt->fetch()) {
                $conflicts[] = $ps['date'] . ' ' . $ps['time'];
            }
        }
        if (!empty($conflicts)) {
          return ['success'=>false, 'errors'=>['Beberapa slot sudah terisi: ' . implode(', ', $conflicts)]];
        }

        // handle file upload
        $buktiPath = '';
        if (isset($files['bukti']) && is_array($files['bukti']) && strlen($files['bukti']['tmp_name'] ?? '') > 0) {
            $fn = basename($files['bukti']['name']);
            $ext = strtolower(pathinfo($fn, PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','pdf'];
            if (!in_array($ext, $allowed)) return ['success'=>false, 'errors'=>['Bukti transfer harus jpg/png/pdf.']];
            $newName = uniqid('bukti_') . '.' . $ext;
            $dest = $this->uploadDir . '/' . $newName;
            if (!move_uploaded_file($files['bukti']['tmp_name'], $dest)) {
                return ['success'=>false, 'errors'=>['Gagal menyimpan file bukti.']];
            }
            $buktiPath = 'uploads/' . $newName;
        } else {
            return ['success'=>false, 'errors'=>['Bukti transfer wajib di-upload.']];
        }

        // compute tanggal_berakhir: last day of last month in paket
        $firstOfStart = new DateTimeImmutable("$y0-$m0-01");
        $lastOfEnd = $firstOfStart->modify('+' . ($monthsCount - 1) . ' month')->modify('last day of this month');
        $tanggal_mulai = $firstOfStart->format('Y-m-d');
        $tanggal_berakhir = $lastOfEnd->format('Y-m-d');

        // price
        $price = $this->prices[$paket] ?? 0;

        // Now insert: use transaction
        try {
            $this->pdo->beginTransaction();

            // insert member
            $stmt = $this->pdo->prepare("INSERT INTO member (id_user, id_lapangan, durasi_bulan, tanggal_mulai, tanggal_berakhir, bukti_pembayaran, method, total_bayar, status, created_at, updated_at)
                VALUES (:id_user, :id_lap, :dur, :tgl_mulai, :tgl_akhir, :bukti, :method, :total, :st, :ca, :ua)");
            // id_user may be null (guest). allow null.
            $stmt->execute([
                ':id_user' => $input['id_user'] ?? null,
                ':id_lap' => $court,
                ':dur' => $paket,
                ':tgl_mulai' => $tanggal_mulai,
                ':tgl_akhir' => $tanggal_berakhir,
                ':bukti' => $buktiPath,
                ':method' => $payment_method,
                ':total' => (int)$transfer_amount,
                ':st' => 'pending',
                ':ca' => date('Y-m-d H:i:s'),
                ':ua' => date('Y-m-d H:i:s'),
            ]);
            $memberId = (int)$this->pdo->lastInsertId();

            // insert jadwal rows (one per parsedSlots)
            $insJ = $this->pdo->prepare("INSERT INTO member_jadwal (id_member, id_lapangan, tanggal_booking, jam_mulai, jam_selesai, harga_per_jam_member, status, created_at, updated_at)
                VALUES (:mid, :lap, :tgl, :jm, :js, :harga, :st, :ca, :ua)");
            foreach ($parsedSlots as $ps) {
                $jamMulai = $ps['time'];
                // compute jam selesai = jam mulai + 1 hour
                $dt = DateTime::createFromFormat('H:i', $jamMulai);
                $dt->modify('+1 hour');
                $jamSelesai = $dt->format('H:i:s');

                $insJ->execute([
                    ':mid' => $memberId,
                    ':lap' => $court,
                    ':tgl' => $ps['date'],
                    ':jm' => $jamMulai,
                    ':js' => $jamSelesai,
                    ':harga' => 0.00,
                    ':st' => 'pending',
                    ':ca' => date('Y-m-d H:i:s'),
                    ':ua' => date('Y-m-d H:i:s'),
                ]);
            }

            $this->pdo->commit();
            return ['success'=>true];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success'=>false, 'errors'=>['Database error: ' . $e->getMessage()]];
        }
    }
}

/* ---------- bootstrap ---------- */
try {
    $db = new DB($DB_HOST,$DB_NAME,$DB_USER,$DB_PASS,$DB_CHAR);
    $pdo = $db->pdo();
} catch (Exception $e) {
    http_response_code(500);
    die("DB Connection error: " . htmlspecialchars($e->getMessage()));
}

$service = new MemberService($pdo, __DIR__ . '/uploads');

/* ---------- Simple router for AJAX endpoints ---------- */
$action = $_REQUEST['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'availability') {
    // GET params: lapangan, ym=YYYY-MM
    $lap = isset($_GET['lapangan']) ? (int)$_GET['lapangan'] : 0;
    $ym = $_GET['ym'] ?? null;
    if (!$lap || !$ym || !preg_match('/^\d{4}-\d{2}$/', $ym)) {
        header('Content-Type: application/json');
        echo json_encode(['success'=>false,'errors'=>['Parameter tidak lengkap (lapangan, ym).']]);
        exit;
    }
    $data = $service->getBookedSlotsForMonth($lap, $ym);
    header('Content-Type: application/json');
    echo json_encode(['success'=>true,'booked'=>$data]);
    exit;
}

/* ---------- Handle form submit ---------- */
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'submit_member')) {
    $res = $service->store($_POST, $_FILES);
    if (($res['success'] ?? false) === true) {
        header('Location: riwayat.php?status=pending');
        exit;
    } else {
        $errors = $res['errors'] ?? ['Terjadi kesalahan.'];
    }
}


/* ---------- Frontend HTML (form + UI) ---------- */
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Member - Booking Lapangan Badminton</title>
  <link rel="stylesheet" href="member.css?v=13">
</head>
<body>
  <main class="page">
    <header class="hero">
      <div class="hero-inner">
        <div class="hero-left">
          <h1 class="title">Member Badminton Premium</h1>
          <p class="subtitle">Prioritas booking, harga spesial, dan jadwal mingguan otomatis.</p>

          <div class="benefit-grid">
            <div class="benefit-card">🎯 Harga tetap jelas</div>
            <div class="benefit-card">⚡ Prioritas pemesanan</div>
            <div class="benefit-card">🔁 1x ubah jadwal/bulan</div>
            <div class="benefit-card">📅 Jadwal otomatis</div>
            <div class="benefit-card">💰 Hemat hingga 30%</div>
            <div class="benefit-card">🎁 Free minuman</div>
          </div>

          <div class="cta">
            <button id="openFlowBtn" class="btn primary" type="button">🎯 Gabung Member Sekarang</button>
          </div>
        </div>

        <div class="hero-right">
          <div class="price-big">Paket Mulai Dari <strong><?= rupiah(100000) ?></strong></div>
          <div class="testi card">
            <strong>⭐ Testimonial Member</strong>
            <p>"Mudah book, lapangan selalu rapi. Jadi member worth it banget!" — Aldo</p>
            <p>"Hemat waktu dan uang, recommended!" — Sari</p>
          </div>
          <div class="stats card" style="margin-top:16px;background:rgba(255,255,255,0.9);padding:16px;border-radius:12px">
            <div style="display:flex;justify-content:space-between;text-align:center">
              <div>
                <div style="font-size:1.5rem;font-weight:700;color:#2563eb">500+</div>
                <div style="font-size:0.8rem;color:#64748b">Member Aktif</div>
              </div>
              <div>
                <div style="font-size:1.5rem;font-weight:700;color:#10b981">98%</div>
                <div style="font-size:0.8rem;color:#64748b">Kepuasan</div>
              </div>
              <div>
                <div style="font-size:1.5rem;font-weight:700;color:#f59e0b">24/7</div>
                <div style="font-size:0.8rem;color:#64748b">Support</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <section class="how card">
      <h2>Harga Paket Member</h2>
      <div class="prices">
        <div class="price-item">
          <strong>1 Bulan</strong>
          <span><?= rupiah(100000) ?></span>
          <div style="font-size:0.9rem;color:#64748b;margin-top:8px">⭐ Cocok untuk pemula</div>
        </div>
        <div class="price-item">
          <strong>2 Bulan</strong>
          <span><?= rupiah(200000) ?></span>
          <div style="font-size:0.9rem;color:#64748b;margin-top:8px">💫 Hemat 15% per bulan</div>
        </div>
        <div class="price-item">
          <strong>3 Bulan</strong>
          <span><?= rupiah(300000) ?></span>
          <div style="font-size:0.9rem;color:#64748b;margin-top:8px">🚀 Hemat 30% per bulan</div>
        </div>
      </div>
    </section>

    <?php if (!empty($errors)): ?>
      <div class="notice error card">
        <strong>Perbaiki dulu:</strong>
        <ul><?php foreach($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul>
      </div>
    <?php endif; ?>

    <footer class="footer">
      <div style="max-width:600px;margin:0 auto;text-align:center">
        <div style="font-size:1.2rem;font-weight:600;margin-bottom:16px;color:#2563eb">Butuh Bantuan?</div>
        <div style="display:flex;justify-content:center;gap:32px;margin-bottom:24px;flex-wrap:wrap">
          <div>📞 0812-3456-7890</div>
          <div>📧 support@badminton.com</div>
          <div>🕒 Buka 24/7</div>
        </div>
        <div>© <?= date('Y') ?> Booking Lapangan Badminton - All rights reserved</div>
      </div>
    </footer>
  </main>

  <!-- FLOW POPUP (besar & per-month flow) -->
  <div id="flowPopup" class="popup big" aria-hidden="true">
    <div class="popup-overlay"></div>
    <div class="popup-dialog">
      <button id="closeFlow" class="close" type="button">&times;</button>
      <h2 class="flow-title">🎯 Gabung / Perpanjang Member</h2>

      <form id="memberForm" method="post" enctype="multipart/form-data" action="member.php?action=submit_member">
        <input type="hidden" name="action" value="submit_member">

        <!-- SECTION A -->
        <div class="section" id="sectionA">
          <h3>1. 📝 Data Diri & Paket</h3>
          <label class="label">Nama lengkap
            <input name="name" id="name" required placeholder="Masukkan nama lengkap Anda">
          </label>
          <label class="label">Email 
            <div class="muted" style="font-size:13px;margin-bottom:8px;">Ketik bagian sebelum @, otomatis berakhiran @gmail.com</div>
            <input name="email" id="email" type="text" required placeholder="nama.anda">
          </label>

          <label class="label">Pilih Paket
            <select name="paket" id="paket" required>
              <option value="">-- Pilih Paket --</option>
              <option value="1">1 Bulan — <?= rupiah(100000) ?> (⭐ Pemula)</option>
              <option value="2">2 Bulan — <?= rupiah(200000) ?> (💫 Hemat 15%)</option>
              <option value="3">3 Bulan — <?= rupiah(300000) ?> (🚀 Hemat 30%)</option>
            </select>
          </label>

          <label class="label">Mulai dari bulan
            <div class="muted" style="font-size:13px;margin-bottom:8px;">Klik area input untuk memilih bulan</div>
            <input type="month" id="startMonth" name="start_month" required style="cursor:pointer;">
          </label>

          <label class="label">Pilih Lapangan 
            <div class="muted" style="font-size:13px;margin-bottom:8px;">Satu pilihan berlaku untuk seluruh periode member</div>
            <select name="court" id="court" required>
              <option value="">-- Pilih Lapangan --</option>
              <option value="1">🏸 Lapangan 1</option>
              <option value="2">🏸 Lapangan 2</option>
              <option value="3">🏸 Lapangan 3</option>
              <option value="4">🏸 Lapangan 4</option>
            </select>
          </label>

          <div class="form-actions">
            <button type="button" id="toScheduleBtn" class="btn primary">📅 Lanjut Pilih Jadwal</button>
          </div>
        </div>

        <!-- SECTION B (per-month) -->
        <div class="section" id="sectionB" style="display:none">
          <h3>2. 📅 Pilih Jadwal Mingguan</h3>
          <!-- REVISI: Update pesan untuk sistem fleksibel -->
          <div class="warning-text">
            <strong>📢 Sistem Fleksibel:</strong> Untuk setiap bulan, pilih <strong>minimal 2 tanggal</strong> (bebas minggu ke berapa). Minggu ke-5 bersifat opsional.
          </div>
          <p class="muted">Tanggal yang ditampilkan hanya yang tersedia berdasarkan pilihan lapangan. Ganti lapangan jika ingin melihat jadwal lain.</p>

          <div id="monthFlowWrap"></div>
          <input type="hidden" name="selected_dates" id="selected_dates">

          <div class="form-actions">
            <button type="button" id="backToA" class="btn outline">⬅️ Kembali</button>
            <button type="button" id="toPaymentBtn" class="btn primary">💰 Lanjut ke Pembayaran</button>
          </div>
        </div>

        <!-- SECTION C: payment -->
        <div class="section" id="sectionC" style="display:none">
          <h3>3. 💰 Pembayaran</h3>
          <label class="label">Metode Pembayaran
            <select name="payment_method" id="payment_method" required>
              <option value="">-- Pilih Metode --</option>
              <option value="qris">📱 QRIS</option>
              <option value="bca">🏦 Transfer BCA</option>
              <option value="mandiri">🏦 Transfer Mandiri</option>
            </select>
          </label>

          <div id="paymentDetails" class="paymentDetails"></div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <label class="label">Upload Bukti Transfer
              <div class="muted" style="font-size:13px;">Format: jpg, png, pdf (maks. 5MB)</div>
              <input type="file" name="bukti" id="bukti" accept=".jpg,.jpeg,.png,.pdf" required>
            </label>

            <label class="label">Jumlah Pembayaran
              <input type="text" id="transfer_display" disabled style="background:#f8fafc">
              <input type="hidden" name="transfer_amount" id="transfer_amount">
            </label>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <label class="label">Nama Pengirim
              <input type="text" name="bank_from_name" id="bank_from_name" placeholder="Nama sesuai rekening">
            </label>

            <label class="label">No. Rekening Pengirim
              <input type="text" name="bank_from_number" id="bank_from_number" inputmode="numeric" pattern="\d*" placeholder="Contoh: 1234567890">
            </label>
          </div>

          <div class="form-actions">
            <button type="button" id="backToB" class="btn outline">⬅️ Kembali</button>
            <button type="button" id="submitBtn" class="btn primary">✅ Kirim Pendaftaran</button>
          </div>
        </div>

      </form>
    </div>
  </div>

  <!-- CONFIRM POPUP -->
  <div id="confirmPopup" class="popup small" aria-hidden="true">
    <div class="popup-overlay"></div>
    <div class="popup-dialog">
      <button id="confirmClose" class="close" type="button">&times;</button>
      <h3>✅ Konfirmasi Data Member</h3>
      <div id="confirmContent" style="max-height:400px;overflow:auto;margin:16px 0"></div>
      <div class="note muted" style="padding:12px;background:#f8fafc;border-radius:8px;margin:16px 0">
        <strong>📋 Pastikan semua data sudah benar:</strong><br>
        • Data tidak dapat diubah setelah dikirim<br>
        • Proses verifikasi membutuhkan waktu 1x24 jam<br>
        • Pilih 'Ubah Data' untuk kembali ke form
      </div>
      <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:20px">
        <button id="editBtn" class="btn outline" type="button">✏️ Ubah Data</button>
        <button id="confirmSendBtn" class="btn primary" type="button">🚀 Kirim & Tunggu Verifikasi</button>
      </div>
    </div>
  </div>

  <!-- SMALL POPUP (auto-close but also has X) -->
  <div id="smallPopup" class="small-popup" aria-hidden="true">
    <div class="popup-overlay"></div>
    <div class="small-popup-box">
      <button id="smallPopupClose" class="close small" type="button">&times;</button>
      <div id="smallPopupMessage"></div>
    </div>
  </div>

  <script src="member.js?v=5" defer></script>
</body>
</html>