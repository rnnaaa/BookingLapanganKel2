/** dashboard.js
 * VERSI DIPERBAIKI:
 * Mengambil data dari 'window.INJECTED_USER_DATA'
 * Menyesuaikan logika pop-up agar sesuai dengan CSS
 * Memfungsikan tombol logout
 */

/* ---------- Mengambil data asli dari PHP ---------- */
// Data ini disuntikkan oleh DashPengguna.php
const USER = window.INJECTED_USER_DATA || { nama: "User Error", email: "error@mail.com", foto_profil: null, no_hp: '', pekerjaan: '' };
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
        return `../uploads/profiles/${foto_profil}`; // Asumsi path
    }
    return '../assets/images/default-avatar.png'; // Path default
}

/* populate header / profile */
document.addEventListener("DOMContentLoaded", () => {
  // header values (dari data asli)
  $("#userName").textContent = USER.nama.split(' ')[0]; // Ambil nama depan
  $("#profileAvatar").src = getAvatarPath(USER.foto_profil);
  $("#todayDate").textContent = new Date().toLocaleDateString("id-ID", { weekday: "long", day: "numeric", month: "long", year: "numeric" });

  // stats (dari data asli)
  const total = BOOKINGS.length;
  // Status 'disetujui' atau 'menunggu'
  const active = BOOKINGS.filter((b) => b.status === "menunggu" || b.status === "disetujui").length;
  const hours = BOOKINGS.reduce((s, b) => s + (parseInt(b.total_jam) || 0), 0);
  const lastPayment = BOOKINGS.slice()
    .reverse()
    .find((b) => b.total_amount > 0);
    
  $("#statTotal").textContent = total;
  $("#statActive").textContent = active;
  $("#statHours").textContent = hours + " jam";
  $("#statLastPayment").textContent = lastPayment ? "Rp " + parseFloat(lastPayment.total_amount).toLocaleString('id-ID') : "-";

  // next booking (dari data asli)
  const next = BOOKINGS.find((b) => b.status === "disetujui" && new Date(b.tanggal) >= new Date()) || null;
  const nb = $("#nextBookingBox");
  if (next) {
    nb.innerHTML = `<strong>${next.nama_lapangan}</strong><div class="muted">${formatDate(next.tanggal)} • ${next.jam_mulai.substring(0,5)}</div>`;
    nb.classList.remove("muted");
  } else {
    nb.textContent = "Tidak ada jadwal aktif";
  }

  // favorites (dari data asli)
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

  // === PERBAIKAN: Logika dropdown disesuaikan dengan CSS (menggunakan .show) ===
  const profileToggle = $("#profileToggle");
  const dropdown = $("#profileDropdown");
  if (profileToggle && dropdown) {
      profileToggle.addEventListener("click", (e) => {
        e.stopPropagation();
        dropdown.classList.toggle("show"); // 'show' akan memicu CSS
      });

      document.addEventListener("click", (e) => {
        if (!profileToggle.contains(e.target) && !dropdown.contains(e.target)) {
          dropdown.classList.remove("show");
        }
      });
  }
  
  // === PERBAIKAN: Tombol Logout difungsikan ===
  if ($("#btnLogout")) {
      $("#btnLogout").addEventListener("click", () => {
        if (confirm("Yakin ingin keluar?")) {
          window.location.href = 'auth/php/logout.php';
        }
      });
  }
  
  if ($("#btnEditProfile")) $("#btnEditProfile").addEventListener("click", openProfileModal);

  // profile modal initialization
  initializeProfileModal();

  // Quick booking button
  if ($("#btnQuickBook")) {
      $("#btnQuickBook").addEventListener("click", () => {
        window.location.href = "booking.php"; // Mengarahkan ke booking.php
      });
  }

  // Navigation buttons
  if ($("#nav-jadwal")) {
      $("#nav-jadwal").addEventListener("click", (e) => {
        e.preventDefault();
        window.location.href = "booking.php";
      });
  }
  if ($("#nav-pembayaran")) {
      $("#nav-pembayaran").addEventListener("click", (e) => {
        e.preventDefault();
        showNotification("Membuka halaman Pembayaran...", "info");
      });
  }

  // Promo button
  if ($(".promo-content .primary")) {
    $(".promo-content .primary").addEventListener("click", () => {
      showNotification("Menampilkan detail promo...", "info");
    });
  }

  // build chart
  buildHourChart();
});

/* ---------- Profile Modal Functions ---------- */
function initializeProfileModal() {
  if (!$("#profileModal")) return;

  // Set initial values (dari data asli)
  $("#inputName").value = USER.nama;
  $("#inputEmail").value = USER.email;
  $("#inputPhone").value = USER.no_hp || "";
  $("#profileAvatarLarge").src = getAvatarPath(USER.foto_profil);
  $("#profileNameDisplay").textContent = USER.nama;
  $("#profileEmailDisplay").textContent = USER.email;

  // Set job value (dari data asli)
  const jobSelect = $("#inputJob");
  const jobCustom = $("#inputJobCustom");
  
  if (USER.pekerjaan) {
      let jobFound = false;
      // Cek apakah ada di <option>
      jobSelect.querySelectorAll("option").forEach(opt => {
          if (opt.value.toLowerCase() === USER.pekerjaan.toLowerCase()) {
              opt.selected = true;
              jobFound = true;
          }
      });

      // Jika tidak ada di <option>
      if (!jobFound) {
          jobSelect.value = "Lainnya";
          jobCustom.value = USER.pekerjaan_lain || USER.pekerjaan;
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

  // Password toggle
  $("#togglePassword").addEventListener("click", function () {
    const passwordInput = $("#inputPassword");
    const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
    passwordInput.setAttribute("type", type);
    this.textContent = type === "password" ? "👁️" : "🙈";
  });

  $("#toggleConfirmPassword").addEventListener("click", function () {
    const passwordInput = $("#inputConfirmPassword");
    const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
    passwordInput.setAttribute("type", type);
    this.textContent = type === "password" ? "👁️" : "🙈";
  });

  // Avatar edit button
  $(".avatar-edit").addEventListener("click", function () {
    showNotification("Fitur upload foto profil akan datang!", "info");
    // Di sini Anda akan memicu <input type="file">
  });

  // Save profile
  $("#saveProfile").addEventListener("click", saveProfile);

  // Tombol tutup modal
  $("#cancelProfile").addEventListener("click", closeProfileModal);
  $("#closeProfileModal").addEventListener("click", closeProfileModal);
  
  // Klik background untuk menutup
  $("#profileModal").addEventListener("click", function(e) {
      if (e.target === $("#profileModal")) {
          closeProfileModal();
      }
  });
}

function saveProfile() {
  const name = $("#inputName").value.trim();
  const email = $("#inputEmail").value.trim();
  const phone = $("#inputPhone").value.trim();
  const jobSelect = $("#inputJob").value;
  const jobCustom = $("#inputJobCustom").value.trim();
  const password = $("#inputPassword").value;
  const confirmPassword = $("#inputConfirmPassword").value;

  // Basic validation
  if (!name || !email || !phone) {
    showNotification("Nama, email, dan nomor HP harus diisi", "error");
    return;
  }
  if (password && password !== confirmPassword) {
    showNotification("Konfirmasi password tidak sesuai", "error");
    return;
  }
  if (password && password.length < 6) {
    showNotification("Password minimal 6 karakter", "error");
    return;
  }
  let finalJob = (jobSelect === "Lainnya") ? jobCustom : jobSelect;
  if (jobSelect === "Lainnya" && !jobCustom) {
      showNotification("Silakan tulis pekerjaan Anda", "error");
      return;
  }

  // PENTING:
  // Di sini Anda harusnya mengambil data form dan mengirimkannya
  // ke file PHP (misal 'update_profile.php') menggunakan AJAX/fetch
  // untuk MENYIMPAN ke database.
  
  // Untuk saat ini, kita hanya simulasi sukses:
  showNotification("Profil berhasil diperbarui! (Simulasi)", "success");
  
  // Update UI secara lokal (simulasi)
  $("#userName").textContent = name.split(' ')[0];
  $("#profileNameDisplay").textContent = name;
  $("#profileEmailDisplay").textContent = email;
  
  closeProfileModal();
}

/* ---------- small helper functions ---------- */
function formatDate(d) {
  try {
    const dt = new Date(d + 'T00:00:00'); // Pastikan zona waktu benar
    return dt.toLocaleDateString("id-ID", { day: "2-digit", month: "short", year: "numeric" });
  } catch {
    return d;
  }
}

// === PERBAIKAN: Fungsi modal disesuaikan dengan CSS (menggunakan .show) ===
function openProfileModal() {
  const modal = $("#profileModal");
  if (modal) {
    modal.setAttribute("aria-hidden", "false");
    modal.classList.add("show"); // Menjalankan animasi fadeIn
  }
}

function closeProfileModal() {
  const modal = $("#profileModal");
  if (modal) {
    modal.setAttribute("aria-hidden", "true");
    modal.classList.remove("show"); // Menghapus kelas show
  }
}
// === AKHIR PERBAIKAN ===

function showNotification(message, type = "info") {
  const notification = document.createElement("div");
  notification.className = `notification ${type}`;
  // Style notifikasi sudah ada di dashboard.css, tidak perlu inline style
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

/* ---------- CHART (Menggunakan data asli) ---------- */
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