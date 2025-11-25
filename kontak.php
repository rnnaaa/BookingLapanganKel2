<?php 
// Memanggil header.php
// (Saya asumsikan path-nya adalah 'include_user' seperti file Anda yang lain)
require 'include_user/header.php'; 
?>

<style>
  /* Animasi hover ikon */
  .contact-icon {
    transition: all 0.3s ease;
  }
  .contact-icon:hover {
    transform: translateY(-5px);
  }
  
  /* Style untuk FAQ details */
  details summary {
    cursor: pointer;
  }
  details summary::-webkit-details-marker {
    display: none;
  }
  details summary:after {
    content: '+';
    float: right;
    font-size: 1.5rem;
    font-weight: bold;
    color: #0b63d6; /* Warna primary Anda */
  }
  details[open] summary:after {
    content: '-';
  }
</style>

<main>
  <section class="py-16 bg-gradient-to-r from-primary to-primaryDark text-white">
    <div class="max-w-7xl mx-auto px-4 text-center">
      <h1 class="text-4xl md:text-5xl font-poppins font-bold mb-4">Hubungi SportField</h1>
      <p class="text-xl text-white/90 max-w-2xl mx-auto">Kami siap membantu Anda dengan segala pertanyaan tentang booking lapangan, fasilitas, dan informasi lainnya</p>
    </div>
  </section>

  <section class="py-20 bg-white">
    <div class="max-w-4xl mx-auto px-4">
      <div class="text-center mb-12">
        <h2 class="text-4xl font-poppins font-bold mb-4">Berikan Saran & Kritik</h2>
        <p class="text-lg text-slate-600">Masukan Anda sangat berharga untuk meningkatkan kualitas layanan kami</p>
      </div>

      <div class="bg-white rounded-2xl shadow-soft p-8">
        <form id="saranForm" onsubmit="submitSaran(event)">
          <div class="grid md:grid-cols-2 gap-4 mb-4">
            <div>
              <label for="nama" class="block text-sm font-medium text-slate-700 mb-2">Nama Lengkap *</label>
              <input 
                type="text" 
                id="nama" 
                name="nama"
                class="w-full p-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all duration-300"
                placeholder="Masukkan nama lengkap"
                required
              >
            </div>
            <div>
              <label for="email" class="block text-sm font-medium text-slate-700 mb-2">Email *</label>
              <input 
                type="email" 
                id="email" 
                name="email"
                class="w-full p-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all duration-300"
                placeholder="email@contoh.com"
                required
              >
            </div>
<<<<<<< HEAD
          </a>
          <div class="hidden lg:flex flex-1 justify-center">
            <ul id="topNav" class="flex gap-8 items-end">
              <li>
                <a href="/BookingLapanganKel2/index.php" class="nav-link px-2 py-1 text-sm transition-colors duration-300">Beranda</a>
              </li>
              <li><a href="#" class="nav-link px-2 py-1 text-sm transition-colors duration-300">Lapangan</a></li>
              <li><a href="kontak.php" class="nav-link px-2 py-1 text-sm transition-colors duration-300 active">Kontak</a></li>
              <li><a href="member.php" class="nav-link px-2 py-1 text-sm transition-colors duration-300">Member</a></li>
              <li><a href="riwayat.php" class="nav-link px-2 py-1 text-sm transition-colors duration-300">Riwayat</a></li>
              <li>
                <a href="#" id="cartIcon" class="cart-btn text-gray-700 hover:text-primary relative cursor-pointer">
                <i class="fa-solid fa-cart-shopping text-lg"></i>
                <span id="cartCount"
                      class="absolute -top-2 -right-2 bg-red-600 text-white text-xs font-semibold rounded-full px-1.5 py-0.5">
                  <?= count($_SESSION['keranjang'] ?? []) ?>
                </span>
                </a>
              </li>           
            </ul>
=======
>>>>>>> c1156a84b9a5c90545d726a175172cd2f8054c82
          </div>

          <div class="mb-4">
            <label for="kategori" class="block text-sm font-medium text-slate-700 mb-2">Kategori *</label>
            <select 
              id="kategori" 
              name="kategori"
              class="w-full p-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all duration-300"
              required
            >
              <option value="" disabled selected>Pilih kategori saran/kritik</option>
              <option value="fasilitas">Fasilitas Lapangan</option>
              <option value="pelayanan">Pelayanan Admin</option>
              <option value="booking">Proses Booking</option>
              <option value="harga">Harga & Promo</option>
              <option value="kebersihan">Kebersihan</option>
              <option value="lainnya">Lainnya</option>
            </select>
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 mb-2">Rating Pengalaman *</label>
            <div class="flex items-center gap-2" id="ratingStars">
              <input type="hidden" id="rating" name="rating" value="0" required>
              <button type="button" class="text-2xl text-slate-300 hover:text-yellow-400 transition-colors" data-rating="1" onclick="setRating(1)">★</button>
              <button type="button" class="text-2xl text-slate-300 hover:text-yellow-400 transition-colors" data-rating="2" onclick="setRating(2)">★</button>
              <button type="button" class="text-2xl text-slate-300 hover:text-yellow-400 transition-colors" data-rating="3" onclick="setRating(3)">★</button>
              <button type="button" class="text-2xl text-slate-300 hover:text-yellow-400 transition-colors" data-rating="4" onclick="setRating(4)">★</button>
              <button type="button" class="text-2xl text-slate-300 hover:text-yellow-400 transition-colors" data-rating="5" onclick="setRating(5)">★</button>
              <span class="ml-2 text-sm text-slate-500" id="ratingText">Pilih rating</span>
            </div>
          </div>

          <div class="mb-4">
            <label for="pesan" class="block text-sm font-medium text-slate-700 mb-2">Pesan / Saran / Kritik *</label>
            <textarea 
              id="pesan" 
              name="pesan"
              rows="4"
              class="w-full p-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all duration-300"
              placeholder="Tuliskan saran, kritik, atau pengalaman Anda menggunakan layanan SportField..."
              required
            ></textarea>
          </div>

          <div class="flex items-center gap-2 mb-6">
            <input 
              type="checkbox" 
              id="anonim" 
              name="anonim"
              class="w-4 h-4 text-primary rounded focus:ring-primary/50 border-slate-300"
            >
            <label for="anonim" class="text-sm text-slate-600">Kirim sebagai anonim (nama tidak akan ditampilkan)</label>
          </div>

          <div class="flex gap-4">
            <button type="button" class="border border-primary text-primary px-4 py-3 rounded-lg text-sm hover:bg-primary hover:text-white transition-all duration-300 flex-1" onclick="resetSaranForm()">Reset Form</button>
            <button type="submit" class="bg-primary text-white px-4 py-3 rounded-lg text-sm hover:bg-primaryDark transition-all duration-300 flex-1">Kirim Saran</button>
          </div>
        </form>
      </div>

      <div id="saranSuccess" class="hidden mt-6 bg-green-50 border border-green-200 rounded-2xl p-6 text-center">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
        </div>
        <h3 class="text-xl font-semibold text-green-800 mb-2">Terima Kasih!</h3>
        <p class="text-green-700">Saran dan kritik Anda telah berhasil dikirim. Kami akan menindaklanjuti masukan Anda untuk meningkatkan layanan SportField.</p>
        <button class="mt-4 text-green-700 hover:text-green-800 font-medium underline" onclick="hideSuccessMessage()">Kirim saran lagi</button>
      </div>
    </div>
  </section>

  <section class="py-20 bg-softGray">
    <div class="max-w-7xl mx-auto px-4">
      <div class="text-center mb-12">
        <h2 class="text-4xl font-poppins font-bold mb-4">Hubungi Kami</h2>
        <p class="text-lg text-slate-600">Pilih cara yang paling nyaman untuk menghubungi tim SportField</p>
      </div>

      <div class="grid md:grid-cols-2 gap-8">
        <a href="https://wa.me/6285234063810" target="_blank" class="block contact-icon">
          <div class="bg-white p-8 rounded-2xl shadow-soft hover:shadow-lift transition-all duration-300 text-center h-full">
            <div class="w-32 h-32 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
              <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M2 21L23 12L2 3V10L17 12L2 14V21Z" fill="#25D366"/>
              </svg>
            </div>
            <h3 class="text-2xl font-semibold mb-4">WhatsApp</h3>
            <p class="text-slate-600 mb-4">Klik untuk chat langsung dengan admin kami</p>
            <div class="text-sm text-slate-500">Respon cepat untuk booking & informasi</div>
          </div>
        </a>

        <a href="mailto:booking@sportfield.id" class="block contact-icon">
          <div class="bg-white p-8 rounded-2xl shadow-soft hover:shadow-lift transition-all duration-300 text-center h-full">
            <div class="w-32 h-32 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
              <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" fill="#4285F4"/>
                <path d="M22 6L12 13L2 6V6C2 4.9 2.9 4 4 4H20C21.1 4 22 4.9 22 6V6Z" fill="#34A853"/>
                <path d="M2 18L12 11L22 18V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V18Z" fill="#FBBC04"/>
                <path d="M22 6L12 13L2 6" fill="#EA4335"/>
              </svg>
            </div>
            <h3 class="text-2xl font-semibold mb-4">Email</h3>
            <p class="text-slate-600 mb-4">Kirim pertanyaan atau permintaan detail via email</p>
            <div class="text-sm text-slate-500">booking@sportfield.id</div>
          </div>
        </a>
      </div>

  <section id="testimoni" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4">
      <div class="text-center mb-12">
        <h2 class="text-4xl font-poppins font-bold mb-4">Apa Kata Mereka?</h2>
        <p class="text-lg text-slate-600">Testimoni dari pelanggan setia SportField</p>
      </div>

      <div class="grid md:grid-cols-3 gap-8" id="testimoniContainer">
        </div>

      <div class="mt-12 text-center">
        <p class="text-slate-600 text-sm">
          <strong>Tips:</strong> Isi form saran & kritik di atas untuk testimoni Anda tampil di sini!
        </p>
      </div>
    </div>
  </section>

  <section id="faq" class="py-20 bg-white">
    <div class="max-w-3xl mx-auto px-4">
      <h2 class="text-4xl font-poppins font-bold text-center mb-12">Pertanyaan Umum</h2>
      
      <div class="space-y-6">
        <details class="p-6 bg-slate-50 rounded-2xl shadow-soft">
          <summary class="font-semibold text-lg cursor-pointer">Bagaimana cara booking?</summary>
          <p class="mt-4 text-slate-600">Pilih lapangan → pilih tanggal & jam → login → konfirmasi & DP via admin.</p>
        </details>
        
        <details class="p-6 bg-slate-50 rounded-2xl shadow-soft">
          <summary class="font-semibold text-lg cursor-pointer">Metode pembayaran?</summary>
          <p class="mt-4 text-slate-600">Transfer bank, e-wallet, atau pembayaran di tempat sesuai ketentuan.</p>
        </details>
        
        <details class="p-6 bg-slate-50 rounded-2xl shadow-soft">
          <summary class="font-semibold text-lg cursor-pointer">Refund?</summary>
          <p class="mt-4 text-slate-600">Refund sesuai syarat & ketentuan; hubungi admin untuk proses.</p>
        </details>
      </div>
    </div>
  </section>

  <section class="py-20 bg-gradient-to-r from-primary to-primaryDark text-white">
    <div class="max-w-3xl mx-auto px-4 text-center">
      <h2 class="text-3xl md:text-4xl font-poppins font-bold mb-4">Siap booking?</h2>
      <h3 class="text-2xl md:text-3xl font-poppins font-bold mb-6">Amankan jadwalmu sekarang juga</h3>
      <p class="text-xl text-white/90 mb-8">Klik booking, login, lalu pilih slot yang tersedia.</p>
      <div class="mt-6">
        <a href="<?= $base_url ?>/BookingPengguna/booking.php" class="bg-white text-primary px-8 py-4 rounded-lg font-semibold text-lg hover:bg-slate-100 transition-all duration-300 inline-block">Lihat Lapangan</a>
      </div>
    </div>
  </section>
</main>

<script>
  // Fungsi untuk mengatur rating ketika user mengklik bintang
  function setRating(value) {
    const ratingInput = document.getElementById('rating');
    const ratingText = document.getElementById('ratingText');
    const stars = document.querySelectorAll('#ratingStars button[data-rating]');

    ratingInput.value = String(value);

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

    const labels = ['Buruk', 'Kurang', 'Cukup', 'Baik', 'Sangat Baik'];
    ratingText.textContent = value + '/5 — ' + (labels[value - 1] || '');
  }

  // Fungsi untuk mereset form saran
  function resetSaranForm() {
    const form = document.getElementById('saranForm');
    if (form) form.reset();

    const ratingInput = document.getElementById('rating');
    if (ratingInput) ratingInput.value = '0';

    const ratingText = document.getElementById('ratingText');
    if (ratingText) ratingText.textContent = 'Pilih rating';
    const stars = document.querySelectorAll('#ratingStars button[data-rating]');
    stars.forEach(btn => {
      btn.classList.remove('text-yellow-400');
      btn.classList.add('text-slate-300');
    });
  }

  // Fungsi untuk menyimpan testimoni ke localStorage
  function saveTestimonials(testimonials) {
    localStorage.setItem('sportfieldTestimonials', JSON.stringify(testimonials));
  }

  // Fungsi untuk memuat testimoni dari localStorage
  function loadTestimonials() {
    const stored = localStorage.getItem('sportfieldTestimonials');
    if (stored) {
      return JSON.parse(stored);
    } else {
      // Data testimoni default jika belum ada
      const defaultTestimonials = [
        {
          nama: 'Ahmad Rizki',
          peran: 'Pengguna Fasilitas',
          testimoni: '"Lapangan futsal berkualitas dengan harga terjangkau. Adminnya ramah dan responsif!"',
          rating: 5
        },
        {
          nama: 'Sari Dewi',
          peran: 'Pelanggan Booking',
          testimoni: '"Proses booking mudah dan cepat. Fasilitas lengkap dan bersih. Recommended banget!"',
          rating: 4
        },
        {
          nama: 'Budi Santoso',
          peran: 'Pengguna Promo',
          testimoni: '"Promo weekend-nya worth it! Lapangan basketnya luas dan nyaman untuk latihan tim."',
          rating: 5
        }
      ];
      saveTestimonials(defaultTestimonials);
      return defaultTestimonials;
    }
  }

  // Fungsi untuk menampilkan testimoni
  function displayTestimonials() {
    const container = document.getElementById('testimoniContainer');
    // Periksa jika container ada di halaman ini
    if (!container) return; 
    
    const testimonials = loadTestimonials();
    
    if (testimonials.length === 0) {
      container.innerHTML = `
        <div class="col-span-3 text-center py-8">
          <p class="text-slate-500">Belum ada testimoni. Jadilah yang pertama memberikan testimoni!</p>
        </div>
      `;
      return;
    }
    
    container.innerHTML = testimonials.map((testimoni, index) => `
      <div class="bg-slate-50 p-8 rounded-2xl shadow-soft hover:shadow-lift hover:scale-105 transform transition duration-300">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-12 h-12 bg-gradient-to-r from-primary to-primaryDark rounded-full flex items-center justify-center text-white font-semibold">
            ${testimoni.nama.charAt(0)}
          </div>
          <div>
            <div class="font-semibold">${testimoni.nama}</div>
            <div class="text-sm text-slate-500">${testimoni.peran}</div>
          </div>
        </div>
        <p class="text-slate-700 italic mb-4">${testimoni.testimoni}</p>
        <div class="flex text-yellow-400">
          ${'★'.repeat(testimoni.rating)}${testimoni.rating < 5 ? '☆'.repeat(5 - testimoni.rating) : ''}
          <span class="text-slate-600 ml-2">${testimoni.rating}.0</span>
        </div>
      </div>
    `).join('');
  }

  // Fungsi untuk menambahkan testimoni baru
  function addTestimonial(nama, kategori, rating, pesan, anonim) {
    const testimonials = loadTestimonials();
    
    const roleMap = {
      'fasilitas': 'Pengguna Fasilitas',
      'pelayanan': 'Pengguna Layanan',
      'booking': 'Pelanggan Booking',
      'harga': 'Pengguna Promo',
      'kebersihan': 'Pengunjung',
      'lainnya': 'Pelanggan'
    };
    
    const newTestimonial = {
      nama: anonim ? 'Pelanggan' : nama,
      peran: roleMap[kategori] || 'Pelanggan',
      testimoni: `"${pesan}"`,
      rating: parseInt(rating)
    };
    
    testimonials.unshift(newTestimonial);
    saveTestimonials(testimonials);
    displayTestimonials();
  }

  // Modifikasi fungsi submitSaran
  function submitSaran(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const rating = formData.get('rating');
    
    if (rating === '0') {
      alert('Silakan berikan rating terlebih dahulu');
      return;
    }
    
    addTestimonial(
      formData.get('nama'),
      formData.get('kategori'),
      rating,
      formData.get('pesan'),
      formData.get('anonim') ? true : false
    );
    
    document.getElementById('saranSuccess').classList.remove('hidden');
    document.getElementById('saranForm').classList.add('hidden');
    
    resetSaranForm();
    
    setTimeout(() => {
      const testimoniSection = document.getElementById('testimoni');
      if (testimoniSection) {
        testimoniSection.scrollIntoView({ behavior: 'smooth' });
      }
    }, 1000);
  }

  // Fungsi untuk menyembunyikan pesan sukses
  function hideSuccessMessage() {
    document.getElementById('saranSuccess').classList.add('hidden');
    document.getElementById('saranForm').classList.remove('hidden');
    resetSaranForm();
  }

  // Panggil displayTestimonials saat halaman dimuat
  // Kita tambahkan listener baru karena script ini inline
  document.addEventListener('DOMContentLoaded', function() {
    displayTestimonials();
  });

  // Catatan: Logika untuk mobile menu (mobileBtn) sudah ditangani
  // oleh 'booking-script.js' yang dimuat oleh 'footer.php'.
</script>

<?php 
// Memanggil footer.php
require 'include_user/footer.php'; 
?>     