document.addEventListener("DOMContentLoaded", function () {
  
  // =========================================================================
  // 1. KONFIGURASI & VARIABEL GLOBAL
  // =========================================================================
  
  // Path Dinamis
  const projectRoot = window.BASE_URL || "/BookingLapanganKel2"; 
  const bookingEndpoint = `${projectRoot}/BookingPengguna/booking.php`;
  const processCheckoutEndpoint = `${projectRoot}/BookingPengguna/process_checkout.php`; 
  const loginPage = `${projectRoot}/auth/login.php`; 
  
  const USER_ID = window.USER_ID || 0;

  // Elemen DOM
  const cartIcon = document.getElementById("cartIcon");       
  const mobileCartIcon = document.getElementById("mobileCartIcon"); 
  const sidebar = document.getElementById("sidebarKeranjang");
  const closeSidebar = document.getElementById("closeSidebar");
  const keranjangList = document.getElementById("keranjangList");
  const cartCount = document.getElementById("cartCount");
  const mobileCartCount = document.getElementById("mobileCartCount"); 
  const checkoutBtn = document.getElementById("checkoutBtn");

  // Cek Status Login
  function isLoggedIn() {
    // User dianggap login jika USER_ID ada, bukan 0, dan bukan 1 (User Demo/Guest)
    return !(USER_ID === 0 || USER_ID === 1);
  }

  // Helper Format Tanggal Indonesia (Senin, 10 Januari 2024)
  function formatDateIndo(dateString) {
      const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
      try {
          const date = new Date(dateString);
          return date.toLocaleDateString('id-ID', options);
      } catch (e) {
          return dateString;
      }
  }

  // Helper Format Angka (Rp 10.000)
  function numberWithCommas(x) { 
      return parseInt(x || 0).toLocaleString("id-ID"); 
  }

  // Helper Escape HTML (Keamanan XSS)
  function escapeHtml(unsafe) {
    return (unsafe || "").replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;");
  }

  // =========================================================================
  // 2. FUNGSI-FUNGSI UTAMA (LOGIKA BISNIS)
  // =========================================================================

  // A. Menampilkan Alert Login
  function showLoginAlert() {
    Swal.fire({
      title: 'Login Diperlukan',
      text: "Anda harus login terlebih dahulu untuk membooking lapangan.",
      icon: 'info',
      showCancelButton: true,
      confirmButtonColor: '#0b63d6',
      cancelButtonColor: '#64748b',
      confirmButtonText: 'Login Sekarang',
      cancelButtonText: 'Nanti Saja',
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = loginPage;
      }
    });
  }

  // B. Eksekusi Checkout (Redirect ke Pembayaran)
  function performCheckout() {
      // Tampilkan Loading
      Swal.fire({
          title: 'Memproses Pesanan',
          text: 'Sedang menyiapkan halaman pembayaran...',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
      });

      fetch(processCheckoutEndpoint, { method: 'POST' })
      .then(r => r.json())
      .then(res => {
          if (res.status === 'ok') {
              // Redirect ke halaman pembayaran
              // Mengambil path dari respons backend dan menyesuaikan dengan projectRoot
              const targetPage = res.redirect.replace('BookingPengguna/', '');
              window.location.href = `${projectRoot}/BookingPengguna/${targetPage}`;
          } else {
              Swal.fire({
                  icon: 'error',
                  title: 'Gagal Checkout',
                  text: res.message,
                  confirmButtonColor: '#d33'
              });
          }
      })
      .catch(err => {
          console.error(err);
          Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal menghubungi server.', confirmButtonColor: '#0b63d6'});
      });
  }

  // C. Proses Booking (API Add to Cart -> Lalu Tentukan Arah)
  function processBooking(slotData, isDirectCheckout) {
      // Jika Direct Checkout, loading akan ditangani performCheckout nanti
      // Jika Masuk Keranjang, tampilkan loading sebentar
      if(!isDirectCheckout) {
          Swal.fire({ title: 'Menyimpan...', didOpen: () => Swal.showLoading() });
      }

      const data = new URLSearchParams();
      data.append("action", "add_to_cart");
      for (const key in slotData) data.append(key, slotData[key]);

      fetch(bookingEndpoint, {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: data.toString(),
      })
      .then((r) => r.json())
      .then((res) => {
          if (res.status === "ok") {
              
              // 1. Update UI (Sidebar & Badge Cart)
              addItemToSidebar(slotData);
              const newCount = res.count ?? parseInt(cartCount?.textContent || "0") + 1;
              if (cartCount) cartCount.textContent = newCount;
              if (mobileCartCount) mobileCartCount.textContent = newCount;
              if (checkoutBtn) checkoutBtn.disabled = false;

              // 2. Percabangan Logika
              if (isDirectCheckout) {
                  // User ingin Langsung Checkout -> Panggil fungsi checkout
                  performCheckout();
              } else {
                  // User ingin Masuk Keranjang -> Tutup Alert, Buka Sidebar
                  Swal.close(); 
                  if (sidebar) sidebar.classList.add("active");
                  
                  // Opsional: Toast Notifikasi Sukses
                  const Toast = Swal.mixin({
                      toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true,
                      didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                      }
                  });
                  Toast.fire({ icon: 'success', title: 'Berhasil masuk keranjang' });
              }

          } else {
              // Gagal (Misal slot sudah diambil orang lain)
              Swal.fire({
                  icon: 'warning',
                  title: 'Gagal',
                  text: res.message || "Gagal menambahkan ke keranjang",
                  confirmButtonColor: '#0b63d6'
              }).then(() => {
                 // Jika error karena 'sudah dibooking', reload agar status slot merah
                 if(res.message && res.message.includes('sudah dibooking')) location.reload();
              });
          }
      })
      .catch(err => {
          console.error(err);
          Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan koneksi.' });
      });
  }

  // D. Menambahkan Item ke HTML Sidebar (Visual Update)
  function addItemToSidebar(item) {
    if (!keranjangList) return;
    // Hapus pesan "Belum ada jadwal" jika ada
    if (keranjangList.querySelector(".text-slate-400")) keranjangList.innerHTML = '';

    const idx = parseInt(cartCount?.textContent || "0"); // Index sementara
    const wrapper = document.createElement("div");
    wrapper.className = "keranjang-item";
    wrapper.setAttribute("data-index", idx);

    wrapper.innerHTML = `
          <div class="left">
            <div class="text-sm font-semibold">${escapeHtml(item.jam)}</div>
            <div class="text-xs text-slate-500">${formatDateIndo(item.tanggal)}</div>
            <div class="text-xs text-slate-500">Lapangan: ${escapeHtml(item.nama_lapangan)}</div>
          </div>
          <div class="right">
            <div class="text-sm font-semibold">Rp ${numberWithCommas(item.harga)}</div>
            <button class="text-xs mt-2 text-red-600 font-medium hover:text-red-800 remove-item-btn" data-index="${idx}" style="background:none;border:none;cursor:pointer;">Hapus</button>
          </div>
        `;
    keranjangList.appendChild(wrapper);
  }

  // =========================================================================
  // 3. EVENT LISTENERS
  // =========================================================================

  // A. KLIK JAM MAIN (PILIHAN: CART vs CHECKOUT)
  document.querySelectorAll(".jam-main").forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.preventDefault(); 

      // 1. Cek Login
      if (!isLoggedIn()) {
         showLoginAlert();
         return;
      }

      // 2. Ambil Data dari Atribut HTML
      const slotData = {
        id_jadwal_waktu: this.dataset.id,
        id_lapangan: this.dataset.lapangan,
        tanggal: this.dataset.tanggal,
        jam: this.dataset.jam,
        harga: this.dataset.harga,
        nama_lapangan: document.querySelector("h1")?.textContent || "Lapangan",
      };

      // 3. Tampilkan SweetAlert Konfirmasi
      Swal.fire({
        title: 'Booking Slot Ini?',
        html: `
            <div class="text-left text-sm space-y-2">
                <p><i class="fa-regular fa-clock w-6"></i> <strong>Jam:</strong> ${slotData.jam}</p>
                <p><i class="fa-regular fa-calendar w-6"></i> <strong>Tanggal:</strong> ${formatDateIndo(slotData.tanggal)}</p>
                <p><i class="fa-solid fa-tag w-6"></i> <strong>Harga:</strong> Rp ${parseInt(slotData.harga).toLocaleString('id-ID')}</p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: '<i class="fa-solid fa-check"></i> Langsung Checkout',
        confirmButtonColor: '#10b981', // Hijau
        denyButtonText: '<i class="fa-solid fa-cart-plus"></i> Masukkan Keranjang', 
        denyButtonColor: '#3b82f6', // Biru
        cancelButtonText: 'Batal',
        cancelButtonColor: '#64748b',
        reverseButtons: false
      }).then((result) => {
        if (result.isConfirmed) {
            // User pilih Langsung Checkout
            processBooking(slotData, true);
        } else if (result.isDenied) {
            // User pilih Masuk Keranjang
            processBooking(slotData, false);
        }
      });
    });
  });

  // B. SIDEBAR TOGGLE
  function openSidebar(e) {
      e.preventDefault();
      if (sidebar) sidebar.classList.add("active");
  }
  if (cartIcon) cartIcon.addEventListener("click", openSidebar);
  if (mobileCartIcon) mobileCartIcon.addEventListener("click", openSidebar);
  if (closeSidebar) closeSidebar.addEventListener("click", () => sidebar.classList.remove("active"));

  // C. TOMBOL CHECKOUT DI SIDEBAR
  if (checkoutBtn) {
    checkoutBtn.addEventListener("click", () => {
      if (!isLoggedIn()) {
        showLoginAlert();
        return;
      }
      // Gunakan fungsi performCheckout yang sama
      performCheckout();
    });
  }

  // D. HAPUS ITEM DARI KERANJANG
  if (keranjangList) {
    keranjangList.addEventListener("click", function (e) {
      if (e.target && e.target.classList.contains("remove-item-btn")) {
        const idx = e.target.dataset.index;
        
        const data = new URLSearchParams();
        data.append("action", "remove_from_cart");
        data.append("index", idx);

        fetch(bookingEndpoint, {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: data.toString(),
        })
          .then((r) => r.json())
          .then((res) => {
            if (res.status === "ok") {
              e.target.closest(".keranjang-item")?.remove();
              
              const newCount = res.count ?? 0;
              if (cartCount) cartCount.textContent = newCount;
              if (mobileCartCount) mobileCartCount.textContent = newCount;
              
              if (newCount <= 0) {
                keranjangList.innerHTML = '<p class="text-slate-400 text-center mt-10">Belum ada jadwal di keranjang.</p>';
                if (checkoutBtn) checkoutBtn.disabled = true;
              } else {
                // Re-index tombol hapus agar urutannya tetap benar
                keranjangList.querySelectorAll(".remove-item-btn").forEach((b, i) => (b.dataset.index = i));
                keranjangList.querySelectorAll(".keranjang-item").forEach((it, i) => (it.dataset.index = i));
              }
            }
          })
          .catch(console.error);
      }
    });
  }

  // E. LOGOUT KONFIRMASI
  const logoutLinks = document.querySelectorAll('a[href*="logout.php"]');
  if (logoutLinks.length > 0) {
      logoutLinks.forEach(link => {
          link.addEventListener("click", function (e) {
              e.preventDefault();
              const targetUrl = this.href;
              Swal.fire({
                  title: 'Konfirmasi Keluar',
                  text: "Apakah Anda yakin ingin keluar dari akun Anda?",
                  icon: 'warning',
                  showCancelButton: true,
                  confirmButtonColor: '#ef4444',
                  cancelButtonColor: '#64748b',
                  confirmButtonText: 'Ya, Keluar',
                  cancelButtonText: 'Batal',
                  reverseButtons: true
              }).then((result) => {
                  if (result.isConfirmed) {
                      window.location.href = targetUrl;
                  }
              });
          });
      });
  }

  // F. TUTUP SIDEBAR JIKA KLIK DI LUAR
  document.addEventListener("click", function (e) {
    if (sidebar && sidebar.classList.contains("active")) {
        if (!e.target.closest("#sidebarKeranjang") && 
            !e.target.closest("#cartIcon") && 
            !e.target.closest("#mobileCartIcon") &&
            !e.target.classList.contains("swal2-confirm") // Biar ga nutup pas klik sweetalert
           ) {
            sidebar.classList.remove("active");
        }
    }
  });

});