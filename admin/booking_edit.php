<?php
// booking_edit.php - FULL FEATURE (Reschedule & Status)
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_GET['id'])) {
    header("Location: booking.php");
    exit;
}

$id_booking = intval($_GET['id']);

// PROSES SIMPAN PERUBAHAN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // UPDATE STATUS & PEMBAYARAN
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

    // RESCHEDULE / GANTI LAPANGAN
    if (isset($_POST['action']) && $_POST['action'] == 'reschedule') {
        $new_id_lapangan = intval($_POST['id_lapangan']);
        $new_tanggal     = $_POST['tanggal'];
        $new_slot_ids    = $_POST['slot_ids'] ?? [];

        if (!$new_id_lapangan || !$new_tanggal || empty($new_slot_ids)) {
            $_SESSION['toast_error'] = "⚠️ Data reschedule tidak lengkap.";
            header("Location: booking_edit.php?id=$id_booking");
            exit;
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("SELECT harga_per_jam FROM lapangan WHERE id_lapangan = ?");
            $stmt->bind_param("i", $new_id_lapangan);
            $stmt->execute();
            $lap = $stmt->get_result()->fetch_assoc();
            $harga_per_jam = floatval($lap['harga_per_jam']);
            $stmt->close();

            $stmt = $conn->prepare("UPDATE jadwal_detail SET status='tersedia', id_booking=NULL WHERE id_booking = ?");
            $stmt->bind_param("i", $id_booking);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM detail_booking WHERE id_booking = ?");
            $stmt->bind_param("i", $id_booking);
            $stmt->execute();
            $stmt->close();

            $placeholders = implode(',', array_fill(0, count($new_slot_ids), '?'));
            $types = str_repeat('i', count($new_slot_ids));
            
            $sql = "SELECT id_detail, status, id_jadwal_waktu FROM jadwal_detail WHERE id_detail IN ($placeholders) FOR UPDATE";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$new_slot_ids);
            $stmt->execute();
            $res = $stmt->get_result();
            $rows = $res->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            if (count($rows) !== count($new_slot_ids)) {
                throw new Exception("Data slot baru tidak valid.");
            }

            $total_baru = 0;
            $durasi_per_slot = 1;
            
            $stmt_upd_slot = $conn->prepare("UPDATE jadwal_detail SET status='dibooking', id_booking = ? WHERE id_detail = ?");
            $stmt_ins_det  = $conn->prepare("INSERT INTO detail_booking (id_booking, id_jadwal_waktu, harga) VALUES (?, ?, ?)");

            foreach ($rows as $r) {
                if ($r['status'] !== 'tersedia') {
                    throw new Exception("Slot sudah diambil orang lain.");
                }

                $harga_slot = $harga_per_jam * $durasi_per_slot;
                $total_baru += $harga_slot;

                $stmt_upd_slot->bind_param("ii", $id_booking, $r['id_detail']);
                $stmt_upd_slot->execute();

                $stmt_ins_det->bind_param("iid", $id_booking, $r['id_jadwal_waktu'], $harga_slot);
                $stmt_ins_det->execute();
            }
            $stmt_upd_slot->close();
            $stmt_ins_det->close();

            $stmt = $conn->prepare("SELECT total_amount, remaining_amount FROM booking WHERE id_booking = ?");
            $stmt->bind_param("i", $id_booking);
            $stmt->execute();
            $curr = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $uang_masuk = floatval($curr['total_amount']) - floatval($curr['remaining_amount']);
            $remaining_baru = $total_baru - $uang_masuk;

            $stmt = $conn->prepare("UPDATE booking SET id_lapangan = ?, tanggal = ?, total_amount = ?, remaining_amount = ?, updated_at = NOW() WHERE id_booking = ?");
            $stmt->bind_param("isddi", $new_id_lapangan, $new_tanggal, $total_baru, $remaining_baru, $id_booking);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $_SESSION['toast_success'] = "✅ Jadwal berhasil diubah!";
            header("Location: booking_detail.php?id=" . $id_booking);
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['toast_error'] = "Gagal: " . $e->getMessage();
            header("Location: booking_edit.php?id=$id_booking");
            exit;
        }
    }
}

// AMBIL DATA SAAT INI
$stmt = $conn->prepare("SELECT b.*, l.nama_lapangan FROM booking b JOIN lapangan l ON b.id_lapangan = l.id_lapangan WHERE b.id_booking = ?");
$stmt->bind_param("i", $id_booking);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

$qLap = $conn->query("SELECT id_lapangan, nama_lapangan, harga_per_jam FROM lapangan WHERE status='aktif'");

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<style>
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.content-wrapper {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    padding: 2rem 1rem;
}

.edit-card {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border: none;
}

.card-header-gradient {
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    padding: 1.5rem;
}

.slot-selector {
    background: #f8f9fc;
    border: 2px solid #e3e6f0;
    border-radius: 10px;
    padding: 1.5rem;
    min-height: 120px;
}

.btn-slot {
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.2s ease;
    min-width: 120px;
}

.btn-slot:hover:not(:disabled) {
    transform: scale(1.05);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.btn-slot.active {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
    border-color: #11998e !important;
}

.total-preview {
    background: linear-gradient(90deg, #667eea, #764ba2);
    color: white;
    padding: 1.5rem;
    border-radius: 10px;
    font-size: 1.3rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.info-alert {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
    border-radius: 10px;
    padding: 1.25rem;
    border: none;
}

.info-alert i {
    font-size: 1.5rem;
}

.form-control:focus,
.form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.status-form-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
}

@media (max-width: 768px) {
    .content-wrapper {
        padding: 1rem 0.5rem;
    }
    
    .btn-slot {
        min-width: 100px;
    }
}
</style>

<div class="content-wrapper">
    <section class="content-header mb-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="mb-3 mb-md-0">
                    <h1 style="font-weight: 700; color: #2d3748;">
                        <i class="fas fa-edit me-2" style="color: #667eea;"></i> 
                        Edit Booking #<?= $id_booking ?>
                    </h1>
                </div>
                <a href="booking_detail.php?id=<?= $id_booking ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <?php if (!empty($_SESSION['toast_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= $_SESSION['toast_error']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['toast_error']); ?>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Status Update Card -->
                <div class="col-md-4">
                    <div class="card edit-card">
                        <div class="card-header card-header-gradient text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-tasks me-2"></i> Update Status
                            </h5>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_status">
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-flag me-2 text-primary"></i>
                                        Status Booking
                                    </label>
                                    <select name="status" class="form-select">
                                        <?php 
                                        $statuses = ['menunggu','disetujui','selesai','ditolak','dibatalkan'];
                                        foreach($statuses as $s) {
                                            $sel = ($data['status'] == $s) ? 'selected' : '';
                                            echo "<option value='$s' $sel>".ucfirst($s)."</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-money-bill-wave me-2 text-success"></i>
                                        Status Pembayaran
                                    </label>
                                    <select name="payment_status" class="form-select">
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
                            <div class="card-footer bg-light p-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save me-2"></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Reschedule Card -->
                <div class="col-md-8">
                    <div class="card edit-card">
                        <div class="card-header card-header-gradient text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-alt me-2"></i> Reschedule / Ganti Jadwal
                            </h5>
                        </div>
                        <form method="POST" id="formReschedule">
                            <input type="hidden" name="action" value="reschedule">
                            <div class="card-body p-4">
                                <div class="info-alert mb-4">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-info-circle me-3"></i>
                                        <div>
                                            <strong class="d-block mb-1">Informasi Penting</strong>
                                            <small>Slot jadwal lama akan otomatis dilepas dan tersedia kembali. Total harga akan dihitung ulang berdasarkan slot baru.</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">
                                            <i class="fas fa-futbol me-2"></i> Pilih Lapangan
                                        </label>
                                        <select name="id_lapangan" id="id_lapangan" class="form-select" required>
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
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">
                                            <i class="far fa-calendar-alt me-2"></i> Pilih Tanggal
                                        </label>
                                        <input type="date" name="tanggal" id="tanggal" class="form-control" 
                                               value="<?= $data['tanggal'] ?>" min="<?= date('Y-m-d') ?>" required>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold mb-3">
                                        <i class="far fa-clock me-2"></i> Pilih Slot Jam Baru
                                    </label>
                                    <div id="slotLoading" style="display:none;" class="text-center py-3">
                                        <div class="spinner-border text-primary"></div>
                                        <p class="mt-2 text-muted">Memuat jadwal...</p>
                                    </div>
                                    <div id="slotList" class="slot-selector">
                                        <small class="text-muted">
                                            <i class="fas fa-hand-pointer me-2"></i>
                                            Silakan pilih lapangan dan tanggal untuk melihat slot tersedia
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="total-preview text-center">
                                    <small class="d-block mb-2 opacity-90">Estimasi Total Baru</small>
                                    <div id="displayTotal" class="fw-bold">-</div>
                                </div>

                            </div>
                            <div class="card-footer bg-light p-3 d-flex justify-content-between">
                                <a href="booking_detail.php?id=<?= $id_booking ?>" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i> Batal
                                </a>
                                <button type="submit" class="btn btn-success" id="btnSaveReschedule">
                                    <i class="fas fa-save me-2"></i> Simpan Perubahan Jadwal
                                </button>
                            </div>
                        </form>
                    </div>
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
        selectedSlots = [];
        updateTotal();

        fetch(`booking_get_slot.php?id_lapangan=${idL}&tanggal=${tgl}&exclude_booking=${idBooking}`)
            .then(res => res.json())
            .then(data => {
                slotLoading.style.display = 'none';
                if(data.status !== 'success') {
                    slotList.innerHTML = `<div class="alert alert-warning py-2">${data.message}</div>`;
                    return;
                }
                
                if(data.slots.length === 0) {
                    slotList.innerHTML = `<div class="text-center text-muted py-3"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>Tidak ada slot tersedia</div>`;
                    return;
                }

                const slotsWrapper = document.createElement('div');
                slotsWrapper.className = 'd-flex flex-wrap gap-2';
                
                data.slots.forEach(s => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'btn btn-outline-primary btn-slot';
                    
                    if (s.status === 'tersedia') {
                        btn.innerHTML = `<i class="far fa-clock me-1"></i> ${s.jam_mulai} - ${s.jam_selesai}`;
                        
                        if(s.is_mine) {
                             btn.classList.add('border-info', 'text-info');
                        }
                        
                        btn.onclick = () => toggleSlot(btn, s.id_detail);
                        
                    } else if (s.status === 'lewat') {
                        btn.disabled = true;
                        btn.className = 'btn btn-secondary btn-slot opacity-50';
                        btn.innerHTML = `<i class="fas fa-history me-1"></i> ${s.jam_mulai}`;
                    } else {
                        btn.disabled = true;
                        btn.className = 'btn btn-danger btn-slot';
                        btn.innerHTML = `<i class="fas fa-lock me-1"></i> ${s.jam_mulai}`;
                    }
                    
                    slotsWrapper.appendChild(btn);
                });
                
                slotList.innerHTML = '';
                slotList.appendChild(slotsWrapper);
            })
            .catch(err => {
                slotLoading.style.display = 'none';
                slotList.innerHTML = `<div class="alert alert-danger py-2">Error koneksi</div>`;
            });
    }

    function toggleSlot(btn, idDetail) {
        const idx = selectedSlots.indexOf(idDetail);
        if (idx > -1) {
            selectedSlots.splice(idx, 1);
            btn.classList.remove('btn-success', 'active', 'text-white');
            
            if (btn.classList.contains('border-info')) {
                btn.classList.add('btn-outline-primary', 'text-info');
            } else {
                btn.classList.add('btn-outline-primary');
            }
            
        } else {
            selectedSlots.push(idDetail);
            btn.classList.remove('btn-outline-primary', 'text-info');
            btn.classList.add('btn-success', 'active', 'text-white');
        }
        updateTotal();
    }

    function updateTotal() {
        form.querySelectorAll('input[name="slot_ids[]"]').forEach(el => el.remove());

        const opt = idLapangan.options[idLapangan.selectedIndex];
        const harga = parseFloat(opt.dataset.harga || 0);
        const total = selectedSlots.length * harga;

        displayTotal.innerText = 'Rp ' + total.toLocaleString('id-ID');

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

    loadSlots();

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