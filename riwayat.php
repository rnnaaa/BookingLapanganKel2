<?php
// 1. Memanggil header (sudah termasuk session_start())
require 'include_user/header.php';

// === INI PERBAIKANNYA ===
// header.php tidak memuat koneksi DB, jadi kita panggil manual
require 'config/database.php';
// === AKHIR PERBAIKAN ===

// 2. Keamanan & Ambil User ID
if (!isset($_SESSION['id_user']) || $_SESSION['id_user'] == 1) { // 1 adalah user demo
    // Jika belum login atau masih user demo, redirect ke login
    header('Location: ' . $base_url . '/auth/login.php');
    exit;
}
$user_id = $_SESSION['id_user'];

// 3. Kueri SQL Baru (sesuai struktur proses_pembayaran.php)
$bookings = [];
$error = '';
try {
    // Baris ini (sebelumnya line 17) tidak akan error lagi
    $stmt = $conn->prepare("
        SELECT 
            b.id_booking,
            b.tanggal,
            b.status AS status_admin, -- 'menunggu', 'disetujui', 'dibatalkan', 'selesai'
            b.payment_status,        -- 'menunggu_verifikasi', 'belum_lunas', 'lunas'
            b.total_amount,
            b.remaining_amount,
            b.alasan_penolakan,
            l.nama_lapangan,
            
            -- Ambil tipe pembayaran awal (DP atau Pelunasan)
            (SELECT p.tipe FROM pembayaran p 
             WHERE p.booking_id = b.id_booking 
             ORDER BY p.id_pembayaran ASC LIMIT 1) as tipe_pembayaran,
             
            -- Gabungkan semua jam jadi satu string
            (SELECT GROUP_CONCAT(CONCAT(SUBSTR(jw.jam_mulai, 1, 5), '-', SUBSTR(jw.jam_selesai, 1, 5)) SEPARATOR ', ') 
             FROM detail_booking db 
             JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu 
             WHERE db.id_booking = b.id_booking
             ORDER BY jw.jam_mulai) as jam_booking
             
        FROM booking b
        JOIN lapangan l ON b.id_lapangan = l.id_lapangan
        WHERE b.id_user = ?
        ORDER BY b.tanggal DESC, b.id_booking DESC
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    $stmt->close();
    
} catch (Exception $e) {
    $error = "Error mengambil data: " . $e->getMessage();
}
?>

<style>
    /* Style untuk Modal Detail (agar riwayat.js tetap berfungsi) */
    .modal {
        display: none; 
        position: fixed; 
        z-index: 1000; 
        left: 0;
        top: 0;
        width: 100%; 
        height: 100%; 
        overflow: auto; 
        background-color: rgba(0,0,0,0.6); 
        align-items: center;
        justify-content: center;
    }
    .modal-content {
        background-color: #fefefe;
        margin: auto;
        padding: 24px;
        border: 1px solid #888;
        width: 90%;
        max-width: 500px;
        border-radius: 0.75rem; /* rounded-xl */
        position: relative;
        animation: modalPopIn 0.3s ease-out;
    }
    @keyframes modalPopIn {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }
    .modal .close {
        color: #aaa;
        position: absolute;
        right: 15px;
        top: 10px;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    .modal .close:hover,
    .modal .close:focus {
        color: black;
        text-decoration: none;
    }
    .modal h2 {
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        font-size: 1.25rem;
        margin-top: 0;
        margin-bottom: 1rem;
    }
    .modal p {
        font-size: 0.875rem;
        color: #334155; /* slate-700 */
        margin-bottom: 0.5rem;
        line-height: 1.5;
    }
    .modal p strong {
        color: #1e293b; /* slate-800 */
    }
    .qrcode {
        margin-top: 1.5rem;
        text-align: center;
    }
    .qrcode img {
        margin: auto;
        border: 4px solid #fff;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
</style>

<main class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold font-poppins text-slate-800 mb-2">Riwayat Booking Anda</h1>
    <p class="text-lg text-slate-500 mb-8">Lihat status dan detail pemesanan lapangan Anda.</p>

    <div class="flex flex-col gap-5">
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded-lg">
                <strong>Error:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php elseif (empty($bookings)): ?>
            <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-5 rounded-lg text-center">
                <h3 class="font-semibold text-lg mb-2">Belum Ada Riwayat Booking</h3>
                <p class="text-sm">Anda belum pernah melakukan booking. Mulai booking lapangan pertama Anda!</p>
                <a href="<?= $base_url ?>/BookingPengguna/booking.php" 
                   class="inline-block bg-primary text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-primaryDark transition-all duration-300 mt-4">
                    Booking Sekarang
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
                <?php
                // --- Logika Penentuan Status ---
                $statusText = 'Selesai';
                $statusClass = 'bg-green-100 text-green-800'; // Selesai / Lunas
                $isDP = false;

                if ($booking['status_admin'] === 'dibatalkan' || $booking['status_admin'] === 'ditolak') {
                    $statusText = 'Dibatalkan / Ditolak';
                    $statusClass = 'bg-red-100 text-red-800';
                } elseif ($booking['payment_status'] === 'menunggu_verifikasi') {
                    $statusText = 'Menunggu Verifikasi';
                    $statusClass = 'bg-yellow-100 text-yellow-800';
                } elseif ($booking['payment_status'] === 'belum_lunas') {
                    $statusText = 'DP Diterima';
                    $statusClass = 'bg-blue-100 text-blue-800'; // DP
                    $isDP = true;
                } elseif ($booking['payment_status'] === 'lunas' && $booking['status_admin'] !== 'selesai') {
                    $statusText = 'Lunas (Disetujui)';
                    $statusClass = 'bg-green-100 text-green-800';
                }
                ?>
                
                <div class="bg-white rounded-xl shadow-soft p-5 transition-all duration-300 hover:shadow-lift">
                    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 pb-4 border-b border-slate-100">
                        <div>
                            <h3 class="font-poppins font-semibold text-xl text-slate-800">
                                <?= htmlspecialchars($booking['nama_lapangan']) ?>
                            </h3>
                            <p class="text-sm text-slate-500 font-medium">
                                ID Booking: <strong>#<?= htmlspecialchars($booking['id_booking']) ?></strong>
                            </p>
                        </div>
                        <div class="flex-shrink-0 flex gap-2">
                            <?php if ($isDP): ?>
                                <span class="text-xs font-bold py-1 px-3 rounded-full bg-blue-100 text-blue-800 uppercase">
                                    DP
                                </span>
                            <?php elseif ($booking['payment_status'] === 'lunas' || $booking['status_admin'] === 'selesai'): ?>
                                <span class="text-xs font-bold py-1 px-3 rounded-full bg-green-100 text-green-800 uppercase">
                                    LUNAS
                                </span>
                            <?php endif; ?>
                            <span class="text-xs font-bold py-1 px-3 rounded-full <?= $statusClass ?> uppercase">
                                <?= htmlspecialchars($statusText) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 py-4">
                        <div>
                            <label class="text-xs text-slate-500">Tanggal</label>
                            <p class="font-medium text-sm text-slate-700">
                                <?= date('d M Y', strtotime($booking['tanggal'])) ?>
                            </p>
                        </div>
                        <div>
                            <label class="text-xs text-slate-500">Jam Main</label>
                            <p class="font-medium text-sm text-slate-700">
                                <?= htmlspecialchars($booking['jam_booking'] ?? '-') ?>
                            </p>
                        </div>
                        <div>
                            <label class="text-xs text-slate-500">Total Biaya</label>
                            <p class="font-medium text-sm text-slate-700">
                                Rp <?= number_format($booking['total_amount'], 0, ',', '.') ?>
                            </p>
                        </div>
                        <div>
                            <label class="text-xs text-slate-500">Sisa Bayar</label>
                            <p class="font-medium text-sm <?= $booking['remaining_amount'] > 0 ? 'text-red-600' : 'text-slate-700' ?>">
                                Rp <?= number_format($booking['remaining_amount'], 0, ',', '.') ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex justify-end pt-4 border-t border-slate-100">
                        <button class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primaryDark transition-all duration-300" 
                                onclick="showDetail(
                                    '<?= $booking['id_booking'] ?>',
                                    '<?= htmlspecialchars($booking['nama_lapangan'], ENT_QUOTES) ?>',
                                    '<?= $booking['tanggal'] ?>',
                                    '<?= htmlspecialchars($booking['jam_booking'] ?? '-', ENT_QUOTES) ?>',
                                    '<?= $booking['total_amount'] ?>',
                                    '<?= htmlspecialchars($booking['tipe_pembayaran'] ?? 'Reguler', ENT_QUOTES) ?>',
                                    '', '', '', '<?= htmlspecialchars($statusText, ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($booking['alasan_penolakan'] ?? '-', ENT_QUOTES) ?>'
                                )">
                            Lihat Detail
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<div class="modal" id="detailModal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Detail Booking</h2>
        <div id="detailContent"></div>
        <div id="qrcode" class="qrcode"></div>
    </div>
</div>

<script src="<?= $base_url ?>/assets/js/qrcode.min.js"></script> <script>
    // Kode dari riwayat.js Anda (disederhanakan karena modal ubah jadwal dihapus)
    
    // Modal Detail
    function showDetail(id, lapangan, tanggal, jam, total, tipeUser, durasiMember, tanggalMulai, tanggalBerakhir, status, deskripsi) {
      const modal = document.getElementById("detailModal");
      const content = document.getElementById("detailContent");
      const qrContainer = document.getElementById("qrcode");

      qrContainer.innerHTML = "";

      // Format tanggal
      const date = new Date(tanggal);
      const formattedDate = date.toLocaleDateString("id-ID", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
      });

      let detailHTML = `
            <p><strong>ID Booking:</strong> #${id}</p>
            <p><strong>Lapangan:</strong> ${lapangan}</p>
            <p><strong>Tanggal Booking:</strong> ${formattedDate}</p>
            <p><strong>Jam:</strong> ${jam || "-"}</p>
            <p><strong>Total:</strong> Rp ${parseInt(total).toLocaleString("id-ID")}</p>
            <p><strong>Tipe Pembayaran:</strong> ${tipeUser.toUpperCase()}</p>
            <p><strong>Status:</strong> ${status}</p>
            <p><strong>Keterangan:</strong> ${deskripsi || "-"}</p>
        `;

      content.innerHTML = detailHTML;

      if (status.includes("Lunas") || status.includes("DP Diterima")) {
        new QRCode(qrContainer, {
          text: `VERIFIKASI_BOOKING_ID_${id}`, // Ganti dengan data QR yang valid
          width: 150,
          height: 150,
          colorDark: "#094ea8", // primaryDark
          colorLight: "#ffffff",
          correctLevel: QRCode.CorrectLevel.H,
        });
      }

      modal.style.display = "flex";
    }

    function closeModal() {
      document.getElementById("detailModal").style.display = "none";
    }

    // Close modal ketika klik di luar content
    window.onclick = function (event) {
      const detailModal = document.getElementById("detailModal");
      if (event.target === detailModal) {
        closeModal();
      }
    };

    // Handle escape key
    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        closeModal();
      }
    });
</script>

<?php 
// 5. Memanggil footer
require 'include_user/footer.php'; 
?>