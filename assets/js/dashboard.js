/** dashboard.js
 * Simple frontend-only dashboard (dummy data).
 * Later: replace dummy data with API calls.
 */

/* ---------- sample user & booking data (replace with real API) ---------- */
const USER = {
  name: "Andi Pratama",
  email: "andi@mail.com",
  phone: "08123456789",
  job: "Mahasiswa",
  avatar: "https://api.dicebear.com/7.x/avataaars/svg?seed=Andi",
  password: "password123", // In a real app, this would be hashed and stored securely
};

const BOOKINGS = [
  { id: "BK121", date: "2025-10-30", time: "18:00-19:00", field: "Lapangan A", status: "done", hours: 1, amount: 80000 },
  { id: "BK120", date: "2025-10-29", time: "20:00-21:00", field: "Lapangan B", status: "pending", hours: 1, amount: 100000 },
  { id: "BK119", date: "2025-10-27", time: "17:00-18:00", field: "Lapangan A", status: "cancelled", hours: 1, amount: 0 },
  { id: "BK118", date: "2025-10-25", time: "19:00-20:00", field: "Lapangan C", status: "done", hours: 1, amount: 90000 },
  { id: "BK117", date: "2025-10-23", time: "16:00-17:00", field: "Lapangan A", status: "done", hours: 1, amount: 80000 },
  { id: "BK116", date: "2025-10-20", time: "18:00-19:00", field: "Lapangan A", status: "done", hours: 1, amount: 80000 },
  { id: "BK115", date: "2025-10-18", time: "19:00-20:00", field: "Lapangan B", status: "done", hours: 1, amount: 100000 },
  { id: "BK114", date: "2025-10-15", time: "17:00-18:00", field: "Lapangan A", status: "done", hours: 1, amount: 80000 },
];

/* ---------- render helpers ---------- */
function $(sel) {
  return document.querySelector(sel);
}
function $all(sel) {
  return Array.from(document.querySelectorAll(sel));
}

/* populate header / profile */
document.addEventListener("DOMContentLoaded", () => {
  // header values
  $("#userName").textContent = USER.name;
  $("#profileAvatar").src = USER.avatar;
  $("#todayDate").textContent = new Date().toLocaleDateString("id-ID", { weekday: "long", day: "numeric", month: "long", year: "numeric" });

  // stats
  const total = BOOKINGS.length;
  const active = BOOKINGS.filter((b) => b.status === "pending").length;
  const hours = BOOKINGS.reduce((s, b) => s + (b.hours || 0), 0);
  const lastPayment = BOOKINGS.slice()
    .reverse()
    .find((b) => b.amount > 0);
  $("#statTotal").textContent = total;
  $("#statActive").textContent = active;
  $("#statHours").textContent = hours + " jam";
  $("#statLastPayment").textContent = lastPayment ? "Rp " + lastPayment.amount.toLocaleString() : "-";

  // next booking
  const next = BOOKINGS.find((b) => b.status === "pending") || null;
  const nb = $("#nextBookingBox");
  if (next) {
    nb.innerHTML = `<strong>${next.field}</strong><div class="muted">${formatDate(next.date)} • ${next.time}</div>`;
    nb.classList.remove("muted");
  } else {
    nb.textContent = "Tidak ada jadwal aktif";
  }

  // favorites (count field frequency)
  const favMap = BOOKINGS.reduce((m, b) => {
    if (b.status !== "cancelled") {
      m[b.field] = (m[b.field] || 0) + 1;
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

  // profile dropdown toggle
  const profileToggle = $("#profileToggle");
  const dropdown = $("#profileDropdown");
  profileToggle.addEventListener("click", (e) => {
    e.stopPropagation();
    dropdown.classList.toggle("show");
  });

  document.addEventListener("click", (e) => {
    if (!profileToggle.contains(e.target) && !dropdown.contains(e.target)) {
      dropdown.classList.remove("show");
    }
  });

  // dropdown actions
  $("#btnEditProfile").addEventListener("click", openProfileModal);
  $("#btnLogout").addEventListener("click", () => {
    if (confirm("Yakin ingin keluar?")) {
      // In a real app, you would clear session/token and redirect to login
      alert("Anda telah keluar (simulasi)");
    }
  });

  // profile modal initialization
  initializeProfileModal();

  // Quick booking button
  $("#btnQuickBook").addEventListener("click", () => {
    showNotification("Membuka halaman booking...", "info");
    // In a real app: window.location.href = "booking.html";
  });

  // Navigation buttons
  $("#nav-jadwal").addEventListener("click", () => {
    showNotification("Membuka halaman Jadwal...", "info");
    // In a real app: window.location.href = "jadwal.html";
  });

  $("#nav-pembayaran").addEventListener("click", () => {
    showNotification("Membuka halaman Pembayaran...", "info");
    // In a real app: window.location.href = "pembayaran.html";
  });

  // Promo button
  $(".promo-content .primary").addEventListener("click", () => {
    showNotification("Menampilkan detail promo...", "info");
  });

  // build chart
  buildHourChart();
});

/* ---------- Profile Modal Functions ---------- */
function initializeProfileModal() {
  // Set initial values
  $("#inputName").value = USER.name;
  $("#inputEmail").value = USER.email;
  $("#inputPhone").value = USER.phone;
  $("#profileAvatarLarge").src = USER.avatar;
  $("#profileNameDisplay").textContent = USER.name;
  $("#profileEmailDisplay").textContent = USER.email;

  // Set job value
  if (USER.job) {
    if (USER.job === "Mahasiswa" || USER.job === "Pelajar" || USER.job === "Pegawai Swasta" || USER.job === "PNS" || USER.job === "Wiraswasta" || USER.job === "Freelancer") {
      $("#inputJob").value = USER.job;
    } else {
      $("#inputJob").value = "Lainnya";
      $("#inputJobCustom").value = USER.job;
      $("#inputJobCustom").style.display = "block";
    }
  }

  // Job selection handler
  $("#inputJob").addEventListener("change", function () {
    if (this.value === "Lainnya") {
      $("#inputJobCustom").style.display = "block";
      $("#inputJobCustom").focus();
    } else {
      $("#inputJobCustom").style.display = "none";
      $("#inputJobCustom").value = "";
    }
  });

  // Password toggle functionality
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
  });

  // Save profile
  $("#saveProfile").addEventListener("click", saveProfile);

  $("#cancelProfile").addEventListener("click", closeProfileModal);
  $("#closeProfileModal").addEventListener("click", closeProfileModal);
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

  // Determine job value
  let finalJob = "";
  if (jobSelect === "Lainnya") {
    if (!jobCustom) {
      showNotification("Silakan tulis pekerjaan Anda", "error");
      return;
    }
    finalJob = jobCustom;
  } else if (jobSelect) {
    finalJob = jobSelect;
  }

  // Update user data
  USER.name = name;
  USER.email = email;
  USER.phone = phone;
  USER.job = finalJob;

  if (password) {
    USER.password = password; // In a real app, this would be hashed
  }

  // Update UI
  $("#userName").textContent = USER.name;
  $("#profileNameDisplay").textContent = USER.name;
  $("#profileEmailDisplay").textContent = USER.email;

  closeProfileModal();
  showNotification("Profil berhasil diperbarui!", "success");
}

/* ---------- small helper functions ---------- */
function formatDate(d) {
  try {
    const dt = new Date(d);
    return dt.toLocaleDateString("id-ID", { day: "2-digit", month: "short", year: "numeric" });
  } catch {
    return d;
  }
}

function openProfileModal() {
  $("#profileModal").classList.add("show");
  $("#profileModal").setAttribute("aria-hidden", "false");

  // Reset password fields when opening modal
  $("#inputPassword").value = "";
  $("#inputConfirmPassword").value = "";
}

function closeProfileModal() {
  $("#profileModal").classList.remove("show");
  $("#profileModal").setAttribute("aria-hidden", "true");
}

function showNotification(message, type = "info") {
  // Create notification element
  const notification = document.createElement("div");
  notification.className = `notification ${type}`;
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: ${type === "success" ? "#10B981" : type === "error" ? "#EF4444" : "#0057D8"};
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 1000;
    animation: slideInRight 0.3s ease-out;
    max-width: 300px;
  `;
  notification.textContent = message;

  document.body.appendChild(notification);

  // Remove notification after 3 seconds
  setTimeout(() => {
    notification.style.animation = "fadeIn 0.3s ease-out reverse";
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 300);
  }, 3000);
}

/* ---------- CHART (horizontal bar for better clarity) ---------- */
function buildHourChart() {
  // Prepare hours data 07..22
  const hours = ["07:00", "08:00", "09:00", "10:00", "11:00", "12:00", "13:00", "14:00", "15:00", "16:00", "17:00", "18:00", "19:00", "20:00", "21:00"];

  // Calculate frequency of bookings per hour
  const freq = Array(hours.length).fill(0);
  BOOKINGS.forEach((b) => {
    if (b.status !== "cancelled") {
      const hourStart = parseInt(b.time.split(":")[0]);
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
          ticks: {
            stepSize: 1,
          },
          grid: {
            color: "rgba(0,0,0,0.05)",
          },
        },
        x: {
          grid: {
            display: false,
          },
        },
      },
      plugins: {
        legend: {
          display: false,
        },
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
