<?php 
session_start();
require 'config/database.php'; // Koneksi Database Wajib
include __DIR__ . '/include_user/header.php'; 

// ============================================================
// LOGIKA BACKEND: AMBIL 3 TESTIMONI TERBARU
// ============================================================
$testimonials = [];
// Limit 3 saja untuk halaman depan
$query = "SELECT * FROM saran ORDER BY created_at DESC LIMIT 3"; 
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Mapping kategori ke 'Peran' (Sama seperti kontak.php)
        $roleMap = [
            'fasilitas' => 'Member', 
            'pelayanan' => 'Pelanggan', 
            'booking' => 'User App', 
            'harga' => 'Member', 
            'lainnya' => 'Pengunjung'
        ];
        
        $displayName = ($row['is_anonim'] == 1) ? 'Pengguna' : htmlspecialchars($row['nama']);
        $displayRole = $roleMap[$row['kategori']] ?? 'Pelanggan';

        $testimonials[] = [
            'nama' => $displayName,
            'peran' => $displayRole,
            'testimoni' => htmlspecialchars($row['pesan']),
            'rating' => (int)$row['rating']
        ];
    }
}
?>

<style>
    /* STYLE KONSISTEN */
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f8fafc;
    }
    h1, h2, h3, h4, h5, h6, .font-poppins {
        font-family: 'Poppins', sans-serif;
    }

    /* Animasi & Efek */
    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }
    .animate-float { animation: float 4s ease-in-out infinite; }
    
    @keyframes pulse-slow {
        0%, 100% { opacity: 0.6; transform: scale(1); }
        50% { opacity: 1; transform: scale(1.1); }
    }
    .animate-pulse-slow { animation: pulse-slow 3s infinite; }

    /* Card Effects */
    .hover-lift { transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .hover-lift:hover { transform: translateY(-5px); box-shadow: 0 10px 30px -10px rgba(0,0,0,0.1); }

    /* FAQ Style */
    details > summary { list-style: none; }
    details > summary::-webkit-details-marker { display: none; }
    details[open] summary ~ * { animation: slideDown 0.3s ease-in-out; }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<main>
    <section class="relative overflow-hidden bg-gradient-to-r from-primary to-primaryDark text-white">
        <div class="absolute top-10 left-10 w-20 h-20 bg-yellow-400/20 rounded-full animate-pulse-slow"></div>
        <div class="absolute bottom-10 right-10 w-32 h-32 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 left-1/4 w-20 h-20 bg-yellow-300/20 rounded-full blur-2xl animate-float"></div>

        <div class="max-w-7xl mx-auto px-4 py-20 lg:py-28 flex flex-col lg:flex-row items-center gap-12 relative z-10">
            <div class="lg:w-6/12" data-aos="fade-right">
                <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-sm rounded-full px-5 py-2 text-sm font-semibold mb-6 border border-white/10 animate-float">
                    <i class="fa-solid fa-bolt text-yellow-300"></i>
                    Booking Instan • DP Mudah
                </div>

                <h1 class="font-poppins font-extrabold text-4xl md:text-5xl lg:text-6xl leading-tight mb-6">
                    Pesan Lapangan <br/><span class="text-yellow-300">Lebih Cepat</span>, Main Tanpa Ribet
                </h1>

                <p class="text-lg md:text-xl text-white/90 max-w-xl mb-8 font-light leading-relaxed">
                    Pilih lapangan, cek jadwal real-time, dan konfirmasi langsung. Solusi terbaik untuk latihan rutin hingga turnamen besar.
                </p>

                <div class="flex flex-wrap gap-4 mb-10">
                    <a href="#lapangan" class="bg-white text-primary hover:bg-gray-100 px-8 py-4 rounded-xl font-bold shadow-lg transform transition hover:scale-105 flex items-center gap-2">
                        Lihat Lapangan <i class="fa-solid fa-arrow-down"></i>
                    </a>
                    <button onclick="document.getElementById('penawaran').scrollIntoView({behavior: 'smooth'})" class="border-2 border-white/30 hover:bg-white/10 text-white px-8 py-4 rounded-xl font-semibold transition-all">
                        Penawaran Spesial
                    </button>
                </div>

                <div class="grid grid-cols-3 gap-4 p-4 bg-white/10 backdrop-blur-md rounded-2xl border border-white/10">
                    <div class="text-center border-r border-white/10 last:border-0">
                        <div class="text-2xl font-bold">4</div>
                        <div class="text-xs text-white/70 uppercase tracking-wider">Tipe Lapangan</div>
                    </div>
                    <div class="text-center border-r border-white/10 last:border-0">
                        <div class="text-2xl font-bold">08-23</div>
                        <div class="text-xs text-white/70 uppercase tracking-wider">Jam Operasional</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold">30%</div>
                        <div class="text-xs text-white/70 uppercase tracking-wider">Min DP Event</div>
                    </div>
                </div>
            </div>

            <div class="lg:w-6/12 relative" data-aos="fade-left">
                <div class="relative rounded-3xl overflow-hidden shadow-2xl border-4 border-white/20 transform rotate-2 hover:rotate-0 transition-transform duration-500">
                    <img src="assets/images/semuaLP.jpg" alt="SportField Arena" class="w-full h-[450px] object-cover hover:scale-105 transition-transform duration-700" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                    <div class="absolute bottom-6 left-6 text-white">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="bg-yellow-400 text-slate-900 text-xs font-bold px-2 py-1 rounded">POPULER</span>
                            <span class="flex text-yellow-400 text-sm">★★★★★</span>
                        </div>
                        <h3 class="text-xl font-bold">4 Lapangan Standard Internasional</h3>
                        <p class="text-sm text-white/80">Futsal • Badminton • Basket</p>
                    </div>
                </div>
                <div class="absolute -top-6 -right-6 bg-white p-4 rounded-2xl shadow-xl animate-float hidden md:block">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-green-600"><i class="fa-solid fa-users"></i></div>
                        <div>
                            <div class="font-bold text-slate-800">500+</div>
                            <div class="text-xs text-slate-500">Member Aktif</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="lapangan" class="max-w-7xl mx-auto px-4 py-24">
        <div class="text-center mb-16" data-aos="fade-up">
            <span class="text-primary font-bold tracking-wider uppercase text-sm">Pilihan Arena</span>
            <h2 class="text-4xl font-poppins font-bold text-slate-800 mt-2 mb-4">Lapangan Kami</h2>
            <p class="text-lg text-slate-600 max-w-2xl mx-auto">Pilih lapangan favorit Anda dengan fasilitas terbaik</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <article class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden hover-lift group" data-aos="fade-up" data-aos-delay="100">
                <div class="relative h-60 overflow-hidden">
                    <img src="assets/images/lapangan1.jpg" alt="Futsal A" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" />
                    <div class="absolute top-4 right-4 bg-yellow-400 text-slate-900 text-xs font-bold px-3 py-1 rounded-full shadow-md">Best Seller</div>
                    <div class="absolute bottom-4 left-4 bg-white/90 backdrop-blur px-3 py-1.5 rounded-lg shadow-sm">
                        <span class="text-primary font-bold text-sm">Rp 30.000</span> <span class="text-xs text-slate-500">/ jam</span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="font-bold text-lg text-slate-800 mb-2 group-hover:text-primary transition-colors">Lapangan A (Matras)</h3>
                    <p class="text-slate-500 text-sm mb-4 line-clamp-2">Karpet standar BWF dengan pencahayaan anti-glare.</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-2 py-1 bg-slate-50 text-slate-600 text-xs rounded border border-slate-100">🏸 Badminton</span>
                        <span class="px-2 py-1 bg-slate-50 text-slate-600 text-xs rounded border border-slate-100">💡 LED</span>
                    </div>
                </div>
            </article>

            <article class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden hover-lift group" data-aos="fade-up" data-aos-delay="200">
                <div class="relative h-60 overflow-hidden">
                    <img src="assets/images/lapangan2.jpg" alt="Futsal B" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" />
                    <div class="absolute bottom-4 left-4 bg-white/90 backdrop-blur px-3 py-1.5 rounded-lg shadow-sm">
                        <span class="text-primary font-bold text-sm">Rp 30.000</span> <span class="text-xs text-slate-500">/ jam</span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="font-bold text-lg text-slate-800 mb-2 group-hover:text-primary transition-colors">Lapangan B (Sintetis)</h3>
                    <p class="text-slate-500 text-sm mb-4 line-clamp-2">Lantai vinyl profesional anti-slip untuk performa maksimal.</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-2 py-1 bg-slate-50 text-slate-600 text-xs rounded border border-slate-100">🏸 Badminton</span>
                        <span class="px-2 py-1 bg-slate-50 text-slate-600 text-xs rounded border border-slate-100">✨ Vinyl</span>
                    </div>
                </div>
            </article>

            <article class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden hover-lift group" data-aos="fade-up" data-aos-delay="300">
                <div class="relative h-60 overflow-hidden">
                    <img src="assets/images/lapangan3.jpg" alt="Badminton" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" />
                    <div class="absolute bottom-4 left-4 bg-white/90 backdrop-blur px-3 py-1.5 rounded-lg shadow-sm">
                        <span class="text-primary font-bold text-sm">Rp 30.000</span> <span class="text-xs text-slate-500">/ jam</span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="font-bold text-lg text-slate-800 mb-2 group-hover:text-primary transition-colors">Lapangan C (Vynil)</h3>
                    <p class="text-slate-500 text-sm mb-4 line-clamp-2">Karpet standar BWF dengan pencahayaan anti-glare.</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-2 py-1 bg-slate-50 text-slate-600 text-xs rounded border border-slate-100">🏸 Badminton</span>
                        <span class="px-2 py-1 bg-slate-50 text-slate-600 text-xs rounded border border-slate-100">🏆 BWF</span>
                    </div>
                </div>
            </article>
        </div>
    </section>

    <section id="penawaran" class="py-20 bg-white relative">
        <div class="absolute inset-0 bg-slate-50 transform -skew-y-3 z-0 origin-top-left"></div>
        <div class="max-w-7xl mx-auto px-4 relative z-10">
            <div class="text-center mb-16">
                <span class="text-primary font-bold tracking-wider uppercase text-sm">Membership & Promo</span>
                <h2 class="text-4xl font-poppins font-bold text-slate-800 mt-2">Penawaran Spesial</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="relative p-8 bg-gradient-to-br from-primary to-primaryDark text-white rounded-3xl shadow-xl hover-lift overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 blur-2xl"></div>
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center text-2xl backdrop-blur">👑</div>
                        <div>
                            <h3 class="font-bold text-xl">Member Weekend</h3>
                            <p class="text-white/80 text-sm">Prioritas Sabtu-Minggu</p>
                        </div>
                    </div>
                    <div class="text-3xl font-bold mb-6">Rp 25rb <span class="text-sm font-normal text-white/70">/Jam</span></div>
                    <ul class="space-y-3 mb-8 text-sm text-white/90">
                        <li class="flex items-center gap-3"><i class="fa-solid fa-check text-yellow-300"></i> Booking H-7</li>
                        <li class="flex items-center gap-3"><i class="fa-solid fa-check text-yellow-300"></i> Diskon 15% Event</li>
                        <li class="flex items-center gap-3"><i class="fa-solid fa-check text-yellow-300"></i> Bonus 2 Jam/Bulan</li>
                    </ul>
                    <button class="w-full py-3 bg-yellow-400 hover:bg-yellow-300 text-primaryDark font-bold rounded-xl transition-colors shadow-lg" onclick="window.location.href='member.php'">Daftar Member</button>
                </div>

                <div class="p-8 bg-white rounded-3xl shadow-soft border border-slate-100 hover-lift">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-14 h-14 rounded-2xl bg-yellow-50 text-yellow-600 flex items-center justify-center text-2xl">🎯</div>
                        <div>
                            <h3 class="font-bold text-xl text-slate-800">Paket Turnamen</h3>
                            <p class="text-slate-500 text-sm">Solusi Event Lengkap</p>
                        </div>
                    </div>
                    <p class="text-slate-600 text-sm mb-6 leading-relaxed">Diskon khusus hingga 20% untuk booking seharian penuh. Termasuk sound system & wasit.</p>
                    <ul class="space-y-3 mb-8 text-sm text-slate-600">
                        <li class="flex items-center gap-3"><i class="fa-solid fa-check text-green-500"></i> Free Konsumsi Panitia</li>
                        <li class="flex items-center gap-3"><i class="fa-solid fa-check text-green-500"></i> Dokumentasi Foto</li>
                    </ul>
                    <button class="w-full py-3 bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold rounded-xl border border-slate-200 transition-all" onclick="window.location.href='kontak.php'">Hubungi Admin</button>
                </div>

                <div class="p-8 bg-white rounded-3xl shadow-soft border border-slate-100 hover-lift">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-14 h-14 rounded-2xl bg-green-50 text-green-600 flex items-center justify-center text-2xl">💎</div>
                        <div>
                            <h3 class="font-bold text-xl text-slate-800">Main Reguler</h3>
                            <p class="text-slate-500 text-sm">Latihan Harian</p>
                        </div>
                    </div>
                    <p class="text-slate-600 text-sm mb-6 leading-relaxed">Pilihan tepat untuk sparring santai atau latihan rutin mingguan tim Anda.</p>
                    <ul class="space-y-3 mb-8 text-sm text-slate-600">
                        <li class="flex items-center gap-3"><i class="fa-solid fa-check text-green-500"></i> Harga Weekday Murah</li>
                        <li class="flex items-center gap-3"><i class="fa-solid fa-check text-green-500"></i> Fasilitas Shower Gratis</li>
                    </ul>
                    <button class="w-full py-3 bg-white hover:bg-slate-50 text-primary border-2 border-primary font-bold rounded-xl transition-all" onclick="window.location.href='BookingPengguna/booking.php'">Booking Sekarang</button>
                </div>
            </div>
        </div>
    </section>

    <section id="fasilitas" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-poppins font-bold text-slate-800 mb-4">Fasilitas Lengkap</h2>
                <p class="text-slate-600">Kenyamanan Anda adalah prioritas kami</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="p-6 rounded-2xl bg-slate-50 text-center hover:bg-white hover:shadow-lg transition-all duration-300 border border-transparent hover:border-slate-100">
                    <div class="w-16 h-16 mx-auto bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center text-2xl mb-4">🕌</div>
                    <h3 class="font-bold text-slate-800 mb-1">Mushola</h3>
                    <p class="text-xs text-slate-500">Bersih & Nyaman</p>
                </div>
                <div class="p-6 rounded-2xl bg-slate-50 text-center hover:bg-white hover:shadow-lg transition-all duration-300 border border-transparent hover:border-slate-100">
                    <div class="w-16 h-16 mx-auto bg-green-100 text-green-600 rounded-2xl flex items-center justify-center text-2xl mb-4">🚿</div>
                    <h3 class="font-bold text-slate-800 mb-1">Shower</h3>
                    <p class="text-xs text-slate-500">Air Hangat</p>
                </div>
                <div class="p-6 rounded-2xl bg-slate-50 text-center hover:bg-white hover:shadow-lg transition-all duration-300 border border-transparent hover:border-slate-100">
                    <div class="w-16 h-16 mx-auto bg-orange-100 text-orange-600 rounded-2xl flex items-center justify-center text-2xl mb-4">☕</div>
                    <h3 class="font-bold text-slate-800 mb-1">Cafe</h3>
                    <p class="text-xs text-slate-500">Snack & Minuman</p>
                </div>
                <div class="p-6 rounded-2xl bg-slate-50 text-center hover:bg-white hover:shadow-lg transition-all duration-300 border border-transparent hover:border-slate-100">
                    <div class="w-16 h-16 mx-auto bg-purple-100 text-purple-600 rounded-2xl flex items-center justify-center text-2xl mb-4">🅿️</div>
                    <h3 class="font-bold text-slate-800 mb-1">Parkir</h3>
                    <p class="text-xs text-slate-500">Luas & Aman</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-24 bg-slate-50" id="location">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-col lg:flex-row gap-12 items-start">
                <div class="lg:w-1/3">
                    <span class="text-primary font-bold text-sm uppercase tracking-wider">Lokasi Kami</span>
                    <h2 class="text-3xl font-poppins font-bold text-slate-800 mt-2 mb-6">Kunjungi Arena</h2>
                    
                    <div class="bg-white p-6 rounded-2xl shadow-sm mb-6">
                        <div class="flex gap-4">
                            <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center shrink-0 text-primary">
                                <i class="fa-solid fa-location-dot text-xl"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-800 mb-1">Rush Badminton Academy</h4>
                                <p class="text-sm text-slate-600 leading-relaxed">
                                    Sebelah Neutron - Kampus, Jl. Kalimantan Gg. 14, Krajan Timur, Sumbersari, Jember.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <a href="https://maps.app.goo.gl/EmggepYvmq8e5FF88" target="_blank" class="w-full block text-center bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-semibold py-3 rounded-xl transition-colors">
                        Buka Google Maps
                    </a>
                </div>

                <div class="lg:w-2/3 w-full h-96 bg-slate-200 rounded-3xl overflow-hidden shadow-lg border-4 border-white">
                     <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3949.317075287345!2d113.7079392747652!3d-8.165585099999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd695aa99d14409%3A0x3c5639c7bcdde6cd!2sRUSH%20Badminton%20Academy!5e0!3m2!1sid!2sid!4v1710000000000!5m2!1sid!2sid" 
                        width="100%" 
                        height="100%" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
        </div>
    </section>

    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-poppins font-bold mb-4">Kata Mereka</h2>
                <p class="text-lg text-slate-600">Pengalaman pengguna setia SportField</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8" id="testimoniContainer">
                </div>
        </div>
    </section>

    <section class="py-20 bg-slate-50">
        <div class="max-w-3xl mx-auto px-4">
            <h2 class="text-3xl font-poppins font-bold text-center mb-10 text-slate-800">Pertanyaan Umum</h2>
            <div class="space-y-4">
                <details class="group bg-white rounded-2xl p-5 cursor-pointer open:bg-white open:shadow-md transition-all">
                    <summary class="font-semibold text-slate-800 flex justify-between items-center">
                        Cara Booking Lapangan?
                        <i class="fa-solid fa-chevron-down text-slate-400 group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <p class="mt-3 text-sm text-slate-600 leading-relaxed">Pilih menu lapangan, klik jam yang tersedia, login akun Anda, dan lakukan pembayaran DP atau Lunas.</p>
                </details>
                <details class="group bg-white rounded-2xl p-5 cursor-pointer open:bg-white open:shadow-md transition-all">
                    <summary class="font-semibold text-slate-800 flex justify-between items-center">
                        Apakah bisa refund DP?
                        <i class="fa-solid fa-chevron-down text-slate-400 group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <p class="mt-3 text-sm text-slate-600 leading-relaxed">Refund hanya dapat dilakukan jika pembatalan dilakukan minimal H-2 sebelum jadwal main. Hubungi admin untuk prosesnya.</p>
                </details>
            </div>
        </div>
    </section>
    
    <section class="py-16 bg-gradient-to-r from-primary to-primaryDark text-white text-center">
        <div class="max-w-4xl mx-auto px-4">
            <h2 class="text-3xl md:text-4xl font-poppins font-bold mb-4">Siap Jadi Juara Berikutnya?</h2>
            <p class="text-white/90 text-lg mb-8">Jangan biarkan jadwal penuh menghalangi latihanmu. Booking sekarang sebelum kehabisan slot!</p>
            <a href="/BookingLapanganKel2/BookingPengguna/booking.php" class="inline-block bg-yellow-400 text-primaryDark font-bold text-lg px-10 py-4 rounded-full shadow-xl hover:bg-yellow-300 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300">
                <i class="fas fa-calendar-check mr-2"></i> Booking Sekarang
            </a>
        </div>
    </section>

</main>

<script>
    // DATA DARI PHP
    const dbTestimonials = <?= json_encode($testimonials) ?>;

    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('testimoniContainer');
        
        if (container) {
            if (dbTestimonials.length === 0) {
                container.innerHTML = `<div class="col-span-3 text-center py-8 text-slate-500">Belum ada testimoni. Jadilah yang pertama memberikan ulasan!</div>`;
            } else {
                container.innerHTML = dbTestimonials.map(t => `
                  <div class="bg-slate-50 p-8 rounded-2xl shadow-soft hover:shadow-lift hover:scale-105 transform transition duration-300">
                    <div class="flex items-center gap-3 mb-4">
                      <div class="w-12 h-12 bg-gradient-to-r from-primary to-primaryDark rounded-full flex items-center justify-center text-white font-bold text-lg shadow-md">
                        ${t.nama.charAt(0).toUpperCase()}
                      </div>
                      <div>
                        <div class="font-bold text-slate-800">${t.nama}</div>
                        <div class="text-xs text-slate-500 font-medium uppercase tracking-wide">${t.peran}</div>
                      </div>
                    </div>
                    <p class="text-slate-600 text-sm italic mb-4 leading-relaxed">"${t.testimoni}"</p>
                    <div class="flex text-yellow-400 text-sm">
                      ${'★'.repeat(t.rating)}${t.rating < 5 ? '☆'.repeat(5 - t.rating) : ''}
                    </div>
                  </div>
                `).join('');
            }
        }
    });
</script>

<?php include __DIR__ . '/include_user/footer.php'; ?>