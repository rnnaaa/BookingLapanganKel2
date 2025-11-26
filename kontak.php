<?php 
// Memanggil header.php
require 'include_user/header.php'; 
?>

<style>
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f8fafc;
    }
    h1, h2, h3, h4, h5, h6, .font-poppins {
        font-family: 'Poppins', sans-serif;
    }
    
    /* Animasi Custom */
    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }
    .animate-float {
        animation: float 3s ease-in-out infinite;
    }
    
    /* Styling Details/Summary untuk FAQ */
    details > summary {
        list-style: none;
    }
    details > summary::-webkit-details-marker {
        display: none;
    }
    details[open] summary ~ * {
        animation: slideDown 0.3s ease-in-out;
    }
    @keyframes slideDown {
        0% { opacity: 0; transform: translateY(-10px); }
        100% { opacity: 1; transform: translateY(0); }
    }
</style>

<main>
    <section class="relative overflow-hidden bg-gradient-to-r from-primary to-primaryDark text-white">
        <div class="absolute top-10 left-10 w-32 h-32 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-10 right-10 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
        
        <div class="max-w-7xl mx-auto px-4 py-20 lg:py-24 text-center relative z-10" data-aos="fade-down">
            <h1 class="font-poppins font-extrabold text-4xl md:text-5xl lg:text-6xl leading-tight mb-6">
                Hubungi <span class="text-yellow-300">Rush Badminton</span>
            </h1>
            <p class="text-lg md:text-xl text-white/90 max-w-2xl mx-auto font-light">
                Punya pertanyaan seputar booking, fasilitas, atau kemitraan? Kami siap membantu Anda kapan saja.
            </p>
        </div>
    </section>

    <section class="relative -mt-16 z-20 pb-20">
        <div class="max-w-5xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <a href="https://wa.me/6285234063810" target="_blank" class="group bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transform transition hover:-translate-y-2 duration-300 flex items-center gap-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="w-20 h-20 bg-green-50 rounded-2xl flex items-center justify-center group-hover:bg-green-100 transition-colors">
                        <i class="fa-brands fa-whatsapp text-4xl text-green-600"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-800 mb-1 font-poppins group-hover:text-green-600 transition-colors">WhatsApp Admin</h3>
                        <p class="text-slate-500 text-sm mb-2">Respon cepat untuk booking & info</p>
                        <span class="text-green-600 font-semibold text-sm flex items-center gap-2">
                            Chat Sekarang <i class="fa-solid fa-arrow-right transition-transform group-hover:translate-x-1"></i>
                        </span>
                    </div>
                </a>

                <?php
                    $email_tujuan = 'gilangoppo417@gmail.com'; 
                ?>
                <a href="https://mail.google.com/mail/?view=cm&fs=1&to=<?= $email_tujuan ?>" target="_blank" class="group bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transform transition hover:-translate-y-2 duration-300 flex items-center gap-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="w-20 h-20 bg-blue-50 rounded-2xl flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                        <i class="fa-regular fa-envelope text-4xl text-blue-600"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-800 mb-1 font-poppins group-hover:text-blue-600 transition-colors">Email Support</h3>
                        <p class="text-slate-500 text-sm mb-2">Kirim email via Gmail</p>
                        <span class="text-blue-600 font-semibold text-sm flex items-center gap-2">
                            Tulis Pesan <i class="fa-solid fa-arrow-right transition-transform group-hover:translate-x-1"></i>
                        </span>
                    </div>
                </a>

            </div>
        </div>
    </section>

    <section class="py-12">
        <div class="max-w-3xl mx-auto px-4"> <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden p-8 md:p-10" data-aos="fade-up">
                
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold font-poppins text-slate-800 mb-2">Kirim Saran & Masukan</h2>
                    <p class="text-slate-500 text-sm">Bantu kami meningkatkan layanan dengan memberikan feedback Anda.</p>
                </div>
                
                <form id="saranForm" onsubmit="submitSaran(event)" class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nama</label>
                            <input type="text" name="nama" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all text-sm" placeholder="Nama Anda" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Email</label>
                            <input type="email" name="email" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all text-sm" placeholder="email@anda.com" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Topik</label>
                        <select name="kategori" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all text-sm cursor-pointer" required>
                            <option value="" disabled selected>Pilih Topik...</option>
                            <option value="fasilitas">Fasilitas Lapangan</option>
                            <option value="pelayanan">Pelayanan Admin</option>
                            <option value="booking">Website / Booking</option>
                            <option value="harga">Harga & Promo</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Rating</label>
                        <div class="flex items-center gap-2 p-3 bg-slate-50 rounded-xl border border-slate-200" id="ratingStars">
                            <input type="hidden" id="rating" name="rating" value="0" required>
                            <button type="button" class="text-2xl text-slate-300 hover:text-yellow-400 transition-colors transform hover:scale-110" data-rating="1" onclick="setRating(1)">★</button>
                            <button type="button" class="text-2xl text-slate-300 hover:text-yellow-400 transition-colors transform hover:scale-110" data-rating="2" onclick="setRating(2)">★</button>
                            <button type="button" class="text-2xl text-slate-300 hover:text-yellow-400 transition-colors transform hover:scale-110" data-rating="3" onclick="setRating(3)">★</button>
                            <button type="button" class="text-2xl text-slate-300 hover:text-yellow-400 transition-colors transform hover:scale-110" data-rating="4" onclick="setRating(4)">★</button>
                            <button type="button" class="text-2xl text-slate-300 hover:text-yellow-400 transition-colors transform hover:scale-110" data-rating="5" onclick="setRating(5)">★</button>
                            <span class="ml-auto text-xs text-slate-500 font-medium bg-white px-2 py-1 rounded-md shadow-sm" id="ratingText">Pilih Bintang</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Pesan</label>
                        <textarea name="pesan" rows="4" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all text-sm resize-none" placeholder="Tulis pengalaman Anda disini..." required></textarea>
                    </div>

                    <div class="flex items-center gap-2 mb-4">
                        <input type="checkbox" id="anonim" name="anonim" class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary">
                        <label for="anonim" class="text-xs text-slate-600 cursor-pointer select-none">Kirim sebagai anonim (Sembunyikan nama)</label>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" onclick="resetSaranForm()" class="w-full md:w-auto px-6 py-3 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">
                            Reset
                        </button>
                        <button type="submit" class="flex-1 px-6 py-3 text-sm font-bold text-white bg-primary hover:bg-primaryDark rounded-xl shadow-lg shadow-primary/30 transform hover:-translate-y-0.5 transition-all">
                            Kirim Masukan
                        </button>
                    </div>
                </form>

                <div id="saranSuccess" class="hidden h-full flex flex-col items-center justify-center text-center animate-fade-in py-10">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fa-solid fa-check text-3xl text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Terima Kasih!</h3>
                    <p class="text-slate-600 text-sm mb-6 max-w-md mx-auto">Masukan Anda telah kami terima dan akan kami jadikan evaluasi untuk pelayanan yang lebih baik.</p>
                    <button onclick="hideSuccessMessage()" class="text-primary font-semibold hover:underline text-sm">Kirim saran lagi</button>
                </div>
            </div>
        </div>
    </section>

    <section id="testimoni" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12" data-aos="fade-up">
                <h2 class="text-3xl md:text-4xl font-poppins font-bold mb-4">Kata Mereka</h2>
                <p class="text-lg text-slate-600">Pengalaman pengguna setia SportField</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8" id="testimoniContainer">
                </div>
        </div>
    </section>

    <section id="faq" class="py-20 bg-slate-50">
        <div class="max-w-3xl mx-auto px-4">
            <div class="text-center mb-12" data-aos="fade-up">
                <h2 class="text-3xl md:text-4xl font-poppins font-bold mb-4">Pertanyaan Umum</h2>
                <p class="text-slate-600">Jawaban untuk pertanyaan yang sering diajukan</p>
            </div>
            
            <div class="space-y-4">
                <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden transition-all duration-300 hover:shadow-md" data-aos="fade-up" data-aos-delay="100">
                    <summary class="flex items-center justify-between p-5 cursor-pointer">
                        <h4 class="font-semibold text-slate-800">Bagaimana prosedur booking lapangan?</h4>
                        <span class="text-slate-400 transition-transform group-open:rotate-180">
                            <i class="fa-solid fa-chevron-down"></i>
                        </span>
                    </summary>
                    <div class="px-5 pb-5 text-slate-600 text-sm leading-relaxed">
                        Sangat mudah! Pilih lapangan di halaman utama, tentukan tanggal & jam, lalu login/daftar. Setelah itu lakukan pembayaran (DP/Lunas) dan upload bukti transfer.
                    </div>
                </details>

                <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden transition-all duration-300 hover:shadow-md" data-aos="fade-up" data-aos-delay="200">
                    <summary class="flex items-center justify-between p-5 cursor-pointer">
                        <h4 class="font-semibold text-slate-800">Metode pembayaran apa yang tersedia?</h4>
                        <span class="text-slate-400 transition-transform group-open:rotate-180">
                            <i class="fa-solid fa-chevron-down"></i>
                        </span>
                    </summary>
                    <div class="px-5 pb-5 text-slate-600 text-sm leading-relaxed">
                        Kami menerima pembayaran via Transfer Bank (BCA, Mandiri) dan QRIS (GoPay, OVO, Dana, ShopeePay). Anda juga bisa melakukan pembayaran tunai langsung di lokasi.
                    </div>
                </details>

                <details class="group bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden transition-all duration-300 hover:shadow-md" data-aos="fade-up" data-aos-delay="300">
                    <summary class="flex items-center justify-between p-5 cursor-pointer">
                        <h4 class="font-semibold text-slate-800">Apakah bisa reschedule atau refund?</h4>
                        <span class="text-slate-400 transition-transform group-open:rotate-180">
                            <i class="fa-solid fa-chevron-down"></i>
                        </span>
                    </summary>
                    <div class="px-5 pb-5 text-slate-600 text-sm leading-relaxed">
                        Reschedule diperbolehkan minimal H-1 sebelum jadwal main. Refund hanya dapat diproses jika ada kendala teknis dari pihak Rush Badminton atau pembatalan H-2 (dikenakan potongan admin).
                    </div>
                </details>
            </div>
        </div>
    </section>
</main>

<script>
  // Fungsi untuk mengatur rating
  function setRating(value) {
    const ratingInput = document.getElementById('rating');
    const ratingText = document.getElementById('ratingText');
    const stars = document.querySelectorAll('#ratingStars button[data-rating]');

    ratingInput.value = String(value);

    const labels = ['Sangat Buruk', 'Kurang', 'Cukup', 'Baik', 'Sangat Baik'];
    ratingText.textContent = labels[value - 1];
    ratingText.className = "ml-auto text-xs font-bold px-2 py-1 rounded-md shadow-sm text-white " + 
                          (value <= 2 ? "bg-red-500" : value == 3 ? "bg-yellow-500" : "bg-green-500");

    stars.forEach(btn => {
      const r = parseInt(btn.getAttribute('data-rating'), 10);
      if (r <= value) {
        btn.classList.remove('text-slate-300');
        btn.classList.add('text-yellow-400');
      } else {
        btn.classList.remove('text-yellow-400');
        btn.classList.add('text-slate-300');
      }
    });
  }

  function resetSaranForm() {
    const form = document.getElementById('saranForm');
    if (form) form.reset();

    document.getElementById('rating').value = '0';
    const ratingText = document.getElementById('ratingText');
    ratingText.textContent = 'Pilih Bintang';
    ratingText.className = "ml-auto text-xs text-slate-500 font-medium bg-white px-2 py-1 rounded-md shadow-sm";
    
    const stars = document.querySelectorAll('#ratingStars button[data-rating]');
    stars.forEach(btn => {
      btn.classList.remove('text-yellow-400');
      btn.classList.add('text-slate-300');
    });
  }

  function saveTestimonials(testimonials) {
    localStorage.setItem('sportfieldTestimonials', JSON.stringify(testimonials));
  }

  function loadTestimonials() {
    const stored = localStorage.getItem('sportfieldTestimonials');
    if (stored) {
      return JSON.parse(stored);
    } else {
      const defaultTestimonials = [
        { nama: 'Ahmad Rizki', peran: 'Pengguna Fasilitas', testimoni: '"Lapangan futsal berkualitas dengan harga terjangkau. Adminnya ramah dan responsif!"', rating: 5 },
        { nama: 'Sari Dewi', peran: 'Pelanggan Booking', testimoni: '"Proses booking mudah dan cepat. Fasilitas lengkap dan bersih. Recommended banget!"', rating: 4 },
        { nama: 'Budi Santoso', peran: 'Pengguna Promo', testimoni: '"Promo weekend-nya worth it! Lapangan basketnya luas dan nyaman untuk latihan tim."', rating: 5 }
      ];
      saveTestimonials(defaultTestimonials);
      return defaultTestimonials;
    }
  }

  function displayTestimonials() {
    const container = document.getElementById('testimoniContainer');
    if (!container) return;
    
    const testimonials = loadTestimonials();
    
    if (testimonials.length === 0) {
      container.innerHTML = `<div class="col-span-3 text-center py-8 text-slate-500">Belum ada testimoni.</div>`;
      return;
    }
    
    container.innerHTML = testimonials.map(t => `
      <div class="bg-slate-50 p-8 rounded-2xl shadow-soft hover:shadow-lift hover:scale-105 transform transition duration-300" data-aos="zoom-in">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-12 h-12 bg-gradient-to-r from-primary to-primaryDark rounded-full flex items-center justify-center text-white font-bold text-lg shadow-md">
            ${t.nama.charAt(0)}
          </div>
          <div>
            <div class="font-bold text-slate-800">${t.nama}</div>
            <div class="text-xs text-slate-500 font-medium uppercase tracking-wide">${t.peran}</div>
          </div>
        </div>
        <p class="text-slate-600 text-sm italic mb-4 leading-relaxed">${t.testimoni}</p>
        <div class="flex text-yellow-400 text-sm">
          ${'★'.repeat(t.rating)}${t.rating < 5 ? '☆'.repeat(5 - t.rating) : ''}
        </div>
      </div>
    `).join('');
  }

  function submitSaran(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const rating = formData.get('rating');
    
    if (rating === '0') {
      alert('Silakan berikan rating terlebih dahulu');
      return;
    }

    const roleMap = { 'fasilitas': 'Member', 'pelayanan': 'Pelanggan', 'booking': 'User App', 'harga': 'Member', 'lainnya': 'Pengunjung' };
    const testimonials = loadTestimonials();
    
    testimonials.unshift({
      nama: formData.get('anonim') ? 'Pengguna' : formData.get('nama'),
      peran: roleMap[formData.get('kategori')] || 'Pelanggan',
      testimoni: `"${formData.get('pesan')}"`,
      rating: parseInt(rating)
    });
    
    saveTestimonials(testimonials);
    displayTestimonials();
    
    document.getElementById('saranSuccess').classList.remove('hidden');
    document.getElementById('saranForm').classList.add('hidden');
  }

  function hideSuccessMessage() {
    document.getElementById('saranSuccess').classList.add('hidden');
    document.getElementById('saranForm').classList.remove('hidden');
    resetSaranForm();
  }

  document.addEventListener('DOMContentLoaded', function() {
    displayTestimonials();
  });
</script>

<?php 
// Memanggil footer.php
require 'include_user/footer.php'; 
?>