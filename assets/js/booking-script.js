// assets/js/booking-script.js
// VERSI BARU DENGAN MODAL LOGOUT KUSTOM

document.addEventListener("DOMContentLoaded", function () {
  // === PATH ABSOLUT (Sesuaikan '/BookingLapanganKel2' jika perlu) ===
  const projectRoot = "/BookingLapanganKel2";
  const bookingEndpoint = `${projectRoot}/BookingPengguna/booking.php`;
  const productPage = `${projectRoot}/BookingPengguna/produk_tambahan.php`;
  const loginPage = `${projectRoot}/auth/login.php`; // Halaman login
  // ======================================

  const USER_ID = window.USER_ID || 0;

  function isLoggedIn() {
    if (USER_ID === 0 || USER_ID === 1) {
      return false;
    }
    return true;
  }
  // =======================

  const modal = document.getElementById("bookingModal");
  const closeBookingModal = document.getElementById("closeBookingModal");
  const btnCheckoutModal = document.getElementById("btnCheckout");
  const btnKeranjangModal = document.getElementById("btnKeranjang");
  const cartIcon = document.getElementById("cartIcon");
  const sidebar = document.getElementById("sidebarKeranjang");
  const closeSidebar = document.getElementById("closeSidebar");
  const keranjangList = document.getElementById("keranjangList");
  const cartCount = document.getElementById("cartCount");
  const checkoutBtn = document.getElementById("checkoutBtn");

  let selectedSlot = null;

  // --- LOGIKA HANYA UNTUK HALAMAN BOOKING.PHP ---

  document.querySelectorAll(".jam-main").forEach((btn) => {
    btn.addEventListener("click", function () {
      selectedSlot = {
        id_jadwal_waktu: this.dataset.id,
        id_lapangan: this.dataset.lapangan,
        tanggal: this.dataset.tanggal,
        jam: this.dataset.jam,
        harga: this.dataset.harga,
        nama_lapangan: document.querySelector("h1")?.textContent || "Lapangan",
      };
      if (modal) modal.style.display = "flex";
    });
  });

  if (closeBookingModal) {
    closeBookingModal.addEventListener("click", () => (modal.style.display = "none"));
  }
  window.addEventListener("click", (e) => {
    if (e.target === modal) modal.style.display = "none";
  });

  if (btnCheckoutModal) {
    btnCheckoutModal.addEventListener("click", () => {
      if (!selectedSlot) return;
      if (!isLoggedIn()) {
        alert("Anda harus login terlebih dahulu untuk melanjutkan checkout.");
        window.location.href = loginPage;
        return;
      }
      const data = new URLSearchParams();
      data.append("action", "add_to_cart");
      data.append("id_jadwal_waktu", selectedSlot.id_jadwal_waktu);
      data.append("id_lapangan", selectedSlot.id_lapangan);
      data.append("tanggal", selectedSlot.tanggal);
      data.append("jam", selectedSlot.jam);
      data.append("harga", selectedSlot.harga);
      data.append("nama_lapangan", selectedSlot.nama_lapangan);

      fetch(bookingEndpoint, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: data.toString(),
      })
        .then((r) => r.json())
        .then((res) => {
          if (res.status === "ok" || res.message === "Slot sudah ada di keranjang.") {
            window.location.href = productPage + "?cart=1";
          } else {
            alert(res.message || "Gagal menambahkan ke keranjang");
          }
        })
        .catch((err) => {
          console.error(err);
          alert("Terjadi kesalahan jaringan.");
        });
    });
  }

  if (btnKeranjangModal) {
    btnKeranjangModal.addEventListener("click", () => {
      if (!selectedSlot) return;
      const data = new URLSearchParams();
      data.append("action", "add_to_cart");
      data.append("id_jadwal_waktu", selectedSlot.id_jadwal_waktu);
      data.append("id_lapangan", selectedSlot.id_lapangan);
      data.append("tanggal", selectedSlot.tanggal);
      data.append("jam", selectedSlot.jam);
      data.append("harga", selectedSlot.harga);
      data.append("nama_lapangan", selectedSlot.nama_lapangan);

      fetch(bookingEndpoint, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: data.toString(),
      })
        .then((r) => r.json())
        .then((res) => {
          if (res.status === "ok") {
            addItemToSidebar(selectedSlot);
            if (cartCount) cartCount.textContent = res.count ?? parseInt(cartCount.textContent || "0") + 1;
            if (checkoutBtn) checkoutBtn.disabled = false;
            if (modal) modal.style.display = "none";
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
  }

  // --- LOGIKA UNTUK SEMUA HALAMAN (INDEX & BOOKING) ---

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
          .catch((err) => {
            console.error(err);
            alert("Terjadi kesalahan jaringan (Hapus).");
          });
      }
    });
  }

  if (checkoutBtn) {
    checkoutBtn.addEventListener("click", () => {
      if (!isLoggedIn()) {
        alert("Anda harus login terlebih dahulu untuk melanjutkan checkout.");
        window.location.href = loginPage;
        return;
      }
      window.location.href = productPage + "?cart=1";
    });
  }

  // === PERBAIKAN LOGIKA LOGOUT ===
  const btnLogoutBooking = document.getElementById("btnLogout");
  const logoutModal = document.getElementById("logoutModal");
  const cancelLogoutBtn = document.getElementById("cancelLogoutBtn");
  const confirmLogoutBtn = document.getElementById("confirmLogoutBtn");
  let logoutUrl = ""; // Variabel untuk menyimpan URL logout

  if (btnLogoutBooking) {
    btnLogoutBooking.addEventListener("click", function (e) {
      // 1. Selalu cegah link default
      e.preventDefault();
      // 2. Simpan URL logout dari link <a>
      logoutUrl = this.href;
      // 3. Tampilkan modal kustom (dengan animasi fade-in)
      if (logoutModal) {
        logoutModal.classList.remove("hidden");
        // Tambahkan animasi fade-in saat ditampilkan
        logoutModal.classList.add("animate-fade-in");
      }
    });
  }

  // Fungsi untuk menutup modal logout
  const closeLogoutModal = () => {
    if (logoutModal) {
      logoutModal.classList.add("hidden");
      logoutModal.classList.remove("animate-fade-in");
    }
  };

  // Tambahkan event ke tombol "Batal"
  if (cancelLogoutBtn) {
    cancelLogoutBtn.addEventListener("click", closeLogoutModal);
  }

  // Tambahkan event ke tombol "Ya, Keluar"
  if (confirmLogoutBtn) {
    confirmLogoutBtn.addEventListener("click", () => {
      if (logoutUrl) {
        window.location.href = logoutUrl; // Arahkan ke URL yang disimpan
      }
    });
  }

  // Tambahkan event klik di luar modal (backdrop)
  if (logoutModal) {
    logoutModal.addEventListener("click", (e) => {
      // Cek jika yang diklik adalah backdrop-nya, bukan panel modal
      if (e.target === logoutModal) {
        closeLogoutModal();
      }
    });
  }
  // === AKHIR PERBAIKAN LOGIKA LOGOUT ===

  // --- FUNGSI HELPERS ---
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

document.addEventListener("DOMContentLoaded", () => {
  try {
    const topNav = document.getElementById("topNav");
    const navLine = document.getElementById("navLine");

    if (topNav && navLine) {
      const activeLink = topNav.querySelector(".active");
      if (activeLink) {
        navLine.style.width = `${activeLink.offsetWidth}px`;
        navLine.style.left = `${activeLink.offsetLeft - topNav.offsetLeft}px`;
      }
    }
  } catch (e) {
    // Abaikan error jika elemen tidak ada
  }
});
