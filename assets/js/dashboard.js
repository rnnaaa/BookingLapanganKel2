/** dashboard.js
 * VERSI DIPERBAIKI:
 * - Menghapus logika password (FIXING TYPEERROR)
 * - Memperbaiki logika dropdown profil (FIXING ARIA-HIDDEN)
 * - Memfungsikan 'fetch' (AJAX) pada 'saveProfile()' ke 'update_profile.php'
 */

/* ---------- Mengambil data asli dari PHP ---------- */
const USER = window.INJECTED_USER_DATA || { nama: "User Error", email: "error@mail.com", foto_profil: null, no_hp: '', pekerjaan: '', pekerjaan_lain: '' };
const BOOKINGS = window.INJECTED_BOOKING_DATA || [];

/* ---------- render helpers ---------- */
function $(sel) {
  return document.querySelector(sel);
}
function $all(sel) {
  return Array.from(document.querySelectorAll(sel));
}

// Helper untuk path foto profil
function getAvatarPath(foto_profil) {
    if (foto_profil) {
        // Path dari root, karena dashboard.js ada di assets/js/
        return `uploads/profiles/${foto_profil}`; 
    }
    return 'assets/images/default-avatar.png'; 
}

/* populate header / profile */
document.addEventListener("DOMContentLoaded", () => {
  // header values
  $("#userName").textContent = USER.nama.split(' ')[0]; 
  $("#profileAvatar").src = getAvatarPath(USER.foto_profil);
  $("#todayDate").textContent = new Date().toLocaleDateString("id-ID", { weekday: "long", day: "numeric", month: "long", year: "numeric" });

  // stats
  const total = BOOKINGS.length;
  const active = BOOKINGS.filter((b) => b.status === "menunggu" || b.status === "disetujui").length;
  const hours = BOOKINGS.reduce((s, b) => s + (parseInt(b.total_jam) || 0), 0);
  const lastPayment = BOOKINGS.slice()
    .reverse()
    .find((b) => b.total_amount > 0);
    
  $("#statTotal").textContent = total;
  $("#statActive").textContent = active;
  $("#statHours").textContent = hours + " jam";
  $("#statLastPayment").textContent = lastPayment ? "Rp " + parseFloat(lastPayment.total_amount).toLocaleString('id-ID') : "-";

  // next booking
  const next = BOOKINGS.find((b) => b.status === "disetujui" && new Date(b.tanggal) >= new Date()) || null;
  const nb = $("#nextBookingBox");
  if (next) {
    nb.innerHTML = `<strong>${next.nama_lapangan}</strong><div class="muted">${formatDate(next.tanggal)} • ${next.jam_mulai.substring(0,5)}</div>`;
    nb.classList.remove("muted");
  } else {
    nb.textContent = "Tidak ada jadwal aktif";
  }

  // favorites
  const favMap = BOOKINGS.reduce((m, b) => {
    if (b.status !== "dibatalkan") {
      m[b.nama_lapangan] = (m[b.nama_lapangan] || 0) + 1;
    }
    return m;
  }, {});
  const favs = Object.entries(favMap)
    .sort((a, b) => b[1] - a[1])
    .slice(0, 2);
  const favBox = $("#favFields");
  
  if (favs.length) {
    favBox.innerHTML = favs
      .map(
        (f) => `
      <div class="fav-item">
        <div class="fav-thumb"></div>
        <div class="fav-meta">
          <div class="name">${f[0]}</div>
          <div class="muted">${f[1]}x booking</div>
        </div>
      </div>
    `
      )
      .join("");
    favBox.classList.remove("muted");
  } else {
    favBox.textContent = "Belum ada data favorit";
  }

  // === PERBAIKAN: Logika dropdown (Fixing ARIA-HIDDEN) ===
  const profileToggle = $("#profileToggle");
  const dropdown = $("#profileDropdown");
  if (profileToggle && dropdown) {
      profileToggle.addEventListener("click", (e) => {
        e.stopPropagation();
        const isHidden = dropdown.classList.toggle("show");
        dropdown.setAttribute("aria-hidden", !isHidden);
      });

      document.addEventListener("click", (e) => {
        if (!profileToggle.contains(e.target) && !dropdown.contains(e.target)) {
          dropdown.classList.remove("show");
          dropdown.setAttribute("aria-hidden", "true");
        }
      });
      
      // Mengatur fokus saat dropdown ditutup
      dropdown.addEventListener("transitionend", (e) => {
          if (!dropdown.classList.contains("show")) {
              profileToggle.focus();
          }
      });
  }
  // === AKHIR PERBAIKAN DROPDOWN ===
  
  // Tombol Logout
  if ($("#btnLogout")) {
      $("#btnLogout").addEventListener("click", () => {
        const baseUrl = window.location.origin + "/BookingLapanganKel2";
        if (confirm("Yakin ingin keluar?")) {
          window.location.href = baseUrl + '/auth/php/logout.php';
        }
      });
  }
  
  // Tombol Edit Profil
  if ($("#btnEditProfile")) $("#btnEditProfile").addEventListener("click", openProfileModal);

  // Inisialisasi Modal Profil
  initializeProfileModal();

  // Quick booking button
  if ($("#btnQuickBook")) {
      $("#btnQuickBook").addEventListener("click", () => {
        const baseUrl = window.location.origin + "/BookingLapanganKel2";
        window.location.href = baseUrl + "/BookingPengguna/booking.php";
      });
  }

  // build chart
  buildHourChart();
});

/* ---------- Profile Modal Functions ---------- */
function initializeProfileModal() {
  if (!$("#profileModal")) return;

  // Set initial values
  $("#inputName").value = USER.nama;
  $("#inputEmail").value = USER.email;
  $("#inputPhone").value = USER.no_hp || "";
  $("#profileAvatarLarge").src = getAvatarPath(USER.foto_profil);
  $("#profileNameDisplay").textContent = USER.nama;
  $("#profileEmailDisplay").textContent = USER.email;

  // Set job value
  const jobSelect = $("#inputJob");
  const jobCustom = $("#inputJobCustom");
  
  if (USER.pekerjaan) {
      let jobFound = false;
      jobSelect.querySelectorAll("option").forEach(opt => {
          if (opt.value.toLowerCase() === USER.pekerjaan.toLowerCase()) {
              opt.selected = true;
              jobFound = true;
          }
      });

      if (!jobFound && USER.pekerjaan) {
          jobSelect.value = "Lainnya";
          jobCustom.value = USER.pekerjaan_lain || USER.pekerjaan; 
          jobCustom.style.display = "block";
      } else if (jobSelect.value === 'Lainnya') {
          jobCustom.value = USER.pekerjaan_lain || '';
          jobCustom.style.display = "block";
      }
  }

  // Job selection handler
  jobSelect.addEventListener("change", function () {
    if (this.value === "Lainnya") {
      jobCustom.style.display = "block";
      jobCustom.focus();
    } else {
      jobCustom.style.display = "none";
      jobCustom.value = "";
    }
  });

  // === PERBAIKAN: Logika password dihapus (FIXING TYPEERROR) ===
  // $("#togglePassword").addEventListener("click", ...);
  // $("#toggleConfirmPassword").addEventListener("click", ...);
  // === AKHIR PERBAIKAN ===

  // Avatar edit button
  $(".avatar-edit").addEventListener("click", function () {
    showNotification("Fitur upload foto profil akan datang!", "info");
  });

  // Save profile
  $("#saveProfile").addEventListener("click", saveProfile);

  // Tombol tutup modal
  $("#cancelProfile").addEventListener("click", closeProfileModal);
  $("#closeProfileModal").addEventListener("click", closeProfileModal);
  
  $("#profileModal").addEventListener("click", function(e) {
      if (e.target === $("#profileModal")) {
          closeProfileModal();
      }
  });
}

/**
 * ========================================================
 * FUNGSI SAVEPROFILE (Sudah Benar)
 * Menggunakan Fetch untuk mengirim data ke update_profile.php
 * ========================================================
 */
function saveProfile() {
  const name = $("#inputName").value.trim();
  const email = $("#inputEmail").value.trim();
  const phone = $("#inputPhone").value.trim();
  const jobSelect = $("#inputJob").value;
  const jobCustom = $("#inputJobCustom").value.trim();

  // 1. Validasi Sisi Klien
  if (!name || !email || !phone) {
    showNotification("Nama, email, dan nomor HP harus diisi", "error");
    return;
  }
  if (jobSelect === "Lainnya" && !jobCustom) {
      showNotification("Silakan tulis pekerjaan Anda", "error");
      return;
  }

  // 2. Siapkan FormData
  const formData = new FormData();
  formData.append('nama', name);
  formData.append('email', email);
  formData.append('no_hp', phone);
  formData.append('pekerjaan', jobSelect);
  formData.append('pekerjaan_lain', jobCustom);

  // 3. Kirim ke Server (update_profile.php)
  fetch('update_profile.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.status === 'success') {
      // 4. Jika Sukses
      showNotification(data.message, "success");
      
      // 5. Perbarui UI & data global (USER) secara lokal
      $("#userName").textContent = name.split(' ')[0];
      $("#profileNameDisplay").textContent = name;
      $("#profileEmailDisplay").textContent = email;
      
      // Update data global
      USER.nama = name;
      USER.email = email;
      USER.no_hp = phone;
      USER.pekerjaan = jobSelect;
      USER.pekerjaan_lain = jobCustom;
      
      closeProfileModal();
    } else {
      // 6. Jika Gagal (misal: email duplikat)
      showNotification(data.message, "error");
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showNotification("Terjadi kesalahan. Gagal menghubungi server.", "error");
  });
}

/* ---------- small helper functions ---------- */
function formatDate(d) {
  try {
    const dt = new Date(d + 'T00:00:00'); 
    return dt.toLocaleDateString("id-ID", { day: "2-digit", month: "short", year: "numeric" });
  } catch {
    return d;
  }
}

function openProfileModal() {
  const modal = $("#profileModal");
  if (modal) {
    modal.setAttribute("aria-hidden", "false");
    modal.classList.add("show");
  }
}

function closeProfileModal() {
  const modal = $("#profileModal");
  if (modal) {
    modal.setAttribute("aria-hidden", "true");
    modal.classList.remove("show");
  }
}

function showNotification(message, type = "info") {
  const notification = document.createElement("div");
  notification.className = `notification ${type}`;
  notification.textContent = message;
  document.body.appendChild(notification);
  setTimeout(() => {
    notification.style.animation = "slideInRight 0.3s ease-out reverse";
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 300);
  }, 3000);
}

/* ---------- CHART (Tidak berubah) ---------- */
function buildHourChart() {
  if (!$("#hourChart")) return;

  const hours = ["07:00", "08:00", "09:00", "10:00", "11:00", "12:00", "13:00", "14:00", "15:00", "16:00", "17:00", "18:00", "19:00", "20:00", "21:00"];
  const freq = Array(hours.length).fill(0);
  
  BOOKINGS.forEach((b) => {
    if (b.status !== "dibatalkan" && b.jam_mulai) {
      const hourStart = parseInt(b.jam_mulai.split(":")[0]);
      const idx = hours.findIndex((h) => parseInt(h.split(":")[0]) === hourStart);
      if (idx >= 0) freq[idx] += 1;
    }
  });

  const ctx = document.getElementById("hourChart").getContext("2d");
  new Chart(ctx, {
    type: "bar",
    data: {
      labels: hours,
      datasets: [
        {
          label: "Jumlah Booking",
          data: freq,
          backgroundColor: "#0057D8",
          borderColor: "#0040A0",
          borderWidth: 1,
          borderRadius: 4,
        },
      ],
    },
    options: {
      indexAxis: "x",
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          ticks: { stepSize: 1 },
          grid: { color: "rgba(0,0,0,0.05)" },
        },
        x: {
          grid: { display: false },
        },
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: function (context) {
              return `Booking: ${context.parsed.y} kali`;
            },
          },
        },
      },
      animation: {
        duration: 1000,
        easing: "easeOutQuart",
      },
    },
  });
}