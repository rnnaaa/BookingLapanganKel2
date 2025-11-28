<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require 'config/database.php';

// Cek Login
if (!isset($_SESSION['id_user'])) {
    header('Location: auth/login.php');
    exit;
}

$base_url = '/BookingLapanganKel2';
$user_id = $_SESSION['id_user'];

// 1. Ambil Data User
$stmt_user = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
$stmt_user->bind_param('i', $user_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();

if (!$user) {
    session_destroy();
    header('Location: auth/login.php');
    exit;
}

// 2. Ambil Data Booking (Untuk Statistik & Grafik)
$bookings = [];
$stmt_booking = $conn->prepare("
    SELECT 
        b.id_booking, b.tanggal, b.status, b.total_amount, l.nama_lapangan,
        (SELECT MIN(jw.jam_mulai) FROM detail_booking db JOIN jadwal_waktu jw ON db.id_jadwal_waktu = jw.id_jadwal_waktu WHERE db.id_booking = b.id_booking) as jam_mulai
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
$stmt_booking->close();

// 3. Cek Status Member Aktif (FITUR BARU)
$is_member = false;
$member_data = null;
$stmt_member = $conn->prepare("
    SELECT m.*, l.nama_lapangan 
    FROM member m 
    LEFT JOIN lapangan l ON m.id_lapangan = l.id_lapangan
    WHERE m.id_user = ? AND m.status = 'aktif' AND m.tanggal_berakhir >= CURDATE() 
    ORDER BY m.id_member DESC LIMIT 1
");
$stmt_member->bind_param('i', $user_id);
$stmt_member->execute();
$res_member = $stmt_member->get_result();
if ($res_member->num_rows > 0) {
    $is_member = true;
    $member_data = $res_member->fetch_assoc();
}
$stmt_member->close();

// Variabel Tampilan
$nama_lengkap = htmlspecialchars($user['nama']);
$nama_depan = explode(' ', $nama_lengkap)[0];
$tampil_username = htmlspecialchars($user['username']);
$foto_profil = !empty($user['foto_profil']) ? 'uploads/profiles/' . $user['foto_profil'] : null;

// Styling Kartu Welcome berdasarkan Member
$welcome_bg = $is_member ? 'bg-gradient-to-r from-yellow-500 to-amber-600' : 'bg-gradient-to-r from-primary to-primaryDark';
$badge_member = $is_member ? '<span class="bg-white/20 text-white text-[10px] sm:text-xs px-2 py-1 rounded-md uppercase tracking-wider font-bold border border-white/30 ml-2">MEMBER VIP</span>' : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Dashboard — <?= $nama_depan ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#0056b3",
                        primaryDark: "#004494",
                        softGray: "#f6f8fb",
                        'primary-light': '#e7f0ff',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        poppins: ['Poppins', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
    <link rel="stylesheet" href="assets/css/dashboard.css" />
</head>
<body class="bg-softGray text-slate-800 font-sans antialiased pb-10"> 
    
    <nav class="sticky top-0 z-40 bg-white border-b border-slate-200 shadow-sm transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 sm:h-20 flex items-center justify-between">
            <a href="<?= $base_url ?>/index.php" class="flex items-center gap-3 group">
                <img src="assets/images/LogoRush.png" alt="Logo Rush" class="w-10 h-10 sm:w-12 sm:h-auto object-contain transition-transform group-hover:scale-105">
                <div class="flex flex-col">
                    <h1 class="font-poppins font-bold text-sm sm:text-lg leading-tight text-slate-900 tracking-tight">Rush Badminton</h1>
                    <span class="text-[10px] sm:text-xs text-slate-500 font-medium hidden sm:block">Booking Lapangan Online</span>
                </div>
            </a>

            <div class="relative">
                <button id="profileMenuBtn" class="flex items-center gap-2 sm:gap-3 hover:bg-slate-50 py-1.5 px-2 sm:py-2 sm:px-3 rounded-full transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <div class="text-right hidden md:block">
                        <div class="text-sm font-bold text-slate-800 leading-none mb-1 flex items-center justify-end">
                            <?= $tampil_username ?>
                            <?php if($is_member): ?><i class="fa-solid fa-crown text-yellow-500 ml-1 text-xs"></i><?php endif; ?>
                        </div>
                        <div class="text-xs text-slate-400">
                            <?php
                                $hari_indo = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
                                $bulan_indo = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                                echo $hari_indo[date('l')] . ', ' . date('d') . ' ' . $bulan_indo[date('n')] . ' ' . date('Y');
                            ?>
                        </div>
                    </div>
                    
                    <?php if($foto_profil): ?>
                        <img src="<?= $foto_profil ?>" alt="Profile" class="w-8 h-8 sm:w-10 sm:h-10 rounded-full object-cover border-2 border-slate-100 shadow-sm">
                    <?php else: ?>
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-[#009ef7] flex items-center justify-center text-white shadow-md">
                            <i class="fa-regular fa-user text-sm sm:text-lg"></i>
                        </div>
                    <?php endif; ?>
                    
                    <i class="fa-solid fa-chevron-down text-[10px] sm:text-xs text-slate-400 ml-1"></i>
                </button>

                <div id="profileDropdown" class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-slate-100 overflow-hidden hidden transform origin-top-right transition-all duration-200 scale-95 opacity-0 z-50">
                    <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Akun Saya</p>
                        <p class="text-sm font-semibold text-slate-800 truncate"><?= htmlspecialchars($user['email']) ?></p>
                        <?php if($is_member): ?>
                            <span class="inline-block mt-1 px-2 py-0.5 bg-yellow-100 text-yellow-700 text-[10px] font-bold rounded border border-yellow-200">MEMBER</span>
                        <?php endif; ?>
                    </div>
                    <div class="p-2">
                        <button id="btnEditProfile" class="w-full text-left px-4 py-2.5 text-sm text-slate-600 hover:bg-primary-light hover:text-primary rounded-lg transition-colors flex items-center gap-3">
                            <i class="fa-regular fa-user"></i> Edit Profil
                        </button>
                        <button id="btnLogout" class="w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors flex items-center gap-3">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-8">
        
        <div class="<?= $welcome_bg ?> rounded-2xl p-6 sm:p-8 shadow-lg shadow-slate-300/50 text-white mb-8 relative overflow-hidden animate-fade-in-up">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-16 -mt-16 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-black/10 rounded-full -ml-10 -mb-10 blur-2xl"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div class="max-w-lg">
                    <div class="flex items-center mb-2">
                        <h2 class="text-2xl sm:text-3xl font-poppins font-bold leading-tight">Selamat Datang, <?= $nama_depan ?>!</h2>
                        <?= $badge_member ?>
                    </div>
                    <?php if($is_member): ?>
                        <p class="text-white/90 text-sm sm:text-lg">Terima kasih telah menjadi member setia. Nikmati prioritas booking dan harga spesial!</p>
                    <?php else: ?>
                        <p class="text-white/80 text-sm sm:text-lg">Siap untuk bermain badminton hari ini? Booking sekarang!</p>
                    <?php endif; ?>
                </div>
                
                <div class="flex flex-wrap gap-3 w-full sm:w-auto">
                    <a href="BookingPengguna/booking.php" class="flex-1 sm:flex-none bg-white text-slate-800 hover:bg-slate-50 px-6 py-3 rounded-xl font-bold shadow-md transition-all transform hover:scale-105 flex items-center justify-center gap-2 text-sm sm:text-base">
                        <i class="fa-regular fa-calendar-plus"></i> Booking
                    </a>
                    
                    <a href="riwayat/riwayat.php" class="flex-1 sm:flex-none bg-white/20 hover:bg-white/30 text-white border border-white/30 px-6 py-3 rounded-xl font-bold backdrop-blur-sm transition-all flex items-center justify-center gap-2 text-sm sm:text-base shadow-sm">
                        <i class="fa-solid fa-clock-rotate-left"></i> Riwayat
                    </a>
                </div>
            </div>
        </div>

    <?php if($is_member): ?>
        <div class="mb-8 animate-fade-in-up" style="animation-delay: 0.1s;">
            <div class="bg-white rounded-2xl p-5 border border-yellow-200 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 relative overflow-hidden">
                <div class="absolute right-0 top-0 h-full w-2 bg-yellow-400"></div>
                <div>
                    <h4 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-1">Status Membership</h4>
                    <p class="font-bold text-lg text-slate-800 mb-1">
                        <?= htmlspecialchars($member_data['nama_lapangan']) ?> • <?= $member_data['durasi_bulan'] ?> Bulan
                    </p>
                    
                    <div class="text-xs text-slate-500 flex flex-col gap-1">
                        <p>
                            Mulai: <span class="font-semibold text-slate-700"><?= date('d F Y', strtotime($member_data['tanggal_mulai'])) ?></span>
                        </p>
                        <p>
                            Berlaku hingga: <span class="font-semibold text-slate-700"><?= date('d F Y', strtotime($member_data['tanggal_berakhir'])) ?></span>
                        </p>
                    </div>

                </div>
                <div class="bg-yellow-50 px-4 py-2 rounded-lg border border-yellow-100 text-yellow-700 text-sm font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-check-circle"></i> Aktif
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="relative overflow-hidden bg-white rounded-2xl shadow-md border border-slate-100 p-6 hover:shadow-xl hover:scale-105 transition-all duration-300 animate-fade-in-up group" style="animation-delay: 0.1s;">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-50/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-2xl shrink-0 shadow-md"><i class="fa-solid fa-clipboard-list"></i></div>
                    <div><p class="text-sm text-slate-500 font-semibold uppercase tracking-wider">Riwayat Order</p><h3 class="text-3xl font-extrabold text-slate-800 mt-1" id="statTotal">0</h3></div>
                </div>
            </div>
            <div class="relative overflow-hidden bg-white rounded-2xl shadow-md border border-slate-100 p-6 hover:shadow-xl hover:scale-105 transition-all duration-300 animate-fade-in-up group" style="animation-delay: 0.2s;">
                <div class="absolute inset-0 bg-gradient-to-br from-green-50/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-2xl shrink-0 shadow-md"><i class="fa-regular fa-calendar-check"></i></div>
                    <div><p class="text-sm text-slate-500 font-semibold uppercase tracking-wider">Jadwal Belum Main</p><h3 class="text-3xl font-extrabold text-slate-800 mt-1" id="statActive">0</h3></div>
                </div>
            </div>
            <div class="relative overflow-hidden bg-white rounded-2xl shadow-md border border-slate-100 p-6 hover:shadow-xl hover:scale-105 transition-all duration-300 animate-fade-in-up group" style="animation-delay: 0.3s;">
                <div class="absolute inset-0 bg-gradient-to-br from-purple-50/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-2xl shrink-0 shadow-md"><i class="fa-solid fa-stopwatch"></i></div>
                    <div><p class="text-sm text-slate-500 font-semibold uppercase tracking-wider">Total Durasi</p><h3 class="text-3xl font-extrabold text-slate-800 mt-1" id="statHours">0</h3></div>
                </div>
            </div>
            <div class="relative overflow-hidden bg-white rounded-2xl shadow-md border border-slate-100 p-6 hover:shadow-xl hover:scale-105 transition-all duration-300 animate-fade-in-up group" style="animation-delay: 0.4s;">
                <div class="absolute inset-0 bg-gradient-to-br from-orange-50/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-2xl shrink-0 shadow-md"><i class="fa-solid fa-money-bill-wave"></i></div>
                    <div><p class="text-sm text-slate-500 font-semibold uppercase tracking-wider">Total Pengeluaran</p><h3 class="text-2xl font-extrabold text-slate-800 mt-1 truncate" id="statTotalSpend">Rp 0</h3></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
            <div class="lg:col-span-2 bg-white p-4 sm:p-6 rounded-2xl shadow-sm border border-slate-100 animate-fade-in-up" style="animation-delay: 0.5s;">
                <div class="flex items-center justify-between mb-4 sm:mb-6">
                    <h3 class="font-poppins font-bold text-base sm:text-lg text-slate-800">Aktivitas Bermain</h3>
                </div>
                <div class="relative w-full h-60 sm:h-72"><canvas id="hourChart"></canvas></div>
            </div>
            
            <div class="flex flex-col gap-6 animate-fade-in-up" style="animation-delay: 0.6s;">
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 text-white p-5 sm:p-6 rounded-2xl shadow-lg relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -mr-10 -mt-10"></div>
                    <h4 class="font-bold text-white/60 text-xs uppercase tracking-wider mb-4">Jadwal Berikutnya</h4>
                    <div id="nextBookingBox"><p class="text-slate-400 text-sm italic">Belum ada jadwal aktif.</p></div>
                </div>
                
                <div class="bg-white p-5 sm:p-6 rounded-2xl shadow-sm border border-slate-100">
                    <h4 class="font-bold text-slate-800 mb-4 flex items-center gap-2"><i class="fa-solid fa-heart text-red-500"></i> Lapangan Favorit</h4>
                    <div id="favFields" class="space-y-3"><p class="text-slate-400 text-sm italic">Data belum cukup.</p></div>
                </div>
            </div>
        </div>
    </main>

    <footer class="py-6 sm:py-8 text-center text-xs sm:text-sm text-slate-500 px-4">
        &copy; 2025 Rush Badminton Academy. Dibuat dengan ❤️.
    </footer>

    <div id="modalOverlay" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm hidden transition-opacity duration-300 opacity-0 flex items-center justify-center p-4 sm:p-6">
        <div id="modalContent" class="bg-white w-full max-w-lg rounded-2xl shadow-2xl transform scale-95 transition-all duration-300 opacity-0 m-4 max-h-[90vh] flex flex-col">
            
            <div class="flex items-center justify-between p-5 sm:p-6 border-b border-slate-100">
                <h3 class="text-lg sm:text-xl font-bold font-poppins text-slate-800">Edit Profil</h3>
                <button id="closeModalBtn" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-500 transition-colors">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-5 sm:p-6 overflow-y-auto custom-scrollbar">
              <form id="editProfileForm" class="space-y-4" enctype="multipart/form-data"> 
                  <div class="flex flex-col items-center mb-6">
                      <label for="inputFoto" class="relative group cursor-pointer">
                          <?php if($foto_profil): ?>
                              <img src="<?= $foto_profil ?>" class="w-20 h-20 sm:w-24 sm:h-24 rounded-full object-cover border-4 border-slate-50 shadow-md transition-opacity group-hover:opacity-75" id="previewAvatar">
                          <?php else: ?>
                              <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-slate-200 flex items-center justify-center text-slate-400 border-4 border-slate-50 shadow-md transition-opacity group-hover:opacity-75" id="previewAvatarDiv">
                                  <img src="" class="w-20 h-20 sm:w-24 sm:h-24 rounded-full object-cover hidden" id="previewAvatarNew"> 
                                  <i class="fa-solid fa-user text-3xl" id="defaultIcon"></i>
                              </div>
                          <?php endif; ?>

                          <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                              <i class="fa-solid fa-camera text-slate-700 text-xl sm:text-2xl bg-white/50 p-2 rounded-full backdrop-blur-sm"></i>
                          </div>
                      </label>
                      
                      <input type="file" id="inputFoto" name="foto_profil" class="hidden" accept="image/png, image/jpeg, image/jpg">
                      <span class="text-xs text-slate-400 mt-2 text-center">Klik foto untuk mengubah (Max 2MB)</span>
                  </div>

                    <div class="grid grid-cols-1 gap-3 sm:gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nama Lengkap</label>
                            <input type="text" id="inputNama" name="nama" class="w-full px-3 py-2.5 sm:px-4 sm:py-3 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all text-sm font-medium text-slate-700" value="<?= htmlspecialchars($user['nama']) ?>" required>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Username</label>
                            <input type="text" id="inputUsername" name="username" class="w-full px-3 py-2.5 sm:px-4 sm:py-3 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all text-sm font-medium text-slate-700" value="<?= htmlspecialchars($user['username']) ?>" required>
                            <p id="usernameError" class="text-red-500 text-xs mt-1 font-medium hidden">Username sudah terpakai.</p>
                            <p id="usernameSuccess" class="text-green-500 text-xs mt-1 font-medium hidden">Username tersedia.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Email (Tidak dapat diubah)</label>
                            <input type="email" id="inputEmail" name="email" class="w-full px-3 py-2.5 sm:px-4 sm:py-3 bg-gray-100 border border-slate-200 rounded-lg text-sm font-medium text-slate-500 cursor-not-allowed" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">No. WhatsApp</label>
                            <input type="number" id="inputHP" name="no_hp" class="w-full px-3 py-2.5 sm:px-4 sm:py-3 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all text-sm font-medium text-slate-700" value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>" required>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Pekerjaan</label>
                            <select id="inputPekerjaan" name="pekerjaan" class="w-full px-3 py-2.5 sm:px-4 sm:py-3 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all text-sm font-medium text-slate-700">
                                <?php
                                $jobs = ['Pelajar', 'Mahasiswa', 'Wirausaha', 'Pegawai Swasta', 'PNS', 'Freelancer', 'Lainnya'];
                                $currentJob = $user['pekerjaan'] ?? '';
                                foreach($jobs as $j) {
                                    $sel = ($j == $currentJob) ? 'selected' : '';
                                    echo "<option value='$j' $sel>$j</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div id="customJobDiv" class="hidden">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Sebutkan Pekerjaan</label>
                            <input type="text" id="inputPekerjaanLain" name="pekerjaan_lain" class="w-full px-3 py-2.5 sm:px-4 sm:py-3 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all text-sm font-medium text-slate-700" value="<?= htmlspecialchars($user['pekerjaan_lain'] ?? '') ?>">
                        </div>
                    </div>
                </form>
            </div>

            <div class="p-5 sm:p-6 border-t border-slate-100 bg-slate-50/50 rounded-b-2xl flex flex-col sm:flex-row justify-end gap-3">
                <button type="button" id="btnCancelEdit" class="w-full sm:w-auto px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:bg-white hover:shadow-sm border border-transparent hover:border-slate-200 transition-all order-2 sm:order-1">Batal</button>
                <button type="button" id="btnSaveProfile" class="w-full sm:w-auto px-6 py-2.5 rounded-xl text-sm font-bold text-white bg-primary hover:bg-primaryDark shadow-lg shadow-primary/30 transform hover:-translate-y-0.5 transition-all order-1 sm:order-2">Simpan Perubahan</button>
            </div>
        </div>
    </div>

    <script>
        window.USER_DATA = <?= json_encode($user) ?>;
        window.BOOKING_DATA = <?= json_encode($bookings) ?>;
    </script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>