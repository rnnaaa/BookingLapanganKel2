<?php
// Ambil base url jika belum didefinisikan (fallback)
if (!isset($base_url)) {
    $base_url = '/BookingLapanganKel2';
}
?>
    <footer class="bg-white border-t border-slate-100">
        <div class="max-w-7xl mx-auto px-4 py-10">
            <div class="grid md:grid-cols-3 gap-6">
                <div>
                    <div class="font-poppins font-semibold text-lg">SportField</div>
                    <div class="text-sm text-slate-500 mt-2">Booking lapangan cepat & aman untuk semua olahraga.</div>
                </div>
                <div>
                    <div class="font-semibold mb-2">Navigasi</div>
                    <ul class="text-sm text-slate-600 space-y-1">
                        <li><a href="<?= $base_url ?>/BookingPengguna/booking.php" class="hover:text-primary">Lapangan</a></li>
                        <li><a href="<?= $base_url ?>/index.php#penawaran" class="hover:text-primary">Penawaran</a></li>
                        <li><a href="<?= $base_url ?>/index.php#fasilitas" class="hover:text-primary">Fasilitas</a></li>
                        <li><a href="<?= $base_url ?>/index.php#harga" class="hover:text-primary">Paket</a></li>
                    </ul>
                </div>
                <div>
                    <div class="font-semibold mb-2">Kontak</div>
                    <div class="text-sm text-slate-600">admin@sportfield.id • +62 852-3406-3810</div>
                    <div class="mt-3 text-sm text-slate-500">Sebelah Neutron - Kampus, Jl. Kalimantan Gg. 14, Krajan Timur, 
                                                            Sumbersari, Kec. Sumbersari, Kabupaten Jember, Jawa Timur 68121</div>
                </div>
            </div>
            <div class="mt-8 text-center text-xs text-slate-500">© 2025 SportField — Semua hak dilindungi</div>
        </div>
    </footer>

    <div id="logoutModal" class="modal-backdrop hidden">
      <div class="modal-panel animate-pop" style="max-width: 400px; animation: pop 0.3s ease-out;">
          <div class="p-6 text-center">
              <i class="fa-solid fa-triangle-exclamation text-5xl text-red-500 mb-4"></i>
              <h3 class="font-poppins font-semibold text-lg text-slate-800 mb-2">Konfirmasi Keluar</h3>
              <p class="text-sm text-slate-500 mb-6">
                  Apakah Anda yakin ingin keluar dari akun Anda?
              </p>
              <div class="flex justify-center gap-3">
                  <button id="cancelLogoutBtn" type="button" class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors">
                      Batal
                  </button>
                  <button id="confirmLogoutBtn" type="button" class="px-6 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                      Ya, Keluar
                  </button>
              </div>
          </div>
      </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
    <script src="<?= $base_url ?>/assets/js/booking-script.js"></script>

    <script>
        // Initialize AOS
        AOS.init({ duration: 700, once: true, offset: 60 });

        // === SEMUA LOGIKA LOGOUT DI SINI SUDAH DIHAPUS ===
        // (Karena sudah ada di booking-script.js)

        // Logika Nav Link Active
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname;
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                // Ambil path dari link
                try {
                    const linkPath = new URL(link.href).pathname;
                    // Cek kesamaan nama file (misal: booking.php)
                    if (currentPage.endsWith(linkPath.split('/').pop())) {
                        link.classList.add('active');
                    }
                } catch(e) {}
            });
        });
    </script>
</body>
</html>