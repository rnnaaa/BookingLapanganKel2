<?php
session_start();
require 'config/database.php'; // Path ke koneksi DB

// Keamanan: Pastikan pengguna sudah login
if (!isset($_SESSION['id_user'])) {
    header('Location: auth/login.php'); // Arahkan ke login jika belum
    exit;
}

// Definisikan $base_url agar path logo benar
$base_url = '/BookingLapanganKel2'; 

// 1. Ambil semua data pengguna dari DB
$user_id = $_SESSION['id_user'];
$stmt_user = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
$stmt_user->bind_param('i', $user_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();

if (!$user) {
    session_destroy();
    header('Location: auth/login.php');
    exit;
}

// 2. Ambil data booking pengguna
$bookings = [];
$stmt_booking = $conn->prepare("
    SELECT 
        b.id_booking, 
        b.tanggal, 
        b.status, 
        b.total_amount,
        l.nama_lapangan,
        (SELECT COUNT(*) FROM detail_booking db WHERE db.id_booking = b.id_booking) as total_jam,
        (SELECT MIN(jw.jam_mulai) FROM detail_booking db JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu WHERE db.id_booking = b.id_booking) as jam_mulai,
        (SELECT MAX(jw.jam_selesai) FROM detail_booking db JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu WHERE db.id_booking = b.id_booking) as jam_selesai
    FROM booking b
    JOIN lapangan l ON b.id_lapangan = l.id_lapangan
    WHERE b.id_user = ?
    ORDER BY b.tanggal DESC
");
$stmt_booking->bind_param('i', $user_id);
$stmt_booking->execute();
$result_booking = $stmt_booking->get_result();
while ($row = $result_booking->fetch_assoc()) {
    $bookings[] = $row;
}
$stmt_user->close();
$stmt_booking->close();

// 3. Siapkan variabel untuk ditampilkan di HTML
$nama_display = htmlspecialchars($user['nama']);
$nama_depan = explode(' ', $nama_display)[0];
$email_display = htmlspecialchars($user['email']);
$no_hp_display = htmlspecialchars($user['no_hp'] ?? '');
$pekerjaan_display = htmlspecialchars($user['pekerjaan'] ?? '');
$pekerjaan_lain_display = htmlspecialchars($user['pekerjaan_lain'] ?? '');

// Tentukan path foto profil
$foto_profil_path = 'assets/images/default-avatar.png'; // Gambar default
if (!empty($user['foto_profil'])) {
    $foto_profil_path = 'uploads/profiles/' . htmlspecialchars($user['foto_profil']);
}

// Daftar pekerjaan untuk dropdown
$daftar_pekerjaan = [
    'Pelajar', 'Mahasiswa', 'Wirausaha', 'Pegawai Swasta', 'PNS', 'Freelancer'
];

?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Dashboard — <?= $nama_depan ?> | SportField</title>
    
    <link rel="stylesheet" href="assets/css/dashboard.css" /> 
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .nav .logo {
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .nav .logo .logo-mark {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: white;
            padding: 4px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .nav .logo:hover .logo-mark {
            transform: scale(1.1) rotate(5deg);
        }
        .nav .logo .logo-mark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 8px;
        }
        .nav .logo .logo-text {
            display: block;
        }
        .nav .logo .title {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.1rem;
            color: white;
            line-height: 1.2;
        }
        .nav .logo .subtitle {
            font-family: 'Inter', sans-serif;
            font-size: 0.75rem;
            color: #e0e0e0;
            opacity: 0.9;
        }
        /* Sembunyikan logo text di layar kecil agar tidak menabrak */
        @media (max-width: 600px) {
            .nav .logo .logo-text {
                display: none;
            }
        }
    </style>
  </head>
  
  <body> 
    
    <nav class="nav"> 
        
        <div class="nav-left">
            <a href="<?= $base_url ?>/index.php" class="logo">
                <div class="logo-mark">
                    <img src="assets/images/LogoRush.png" alt="Logo">
                </div>
                <div class="logo-text">
                    <div class="title">Rush Badminton Academy</div>
                    <div class="subtitle">Booking Lapangan Online</div>
                </div>
            </a>
            </div>

        <div class="nav-right">
            <div class="today" id="todayDate"></div>
            <div class="profile" id="profileToggle" tabindex="0" aria-haspopup="true">
                <img id="profileAvatar" src="<?= $foto_profil_path ?>" alt="avatar" />
            </div>
            <div class="profile-dropdown" id="profileDropdown" aria-hidden="true">
                <button id="btnEditProfile">Edit Profil</button>
                <button id="btnLogout" class="danger">Keluar</button>
            </div>
        </div>

    </nav>
    <main class="wrap">
      <?php
      if (isset($_SESSION['booking_success'])) {
          echo '<div class="card fade-in" style="background-color: #f0fdf4; border: 1px solid #a7f3d0; color: #15803d; margin-bottom: var(--gap);">';
          echo '<h4 style="margin-top:0; margin-bottom: 5px; font-size: 16px;">Booking Berhasil!</h4>';
          echo '<p style="margin:0; font-size: 14px;">' . htmlspecialchars($_SESSION['booking_success']) . '</p>';
          echo '</div>';
          unset($_SESSION['booking_success']);
      }
      
      if (isset($_SESSION['booking_error'])) {
          echo '<div class="card fade-in" style="background-color: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; margin-bottom: var(--gap);">';
          echo '<h4 style="margin-top:0; margin-bottom: 5px; font-size: 16px;">Booking Gagal</h4>';
          echo '<p style="margin:0; font-size: 14px;">' . htmlspecialchars($_SESSION['booking_error']) . '</p>';
          echo '</div>';
          unset($_SESSION['booking_error']); 
      }
      ?>
      <section class="welcome card fade-in">
        <div class="welcome-left">
          <h1>Hai, <span id="userName"><?= $nama_depan ?></span> 👋</h1>
          <p class="muted">Cek ringkasan booking dan jadwal favoritmu di sini.</p>
        </div>
        <div class="welcome-right">
          <a id="btnQuickBook" href="<?= $base_url ?>/BookingPengguna/booking.php" class="primary small pulse">Booking Sekarang</a>
        </div>
      </section>

      <section class="stats-grid">
        <div class="stat card slide-up" style="animation-delay: 0.1s">
          <div class="stat-title">Total Booking</div>
          <div class="stat-value" id="statTotal">0</div>
          <div class="stat-note">Total semua booking kamu</div>
        </div>
        <div class="stat card slide-up" style="animation-delay: 0.2s">
          <div class="stat-title">Booking Aktif</div>
          <div class="stat-value" id="statActive">0</div>
          <div class="stat-note">Booking yang belum selesai</div>
        </div>
        <div class="stat card slide-up" style="animation-delay: 0.3s">
          <div class="stat-title">Total Jam Main</div>
          <div class="stat-value" id="statHours">0</div>
          <div class="stat-note">Jumlah jam yang pernah kamu main</div>
        </div>
        <div class="stat card slide-up" style="animation-delay: 0.4s">
          <div class="stat-title">Pembayaran Terakhir</div>
          <div class="stat-value" id="statLastPayment">—</div>
          <div class="stat-note">Nominal transaksi terakhir</div>
        </div>
      </section>

      <section class="middle-grid">
        <div class="card chart-card fade-in">
          <div class="section-head">
            <h3>Jam Favorit Kamu</h3>
            <p class="muted small">Frekuensi booking berdasarkan jam</p>
          </div>
          <div class="chart-container">
            <canvas id="hourChart"></canvas>
          </div>
        </div>

        <aside class="right-col">
          <div class="card next-booking slide-in-right" style="animation-delay: 0.1s">
            <div class="section-head">
              <h4>Jadwal Berikutnya</h4>
            </div>
            <div id="nextBookingBox" class="next-box muted">Tidak ada jadwal aktif</div>
          </div>
          <div class="card favorites-box slide-in-right" style="animation-delay: 0.2s">
            <div class="section-head">
              <h4>Lapangan Favorit</h4>
            </div>
            <div id="favFields" class="fav-list muted">Belum ada data favorit</div>
          </div>
        </aside>
      </section>
      
    </main>

    <div class="modal-bg" id="profileModal" aria-hidden="true">
      <div class="modal card scale-in">
        <button class="modal-close" id="closeProfileModal">&times;</button>
        <h3>Edit Profil</h3>
        <form id="profileForm">
          <div class="profile-header">
            <div class="profile-avatar">
              <img id="profileAvatarLarge" src="<?= $foto_profil_path ?>" alt="avatar" />
              <button type="button" class="avatar-edit">✏️</button>
            </div>
            <div class="profile-info">
              <div class="profile-name" id="profileNameDisplay"><?= $nama_display ?></div>
              <div class="profile-email" id="profileEmailDisplay"><?= $email_display ?></div>
            </div>
          </div>

          <div class="form-section">
            <label for="inputName">Nama Lengkap</label>
            <input id="inputName" type="text" value="<?= $nama_display ?>" required />

            <label for="inputEmail">Email</label>
            <input id="inputEmail" type="email" value="<?= $email_display ?>" required />

            <label for="inputPhone">No. HP</label>
            <input id="inputPhone" type="tel" value="<?= $no_hp_display ?>" required />

            <label for="inputJob">Pekerjaan</label>
            <div class="select-container">
              <select id="inputJob">
                <option value="">Pilih pekerjaan...</option>
                <?php
                $pekerjaanDitemukan = false;
                foreach ($daftar_pekerjaan as $pekerjaan) {
                    $selected = (strcasecmp($pekerjaan_display, $pekerjaan) == 0) ? 'selected' : '';
                    if ($selected) $pekerjaanDitemukan = true;
                    echo "<option value=\"$pekerjaan\" $selected>" . htmlspecialchars($pekerjaan) . "</option>";
                }
                $selectedLainnya = (!$pekerjaanDitemukan && !empty($pekerjaan_display)) || (strcasecmp($pekerjaan_display, 'Lainnya') == 0) ? 'selected' : '';
                ?>
                <option value="Lainnya" <?= $selectedLainnya ?>>Lainnya</option>
              </select>
            </div>
            <input id="inputJobCustom" type="text" placeholder="Tulis pekerjaanmu..." 
                   value="<?= $pekerjaan_lain_display ?>" 
                   style="<?= $selectedLainnya ? 'display: block;' : 'display: none;' ?>" />
          </div>

          <div class="modal-actions">
            <button type="button" id="saveProfile" class="primary">Simpan Perubahan</button>
            <button type="button" id="cancelProfile" class="btn-ghost">Batal</button>
          </div>
        </form>
      </div>
    </div>
    
    <footer style="background-color: white; border-top: 1px solid #e5e7eb; margin-top: 4rem; font-family: 'Inter', sans-serif;">
        <div style="max-width: 1160px; margin: 0 auto; padding: 2.5rem 1rem; text-align: center; font-size: 0.75rem; color: #64748b;">
            © 2025 SportField — Semua hak dilindungi
        </div>
    </footer>
    <script>
        // Mengirim data PHP ke window agar bisa dibaca oleh dashboard.js
        window.INJECTED_USER_DATA = <?php echo json_encode($user); ?>;
        window.INJECTED_BOOKING_DATA = <?php echo json_encode($bookings); ?>;
    </script>
    
    <script src="assets/js/dashboard.js"></script>

  </body>
</html>