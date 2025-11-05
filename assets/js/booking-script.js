// ---------- Client-side Interactivity ----------
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('bookingModal');
    const closeBookingModal = document.getElementById('closeBookingModal');
    const btnCheckoutModal = document.getElementById('btnCheckout');
    const btnKeranjangModal = document.getElementById('btnKeranjang');
    const cartIcon = document.getElementById('cartIcon');
    const sidebar = document.getElementById('sidebarKeranjang');
    const closeSidebar = document.getElementById('closeSidebar');
    const keranjangList = document.getElementById('keranjangList');
    const cartCount = document.getElementById('cartCount');
    const checkoutBtn = document.getElementById('checkoutBtn');

    let selectedSlot = null;

    // Ketika klik slot
    document.querySelectorAll('.jam-main').forEach(btn => {
      btn.addEventListener('click', function() {
        selectedSlot = {
          id_jadwal_waktu: this.dataset.id,
          id_lapangan: this.dataset.lapangan,
          tanggal: this.dataset.tanggal,
          jam: this.dataset.jam,
          harga: this.dataset.harga
        };
        // tampilkan modal
        modal.style.display = 'flex';
      });
    });

    // Tutup modal
    if (closeBookingModal) {
        closeBookingModal.addEventListener('click', () => modal.style.display = 'none');
    }
    window.addEventListener('click', (e) => {
        if (e.target === modal) modal.style.display = 'none';
    });

    // Direct checkout -> redirect ke payment dengan parameter slot
    if (btnCheckoutModal) {
        btnCheckoutModal.addEventListener('click', () => {
          if (!selectedSlot) return;
          // redirect ke payment, payment.php harus siap menerima param single slot
          const params = new URLSearchParams({
            id_jadwal_waktu: selectedSlot.id_jadwal_waktu,
            tanggal: selectedSlot.tanggal,
            id_lapangan: selectedSlot.id_lapangan,
            direct: 1
          });
          window.location.href = 'payment.php?' + params.toString();
        });
    }

    // Tambah ke keranjang -> AJAX POST ke booking.php?action=add_to_cart
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

          fetch('booking.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: data.toString()
          })
          .then(r => r.json())
          .then(res => {
            if (res.status === 'ok') {
              // Update UI: tambahkan item ke sidebar (client-side)
              addItemToSidebar(selectedSlot);
              cartCount.textContent = res.count ?? (parseInt(cartCount.textContent||'0') + 1);
              checkoutBtn.disabled = false;
              // tutup modal
              modal.style.display = 'none';
              // buka sidebar untuk memberi umpan balik
              sidebar.classList.add('active');
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

    // Cart icon klik -> buka sidebar
    if (cartIcon) {
        cartIcon.addEventListener('click', () => {
          sidebar.classList.add('active');
        });
    }

    // Tutup sidebar
    if (closeSidebar) {
        closeSidebar.addEventListener('click', () => {
          sidebar.classList.remove('active');
        });
    }

    // Hapus item (delegation)
    if (keranjangList) {
        keranjangList.addEventListener('click', function(e) {
          if (e.target && e.target.classList.contains('remove-item-btn')) {
            const idx = e.target.dataset.index;
            if (!confirm('Hapus item dari keranjang?')) return;
            const data = new URLSearchParams();
            data.append('action','remove_from_cart');
            data.append('index', idx);
            fetch('booking.php', { method:'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: data.toString() })
              .then(r => r.json())
              .then(res => {
                if (res.status === 'ok') {
                  // remove from DOM
                  const el = e.target.closest('.keranjang-item');
                  if (el) el.remove();
                  cartCount.textContent = res.count ?? Math.max(0, (parseInt(cartCount.textContent||'0') - 1));
                  if ((res.count ?? 0) <= 0) {
                    keranjangList.innerHTML = '<p class="text-slate-400">Belum ada jadwal di keranjang.</p>';
                    checkoutBtn.disabled = true;
                  } else {
                    // re-index remove buttons to match server indices
                    reindexRemoveButtons();
                  }
                } else {
                  alert(res.message || 'Gagal menghapus item');
                }
              });
          }
        });
    }

    // Checkout dari sidebar -> redirect ke payment.php?cart=1
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', () => {
          // Redirect, payment.php harus membaca session keranjang
          window.location.href = 'payment.php?cart=1';
        });
    }

    // helper: tambah item di sidebar DOM (client side)
    function addItemToSidebar(item) {
      // jika placeholder ada, buang
      const placeholder = keranjangList.querySelector('.text-slate-400');
      if (placeholder) placeholder.remove();

      // buat element baru
      const idx = parseInt(cartCount.textContent || '0'); // index di session akan sesuai karena server menambah terakhir
      const wrapper = document.createElement('div');
      wrapper.className = 'keranjang-item';
      wrapper.setAttribute('data-index', idx);
      wrapper.innerHTML = `
        <div class="left">
          <div class="text-sm font-semibold">${escapeHtml(item.jam)}</div>
          <div class="text-xs text-slate-500">${formatDate(item.tanggal)}</div>
          <div class="text-xs text-slate-500">Lapangan: <?= htmlspecialchars($lapangan['nama_lapangan']) ?></div>
        </div>
        <div classright">
          <div class="text-sm font-semibold">Rp ${numberWithCommas(item.harga)}</div>
          <button class="text-xs mt-2 text-red-600 remove-item-btn" data-index="${idx}" style="background:none;border:none;cursor:pointer;">Hapus</button>
        </div>
      `;
      keranjangList.appendChild(wrapper);
    }

    function reindexRemoveButtons() {
      const buttons = keranjangList.querySelectorAll('.remove-item-btn');
      buttons.forEach((b,i) => b.dataset.index = i);
      const items = keranjangList.querySelectorAll('.keranjang-item');
      items.forEach((it, i) => it.dataset.index = i);
    }

    function numberWithCommas(x) {
      // assume x is number or numeric string
      return parseInt(x||0).toLocaleString('id-ID');
    }

    function formatDate(d) {
      try {
        const dt = new Date(d);
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

});

// Script untuk Nav Indicator
document.addEventListener("DOMContentLoaded", () => {
    const topNav = document.getElementById("topNav");
    const navLine = document.getElementById("navLine");
    const activeLink = topNav.querySelector(".active");

    if (activeLink && navLine) {
        navLine.style.width = `${activeLink.offsetWidth}px`;
        navLine.style.left = `${activeLink.offsetLeft - topNav.offsetLeft}px`;
    }
});