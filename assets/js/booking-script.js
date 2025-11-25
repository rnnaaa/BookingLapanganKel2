// assets/js/booking-script.js
document.addEventListener("DOMContentLoaded", function () {
  // === PATH ABSOLUT ===
  const projectRoot = "/BookingLapanganKel2";
  const bookingEndpoint = `${projectRoot}/BookingPengguna/booking.php`;
  const processCheckoutEndpoint = `${projectRoot}/BookingPengguna/process_checkout.php`; 
  const loginPage = `${projectRoot}/auth/login.php`; 
  // ====================

  const USER_ID = window.USER_ID || 0;

  function isLoggedIn() {
    return !(USER_ID === 0 || USER_ID === 1);
  }

  // Elemen DOM
  const cartIcon = document.getElementById("cartIcon");
  const sidebar = document.getElementById("sidebarKeranjang");
  const closeSidebar = document.getElementById("closeSidebar");
  const keranjangList = document.getElementById("keranjangList");
  const cartCount = document.getElementById("cartCount");
  const checkoutBtn = document.getElementById("checkoutBtn");

  // === ELEMEN MODAL LOGIN CUSTOM ===
  const loginModal = document.getElementById("loginRequiredModal");
  const btnLoginYes = document.getElementById("btnLoginYes");
  const btnLoginNo = document.getElementById("btnLoginNo");
  const loginModalContent = document.getElementById("loginModalContent");

  // Fungsi Buka Modal
  function showLoginModal() {
    if (loginModal) {
      loginModal.classList.remove("hidden");
      // Sedikit delay agar transisi CSS berjalan smooth
      setTimeout(() => {
        loginModal.classList.remove("opacity-0", "pointer-events-none");
        loginModalContent.classList.remove("scale-95");
        loginModalContent.classList.add("scale-100");
      }, 10);
    }
  }

  // Fungsi Tutup Modal
  function hideLoginModal() {
    if (loginModal) {
      loginModal.classList.add("opacity-0", "pointer-events-none");
      loginModalContent.classList.remove("scale-100");
      loginModalContent.classList.add("scale-95");
      setTimeout(() => {
        loginModal.classList.add("hidden");
      }, 300); // Sesuaikan dengan durasi transition CSS
    }
  }

  // Event Listener Tombol Modal
  if (btnLoginYes) {
    btnLoginYes.addEventListener("click", () => {
      window.location.href = loginPage;
    });
  }
  if (btnLoginNo) {
    btnLoginNo.addEventListener("click", hideLoginModal);
  }
  // Tutup jika klik di luar area modal (backdrop)
  if (loginModal) {
    loginModal.addEventListener("click", (e) => {
        if (e.target === loginModal) hideLoginModal();
    });
  }

  // --- 1. LOGIKA KLIK JAM ---
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
      data.append("id_jadwal_waktu", slotData.id_jadwal_waktu);
      data.append("id_lapangan", slotData.id_lapangan);
      data.append("tanggal", slotData.tanggal);
      data.append("jam", slotData.jam);
      data.append("harga", slotData.harga);
      data.append("nama_lapangan", slotData.nama_lapangan);

      fetch(bookingEndpoint, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: data.toString(),
      })
        .then((r) => r.json())
        .then((res) => {
          if (res.status === "ok") {
            addItemToSidebar(slotData);
            if (cartCount) cartCount.textContent = res.count ?? parseInt(cartCount.textContent || "0") + 1;
            if (checkoutBtn) checkoutBtn.disabled = false;
            if (sidebar) sidebar.classList.add("active");
          } else {
            alert(res.message || "Gagal menambahkan ke keranjang");
          }
        })
        .catch((err) => {
          console.error(err);
          alert("Terjadi kesalahan jaringan.");
        });
    });
  });

  // --- 2. SIDEBAR TOGGLE ---
  if (cartIcon) {
    cartIcon.addEventListener("click", (e) => {
      e.preventDefault();
      if (sidebar) sidebar.classList.add("active");
    });
  }
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
        if (!confirm("Hapus item dari keranjang?")) return;

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
              const el = e.target.closest(".keranjang-item");
              if (el) el.remove();
              if (cartCount) cartCount.textContent = res.count ?? 0;
              if ((res.count ?? 0) <= 0) {
                keranjangList.innerHTML = '<p class="text-slate-400">Belum ada jadwal di keranjang.</p>';
                if (checkoutBtn) checkoutBtn.disabled = true;
              } else {
                reindexRemoveButtons();
              }
            } else {
              alert(res.message || "Gagal menghapus item");
            }
          })
          .catch((err) => console.error(err));
      }
    });
  }

  // --- 4. TOMBOL CHECKOUT (MODIFIED) ---
  if (checkoutBtn) {
    checkoutBtn.addEventListener("click", () => {
      
      // === PERUBAHAN UTAMA DI SINI ===
      // Jika belum login, tampilkan MODAL, bukan alert
      if (!isLoggedIn()) {
        showLoginModal(); // Panggil fungsi modal custom
        return;
      }
      // ===============================

      const originalText = checkoutBtn.innerText;
      checkoutBtn.innerText = "Memproses...";
      checkoutBtn.disabled = true;

      fetch(processCheckoutEndpoint, { method: 'POST' })
      .then(r => r.json())
      .then(res => {
          if (res.status === 'ok') {
              window.location.href = res.redirect;
          } else {
              alert(res.message);
              checkoutBtn.innerText = originalText;
              checkoutBtn.disabled = false;
              location.reload(); 
          }
      })
      .catch(err => {
          console.error(err);
          alert("Gagal memproses checkout.");
          checkoutBtn.innerText = originalText;
          checkoutBtn.disabled = false;
      });
    });
  }

  // --- 5. LOGOUT LOGIC ---
  const btnLogoutBooking = document.getElementById("btnLogout");
  const logoutModal = document.getElementById("logoutModal");
  const cancelLogoutBtn = document.getElementById("cancelLogoutBtn");
  const confirmLogoutBtn = document.getElementById("confirmLogoutBtn");
  let logoutUrl = "";

  if (btnLogoutBooking) {
    btnLogoutBooking.addEventListener("click", function (e) {
      e.preventDefault();
      logoutUrl = this.href;
      if (logoutModal) {
        logoutModal.classList.remove("hidden");
        logoutModal.classList.add("animate-fade-in");
      }
    });
  }
  const closeLogoutModal = () => {
    if (logoutModal) {
      logoutModal.classList.add("hidden");
      logoutModal.classList.remove("animate-fade-in");
    }
  };
  if (cancelLogoutBtn) cancelLogoutBtn.addEventListener("click", closeLogoutModal);
  if (confirmLogoutBtn) confirmLogoutBtn.addEventListener("click", () => { if (logoutUrl) window.location.href = logoutUrl; });
  if (logoutModal) logoutModal.addEventListener("click", (e) => { if (e.target === logoutModal) closeLogoutModal(); });

  // --- HELPER FUNCTIONS ---
  function reindexRemoveButtons() {
    if (!keranjangList) return;
    const buttons = keranjangList.querySelectorAll(".remove-item-btn");
    buttons.forEach((b, i) => (b.dataset.index = i));
    const items = keranjangList.querySelectorAll(".keranjang-item");
    items.forEach((it, i) => (it.dataset.index = i));
  }

  function addItemToSidebar(item) {
    if (!keranjangList || !cartCount) return;
    const placeholder = keranjangList.querySelector(".text-slate-400");
    if (placeholder) placeholder.remove();

    const idx = parseInt(cartCount.textContent || "0");
    const wrapper = document.createElement("div");
    wrapper.className = "keranjang-item";
    wrapper.setAttribute("data-index", idx);

    const namaLapanganHTML = item.nama_lapangan ? `<div class="text-xs text-slate-500">Lapangan: ${escapeHtml(item.nama_lapangan)}</div>` : "";

    wrapper.innerHTML = `
          <div class="left">
            <div class="text-sm font-semibold">${escapeHtml(item.jam)}</div>
            <div class="text-xs text-slate-500">${formatDate(item.tanggal)}</div>
            ${namaLapanganHTML} 
          </div>
          <div class="right">
            <div class="text-sm font-semibold">Rp ${numberWithCommas(item.harga)}</div>
            <button class="text-xs mt-2 text-red-600 remove-item-btn" data-index="${idx}" style="background:none;border:none;cursor:pointer;">Hapus</button>
          </div>
        `;
    keranjangList.appendChild(wrapper);
  }

  function numberWithCommas(x) {
    return parseInt(x || 0).toLocaleString("id-ID");
  }

  function formatDate(d) {
    try {
      const dt = new Date(d + "T00:00:00");
      const opts = { day: "2-digit", month: "short", year: "numeric" };
      return dt.toLocaleDateString("id-ID", opts);
    } catch (e) {
      return d;
    }
  }

  function escapeHtml(unsafe) {
    return unsafe.replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll('"', "&quot;").replaceAll("'", "&#039;");
  }

  document.addEventListener("click", function (e) {
    if (!sidebar || !cartIcon) return;
    const isSidebarActive = sidebar.classList.contains("active");
    const isClickInsideSidebar = e.target.closest("#sidebarKeranjang");
    const isClickInsideCartIcon = e.target.closest("#cartIcon");
    if (isSidebarActive && !isClickInsideSidebar && !isClickInsideCartIcon) {
      sidebar.classList.remove("active");
    }
  });
});