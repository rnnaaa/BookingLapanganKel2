// assets/js/booking-script.js
document.addEventListener("DOMContentLoaded", function () {
  
  // === PATH DINAMIS BERDASARKAN HEADER PHP ===
  // Mengambil base_url yang diset di header.php
  const projectRoot = window.BASE_URL || "/BookingLapanganKel2"; 
  
  const bookingEndpoint = `${projectRoot}/BookingPengguna/booking.php`;
  const processCheckoutEndpoint = `${projectRoot}/BookingPengguna/process_checkout.php`; 
  const loginPage = `${projectRoot}/auth/login.php`; 
  
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

  // === ELEMEN MODAL LOGIN ===
  const loginModal = document.getElementById("loginRequiredModal");
  const btnLoginYes = document.getElementById("btnLoginYes");
  const btnLoginNo = document.getElementById("btnLoginNo");

  function showLoginModal() {
    if (loginModal) loginModal.classList.remove("hidden");
  }

  function hideLoginModal() {
    if (loginModal) loginModal.classList.add("hidden");
  }

  if (btnLoginYes) btnLoginYes.addEventListener("click", () => window.location.href = loginPage);
  if (btnLoginNo) btnLoginNo.addEventListener("click", hideLoginModal);
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
      // Copy semua property
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
            if (cartCount) cartCount.textContent = res.count ?? parseInt(cartCount.textContent || "0") + 1;
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
              if (cartCount) cartCount.textContent = res.count ?? 0;
              
              if ((res.count ?? 0) <= 0) {
                keranjangList.innerHTML = '<p class="text-slate-400">Belum ada jadwal di keranjang.</p>';
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

      // FETCH LANGSUNG KE PATH ABSOLUT
      fetch(processCheckoutEndpoint, { method: 'POST' })
      .then(r => r.json())
      .then(res => {
          if (res.status === 'ok') {
              // Redirect ke URL Absolut juga agar aman
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
  
  // --- 5. LOGOUT (DESKTOP & MOBILE) ---
  const logoutLinks = document.querySelectorAll('a[href*="logout.php"]');
  const logoutModal = document.getElementById("logoutModal");
  const cancelLogoutBtn = document.getElementById("cancelLogoutBtn");
  const confirmLogoutBtn = document.getElementById("confirmLogoutBtn");
  let targetLogoutUrl = "";

  if (logoutLinks.length > 0) {
      logoutLinks.forEach(link => {
          link.addEventListener("click", function (e) {
              e.preventDefault();
              targetLogoutUrl = this.href;
              if (logoutModal) logoutModal.classList.remove("hidden");
          });
      });
  }
  if (cancelLogoutBtn) cancelLogoutBtn.addEventListener("click", () => logoutModal?.classList.add("hidden"));
  if (confirmLogoutBtn) confirmLogoutBtn.addEventListener("click", () => { if (targetLogoutUrl) window.location.href = targetLogoutUrl; });

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
            <button class="text-xs mt-2 text-red-600 remove-item-btn" data-index="${idx}" style="background:none;border:none;cursor:pointer;">Hapus</button>
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

  // Close sidebar on outside click
  document.addEventListener("click", function (e) {
    if (sidebar?.classList.contains("active") && !e.target.closest("#sidebarKeranjang") && !e.target.closest("#cartIcon")) {
      sidebar.classList.remove("active");
    }
  });
});