document.addEventListener("DOMContentLoaded", function () {
  
  // === PATH DINAMIS BERDASARKAN HEADER PHP ===
  const projectRoot = window.BASE_URL || "/BookingLapanganKel2"; 
  
  const bookingEndpoint = `${projectRoot}/BookingPengguna/booking.php`;
  const processCheckoutEndpoint = `${projectRoot}/BookingPengguna/process_checkout.php`; 
  const loginPage = `${projectRoot}/auth/login.php`; 
  
  const USER_ID = window.USER_ID || 0;

  function isLoggedIn() {
    // User dianggap login jika USER_ID ada, bukan 0, dan bukan 1 (User Demo)
    return !(USER_ID === 0 || USER_ID === 1);
  }

  // Elemen DOM
  const cartIcon = document.getElementById("cartIcon");       
  const mobileCartIcon = document.getElementById("mobileCartIcon"); 
  const sidebar = document.getElementById("sidebarKeranjang");
  const closeSidebar = document.getElementById("closeSidebar");
  const keranjangList = document.getElementById("keranjangList");
  const cartCount = document.getElementById("cartCount");
  const mobileCartCount = document.getElementById("mobileCartCount"); 
  const checkoutBtn = document.getElementById("checkoutBtn");

  // === FUNGSI MENAMPILKAN SWEETALERT LOGIN ===
  function showLoginAlert() {
    Swal.fire({
      title: 'Login Diperlukan',
      text: "Anda harus login terlebih dahulu untuk melanjutkan proses checkout.",
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

  // --- 1. LOGIKA KLIK JAM (ADD TO CART) ---
  document.querySelectorAll(".jam-main").forEach((btn) => {
    btn.addEventListener("click", function () {
      const slotData = {
        id_jadwal_waktu: this.dataset.id,
        id_lapangan: this.dataset.lapangan,
        tanggal: this.dataset.tanggal,
        jam: this.dataset.jam,
        harga: this.dataset.harga,
        nama_lapangan: document.querySelector("h1")?.textContent || "Lapangan",
      };

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
            addItemToSidebar(slotData);
            
            // Update Count di Desktop & Mobile
            const newCount = res.count ?? parseInt(cartCount?.textContent || "0") + 1;
            if (cartCount) cartCount.textContent = newCount;
            if (mobileCartCount) mobileCartCount.textContent = newCount;

            if (checkoutBtn) checkoutBtn.disabled = false;
            if (sidebar) sidebar.classList.add("active");
          } else {
            Swal.fire({
                icon: 'warning',
                title: 'Oops...',
                text: res.message || "Gagal menambahkan ke keranjang",
                confirmButtonColor: '#0b63d6',
                confirmButtonText: 'Oke'
            });
          }
        })
        .catch(console.error);
    });
  });

  // --- 2. SIDEBAR TOGGLE (DESKTOP & MOBILE) ---
  function openSidebar(e) {
      e.preventDefault();
      if (sidebar) sidebar.classList.add("active");
  }

  if (cartIcon) cartIcon.addEventListener("click", openSidebar);
  if (mobileCartIcon) mobileCartIcon.addEventListener("click", openSidebar);

  if (closeSidebar) {
    closeSidebar.addEventListener("click", () => {
      if (sidebar) sidebar.classList.remove("active");
    });
  }

  // --- 3. HAPUS ITEM ---
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
                reindexRemoveButtons();
              }
            }
          })
          .catch(console.error);
      }
    });
  }

  // --- 4. CHECKOUT (LOGIKA LOGIN SWEETALERT DI SINI) ---
  if (checkoutBtn) {
    checkoutBtn.addEventListener("click", () => {
      
      // CEK LOGIN DULU
      if (!isLoggedIn()) {
        showLoginAlert(); // Panggil fungsi SweetAlert
        return;           // Hentikan proses checkout
      }

      const originalText = checkoutBtn.innerText;
      checkoutBtn.innerText = "Memproses...";
      checkoutBtn.disabled = true;

      fetch(processCheckoutEndpoint, { method: 'POST' })
      .then(r => r.json())
      .then(res => {
          if (res.status === 'ok') {
              // Redirect ke halaman pembayaran/sukses
              window.location.href = projectRoot + "/BookingPengguna/" + res.redirect.replace('BookingPengguna/', '');
          } else {
              Swal.fire({
                  icon: 'error',
                  title: 'Gagal Checkout',
                  text: res.message,
                  confirmButtonColor: '#d33',
                  allowOutsideClick: false
              }).then(() => location.reload());
              checkoutBtn.innerText = originalText;
              checkoutBtn.disabled = false;
          }
      })
      .catch(err => {
          console.error(err);
          Swal.fire({ icon: 'error', title: 'Kesalahan Sistem', text: 'Gagal menghubungi server.', confirmButtonColor: '#0b63d6'});
          checkoutBtn.innerText = originalText;
          checkoutBtn.disabled = false;
      });
    });
  }
  
  // --- 5. LOGOUT LOGIC ---
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

  // --- 6. CLOSE SIDEBAR ON OUTSIDE CLICK ---
  document.addEventListener("click", function (e) {
    if (sidebar && sidebar.classList.contains("active")) {
        if (!e.target.closest("#sidebarKeranjang") && 
            !e.target.closest("#cartIcon") && 
            !e.target.closest("#mobileCartIcon")) {
            sidebar.classList.remove("active");
        }
    }
  });

  // --- HELPER ---
  function reindexRemoveButtons() {
    keranjangList?.querySelectorAll(".remove-item-btn").forEach((b, i) => (b.dataset.index = i));
    keranjangList?.querySelectorAll(".keranjang-item").forEach((it, i) => (it.dataset.index = i));
  }

  function addItemToSidebar(item) {
    if (!keranjangList) return;
    if (keranjangList.querySelector(".text-slate-400")) keranjangList.innerHTML = '';

    const idx = parseInt(cartCount?.textContent || "0");
    const wrapper = document.createElement("div");
    wrapper.className = "keranjang-item";
    wrapper.setAttribute("data-index", idx);

    wrapper.innerHTML = `
          <div class="left">
            <div class="text-sm font-semibold">${escapeHtml(item.jam)}</div>
            <div class="text-xs text-slate-500">${formatDate(item.tanggal)}</div>
            <div class="text-xs text-slate-500">Lapangan: ${escapeHtml(item.nama_lapangan)}</div>
          </div>
          <div class="right">
            <div class="text-sm font-semibold">Rp ${numberWithCommas(item.harga)}</div>
            <button class="text-xs mt-2 text-red-600 font-medium hover:text-red-800 remove-item-btn" data-index="${idx}" style="background:none;border:none;cursor:pointer;">Hapus</button>
          </div>
        `;
    keranjangList.appendChild(wrapper);
  }

  function numberWithCommas(x) { return parseInt(x || 0).toLocaleString("id-ID"); }
  
  function formatDate(d) {
    try { return new Date(d).toLocaleDateString("id-ID", { day: "2-digit", month: "short", year: "numeric" }); } 
    catch (e) { return d; }
  }

  function escapeHtml(unsafe) {
    return (unsafe || "").replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;");
  }
});