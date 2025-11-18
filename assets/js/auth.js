// js/auth.js
document.addEventListener("DOMContentLoaded", function () {
  
  // 1. Logika untuk "Pekerjaan Lainnya"
  const pekerjaan = document.getElementById("pekerjaan");
  const wrapper = document.getElementById("pekerjaan_lain_wrapper");
  
  if (pekerjaan && wrapper) {
    pekerjaan.addEventListener("change", function () {
      wrapper.style.display = this.value === "Lainnya" ? "block" : "none";
    });
    // Pengecekan awal jika user me-refresh halaman
    if (pekerjaan.value === "Lainnya") {
        wrapper.style.display = "block";
    }
  }
  
  // 2. PERBAIKAN: Logika "Tampilkan/Sembunyikan Password"
  document.querySelectorAll('[data-toggle-password]').forEach(button => {
    button.addEventListener('click', function() {
      // Dapatkan elemen input di dalam .password-wrapper
      const wrapper = this.closest('.password-wrapper');
      if (!wrapper) return;
      
      const input = wrapper.querySelector('input');
      if (!input) return;

      // Dapatkan kedua ikon SVG
      const iconEye = button.querySelector('.icon-eye');
      const iconEyeSlash = button.querySelector('.icon-eye-slash');
      
      if (!iconEye || !iconEyeSlash) return;

      // Cek tipe input saat ini
      const isPassword = input.type === 'password';
      
      if (isPassword) {
        // Jika sedang mode password, ubah ke teks
        input.type = 'text';
        iconEye.style.display = 'none'; // Sembunyikan mata terbuka
        iconEyeSlash.style.display = 'block'; // Tampilkan mata tercoret
      } else {
        // Jika sedang mode teks, ubah ke password
        input.type = 'password';
        iconEye.style.display = 'block'; // Tampilkan mata terbuka
        iconEyeSlash.style.display = 'none'; // Sembunyikan mata tercoret
      }
    });
  });

});