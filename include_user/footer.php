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
                        <li><a href="#lapangan" class="hover:text-primary">Lapangan</a></li>
                        <li><a href="#penawaran" class="hover:text-primary">Penawaran</a></li>
                        <li><a href="#fasilitas" class="hover:text-primary">Fasilitas</a></li>
                        <li><a href="#harga" class="hover:text-primary">Paket</a></li>
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

    <!-- Modal Sections -->
    <div class="modal-backdrop hidden" id="loginModal">
        <div class="modal-panel">
            <div class="modal-header">
                <h4>Masuk ke SportField</h4>
                <button class="modal-close" data-modal-close="loginModal">&times;</button>
            </div>
            <form onsubmit="loginDemo(event)" class="p-4">
                <label class="text-xs text-slate-600">Email</label>
                <input id="loginEmail" type="email" class="modal-input" required />
                <label class="text-xs text-slate-600 mt-2">Password</label>
                <input id="loginPassword" type="password" class="modal-input" required />
                <div class="flex gap-2 mt-4">
                    <button type="button" class="btn-outline" data-modal-close="loginModal">Batal</button>
                    <button type="submit" class="btn-primary">Masuk</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-backdrop hidden" id="registerModal">
        <div class="modal-panel">
            <div class="modal-header">
                <h4>Buat Akun</h4>
                <button class="modal-close" data-modal-close="registerModal">&times;</button>
            </div>
            <form onsubmit="registerDemo(event)" class="p-4">
                <label class="text-xs text-slate-600">Nama</label>
                <input id="regName" class="modal-input" required />
                <label class="text-xs text-slate-600 mt-2">Email</label>
                <input id="regEmail" type="email" class="modal-input" required />
                <div class="flex gap-2 mt-4">
                    <button type="button" class="btn-outline" data-modal-close="registerModal">Batal</button>
                    <button type="submit" class="btn-primary">Buat Akun</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-backdrop hidden" id="memberModal">
        <div class="modal-panel">
            <div class="modal-header">
                <h4>Syarat Membership</h4>
                <button class="modal-close" data-modal-close="memberModal">&times;</button>
            </div>
            <div class="p-4 text-sm">
                <ul class="list-disc pl-4">
                    <li>Rp 150.000 / bulan</li>
                    <li>Berlaku akhir pekan</li>
                    <li>Prioritas jadwal & diskon</li>
                </ul>
                <div class="mt-4 flex gap-2">
                    <button class="btn-outline" data-modal-close="memberModal">Tutup</button>
                    <button class="btn-primary" onclick="handleBookingClick('Membership Weekend',150000)">Daftar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-backdrop hidden" id="bookingModal">
        <div class="modal-panel">
            <div class="modal-header">
                <h4 id="bookingTitle">Booking</h4>
                <button class="modal-close" data-modal-close="bookingModal">&times;</button>
            </div>
            <form id="bookingForm" class="p-4" onsubmit="submitBooking(event)">
                <div class="text-sm text-slate-600 mb-2" id="bookingPrice">Harga: —</div>
                <label class="text-xs text-slate-600">Nama Pemesan</label>
                <input id="custName" class="modal-input" required />
                <label class="text-xs text-slate-600 mt-2">Tanggal & Jam</label>
                <input id="slot" type="datetime-local" class="modal-input" required />
                <label class="text-xs text-slate-600 mt-2">Catatan (opsional)</label>
                <textarea id="note" class="modal-input" rows="2"></textarea>
                <div class="flex gap-2 mt-4">
                    <button type="button" class="btn-outline" data-modal-close="bookingModal">Batal</button>
                    <button type="submit" class="btn-primary">Lanjut ke Checkout</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
    <script src="js/app.js"></script>
    
    <script src="assets/js/booking-script.js"></script>

    <script>
        // Initialize AOS (Animate On Scroll)
        AOS.init({
            duration: 700,
            once: true,
            offset: 60,
        });

        // NAVBAR UNDERLINE FUNCTION
        function updateNavIndicator() {
            const currentPage = window.location.pathname;
            const navLinks = document.querySelectorAll('.nav-link');
            const navLine = document.getElementById('navLine');
            
            // Remove all active classes first
            navLinks.forEach(link => link.classList.remove('active'));
            
            // Determine which link should be active
            let activeLink = null;
            
            if (currentPage.includes('index.php') || currentPage === '/' || currentPage === '') {
                activeLink = document.querySelector('a[href="index.php"]');
            } else if (currentPage.includes('booking.php')) {
                activeLink = document.querySelector('a[href*="booking.php"]');
            } else if (currentPage.includes('kontak.php')) {
                activeLink = document.querySelector('a[href="kontak.php"]');
            } else if (currentPage.includes('member.php')) {
                activeLink = document.querySelector('a[href="member.php"]');
            } else if (currentPage.includes('riwayat.php')) {
                activeLink = document.querySelector('a[href="riwayat.php"]');
            }
            
            // Add active class and update underline
            if (activeLink) {
                activeLink.classList.add('active');
                
                if (navLine) {
                    const parentLi = activeLink.closest('li');
                    if (parentLi) {
                        const navContainer = document.getElementById('topNav');
                        const containerRect = navContainer.getBoundingClientRect();
                        const liRect = parentLi.getBoundingClientRect();
                        
                        const left = liRect.left - containerRect.left;
                        const width = liRect.width;
                        
                        navLine.style.left = `${left}px`;
                        navLine.style.width = `${width}px`;
                    }
                }
            }
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            updateNavIndicator();
            window.addEventListener('resize', updateNavIndicator);
            
            // Scroll to location function
            document.querySelector(".scroll-to-location")?.addEventListener("click", function () {
                const target = document.getElementById("location");
                if (target) {
                    target.scrollIntoView({ behavior: "smooth" });
                }
            });
        });
    </script>
</body>
</html>