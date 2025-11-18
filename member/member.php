<?php
session_start();

// Database configuration - SESUAIKAN DENGAN SETTINGANMU
$DB_HOST = 'localhost';
$DB_NAME = 'bookinglapanganb2';
$DB_USER = 'root';
$DB_PASS = '';
$DB_CHAR = 'utf8mb4';

class DB {
    private $pdo;
    public function __construct($host, $db, $user, $pass, $charset = 'utf8mb4') {
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $opt = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        $this->pdo = new PDO($dsn, $user, $pass, $opt);
    }
    public function pdo() { return $this->pdo; }
}

try {
    $db = new DB($DB_HOST, $DB_NAME, $DB_USER, $DB_PASS, $DB_CHAR);
    $pdo = $db->pdo();
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

class MemberService {
    private $pdo;
    private $uploadDir;
    private $prices = [1 => 100000, 2 => 200000, 3 => 300000];

    public function __construct($pdo, $uploadDir = 'uploads/') {
        $this->pdo = $pdo;
        $this->uploadDir = rtrim($uploadDir, '/');
        if (!is_dir($this->uploadDir)) mkdir($this->uploadDir, 0755, true);
    }

    public function getBookedSlotsForMonth($lapanganId, $ym) {
        [$y, $m] = explode('-', $ym);
        $start = "$ym-01";
        $end = date('Y-m-t', strtotime($start));

        $sql = "SELECT tanggal_booking, jam_mulai FROM member_jadwal 
                WHERE id_lapangan = :lapangan 
                AND tanggal_booking BETWEEN :start AND :end 
                AND status IN ('pending','aktif')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':lapangan' => $lapanganId, ':start' => $start, ':end' => $end]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $res = [];
        foreach ($rows as $r) {
            $d = $r['tanggal_booking'];
            $t = substr($r['jam_mulai'], 0, 5);
            if (!isset($res[$d])) $res[$d] = [];
            $res[$d][] = $t;
        }
        return $res;
    }

    public function store($input, $files) {
        $errors = [];

        // Validasi user login
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'errors' => ['Silakan login terlebih dahulu']];
        }

        $name = trim($input['name'] ?? '');
        $emailRaw = trim($input['email'] ?? '');
        if ($emailRaw !== '' && strpos($emailRaw, '@') === false) $emailRaw .= '@gmail.com';
        $email = $emailRaw;
        $paket = (int)($input['paket'] ?? 0);
        $start_month = trim($input['start_month'] ?? '');
        $court = (int)($input['court'] ?? 0);
        $selected_dates_raw = $input['selected_dates'] ?? '[]';
        $selected_dates = json_decode($selected_dates_raw, true);
        $payment_method = trim($input['payment_method'] ?? '');
        $bank_from_name = trim($input['bank_from_name'] ?? '');
        $bank_from_number = trim($input['bank_from_number'] ?? '');
        $transfer_amount = trim($input['transfer_amount'] ?? '');

        // Validasi dasar
        if ($name === '') $errors[] = 'Nama wajib diisi.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email wajib dan harus valid.';
        if (!in_array($paket, [1,2,3])) $errors[] = 'Pilih paket yang valid.';
        if ($start_month === '' || !preg_match('/^\d{4}-\d{2}$/', $start_month)) $errors[] = 'Pilih bulan mulai.';
        if (!is_array($selected_dates) || count($selected_dates) < 2) $errors[] = 'Pilih minimal 2 tanggal per bulan.';
        if (empty($court)) $errors[] = 'Pilih lapangan.';
        if (!in_array($payment_method, ['qris','bca','mandiri','tunai'])) $errors[] = 'Pilih metode pembayaran.';
        if ($transfer_amount === '' || !preg_match('/^\d+$/', $transfer_amount)) $errors[] = 'Nominal harus angka.';

        // Parse selected_dates
        $parsedSlots = [];
        foreach ($selected_dates as $item) {
            if (is_array($item) && isset($item['date']) && isset($item['time'])) {
                $date = $item['date'];
                $time = $item['time'];
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { 
                    $errors[] = "Format tanggal tidak valid: $date"; 
                    continue; 
                }
                if (!preg_match('/^\d{2}:\d{2}$/', $time)) { 
                    $errors[] = "Format jam tidak valid: $time"; 
                    continue; 
                }
                $parsedSlots[] = ['date' => $date, 'time' => $time];
            }
        }

        if (!empty($errors)) return ['success' => false, 'errors' => $errors];

        // Validasi minimal 2 tanggal per bulan
        [$y0, $m0] = explode('-', $start_month);
        $y0 = (int)$y0; $m0 = (int)$m0;
        $monthsCount = $paket;
        
        for ($i = 0; $i < $monthsCount; $i++) {
            $inspect = new DateTime("$y0-$m0-01");
            $inspect->modify("+$i month");
            $year = (int)$inspect->format('Y');
            $month = (int)$inspect->format('n');
            $monthName = $inspect->format('F Y');

            $datesInMonth = array_filter($parsedSlots, function($slot) use ($year, $month) {
                $dt = DateTime::createFromFormat('Y-m-d', $slot['date']);
                return $dt && (int)$dt->format('Y') === $year && (int)$dt->format('n') === $month;
            });
            
            $selectedCount = count($datesInMonth);
            if ($selectedCount < 2) {
                $errors[] = "Tanggal belum lengkap untuk bulan $monthName (minimal 2 tanggal, baru $selectedCount).";
            }
        }

        if (!empty($errors)) return ['success' => false, 'errors' => $errors];

        // Cek konflik jadwal
        $conflicts = [];
        $checkStmt = $this->pdo->prepare("SELECT id_member_jadwal FROM member_jadwal 
            WHERE id_lapangan = :lap AND tanggal_booking = :tgl AND jam_mulai = :jm 
            AND status IN ('pending','aktif') LIMIT 1");
        
        foreach ($parsedSlots as $ps) {
            $checkStmt->execute([':lap' => $court, ':tgl' => $ps['date'], ':jm' => $ps['time']]);
            if ($checkStmt->fetch()) {
                $conflicts[] = $ps['date'] . ' ' . $ps['time'];
            }
        }
        
        if (!empty($conflicts)) {
            return ['success' => false, 'errors' => ['Beberapa slot sudah terisi: ' . implode(', ', $conflicts)]];
        }

        // Handle file upload
        $buktiPath = '';
        if (isset($files['bukti']) && $files['bukti']['error'] === UPLOAD_ERR_OK) {
            $fn = basename($files['bukti']['name']);
            $ext = strtolower(pathinfo($fn, PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','pdf'];
            if (!in_array($ext, $allowed)) {
                return ['success' => false, 'errors' => ['Bukti transfer harus jpg/png/pdf.']];
            }
            $newName = uniqid('bukti_') . '.' . $ext;
            $dest = $this->uploadDir . '/' . $newName;
            if (!move_uploaded_file($files['bukti']['tmp_name'], $dest)) {
                return ['success' => false, 'errors' => ['Gagal menyimpan file bukti.']];
            }
            $buktiPath = 'uploads/' . $newName;
        } else {
            return ['success' => false, 'errors' => ['Bukti transfer wajib di-upload.']];
        }

        // Calculate dates
        $firstOfStart = new DateTime("$y0-$m0-01");
        $lastOfEnd = clone $firstOfStart;
        $lastOfEnd->modify('+' . ($monthsCount - 1) . ' month')->modify('last day of this month');
        $tanggal_mulai = $firstOfStart->format('Y-m-d');
        $tanggal_berakhir = $lastOfEnd->format('Y-m-d');

        // Price
        $price = $this->prices[$paket] ?? 0;

        try {
            $this->pdo->beginTransaction();

            // Insert to member table
            $stmt = $this->pdo->prepare("INSERT INTO member (id_user, id_lapangan, durasi_bulan, tanggal_mulai, 
                tanggal_berakhir, bukti_pembayaran, method, total_bayar, status, created_at, updated_at) 
                VALUES (:id_user, :id_lap, :dur, :tgl_mulai, :tgl_akhir, :bukti, :method, :total, :st, NOW(), NOW())");
            
            $stmt->execute([
                ':id_user' => $_SESSION['user_id'],
                ':id_lap' => $court,
                ':dur' => $paket,
                ':tgl_mulai' => $tanggal_mulai,
                ':tgl_akhir' => $tanggal_berakhir,
                ':bukti' => $buktiPath,
                ':method' => $payment_method,
                ':total' => (int)$transfer_amount,
                ':st' => 'pending'
            ]);
            $memberId = $this->pdo->lastInsertId();

            // Insert to member_jadwal and jadwal_detail
            $insJ = $this->pdo->prepare("INSERT INTO member_jadwal (id_member, id_lapangan, tanggal_booking, 
                jam_mulai, jam_selesai, harga_per_jam_member, status, created_at, updated_at) 
                VALUES (:mid, :lap, :tgl, :jm, :js, :harga, :st, NOW(), NOW())");

            foreach ($parsedSlots as $ps) {
                $jamMulai = $ps['time'];
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
                    ':st' => 'pending'
                ]);
                $memberJadwalId = $this->pdo->lastInsertId();

                // Update jadwal_detail
                $this->updateJadwalDetail($court, $ps['date'], $jamMulai, $memberJadwalId);
            }

            // Insert to keuangan
            $sqlKeuangan = "INSERT INTO keuangan (tanggal, jenis, kategori, keterangan, 
                jumlah, sumber, created_at) 
                VALUES (CURDATE(), 'pemasukan', 'Membership', 
                'Pendaftaran Member ID $memberId', :jumlah, 'Pelunasan', NOW())";
            $stmtKeuangan = $this->pdo->prepare($sqlKeuangan);
            $stmtKeuangan->execute([':jumlah' => (int)$transfer_amount]);

            $this->pdo->commit();
            return ['success' => true, 'member_id' => $memberId];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            // Delete uploaded file if error
            if (file_exists($this->uploadDir . '/' . basename($buktiPath))) {
                unlink($this->uploadDir . '/' . basename($buktiPath));
            }
            return ['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]];
        }
    }

    private function updateJadwalDetail($lapanganId, $tanggal, $jamMulai, $memberJadwalId) {
        // Cari atau buat jadwal_harian
        $sqlHarian = "SELECT id_jadwal_harian FROM jadwal_harian 
                      WHERE id_lapangan = ? AND tanggal = ?";
        $stmtHarian = $this->pdo->prepare($sqlHarian);
        $stmtHarian->execute([$lapanganId, $tanggal]);
        $harian = $stmtHarian->fetch(PDO::FETCH_ASSOC);

        if (!$harian) {
            $hari = date('l', strtotime($tanggal));
            $sqlInsertHarian = "INSERT INTO jadwal_harian (id_lapangan, tanggal, hari, status_harian, created_at) 
                                VALUES (?, ?, ?, 'tersedia', NOW())";
            $stmtInsertHarian = $this->pdo->prepare($sqlInsertHarian);
            $stmtInsertHarian->execute([$lapanganId, $tanggal, $hari]);
            $harianId = $this->pdo->lastInsertId();
        } else {
            $harianId = $harian['id_jadwal_harian'];
        }

        // Cari jadwal_waktu
        $sqlWaktu = "SELECT id_jadwal_waktu FROM jadwal_waktu 
                     WHERE id_lapangan = ? AND jam_mulai = ?";
        $stmtWaktu = $this->pdo->prepare($sqlWaktu);
        $stmtWaktu->execute([$lapanganId, $jamMulai]);
        $waktu = $stmtWaktu->fetch(PDO::FETCH_ASSOC);

        if ($waktu) {
            $waktuId = $waktu['id_jadwal_waktu'];
            
            // Update jadwal_detail
            $sqlDetail = "INSERT INTO jadwal_detail (id_jadwal_harian, id_jadwal_waktu, status, id_member_jadwal, created_at) 
                          VALUES (?, ?, 'dibooking', ?, NOW()) 
                          ON DUPLICATE KEY UPDATE status = 'dibooking', id_member_jadwal = ?";
            $stmtDetail = $this->pdo->prepare($sqlDetail);
            $stmtDetail->execute([$harianId, $waktuId, $memberJadwalId, $memberJadwalId]);
        }
    }
}

$service = new MemberService($pdo);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    if ($_GET['action'] === 'availability' && isset($_GET['lapangan'], $_GET['ym'])) {
        $booked = $service->getBookedSlotsForMonth($_GET['lapangan'], $_GET['ym']);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'booked' => $booked]);
        exit;
    }
}

// Handle form submission
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_member') {
    $result = $service->store($_POST, $_FILES);
    if ($result['success']) {
        $_SESSION['success_message'] = 'Pendaftaran member berhasil! Menunggu verifikasi admin.';
        header('Location: riwayat.php');
        exit;
    } else {
        $errors = $result['errors'];
    }
}

// Get user data for auto-fill
$userName = $_SESSION['user_name'] ?? '';
$userEmail = $_SESSION['user_email'] ?? '';

function rupiah($n) { 
    return 'Rp ' . number_format($n, 0, ',', '.'); 
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <title>Member - Booking Lapangan Badminton</title>
    <link rel="stylesheet" href="member.css">
</head>
<body>
    <main class="page">
        <header class="hero">
            <div class="hero-inner">
                <div class="hero-left">
                    <h1 class="title">Member Badminton Premium</h1>
                    <p class="subtitle">Prioritas booking, harga spesial, dan jadwal mingguan otomatis.</p>
                    <div class="benefit-grid">
                        <div class="benefit-card">Harga tetap jelas</div>
                        <div class="benefit-card">Prioritas pemesanan</div>
                        <div class="side-by-side-container">
                            <div class="benefit-card">Hemat hingga 30%</div>
                            <div class="benefit-card">1x ubah jadwal/bulan</div>
                        </div>
                    </div>
                    <div class="cta">
                        <button id="openFlowBtn" class="btn primary" type="button">Gabung Member Sekarang</button>
                    </div>
                </div>

                <div class="hero-right">
                    <div class="price-big">Paket Mulai Dari <strong><?= rupiah(100000) ?></strong></div>
                    <div class="testi card">
                        <strong>Testimonial Member</strong>
                        <p>"Mudah book, lapangan selalu rapi. Jadi member worth it banget!" — Aldo</p>
                        <p>"Hemat waktu dan uang, recommended!" — Sari</p>
                    </div>
                    <div class="stats card">
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
                    <div style="font-size:0.9rem;color:#64748b;margin-top:8px"></div>
                </div>
                <div class="price-item">
                    <strong>2 Bulan</strong>
                    <span><?= rupiah(200000) ?></span>
                    <div style="font-size:0.9rem;color:#64748b;margin-top:8px"></div>
                </div>
                <div class="price-item">
                    <strong>3 Bulan</strong>
                    <span><?= rupiah(300000) ?></span>
                    <div style="font-size:0.9rem;color:#64748b;margin-top:8px"></div>
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

    <!-- FLOW POPUP -->
    <div id="flowPopup" class="popup big" aria-hidden="true">
        <div class="popup-overlay"></div>
        <div class="popup-dialog">
            <button id="closeFlow" class="close" type="button">&times;</button>
            <h2 class="flow-title">Gabung / Perpanjang Member</h2>

            <form id="memberForm" method="post" enctype="multipart/form-data" action="member.php?action=submit_member">
                <input type="hidden" name="action" value="submit_member">

                <!-- SECTION A -->
                <div class="section" id="sectionA">
                    <h3>1. Data Diri & Paket</h3>
                    <label class="label">Nama lengkap
                        <input name="name" id="name" required placeholder="Masukkan nama lengkap Anda" 
                               value="<?= htmlspecialchars($userName) ?>" readonly>
                    </label>
                    <label class="label">Email 
                        <div class="muted" style="font-size:13px;margin-bottom:8px;">Email Anda (otomatis dari akun)</div>
                        <input name="email" id="email" type="email" required placeholder="email@anda.com" 
                               value="<?= htmlspecialchars($userEmail) ?>" readonly>
                    </label>

                    <label class="label">Pilih Paket
                        <select name="paket" id="paket" required>
                            <option value="">-- Pilih Paket --</option>
                            <option value="1">1 Bulan — <?= rupiah(100000) ?></option>
                            <option value="2">2 Bulan — <?= rupiah(200000) ?></option>
                            <option value="3">3 Bulan — <?= rupiah(300000) ?></option>
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
                            <option value="7">Lapangan A</option>
                            <option value="8">Lapangan B</option>
                            <option value="9">Lapangan C</option>
                        </select>
                    </label>

                    <div class="form-actions">
                        <button type="button" id="toScheduleBtn" class="btn primary">Lanjut Pilih Jadwal</button>
                    </div>
                </div>

                <!-- SECTION B -->
                <div class="section" id="sectionB" style="display:none">
                    <h3>2. Pilih Jadwal Mingguan</h3>
                    <div class="warning-text">
                        <strong>Sistem Fleksibel:</strong> Untuk setiap bulan, pilih <strong>minimal 2 tanggal</strong> (bebas minggu ke berapa). Minggu ke-5 bersifat opsional.
                    </div>
                    <p class="muted">Tanggal yang ditampilkan hanya yang tersedia berdasarkan pilihan lapangan. Ganti lapangan jika ingin melihat jadwal lain.</p>

                    <div id="monthFlowWrap"></div>
                    <input type="hidden" name="selected_dates" id="selected_dates">

                    <div class="form-actions">
                        <button type="button" id="backToA" class="btn outline">Kembali</button>
                        <button type="button" id="toPaymentBtn" class="btn primary">Lanjut ke Pembayaran</button>
                    </div>
                </div>

                <!-- SECTION C -->
                <div class="section" id="sectionC" style="display:none">
                    <h3>3. Pembayaran</h3>
                    <label class="label">Metode Pembayaran
                        <select name="payment_method" id="payment_method" required>
                            <option value="">-- Pilih Metode --</option>
                            <option value="qris">QRIS</option>
                            <option value="bca">Transfer BCA</option>
                            <option value="mandiri">Transfer Mandiri</option>
                            <option value="tunai">Tunai</option>
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
                        <button type="button" id="backToB" class="btn outline">Kembali</button>
                        <button type="button" id="submitBtn" class="btn primary">Kirim Pendaftaran</button>
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
            <h3>Konfirmasi Data Member</h3>
            <div id="confirmContent" style="max-height:400px;overflow:auto;margin:16px 0"></div>
            <div class="note muted" style="padding:12px;background:#f8fafc;border-radius:8px;margin:16px 0">
                <strong>Pastikan semua data sudah benar:</strong><br>
                • Data tidak dapat diubah setelah dikirim<br>
                • Proses verifikasi membutuhkan waktu 1x24 jam<br>
                • Pilih 'Ubah Data' untuk kembali ke form
            </div>
            <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:20px">
                <button id="editBtn" class="btn outline" type="button">Ubah Data</button>
                <button id="confirmSendBtn" class="btn primary" type="button">Kirim & Tunggu Verifikasi</button>
            </div>
        </div>
    </div>

    <script src="member.js"></script>
</body>
</html>