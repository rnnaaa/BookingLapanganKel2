<?php
// booking_edit.php - FULL FEATURE (Reschedule & Status)
require_once __DIR__ . '/../config/database.php';

// Pastikan session dimulai
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_GET['id'])) {
    header("Location: booking.php");
    exit;
}

$id_booking = intval($_GET['id']);

// --- 1. PROSES SIMPAN PERUBAHAN (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // A. UPDATE STATUS & PEMBAYARAN (Simpel)
    if (isset($_POST['action']) && $_POST['action'] == 'update_status') {
        $status_baru = $_POST['status'];
        $payment_status_baru = $_POST['payment_status'];

        $stmt = $conn->prepare("UPDATE booking SET status = ?, payment_status = ?, updated_at = NOW() WHERE id_booking = ?");
        $stmt->bind_param("ssi", $status_baru, $payment_status_baru, $id_booking);
        if ($stmt->execute()) {
            $_SESSION['toast_success'] = "Status booking berhasil diperbarui.";
        } else {
            $_SESSION['toast_error'] = "Gagal update status.";
        }
        header("Location: booking_detail.php?id=" . $id_booking);
        exit;
    }

    // B. RESCHEDULE / GANTI LAPANGAN (Kompleks)
    if (isset($_POST['action']) && $_POST['action'] == 'reschedule') {
        $new_id_lapangan = intval($_POST['id_lapangan']);
        $new_tanggal     = $_POST['tanggal'];
        $new_slot_ids    = $_POST['slot_ids'] ?? [];

        if (!$new_id_lapangan || !$new_tanggal || empty($new_slot_ids)) {
            $_SESSION['toast_error'] = "⚠️ Data reschedule tidak lengkap. Pilih Lapangan, Tanggal, dan Slot.";
            header("Location: booking_edit.php?id=$id_booking");
            exit;
        }

        $conn->begin_transaction();
        try {
            // 1. Ambil Harga Lapangan Baru
            $stmt = $conn->prepare("SELECT harga_per_jam FROM lapangan WHERE id_lapangan = ?");
            $stmt->bind_param("i", $new_id_lapangan);
            $stmt->execute();
            $lap = $stmt->get_result()->fetch_assoc();
            $harga_per_jam = floatval($lap['harga_per_jam']);
            $stmt->close();

            // 2. LEPAS SLOT LAMA (PENTING: Ini membuat slot lama tersedia lagi untuk orang lain)
            // Set status='tersedia' dan id_booking=NULL untuk semua slot yg dimiliki booking ini saat ini
            $stmt = $conn->prepare("UPDATE jadwal_detail SET status='tersedia', id_booking=NULL WHERE id_booking = ?");
            $stmt->bind_param("i", $id_booking);
            $stmt->execute();
            $stmt->close();

            // 3. HAPUS DETAIL BOOKING LAMA (Agar tidak duplikat data harga)
            $stmt = $conn->prepare("DELETE FROM detail_booking WHERE id_booking = ?");
            $stmt->bind_param("i", $id_booking);
            $stmt->execute();
            $stmt->close();

            // 4. VALIDASI & KUNCI SLOT BARU
            $placeholders = implode(',', array_fill(0, count($new_slot_ids), '?'));
            $types = str_repeat('i', count($new_slot_ids));
            
            // Gunakan FOR UPDATE untuk mengunci baris agar tidak diambil orang lain saat proses ini
            $sql = "SELECT id_detail, status, id_jadwal_waktu FROM jadwal_detail WHERE id_detail IN ($placeholders) FOR UPDATE";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$new_slot_ids);
            $stmt->execute();
            $res = $stmt->get_result();
            $rows = $res->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            if (count($rows) !== count($new_slot_ids)) {
                throw new Exception("Data slot baru tidak valid atau tidak ditemukan.");
            }

            // 5. PROSES BOOKING SLOT BARU
            $total_baru = 0;
            $durasi_per_slot = 1; // Asumsi 1 jam per slot (sesuaikan jika sistem Anda 30 menit)
            
            // Siapkan statement update dan insert
            $stmt_upd_slot = $conn->prepare("UPDATE jadwal_detail SET status='dibooking', id_booking = ? WHERE id_detail = ?");
            $stmt_ins_det  = $conn->prepare("INSERT INTO detail_booking (id_booking, id_jadwal_waktu, harga) VALUES (?, ?, ?)");

            foreach ($rows as $r) {
                // Pastikan slot target statusnya tersedia (karena slot lama sudah dilepas di step 2, 
                // jika user memilih slot yang sama dengan sebelumnya, statusnya sudah 'tersedia' sekarang)
                if ($r['status'] !== 'tersedia') {
                    throw new Exception("Salah satu slot yang dipilih sudah diambil orang lain barusan.");
                }

                $harga_slot = $harga_per_jam * $durasi_per_slot;
                $total_baru += $harga_slot;

                // Update status slot jadi dibooking
                $stmt_upd_slot->bind_param("ii", $id_booking, $r['id_detail']);
                $stmt_upd_slot->execute();

                // Insert detail baru
                $stmt_ins_det->bind_param("iid", $id_booking, $r['id_jadwal_waktu'], $harga_slot);
                $stmt_ins_det->execute();
            }
            $stmt_upd_slot->close();
            $stmt_ins_det->close();

            // 6. HITUNG ULANG KEUANGAN
            // Kita perlu tahu berapa yang SUDAH dibayar user sebelumnya
            $stmt = $conn->prepare("SELECT total_amount, remaining_amount FROM booking WHERE id_booking = ?");
            $stmt->bind_param("i", $id_booking);
            $stmt->execute();
            $curr = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            // Rumus: Uang Masuk = Total Lama - Sisa Lama
            $uang_masuk = floatval($curr['total_amount']) - floatval($curr['remaining_amount']);
            
            // Sisa Baru = Total Baru - Uang Masuk
            $remaining_baru = $total_baru - $uang_masuk;

            // 7. UPDATE HEADER BOOKING
            $stmt = $conn->prepare("UPDATE booking SET id_lapangan = ?, tanggal = ?, total_amount = ?, remaining_amount = ?, updated_at = NOW() WHERE id_booking = ?");
            $stmt->bind_param("isddi", $new_id_lapangan, $new_tanggal, $total_baru, $remaining_baru, $id_booking);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $_SESSION['toast_success'] = "✅ Jadwal berhasil diubah! Slot lama sudah dilepas dan slot baru telah disimpan.";
            header("Location: booking_detail.php?id=" . $id_booking);
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['toast_error'] = "Gagal Reschedule: " . $e->getMessage();
            header("Location: booking_edit.php?id=$id_booking");
            exit;
        }
    }
}

// --- 2. AMBIL DATA SAAT INI ---
$stmt = $conn->prepare("SELECT b.*, l.nama_lapangan FROM booking b JOIN lapangan l ON b.id_lapangan = l.id_lapangan WHERE b.id_booking = ?");
$stmt->bind_param("i", $id_booking);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

// Ambil list lapangan untuk dropdown
$qLap = $conn->query("SELECT id_lapangan, nama_lapangan, harga_per_jam FROM lapangan WHERE status='aktif'");

include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper animate__animated animate__fadeIn">
    <section class="content-header">
        <h1>
            <i class="fas fa-edit me-2"></i> 
            Edit Booking #<?= $id_booking ?>
        </h1>
    </section>

    <section class="content">
        
        <?php if (!empty($_SESSION['toast_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $_SESSION['toast_error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['toast_error']); ?>
        <?php endif; ?>

        <div class="row">
            <!-- KOLOM KIRI: GANTI STATUS -->
            <div class="col-md-4">
                <div class="card shadow-lg border-0">
                    <div class="card-header text-white" style="background: linear-gradient(90deg, #0e5c91, #2196f3);">
                        <h3 class="card-title"><i class="fas fa-edit"></i> Update Status</h3>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_status">
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label>Status Booking</label>
                                <select name="status" class="form-control">
                                    <?php 
                                    $statuses = ['menunggu','disetujui','selesai','ditolak','dibatalkan'];
                                    foreach($statuses as $s) {
                                        $sel = ($data['status'] == $s) ? 'selected' : '';
                                        echo "<option value='$s' $sel>".ucfirst($s)."</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label>Status Pembayaran</label>
                                <select name="payment_status" class="form-control">
                                    <?php 
                                    $pays = ['belum_bayar','dp_bayar','lunas'];
                                    foreach($pays as $p) {
                                        $sel = ($data['payment_status'] == $p) ? 'selected' : '';
                                        echo "<option value='$p' $sel>".ucfirst(str_replace('_',' ',$p))."</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="card-footer text-end bg-light">
                            <!-- TOMBOL UPDATE JADI BIRU (PRIMARY) -->
                            <button type="submit" class="btn btn-primary">Update Status</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- KOLOM KANAN: RESCHEDULE (GANTI LAPANGAN/TANGGAL) -->
            <div class="col-md-8">
                <div class="card shadow-lg border-0">
                    <div class="card-header text-white" style="background: linear-gradient(90deg, #0e5c91, #2196f3);">
                        <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Reschedule / Ganti Jadwal</h3>
                    </div>
                    <form method="POST" id="formReschedule">
                        <input type="hidden" name="action" value="reschedule">
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> <strong>Penting:</strong> Saat Anda menyimpan perubahan, slot jadwal yang lama otomatis akan dilepas (menjadi tersedia kembali) dan slot baru akan dibooking. Total harga akan dihitung ulang.
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Pilih Lapangan</label>
                                    <select name="id_lapangan" id="id_lapangan" class="form-control" required>
                                        <?php while($l = $qLap->fetch_assoc()): ?>
                                            <option value="<?= $l['id_lapangan'] ?>" 
                                                    data-harga="<?= $l['harga_per_jam'] ?>"
                                                    <?= ($l['id_lapangan'] == $data['id_lapangan']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($l['nama_lapangan']) ?> 
                                                (Rp <?= number_format($l['harga_per_jam'],0) ?>/jam)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Pilih Tanggal Baru</label>
                                    <input type="date" name="tanggal" id="tanggal" class="form-control" 
                                           value="<?= $data['tanggal'] ?>" min="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label>Pilih Slot Jam Baru</label>
                                <div id="slotLoading" style="display:none;" class="text-primary">
                                    <i class="fas fa-spinner fa-spin"></i> Memuat jadwal...
                                </div>
                                <div id="slotList" class="d-flex flex-wrap gap-2 p-2 border bg-light rounded" style="min-height: 60px;">
                                    <small class="text-muted">Silakan pilih lapangan dan tanggal untuk melihat slot.</small>
                                </div>
                                <input type="hidden" id="total_harga_temp">
                            </div>
                            
                            <!-- BAGIAN TOTAL: GRADASI BIRU & FONT PUTIH -->
                            <div class="mt-3 p-3 rounded shadow-sm" style="background: linear-gradient(90deg, #0e5c91, #2196f3); color: white;">
                                <h5 class="mb-0">Estimasi Total Baru: <span id="displayTotal" class="fw-bold">-</span></h5>
                            </div>

                        </div>
                        <div class="card-footer ">
                            <!-- TOMBOL BATAL FONT PUTIH -->
                            <a href="booking_detail.php?id=<?= $id_booking ?>" class="btn btn-secondary me-2 text-white">Batal</a>
                            <button type="submit" class="btn btn-primary" id="btnSaveReschedule">Simpan Perubahan Jadwal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include('../includes/footer.php'); ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const idBooking = <?= $id_booking ?>;
    const idLapangan = document.getElementById('id_lapangan');
    const tanggal = document.getElementById('tanggal');
    const slotList = document.getElementById('slotList');
    const slotLoading = document.getElementById('slotLoading');
    const displayTotal = document.getElementById('displayTotal');
    const form = document.getElementById('formReschedule');

    let selectedSlots = [];

    function loadSlots() {
        const idL = idLapangan.value;
        const tgl = tanggal.value;

        if(!idL || !tgl) return;

        slotList.innerHTML = '';
        slotLoading.style.display = 'block';
        selectedSlots = []; // Reset pilihan saat ganti tanggal/lapangan
        updateTotal();

        // Panggil API get_slot dengan parameter exclude_booking agar slot kita sendiri tetap terlihat & bisa dipilih
        fetch(`booking_get_slot.php?id_lapangan=${idL}&tanggal=${tgl}&exclude_booking=${idBooking}`)
            .then(res => res.json())
            .then(data => {
                slotLoading.style.display = 'none';
                if(data.status !== 'success') {
                    slotList.innerHTML = `<span class="text-danger">${data.message}</span>`;
                    return;
                }
                
                if(data.slots.length === 0) {
                    slotList.innerHTML = `<span class="text-muted">Tidak ada slot tersedia.</span>`;
                    return;
                }

                data.slots.forEach(s => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'btn btn-outline-primary btn-sm m-1';
                    
                    // Logic Tampilan Tombol
                    if (s.status === 'tersedia') {
                        // Slot kosong -> Bisa dipilih
                        btn.innerHTML = `${s.jam_mulai} - ${s.jam_selesai}`;
                        
                        // Jika ini slot milik booking ini sendiri (karena exclude_booking), 
                        // kita beri tanda visual (opsional)
                        if(s.is_mine) {
                             btn.classList.add('border-info', 'text-info'); 
                             // Opsional: btn.innerHTML += ' <i class="fas fa-check-circle"></i>';
                        }
                        
                        btn.onclick = () => toggleSlot(btn, s.id_detail);
                        
                    } else if (s.status === 'lewat') {
                        btn.disabled = true;
                        btn.className = 'btn btn-secondary btn-sm m-1 opacity-50';
                        btn.innerHTML = `<i class="fas fa-history"></i> ${s.jam_mulai}`;
                    } else {
                        // Dibooking orang lain
                        btn.disabled = true;
                        btn.className = 'btn btn-danger btn-sm m-1 disabled';
                        btn.innerHTML = `<i class="fas fa-lock"></i> ${s.jam_mulai}`;
                    }
                    
                    slotList.appendChild(btn);
                });
            })
            .catch(err => {
                slotLoading.style.display = 'none';
                slotList.innerHTML = `<span class="text-danger">Error koneksi.</span>`;
            });
    }

    function toggleSlot(btn, idDetail) {
        const idx = selectedSlots.indexOf(idDetail);
        if (idx > -1) {
            // Unselect
            selectedSlots.splice(idx, 1);
            btn.classList.remove('btn-success', 'active', 'text-white');
            
            // Kembalikan class awal (cek jika punya class info)
            if (btn.classList.contains('border-info')) {
                btn.classList.add('btn-outline-primary', 'text-info');
            } else {
                btn.classList.add('btn-outline-primary');
            }
            
        } else {
            // Select
            selectedSlots.push(idDetail);
            btn.classList.remove('btn-outline-primary', 'text-info');
            btn.classList.add('btn-success', 'active', 'text-white');
        }
        updateTotal();
    }

    function updateTotal() {
        // Hapus input hidden lama
        form.querySelectorAll('input[name="slot_ids[]"]').forEach(el => el.remove());

        const opt = idLapangan.options[idLapangan.selectedIndex];
        const harga = parseFloat(opt.dataset.harga || 0);
        const total = selectedSlots.length * harga; // Asumsi 1 jam per slot

        displayTotal.innerText = 'Rp ' + total.toLocaleString('id-ID');

        // Tambahkan input hidden untuk form
        selectedSlots.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'slot_ids[]';
            input.value = id;
            form.appendChild(input);
        });
    }

    idLapangan.addEventListener('change', loadSlots);
    tanggal.addEventListener('change', loadSlots);

    // Load awal saat halaman dibuka
    loadSlots();

    // Validasi Submit
    form.addEventListener('submit', (e) => {
        if (selectedSlots.length === 0) {
            e.preventDefault();
            alert("Silakan pilih minimal 1 slot jam baru.");
        } else {
            if(!confirm("Yakin ingin mengubah jadwal? \n\nPERINGATAN: Jadwal lama akan dihapus dan diganti dengan yang baru.")) {
                e.preventDefault();
            }
        }
    });
});
</script>