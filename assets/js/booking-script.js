document.addEventListener("DOMContentLoaded", function () {
  
  // === PATH DINAMIS BERDASARKAN HEADER PHP ===
  const projectRoot = window.BASE_URL || "/BookingLapanganKel2"; 
  
  const bookingEndpoint = `${projectRoot}/BookingPengguna/booking.php`;
  const processCheckoutEndpoint = `${projectRoot}/BookingPengguna/process_checkout.php`; 
  const loginPage = `${projectRoot}/auth/login.php`; 
  
  const USER_ID = window.USER_ID || 0;

  function isLoggedIn() {
    return !(USER_ID === 0 || USER_ID === 1);
  }

  // Elemen DOM
  const cartIcon = document.getElementById("cartIcon");       // Desktop
  const mobileCartIcon = document.getElementById("mobileCartIcon"); // Mobile (BARU)
  const sidebar = document.getElementById("sidebarKeranjang");
  const closeSidebar = document.getElementById("closeSidebar");
  const keranjangList = document.getElementById("keranjangList");
  const cartCount = document.getElementById("cartCount");
  const mobileCartCount = document.getElementById("mobileCartCount"); // Count Mobile
  const checkoutBtn = document.getElementById("checkoutBtn");

  // === ELEMEN MODAL LOGIN ===
  const loginModal = document.getElementById("loginRequiredModal");
  const btnLoginYes = document.getElementById("btnLoginYes");
  const btnLoginNo = document.getElementById("btnLoginNo");

  function showLoginModal() {
    if (loginModal) {
        loginModal.classList.remove("hidden");
        // Animasi halus
        setTimeout(() => {
            loginModal.classList.remove("opacity-0", "pointer-events-none");
            const panel = loginModal.querySelector('.modal-panel');
            if(panel) panel.classList.add('scale-100');
        }, 10);
    }
  }

  function hideLoginModal() {
    if (loginModal) {
        loginModal.classList.add("opacity-0", "pointer-events-none");
        const panel = loginModal.querySelector('.modal-panel');
        if(panel) panel.classList.remove('scale-100');
        setTimeout(() => loginModal.classList.add("hidden"), 300);
    }
  }

  if (btnLoginYes) btnLoginYes.addEventListener("click", () => window.location.href = loginPage);
  if (btnLoginNo) btnLoginNo.addEventListener("click", hideLoginModal);
  if (loginModal) {
    loginModal.addEventListener("click", (e) => {
        if (e.target === loginModal) hideLoginModal();
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
  if (mobileCartIcon) mobileCartIcon.addEventListener("click", openSidebar); // Listener untuk Mobile

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
              
              // Update Count Desktop & Mobile
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

  // --- 4. CHECKOUT ---
  if (checkoutBtn) {
    checkoutBtn.addEventListener("click", () => {
      if (!isLoggedIn()) {
        showLoginModal(); 
        return;
      }

      const originalText = checkoutBtn.innerText;
      checkoutBtn.innerText = "Memproses...";
      checkoutBtn.disabled = true;

      fetch(processCheckoutEndpoint, { method: 'POST' })
      .then(r => r.json())
      .then(res => {
          if (res.status === 'ok') {
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
                  iconColor: '#ef4444',
                  showCancelButton: true,
                  confirmButtonText: 'Ya, Keluar',
                  cancelButtonText: 'Batal',
                  reverseButtons: true,
                  customClass: {
                      popup: 'rounded-2xl font-sans', 
                      title: 'text-xl font-bold text-slate-800',
                      htmlContainer: 'text-slate-500',
                      confirmButton: 'bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-sm mx-1',
                      cancelButton: 'bg-white hover:bg-slate-50 text-slate-600 border border-slate-300 font-bold py-2.5 px-6 rounded-lg shadow-sm mx-1'
                  },
                  buttonsStyling: false 
              }).then((result) => {
                  if (result.isConfirmed) {
                      window.location.href = targetUrl;
                  }
              });
          });
      });
  }

  // --- 6. CLOSE SIDEBAR ON OUTSIDE CLICK (PERBAIKAN UTAMA DISINI) ---
  document.addEventListener("click", function (e) {
    // Cek apakah sidebar sedang aktif
    if (sidebar && sidebar.classList.contains("active")) {
        // Jika yang diklik BUKAN sidebar, BUKAN cart desktop, DAN BUKAN cart mobile
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