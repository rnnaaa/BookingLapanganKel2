<?php include __DIR__ . '/include_user/header.php'; ?>

<main>
    <section class="relative overflow-hidden bg-gradient-to-r from-primary to-primaryDark text-white">
        <div class="absolute top-10 left-10 w-20 h-20 bg-accent/20 rounded-full animate-pulse-slow"></div>
        <div class="absolute bottom-10 right-10 w-32 h-32 bg-white/10 rounded-full animate-bounce-slow"></div>
        <div class="absolute top-1/2 left-1/4 w-16 h-16 bg-accent/30 rounded-full animate-pulse"></div>

        <div class="max-w-7xl mx-auto px-4 py-20 lg:py-28 flex flex-col lg:flex-row items-center gap-12 relative z-10">
            <div class="lg:w-7/12" data-aos="fade-right">
                <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur rounded-full px-4 py-2 text-sm font-semibold mb-6 animate-pulse">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2v10l9 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Booking Instan • DP via Admin
                </div>

                <h1 class="font-poppins font-extrabold text-4xl md:text-5xl lg:text-6xl leading-tight mb-6">Pesan Lapangan <span class="text-accent">Lebih Cepat</span>, Main Tanpa Ribet</h1>

                <p class="text-lg md:text-xl text-white/90 max-w-2xl mb-8">Pilih lapangan, cek ketersediaan slot, dan konfirmasi langsung. Sistem memudahkan latihan harian hingga event—semua transparan dan aman.</p>

                <div class="flex flex-wrap gap-4 mb-8">
                    <a href="#lapangan" class="btn-primary btn-lg transform transition hover:scale-105 hover:shadow-lg">Lihat Lapangan</a>
                    <button class="btn-outline btn-lg transform transition hover:scale-105" onclick="scrollToSection('penawaran')">Penawaran Spesial</button>
                    <button class="btn-ghost ml-2 transform transition hover:scale-105 flex items-center gap-2 scroll-to-location">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Lihat di Maps
                    </button>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 max-w-md">
                    <div class="bg-white/20 rounded-xl p-4 text-center backdrop-blur shadow-soft hover:scale-105 transition-transform duration-300">
                        <div class="text-xs opacity-90">Lapangan</div>
                        <div class="font-semibold text-lg">4 Tipe</div>
                    </div>
                    <div class="bg-white/20 rounded-xl p-4 text-center backdrop-blur shadow-soft hover:scale-105 transition-transform duration-300">
                        <div class="text-xs opacity-90">Jam Operasional</div>
                        <div class="font-semibold text-lg">Senin-Jumat 08.00 - 23.00</div>
                        <div class="font-semibold text-lg">Sabtu-Minggu 07.00 - 23.00</div>
                    </div>
                    <div class="bg-white/20 rounded-xl p-4 text-center backdrop-blur shadow-soft hover:scale-105 transition-transform duration-300">
                        <div class="text-xs opacity-90">DP Event</div>
                        <div class="font-semibold text-lg">Min 30%</div>
                    </div>
                </div>
            </div>

            <div class="lg:w-5/12" data-aos="fade-left">
                <div class="rounded-2xl bg-white shadow-lift overflow-hidden transform transition hover:scale-105 duration-300">
                    <div class="relative">
                        <img src="assets/images/semuaLP.jpg" alt="Preview Lapangan SportField" class="w-full h-64 md:h-80 object-cover" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                        <div class="absolute top-4 left-4">
                            <span class="inline-block px-3 py-1 rounded-full text-xs bg-accent text-white font-semibold animate-pulse">⭐ Populer</span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <div class="font-semibold text-lg text-gray-900">4 Lapangan Siap Pakai</div>
                                <div class="text-sm text-slate-500">Futsal • Badminton • Basket • Court</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-4">
                    <div class="bg-white/20 rounded-xl p-4 text-center backdrop-blur shadow-soft">
                        <div class="text-xs opacity-90">Rating Pengguna</div>
                        <div class="font-semibold">4.8 ★ (128 Reviews)</div>
                    </div>
                    <div class="bg-white/20 rounded-xl p-4 text-center backdrop-blur shadow-soft">
                        <div class="text-xs opacity-90">Member Aktif</div>
                        <div class="font-semibold">500+</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="lapangan" class="max-w-7xl mx-auto px-4 py-20" data-aos="fade-up">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-poppins font-bold mb-4">Lapangan Kami</h2>
            <p class="text-lg text-slate-600 max-w-2xl mx-auto">Pilih lapangan favorit Anda, cek fasilitas, dan booking dengan mudah</p>
            <div class="mt-2 text-sm text-slate-500">Jam Operasional: Senin-Jumat: 07.00–22.00 • Sabtu-Minggu: 06.00–22.00</div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <article class="card-lapangan bg-white rounded-2xl shadow-soft overflow-hidden hover:shadow-lift transform transition hover:scale-105 duration-300" data-aos="zoom-in" data-aos-delay="100">
                <div class="relative">
                    <img src="assets/images/lapangan1.jpg" alt="Lapangan Futsal A - Sintetis Premium" class="w-full h-56 object-cover" />
                    <div class="absolute left-4 top-4 bg-white/90 text-primary px-3 py-2 rounded-lg font-semibold text-sm shadow-soft">Rp 150.000 / jam</div>
                    <div class="absolute right-4 top-4">
                        <span class="bg-accent text-white text-xs px-3 py-1 rounded-full font-semibold">🔥 Best Seller</span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="font-semibold text-xl mb-2">Futsal A - Sintetis Premium</h3>
                    <p class="text-slate-600 mb-4">Lapangan rumput sintetis terbaik dengan drainage & pencahayaan turnamen.</p>

                    <div class="flex gap-2 mb-4">
                        <span class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded">⚽ Futsal</span>
                        <span class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded">💡 LED</span>
                        <span class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded">🛠️ Premium</span>
                    </div>
                </div>
            </article>

            <article class="card-lapangan bg-white rounded-2xl shadow-soft overflow-hidden hover:shadow-lift transform transition hover:scale-105 duration-300" data-aos="zoom-in" data-aos-delay="150">
                <div class="relative">
                    <img src="assets/images/lapangan2.jpg" alt="Lapangan Futsal B - Vinyl Anti Slip" class="w-full h-56 object-cover" />
                    <div class="absolute left-4 top-4 bg-white/90 text-primary px-3 py-2 rounded-lg font-semibold text-sm shadow-soft">Rp 120.000 / jam</div>
                </div>
                <div class="p-6">
                    <h3 class="font-semibold text-xl mb-2">Futsal B - Vinyl Anti Slip</h3>
                    <p class="text-slate-600 mb-4">Permukaan vinyl anti-slip & lampu LED untuk visibilitas optimal.</p>

                    <div class="flex gap-2 mb-4">
                        <span class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded">⚽ Futsal</span>
                        <span class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded">🔒 Anti Slip</span>
                        <span class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded">💡 LED</span>
                    </div>
                </div>
            </article>

            <article class="card-lapangan bg-white rounded-2xl shadow-soft overflow-hidden hover:shadow-lift transform transition hover:scale-105 duration-300" data-aos="zoom-in" data-aos-delay="200">
                <div class="relative">
                    <img src="assets/images/lapangan3.jpg" alt="Lapangan Badminton - Standard Intl" class="w-full h-56 object-cover" />
                    <div class="absolute left-4 top-4 bg-white/90 text-primary px-3 py-2 rounded-lg font-semibold text-sm shadow-soft">Rp 80.000 / jam</div>
                </div>
                <div class="p-6">
                    <h3 class="font-semibold text-xl mb-2">Badminton - Standard Intl</h3>
                    <p class="text-slate-600 mb-4">Lantai vinyl khusus dan garis standard internasional, cocok latihan & turnamen.</p>

                    <div class="flex gap-2 mb-4">
                        <span class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded">🏸 Badminton</span>
                        <span class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded">🌍 International</span>
                        <span class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded">✨ Premium</span>
                    </div>
                </div>
            </article>

            <article class="card-lapangan bg-white rounded-2xl shadow-soft overflow-hidden hover:shadow-lift transform transition hover:scale-105 duration-300" data-aos="zoom-in" data-aos-delay="250">
                <div class="relative">
                    <img src="assets/images/lapangan4.jpg" alt="Lapangan Basket - Full Court Indoor" class="w-full h-56 object-cover" />
                    <div class="absolute left-4 top-4 bg-white/90 text-primary px-3 py-2 rounded-lg font-semibold text-sm shadow-soft">Rp 200.000 / jam</div>
                </div>
                <div class="p-6">
                    <h3 class="font-semibold text-xl mb-2">Basket - Full Court Indoor</h3>
                    <p class="text-slate-600 mb-4">Tribun & sound system tersedia, cocok event besar & latihan tim.</p>

                    <div class="flex gap-2 mb-4">
                        <span class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded">🏀 Basket</span>
                        <span class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded">🏟️ Full Court</span>
                        <span class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded">🔊 Sound System</span>
                    </div>
                </div>
            </article>
        </div>
    </section>

    <section id="fieldDetail" class="hidden max-w-7xl mx-auto px-4 py-12 bg-white rounded-2xl shadow-soft mb-12">
        <div id="fieldDetailContent"></div>
    </section>

    <section id="penawaran" class="py-20 bg-white" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-poppins font-bold mb-4">Penawaran Spesial</h2>
                <p class="text-lg text-slate-600">Pilihan membership, paket event, dan promo menarik untuk Anda</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="p-8 bg-gradient-to-br from-primary to-primaryDark text-white rounded-2xl shadow-lift transform transition hover:scale-105 duration-300">
                    <div class="flex items-start gap-6 mb-6">
                        <div class="w-16 h-16 rounded-2xl bg-white/20 flex items-center justify-center text-2xl">👑</div>
                        <div>
                            <div class="font-semibold text-xl mb-2">Membership Weekend</div>
                            <div class="text-white/90">Rp 150.000 / bulan — prioritas booking & bonus jam.</div>
                        </div>
                    </div>
                    <ul class="space-y-2 mb-6">
                        <li class="flex items-center gap-2">✓ Prioritas booking weekend</li>
                        <li class="flex items-center gap-2">✓ Bonus 2 jam setiap bulan</li>
                        <li class="flex items-center gap-2">✓ Diskon 15% untuk event</li>
                    </ul>
                    <div class="flex gap-3">
                        <button class="flex-1 btn-primary bg-accent border-accent hover:bg-yellow-500" onclick="handleBookingClick('Membership Weekend',150000)">Daftar</button>
                    </div>
                </div>

                <div class="p-8 bg-white rounded-2xl shadow-soft border border-slate-100 transform transition hover:scale-105 duration-300">
                    <div class="flex items-start gap-6 mb-6">
                        <div class="w-16 h-16 rounded-2xl bg-yellow-100 flex items-center justify-center text-2xl">🎯</div>
                        <div>
                            <div class="font-semibold text-xl mb-2">Promo Event</div>
                            <div class="text-slate-600">Diskon hingga 20% untuk peserta turnamen.</div>
                        </div>
                    </div>
                    <ul class="space-y-2 mb-6 text-slate-700">
                        <li class="flex items-center gap-2">✓ Diskon 20% paket turnamen</li>
                        <li class="flex items-center gap-2">✓ Free konsumsi untuk 20 orang</li>
                        <li class="flex items-center gap-2">✓ Sponsor kit tersedia</li>
                    </ul>
                    <div class="flex gap-3">
                        <button class="flex-1 btn-primary" onclick="contactAdmin()">Hubungi Admin</button>
                    </div>
                </div>

                <div class="p-8 bg-white rounded-2xl shadow-soft border border-slate-100 transform transition hover:scale-105 duration-300">
                    <div class="flex items-start gap-6 mb-6">
                        <div class="w-16 h-16 rounded-2xl bg-green-100 flex items-center justify-center text-2xl">💎</div>
                        <div>
                            <div class="font-semibold text-xl mb-2">Paket Reguler</div>
                            <div class="text-slate-600">Harga terjangkau dengan fasilitas lengkap.</div>
                        </div>
                    </div>
                    <ul class="space-y-2 mb-6 text-slate-700">
                        <li class="flex items-center gap-2">✓ Harga weekday lebih murah</li>
                        <li class="flex items-center gap-2">✓ Free akses fasilitas pendukung</li>
                        <li class="flex items-center gap-2">✓ Bisa booking via WhatsApp</li>
                    </ul>
                    <div class="flex gap-3">
                        <button class="flex-1 btn-primary" onclick="openWhatsApp()">Booking via WA</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="fasilitas" class="py-20 bg-softGray" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-poppins font-bold mb-4">Fasilitas Lengkap</h2>
                <p class="text-lg text-slate-600">Fasilitas pendukung yang membuat pengalaman bermain lebih nyaman</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="bg-white p-8 rounded-2xl shadow-soft text-center hover:scale-105 hover:shadow-lift transition-transform duration-300">
                    <div class="w-20 h-20 bg-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl">🕌</span>
                    </div>
                    <div class="font-semibold text-lg mb-2">Mushola</div>
                    <p class="text-sm text-slate-600">Tempat ibadah yang nyaman dan bersih</p>
                </div>
                <div class="bg-white p-8 rounded-2xl shadow-soft text-center hover:scale-105 hover:shadow-lift transition-transform duration-300">
                    <div class="w-20 h-20 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl">🚿</span>
                    </div>
                    <div class="font-semibold text-lg mb-2">Toilet & Shower</div>
                    <p class="text-sm text-slate-600">Fasilitas mandi setelah berolahraga</p>
                </div>
                <div class="bg-white p-8 rounded-2xl shadow-soft text-center hover:scale-105 hover:shadow-lift transition-transform duration-300">
                    <div class="w-20 h-20 bg-yellow-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl">☕</span>
                    </div>
                    <div class="font-semibold text-lg mb-2">Kantin & Cafe</div>
                    <p class="text-sm text-slate-600">Tempat istirahat dan ngopi</p>
                </div>
                <div class="bg-white p-8 rounded-2xl shadow-soft text-center hover:scale-105 hover:shadow-lift transition-transform duration-300">
                    <div class="w-20 h-20 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl">🅿️</span>
                    </div>
                    <div class="font-semibold text-lg mb-2">Parkir Luas</div>
                    <p class="text-sm text-slate-600">Area parkir yang aman dan luas</p>
                </div>
            </div>
        </div>
    </section>

    <!-- HERO LOKASI -->
    <!-- SECTION: Maps Header -->
    <section class="maps-section" id="location">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-5xl font-poppins font-bold mb-4">
                Lokasi SportField
            </h1>
            <p class="text-xl text-white/90 max-w-2xl mx-auto">
                Temukan lokasi kami dengan mudah dan rencanakan kunjungan Anda
            </p>
        </div>
    </section>

    <!-- SECTION: Peta dan Informasi Lokasi -->
    <section class="location-section">
        <div class="max-w-7xl mx-auto px-4">
            <!-- PETA INTERAKTIF - Lebih lebar -->
            <div class="w-full mb-12">
                <h2 class="text-3xl font-poppins font-bold mb-6">Peta Lokasi</h2>

                <div class="map-container h-96 bg-slate-200 rounded-2xl overflow-hidden">
                    <!-- Google Maps Embed -->
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

                <!-- Tombol Navigasi -->
                <div class="mt-6 flex flex-wrap gap-4">
                    <a
                        href="https://www.google.com/maps/place/RUSH+Badminton+Academy/@-8.1655851,113.7101279,17z/data=!3m1!4b1!4m6!3m5!1s0x2dd695aa99d14409:0x3c5639c7bcdde6cd!8m2!3d-8.1655851!4d113.7101279!16s%2Fg%2F11gzq2dbwd?entry=ttu"
                        target="_blank"
                        class="bg-primary text-white px-6 py-3 rounded-lg font-medium hover:bg-primaryDark transition-all duration-300 flex items-center gap-2">
                        
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                        </svg>
                        Buka di Google Maps
                    </a>
                </div>
            </div>

            <!-- INFORMASI LOKASI - Dipindahkan ke bawah peta -->
            <div class="w-full">
                <h2 class="text-3xl font-poppins font-bold mb-6">Informasi Lokasi</h2>

                <div class="space-y-6">
                    
                    <!-- Alamat Detail -->
                    <div class="bg-white p-6 rounded-2xl shadow-soft">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-lg mb-2">RUSH Badminton Academy</h3>
                                <p class="text-slate-700">
                                    Sebelah Neutron - Kampus, Jl. Kalimantan Gg. 14, Krajan Timur, 
                                    Sumbersari, Kec. Sumbersari, Kabupaten Jember, Jawa Timur 68121
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="testimoni" class="py-20 bg-white" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-poppins font-bold mb-4">Apa Kata Mereka?</h2>
                <p class="text-lg text-slate-600">Testimoni dari pelanggan setia SportField</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-slate-50 p-8 rounded-2xl shadow-soft hover:shadow-lift hover:scale-105 transform transition duration-300">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-primary to-primaryDark rounded-full flex items-center justify-center text-white font-semibold">B</div>
                        <div>
                            <div class="font-semibold">Budi Santoso</div>
                            <div class="text-sm text-slate-500">Tim Futsal Regular</div>
                        </div>
                    </div>
                    <p class="text-slate-700 italic mb-4">"Booking gampang banget, lapangan selalu terawat & customer service sigap! Recommended buat yang mau main futsal seru."</p>
                    <div class="flex text-yellow-400">★★★★★ <span class="text-slate-600 ml-2">5.0</span></div>
                </div>
                <div class="bg-slate-50 p-8 rounded-2xl shadow-soft hover:shadow-lift hover:scale-105 transform transition duration-300">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-pink-400 to-pink-600 rounded-full flex items-center justify-center text-white font-semibold">S</div>
                        <div>
                            <div class="font-semibold">Siti Rahayu</div>
                            <div class="text-sm text-slate-500">Komunitas Badminton</div>
                        </div>
                    </div>
                    <p class="text-slate-700 italic mb-4">"Cocok untuk latihan tim kami, fasilitas lengkap dan harga terjangkau. Parkirnya luas jadi gak ribet cari tempat."</p>
                    <div class="flex text-yellow-400">★★★★☆ <span class="text-slate-600 ml-2">4.5</span></div>
                </div>
                <div class="bg-slate-50 p-8 rounded-2xl shadow-soft hover:shadow-lift hover:scale-105 transform transition duration-300">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-green-400 to-green-600 rounded-full flex items-center justify-center text-white font-semibold">A</div>
                        <div>
                            <div class="font-semibold">Andi Wijaya</div>
                            <div class="text-sm text-slate-500">Event Organizer</div>
                        </div>
                    </div>
                    <p class="text-slate-700 italic mb-4">"Harga transparan & prosesnya cepat. Udah beberapa kali bikin event di sini, selalu memuaskan. Adminnya responsif banget!"</p>
                    <div class="flex text-yellow-400">★★★★★ <span class="text-slate-600 ml-2">5.0</span></div>
                </div>
            </div>
        </div>
    </section>

    <section id="faq" class="py-12 bg-white" data-aos="fade-up">
        <div class="max-w-3xl mx-auto px-4">
            <h3 class="text-3xl font-poppins font-semibold text-center mb-6">Pertanyaan Umum</h3>
            <div class="space-y-4">
                <details class="p-4 bg-slate-50 rounded-lg">
                    <summary class="font-semibold cursor-pointer">Bagaimana cara booking?</summary>
                    <p class="mt-2 text-sm text-slate-600">Pilih lapangan → pilih tanggal & jam → login → konfirmasi & DP via admin.</p>
                </details>
                <details class="p-4 bg-slate-50 rounded-lg">
                    <summary class="font-semibold cursor-pointer">Metode pembayaran?</summary>
                    <p class="mt-2 text-sm text-slate-600">Transfer bank, e-wallet, atau pembayaran di tempat sesuai ketentuan.</p>
                </details>
                <details class="p-4 bg-slate-50 rounded-lg">
                    <summary class="font-semibold cursor-pointer">Refund?</summary>
                    <p class="mt-2 text-sm text-slate-600">Refund sesuai syarat & ketentuan; hubungi admin untuk proses.</p>
                </details>
            </div>
        </div>
    </section>

    <section class="py-12 bg-gradient-to-r from-primary to-primaryDark text-white">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h3 class="text-3xl font-poppins font-bold">Siap booking? Amankan jadwalmu sekarang juga</h3>
            <p class="mt-3 text-white/90">Klik booking, login, lalu pilih slot yang tersedia.</p>
            <div class="mt-6">
                <a href="/BookingLapanganKel2/BookingPengguna/booking.php" class="btn-accent text-lg px-8 py-4 inline-block">
                <i class="fas fa-calendar-plus mr-2"></i>Booking Lapangan Sekarang
                </a>         
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/include_user/footer.php'; ?>