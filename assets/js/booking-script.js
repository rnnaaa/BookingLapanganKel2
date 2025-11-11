// assets/js/booking-script.js
// VERSI BARU DENGAN PERBAIKAN "LANGSUNG CHECKOUT"

document.addEventListener('DOMContentLoaded', function() {
    // === PATH ABSOLUT (Sesuaikan '/BookingLapanganKel2' jika perlu) ===
    const projectRoot = '/BookingLapanganKel2';
    const bookingEndpoint = `${projectRoot}/BookingPengguna/booking.php`;
    
    // --- PERBAIKAN: TOMBOL CHECKOUT SEKARANG MENGARAH KE PRODUK TAMBAHAN ---
    const paymentPage = `${projectRoot}/BookingPengguna/produk_tambahan.php`;
    // ======================================

    const modal = document.getElementById('bookingModal');
    const closeBookingModal = document.getElementById('closeBookingModal');
    const btnCheckoutModal = document.getElementById('btnCheckout'); // Tombol "Langsung Checkout"
    const btnKeranjangModal = document.getElementById('btnKeranjang');
    const cartIcon = document.getElementById('cartIcon');
    const sidebar = document.getElementById('sidebarKeranjang');
    const closeSidebar = document.getElementById('closeSidebar');
    const keranjangList = document.getElementById('keranjangList');
    const cartCount = document.getElementById('cartCount');
    const checkoutBtn = document.getElementById('checkoutBtn'); // Tombol "Checkout" di sidebar

    let selectedSlot = null;

    // --- LOGIKA HANYA UNTUK HALAMAN BOOKING.PHP ---

    // Ketika klik slot (hanya ada di booking.php)
    document.querySelectorAll('.jam-main').forEach(btn => {
      btn.addEventListener('click', function() {
        selectedSlot = {
          id_jadwal_waktu: this.dataset.id,
          id_lapangan: this.dataset.lapangan,
          tanggal: this.dataset.tanggal,
          jam: this.dataset.jam,
          harga: this.dataset.harga,
          nama_lapangan: document.querySelector('h1')?.textContent || 'Lapangan'
        };
        if(modal) modal.style.display = 'flex';
      });
    });

    // Tutup modal booking (hanya ada di booking.php)
    if (closeBookingModal) {
        closeBookingModal.addEventListener('click', () => modal.style.display = 'none');
    }
    window.addEventListener('click', (e) => {
        if (e.target === modal) modal.style.display = 'none';
    });

    // === PERBAIKAN DI SINI: FUNGSI TOMBOL "LANGSUNG CHECKOUT" ===
    if (btnCheckoutModal) {
        btnCheckoutModal.addEventListener('click', () => {
          if (!selectedSlot) return;
          
          // 1. Tambahkan dulu ke keranjang via AJAX (agar session terisi)
          const data = new URLSearchParams();
          data.append('action','add_to_cart');
          data.append('id_jadwal_waktu', selectedSlot.id_jadwal_waktu);
          data.append('id_lapangan', selectedSlot.id_lapangan);
          data.append('tanggal', selectedSlot.tanggal);
          data.append('jam', selectedSlot.jam);
          data.append('harga', selectedSlot.harga);
          data.append('nama_lapangan', selectedSlot.nama_lapangan);

          fetch(bookingEndpoint, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: data.toString()
          })
          .then(r => r.json())
          .then(res => {
            if (res.status === 'ok' || res.message === 'Slot sudah ada di keranjang.') {
              // 2. Jika berhasil (atau sudah ada), BARU redirect ke halaman produk
              
              // Kita gunakan ?cart=1 karena itemnya sudah ada di keranjang session
              window.location.href = paymentPage + '?cart=1'; 
              
            } else {
              alert(res.message || 'Gagal menambahkan ke keranjang');
            }
          })
          .catch(err => {
            console.error(err);
            alert('Terjadi kesalahan jaringan.');
          });
        });
    }
    // === AKHIR PERBAIKAN ===

    // Tambah ke keranjang -> AJAX (hanya ada di booking.php)
    if (btnKeranjangModal) {
        btnKeranjangModal.addEventListener('click', () => {
          if (!selectedSlot) return;
          const data = new URLSearchParams();
          data.append('action','add_to_cart');
          data.append('id_jadwal_waktu', selectedSlot.id_jadwal_waktu);
          data.append('id_lapangan', selectedSlot.id_lapangan);
          data.append('tanggal', selectedSlot.tanggal);
          data.append('jam', selectedSlot.jam);
          data.append('harga', selectedSlot.harga);
          data.append('nama_lapangan', selectedSlot.nama_lapangan);

          fetch(bookingEndpoint, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: data.toString()
          })
          .then(r => r.json())
          .then(res => {
            if (res.status === 'ok') {
              addItemToSidebar(selectedSlot); 
              if (cartCount) cartCount.textContent = res.count ?? (parseInt(cartCount.textContent||'0') + 1);
              if (checkoutBtn) checkoutBtn.disabled = false;
              if (modal) modal.style.display = 'none';
              if (sidebar) sidebar.classList.add('active');
            } else {
              alert(res.message || 'Gagal menambahkan ke keranjang');
            }
          })
          .catch(err => {
            console.error(err);
            alert('Terjadi kesalahan jaringan.');
          });
        });
    }


    // --- LOGIKA UNTUK SEMUA HALAMAN (INDEX & BOOKING) ---
    if (cartIcon) {
        cartIcon.addEventListener('click', (e) => {
          e.preventDefault(); 
          if (sidebar) sidebar.classList.add('active');
        });
    }

    if (closeSidebar) {
        closeSidebar.addEventListener('click', () => {
          if (sidebar) sidebar.classList.remove('active');
        });
    }

    if (keranjangList) {
        keranjangList.addEventListener('click', function(e) {
          if (e.target && e.target.classList.contains('remove-item-btn')) {
            const idx = e.target.dataset.index;
            if (!confirm('Hapus item dari keranjang?')) return;
            
            const data = new URLSearchParams();
            data.append('action','remove_from_cart');
            data.append('index', idx);
            
            fetch(bookingEndpoint, { 
                method:'POST', 
                headers: {'Content-Type':'application/x-www-form-urlencoded'}, 
                body: data.toString() 
            })
              .then(r => r.json())
              .then(res => {
                if (res.status === 'ok') {
                  const el = e.target.closest('.keranjang-item');
                  if (el) el.remove();
                  if (cartCount) cartCount.textContent = res.count ?? 0;
                  if ((res.count ?? 0) <= 0) {
                    keranjangList.innerHTML = '<p class="text-slate-400">Belum ada jadwal di keranjang.</p>';
                    if (checkoutBtn) checkoutBtn.disabled = true;
                  } else {
                    reindexRemoveButtons();
                  }
                } else {
                  alert(res.message || 'Gagal menghapus item');
                }
              })
              .catch(err => {
                  console.error(err);
                  alert('Terjadi kesalahan jaringan (Hapus).');
              });
          }
        });
    }

    // Checkout dari sidebar -> redirect ke payment.php
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', () => {
          window.location.href = paymentPage + '?cart=1';
        });
    }

    // --- FUNGSI HELPERS ---
    function reindexRemoveButtons() {
        if (!keranjangList) return;
        const buttons = keranjangList.querySelectorAll('.remove-item-btn');
        buttons.forEach((b,i) => b.dataset.index = i);
        const items = keranjangList.querySelectorAll('.keranjang-item');
        items.forEach((it, i) => it.dataset.index = i);
    }

    function addItemToSidebar(item) {
        if (!keranjangList || !cartCount) return;
        const placeholder = keranjangList.querySelector('.text-slate-400');
        if (placeholder) placeholder.remove();

        const idx = parseInt(cartCount.textContent || '0');
        const wrapper = document.createElement('div');
        wrapper.className = 'keranjang-item';
        wrapper.setAttribute('data-index', idx);
        
        const namaLapanganHTML = item.nama_lapangan 
            ? `<div class="text-xs text-slate-500">Lapangan: ${escapeHtml(item.nama_lapangan)}</div>`
            : '';

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
      return parseInt(x||0).toLocaleString('id-ID');
    }

    function formatDate(d) {
      try {
        const dt = new Date(d + 'T00:00:00');
        const opts = { day:'2-digit', month:'short', year:'numeric' };
        return dt.toLocaleDateString('id-ID', opts);
      } catch (e) {
        return d;
      }
    }

    function escapeHtml(unsafe) {
      return unsafe
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'",'&#039;');
    }
    
    document.addEventListener('click', function(e) {
        if (!sidebar || !cartIcon) return;
        const isSidebarActive = sidebar.classList.contains('active');
        const isClickInsideSidebar = e.target.closest('#sidebarKeranjang');
        const isClickInsideCartIcon = e.target.closest('#cartIcon');
        if (isSidebarActive && !isClickInsideSidebar && !isClickInsideCartIcon) {
            sidebar.classList.remove('active');
        }
    });
});

document.addEventListener("DOMContentLoaded", () => {
    try {
      const topNav = document.getElementById("topNav");
      const navLine = document.getElementById("navLine");
      
      if(topNav && navLine) {
          const activeLink = topNav.querySelector(".active");
          if (activeLink) {
              navLine.style.width = `${activeLink.offsetWidth}px`;
              navLine.style.left = `${activeLink.offsetLeft - topNav.offsetLeft}px`;
          }
      }
    } catch(e) {
      // Abaikan error jika elemen tidak ada
    }
});