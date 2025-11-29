<?php
// Ambil base url jika belum didefinisikan (fallback)
if (!isset($base_url)) {
    $base_url = '/BookingLapanganKel2';
}
?>
    <div class="w-full overflow-hidden leading-[0] transform rotate-180 mt-20">
        <svg class="relative block w-[calc(100%+1.3px)] h-[50px] text-slate-900" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="fill-current"></path>
        </svg>
    </div>

    <footer class="bg-slate-900 text-white pt-10 pb-8 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4">
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">
                
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <img src="<?= $base_url ?>/assets/images/LogoRush1(white).png" alt="Logo" class="w-10 h-10 object-contain brightness-0 invert">
                        <span class="font-poppins font-bold text-2xl tracking-tight">Rush<span class="text-primary">Badminton</span></span>
                    </div>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Platform booking lapangan badminton modern. Cepat, mudah, dan terpercaya untuk mendukung gaya hidup sehat Anda.
                    </p>
                    <div class="flex gap-3 pt-2">
                        <a href="#" class="w-9 h-9 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 hover:bg-primary hover:text-white transition-all duration-300">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                        <a href="#" class="w-9 h-9 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 hover:bg-primary hover:text-white transition-all duration-300">
                            <i class="fa-brands fa-whatsapp"></i>
                        </a>
                        <a href="#" class="w-9 h-9 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 hover:bg-primary hover:text-white transition-all duration-300">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>
                    </div>
                </div>

                <div>
                    <h4 class="font-poppins font-semibold text-lg mb-6 relative inline-block">
                        Akses Cepat
                        <span class="absolute bottom-0 left-0 w-1/2 h-1 bg-primary rounded-full"></span>
                    </h4>
                    <ul class="space-y-3 text-sm text-slate-400">
                        <li><a href="<?= $base_url ?>/index.php" class="hover:text-white hover:translate-x-1 transition-all flex items-center gap-2"><i class="fa-solid fa-chevron-right text-xs text-primary"></i> Beranda</a></li>
                        <li><a href="<?= $base_url ?>/BookingPengguna/booking.php" class="hover:text-white hover:translate-x-1 transition-all flex items-center gap-2"><i class="fa-solid fa-chevron-right text-xs text-primary"></i> Sewa Lapangan</a></li>
                        <li><a href="<?= $base_url ?>/member/member.php" class="hover:text-white hover:translate-x-1 transition-all flex items-center gap-2"><i class="fa-solid fa-chevron-right text-xs text-primary"></i> Daftar Member</a></li>
                        <li><a href="<?= $base_url ?>/riwayat/riwayat.php" class="hover:text-white hover:translate-x-1 transition-all flex items-center gap-2"><i class="fa-solid fa-chevron-right text-xs text-primary"></i> Cek Pesanan</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-poppins font-semibold text-lg mb-6 relative inline-block">
                        Hubungi Kami
                        <span class="absolute bottom-0 left-0 w-1/2 h-1 bg-primary rounded-full"></span>
                    </h4>
                    <ul class="space-y-4 text-sm text-slate-400">
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-location-dot text-primary mt-1"></i>
                            <span>Jl. Kalimantan Gg. 14, Krajan Timur, Sumbersari, Jember, Jawa Timur 68121</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fa-solid fa-phone text-primary"></i>
                            <span>+62 852-3406-3810</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fa-solid fa-envelope text-primary"></i>
                            <span>admin@rushbadminton.id</span>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-poppins font-semibold text-lg mb-6 relative inline-block">
                        Jam Operasional
                        <span class="absolute bottom-0 left-0 w-1/2 h-1 bg-primary rounded-full"></span>
                    </h4>
                    <div class="bg-slate-800/50 p-4 rounded-xl border border-slate-700">
                        <ul class="space-y-3 text-sm text-slate-300">
                            <li class="flex justify-between border-b border-slate-700 pb-2">
                                <span>Senin - Jumat</span>
                                <span class="font-bold text-white">08:00 - 23:00</span>
                            </li>
                            <li class="flex justify-between border-b border-slate-700 pb-2">
                                <span>Sabtu - Minggu</span>
                                <span class="font-bold text-primary">07:00 - 24:00</span>
                            </li>
                        </ul>
                        <div class="mt-3 text-xs text-slate-500 text-center italic">
                            *Buka setiap hari termasuk tanggal merah
                        </div>
                    </div>
                </div>

            </div>

            <div class="pt-8 border-t border-slate-800 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-xs text-slate-500 text-center md:text-left">
                    &copy; <?= date('Y') ?> Rush Badminton Academy. Dibuat dengan <i class="fa-solid fa-heart text-red-500 mx-1"></i> oleh Kelompok 2.
                </p>
                <div class="flex gap-6 text-xs text-slate-500 font-medium">
                    <a href="#" class="hover:text-white transition-colors">Syarat & Ketentuan</a>
                    <a href="#" class="hover:text-white transition-colors">Kebijakan Privasi</a>
                    <a href="#" class="hover:text-white transition-colors">Bantuan</a>
                </div>
            </div>
        </div>
    </footer>

    <div id="logoutModal" class="modal-backdrop hidden">
      <div class="modal-panel animate-pop" style="max-width: 400px; animation: pop 0.3s ease-out;">
          <div class="p-6 text-center">
              <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-right-from-bracket text-3xl text-red-500 ml-1"></i>
              </div>
              <h3 class="font-poppins font-bold text-xl text-slate-800 mb-2">Konfirmasi Keluar</h3>
              <p class="text-sm text-slate-500 mb-6">
                  Apakah Anda yakin ingin mengakhiri sesi ini? Anda harus login ulang untuk memesan lapangan.
              </p>
              <div class="flex justify-center gap-3">
                  <button id="cancelLogoutBtn" type="button" class="flex-1 px-6 py-2.5 text-sm font-bold text-slate-600 bg-slate-50 border border-slate-200 rounded-xl hover:bg-slate-100 transition-colors">
                      Batal
                  </button>
                  <button id="confirmLogoutBtn" type="button" class="flex-1 px-6 py-2.5 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-lg shadow-red-200 transition-colors">
                      Ya, Keluar
                  </button>
              </div>
          </div>
      </div>
    </div>

    <button id="scrollToTop" class="fixed bottom-6 right-6 bg-primary text-white w-12 h-12 rounded-full shadow-lg shadow-primary/40 flex items-center justify-center translate-y-20 opacity-0 transition-all duration-300 z-40 hover:bg-primaryDark hover:-translate-y-1">
        <i class="fa-solid fa-arrow-up"></i>
    </button>

    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
    <script src="<?= $base_url ?>/assets/js/booking-script.js"></script>

    <script>
        // Initialize AOS
        AOS.init({ duration: 700, once: true, offset: 60 });

        // Nav Link Active Logic
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname;
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                try {
                    const linkPath = new URL(link.href).pathname;
                    if (currentPage.endsWith(linkPath.split('/').pop())) {
                        link.classList.add('active');
                    }
                } catch(e) {}
            });

            // Scroll to Top Logic
            const scrollBtn = document.getElementById('scrollToTop');
            window.addEventListener('scroll', () => {
                if (window.scrollY > 300) {
                    scrollBtn.classList.remove('translate-y-20', 'opacity-0');
                } else {
                    scrollBtn.classList.add('translate-y-20', 'opacity-0');
                }
            });
            scrollBtn.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    </script>
</body>
</html>