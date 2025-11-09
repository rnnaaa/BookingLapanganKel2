<?php
// === SETUP & VALIDASI DASAR ===
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../config/database.php';

$id_member = intval($_GET['id_member'] ?? 0);
$successMsg = '';
$errorMsg = '';

// === AMBIL DATA MEMBER (HEADER INFO) ===
$member_info = null;
if ($id_member > 0) {
    $stmt = $conn->prepare("
        SELECT m.*, u.nama AS nama_user, l.nama_lapangan
        FROM member m
        JOIN users u ON m.id_user = u.id_user
        JOIN lapangan l ON m.id_lapangan = l.id_lapangan
        WHERE m.id_member = ?
    ");
    $stmt->bind_param("i", $id_member);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows) {
        $member_info = $res->fetch_assoc();
    }
    $stmt->close();
}

// === PROSES TAMBAH JADWAL MEMBER ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_member_post = intval($_POST['id_member'] ?? 0);
    $id_lapangan = intval($_POST['id_lapangan'] ?? 0);
    $tanggal_booking = $_POST['tanggal_booking'] ?? '';
    $jam_mulai = $_POST['jam_mulai'] ?? '';
    $jam_selesai = $_POST['jam_selesai'] ?? '';

    $hari_map = [
        'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'
    ];
    $hari_indonesia = $hari_map[date('l', strtotime($tanggal_booking))] ?? '';

    if (!$id_member_post || !$id_lapangan || !$tanggal_booking || !$jam_mulai || !$jam_selesai) {
        $errorMsg = "❌ Semua kolom wajib diisi.";
    } else {
        $conn->begin_transaction();
        try {
            // ✅ 1. Validasi status member
            $m = $conn->query("
                SELECT id_user, tanggal_mulai, tanggal_berakhir 
                FROM member 
                WHERE id_member=$id_member_post AND status='aktif'
            ")->fetch_assoc() ?? null;
            if (!$m) throw new Exception("⚠️ Member tidak aktif atau tidak ditemukan.");
            if ($tanggal_booking < $m['tanggal_mulai'] || $tanggal_booking > $m['tanggal_berakhir']) {
                throw new Exception("⚠️ Tanggal diluar masa aktif member (" . $m['tanggal_mulai'] . " s/d " . $m['tanggal_berakhir'] . ")");
            }

            // ✅ 2. Cek apakah sudah ada jadwal minggu ini
            $week_start = date('Y-m-d', strtotime('monday this week', strtotime($tanggal_booking)));
            $week_end = date('Y-m-d', strtotime('sunday this week', strtotime($tanggal_booking)));
            $cek = $conn->query("
                SELECT 1 FROM member_jadwal 
                WHERE id_member=$id_member_post 
                AND tanggal_booking BETWEEN '$week_start' AND '$week_end'
                AND status='aktif'
            ");
            if ($cek->num_rows) throw new Exception("⚠️ Member ini sudah punya jadwal di minggu yang sama.");

            // ✅ 3. Ambil harga per jam member
            $harga = $conn->query("SELECT harga_per_jam_member FROM lapangan WHERE id_lapangan=$id_lapangan")->fetch_assoc()['harga_per_jam_member'] ?? 0;
            if ($harga <= 0) throw new Exception("⚠️ Harga per jam member belum diatur untuk lapangan ini.");

            // ✅ 4. Ambil slot di jadwal_detail yang masih tersedia
            $slot = $conn->query("
                SELECT jd.id_detail 
                FROM jadwal_detail jd
                JOIN jadwal_harian jh ON jd.id_jadwal_harian=jh.id_jadwal_harian
                WHERE jh.id_lapangan=$id_lapangan
                AND jh.hari='$hari_indonesia'
                AND jd.jam_mulai='$jam_mulai'
                AND jd.jam_selesai='$jam_selesai'
                AND jd.status='tersedia'
                LIMIT 1
            ")->fetch_assoc();
            if (!$slot) throw new Exception("❌ Slot jam $jam_mulai - $jam_selesai pada hari $hari_indonesia tidak tersedia.");

            $id_detail = $slot['id_detail'];

            // ✅ 5. Insert ke booking (gratis)
            $stmt = $conn->prepare("
                INSERT INTO booking (id_user, id_lapangan, type_booking, tanggal, dp_amount, total_amount, status_pembayaran, created_at, updated_at)
                VALUES (?, ?, 'member', ?, 0, 0, 'selesai', NOW(), NOW())
            ");
            $stmt->bind_param("iis", $m['id_user'], $id_lapangan, $tanggal_booking);
            $stmt->execute();
            $id_booking = $conn->insert_id;
            $stmt->close();

            // ✅ 6. Simpan member_jadwal
            $stmt = $conn->prepare("
                INSERT INTO member_jadwal 
                (id_member, id_lapangan, tanggal_booking, jam_mulai, jam_selesai, harga_per_jam_member, status, created_at, hari, id_booking)
                VALUES (?, ?, ?, ?, ?, ?, 'aktif', NOW(), ?, ?)
            ");
            $stmt->bind_param("iisssdsi", $id_member_post, $id_lapangan, $tanggal_booking, $jam_mulai, $jam_selesai, $harga, $hari_indonesia, $id_booking);
            $stmt->execute();
            $id_member_jadwal = $conn->insert_id;
            $stmt->close();

            // ✅ 7. Update jadwal_detail → jadi dibooking
            $stmt = $conn->prepare("UPDATE jadwal_detail SET status='dibooking', id_booking=?, id_member_jadwal=? WHERE id_detail=?");
            $stmt->bind_param("iii", $id_booking, $id_member_jadwal, $id_detail);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            header("Location: member_jadwal.php?id_member=$id_member_post&success=" . urlencode("✅ Jadwal berhasil ditambahkan."));
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $errorMsg = $e->getMessage();
        }
    }
}

// === PERSIAPAN DATA UNTUK FORM ===
$qMembers = $conn->query("
    SELECT m.id_member, u.nama AS nama_user, l.nama_lapangan, m.id_lapangan 
    FROM member m
    JOIN users u ON m.id_user=u.id_user
    JOIN lapangan l ON m.id_lapangan=l.id_lapangan
    WHERE m.status='aktif'
    ORDER BY u.nama
");
$qLapangan = $conn->query("SELECT id_lapangan, nama_lapangan FROM lapangan ORDER BY nama_lapangan");
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
include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper animate__animated animate__fadeIn">
    <section class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div>
                <h1><i class="fas fa-calendar-week"></i> Jadwal Member</h1>
                <?php if ($member_info): ?>
                    <small>Member: <b><?= htmlspecialchars($member_info['nama_user']) ?></b> — Lapangan: <b><?= htmlspecialchars($member_info['nama_lapangan']) ?></b></small>
                <?php endif; ?>
            </div>
            <div>
                <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#formTambahJadwal"><i class="fas fa-plus-circle"></i> Tambah Jadwal</button>
                <a href="member.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <?php if ($successMsg): ?><div class="alert alert-success"><?= $successMsg ?></div><?php endif; ?>
            <?php if ($errorMsg): ?><div class="alert alert-danger"><?= $errorMsg ?></div><?php endif; ?>

            <div class="collapse show mt-3" id="formTambahJadwal">
                <div class="card card-primary">
                    <div class="card-header"><h3 class="card-title">Tambah Jadwal Member</h3></div>
                    <form method="POST" class="p-3">
                        <div class="row">
                            <div class="col-md-4">
                                <label>Member</label>
                                <select name="id_member" id="id_member" class="form-select" required>
                                    <option value="">-- Pilih Member --</option>
                                    <?php while($m=$qMembers->fetch_assoc()): ?>
                                        <option value="<?= $m['id_member'] ?>" data-id-lapangan="<?= $m['id_lapangan'] ?>" <?= $id_member==$m['id_member']?'selected':'' ?>>
                                            <?= htmlspecialchars($m['nama_user']) ?> (<?= htmlspecialchars($m['nama_lapangan']) ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Lapangan</label>
                                <select name="id_lapangan" id="id_lapangan" class="form-select" required>
                                    <option value="">-- Pilih Lapangan --</option>
                                    <?php while($l=$qLapangan->fetch_assoc()): ?>
                                        <option value="<?= $l['id_lapangan'] ?>"><?= htmlspecialchars($l['nama_lapangan']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Tanggal Booking</label>
                                <input type="date" name="tanggal_booking" id="tanggal_booking" class="form-control" min="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <div id="slotContainer" class="mt-3" style="display:none;">
                            <label>Pilih Slot Jam:</label>
                            <div id="slotList" class="d-flex flex-wrap gap-2"></div>
                            <input type="hidden" name="jam_mulai" id="jam_mulai">
                            <input type="hidden" name="jam_selesai" id="jam_selesai">
                            <small class="text-muted">Klik satu slot untuk memilih jam.</small>
                        </div>

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header"><h3 class="card-title">Daftar Jadwal Member</h3></div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped" id="tblMemberJadwal">
                        <thead>
                            <tr><th>No</th><th>Member</th><th>Lapangan</th><th>Tanggal</th><th>Jam</th><th>Status</th><th>Dibuat</th></tr>
                        </thead>
                        <tbody>
                            <?php $no=1; while($r=$qJadwal->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($r['nama_user']) ?></td>
                                    <td><?= htmlspecialchars($r['nama_lapangan']) ?></td>
                                    <td><?= date('d-m-Y', strtotime($r['tanggal_booking'])) ?></td>
                                    <td><?= substr($r['jam_mulai'],0,5) . ' - ' . substr($r['jam_selesai'],0,5) ?></td>
                                    <td><?= $r['status']=='aktif' ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>' ?></td>
                                    <td><?= date('d-m-Y', strtotime($r['created_at'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const idLapangan = document.getElementById('id_lapangan');
    const idMember = document.getElementById('id_member');
    const tanggal = document.getElementById('tanggal_booking');
    const slotContainer = document.getElementById('slotContainer');
    const slotList = document.getElementById('slotList');
    const jamMulai = document.getElementById('jam_mulai');
    const jamSelesai = document.getElementById('jam_selesai');

    function loadSlots() {
        const idL = idLapangan.value;
        const tgl = tanggal.value;
        slotList.innerHTML = '';
        if (!idL || !tgl) { slotContainer.style.display='none'; return; }

        fetch(`member_jadwal_get_slot.php?id_lapangan=${idL}&tanggal=${tgl}`)
            .then(res => res.json())
            .then(data => {
                if (data.status !== 'ok') throw new Error(data.message);
                if (!data.slots.length) {
                    slotList.innerHTML = '<p class="text-danger">Tidak ada slot tersedia.</p>';
                    slotContainer.style.display = 'block';
                    return;
                }
                slotContainer.style.display = 'block';
                data.slots.forEach(s => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'btn btn-outline-success btn-sm me-2 mb-2';
                    btn.textContent = `${s.jam_mulai.substring(0,5)} - ${s.jam_selesai.substring(0,5)}`;
                    btn.onclick = () => {
                        document.querySelectorAll('#slotList button').forEach(b => b.classList.remove('btn-success','active'));
                        btn.classList.add('btn-success','active');
                        jamMulai.value = s.jam_mulai;
                        jamSelesai.value = s.jam_selesai;
                    };
                    slotList.appendChild(btn);
                });
            })
            .catch(err => {
                slotList.innerHTML = `<p class="text-danger">Gagal memuat slot: ${err.message}</p>`;
                slotContainer.style.display = 'block';
            });
    }

    idMember.addEventListener('change', () => {
        const lapangan = idMember.selectedOptions[0].dataset.idLapangan;
        if (lapangan) idLapangan.value = lapangan;
        loadSlots();
    });
    idLapangan.addEventListener('change', loadSlots);
    tanggal.addEventListener('change', loadSlots);
});
</script>
<?php include('../includes/footer.php'); ?>
