// ==============================================
// FILE: riwayat.js
// DESCRIPTION: Frontend JavaScript untuk interaktivitas riwayat booking
// ==============================================

// === 1. KODE INISIALISASI DAN TAB FUNCTIONALITY ===
document.addEventListener("DOMContentLoaded", function () {
  console.log("DOM Content Loaded - Riwayat Page");

  const tabButtons = document.querySelectorAll(".tab-button");
  const tabContents = document.querySelectorAll(".tab-content");

  tabButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const tabId = button.getAttribute("data-tab");
      console.log("Tab clicked:", tabId);

      // Update buttons
      tabButtons.forEach((btn) => btn.classList.remove("active"));
      button.classList.add("active");

      // Update contents
      tabContents.forEach((content) => content.classList.remove("active"));
      const targetTab = document.getElementById(tabId + "-tab");
      if (targetTab) {
        targetTab.classList.add("active");
      }
    });
  });
});

// === 2. KODE NOTIFICATION SYSTEM ===
function showNotification(message, type = "info") {
  console.log("Showing notification:", message, type);

  // Hapus notif lama jika ada
  const oldNotif = document.querySelector(".notif-toast");
  if (oldNotif) oldNotif.remove();

  const notif = document.createElement("div");
  notif.className = `notif-toast ${type}`;
  notif.textContent = message;

  document.body.appendChild(notif);

  // Auto remove after 5 seconds
  setTimeout(() => {
    if (notif.parentNode) {
      notif.remove();
    }
  }, 5000);
}

// === 3. KODE COUNTDOWN TIMER SYSTEM ===
// COUNTDOWN TIMER — VERSI FINAL SUPER JELAS & AKURAT
document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".countdown-timer").forEach((timer) => {
    const deadlineAttr = timer.getAttribute("data-booking-deadline");
    if (!deadlineAttr) {
      timer.innerHTML = '<span class="expired-text">Waktu ubah jadwal habis</span>';
      timer.classList.add("expired");
      return;
    }

    const deadline = new Date(deadlineAttr).getTime();

    const updateTimer = () => {
      const now = new Date().getTime();
      const distance = deadline - now;

      if (distance <= 0) {
        timer.innerHTML = '<span class="expired-text">Waktu ubah jadwal habis</span>';
        timer.classList.add("expired");

        // Auto disable tombol ubah jadwal
        const card = timer.closest(".card");
        const btn = card?.querySelector(".btn-ubah:not(.disabled)");
        if (btn) {
          btn.classList.add("disabled");
          btn.onclick = () => showDisabledReason("time_expired");
        }
        return;
      }

      const days = Math.floor(distance / (1000 * 60 * 60 * 24));
      const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((distance % (1000 * 60)) / 1000);

      let text = "";
      if (days > 0) text += `${days} hari `;
      if (hours > 0 || days > 0) text += `${hours} jam `;
      if (minutes > 0 || hours > 0 || days > 0) text += `${minutes} menit `;
      text += `${seconds} detik lagi`;

      // Update teks di dalam span timer
      const timerSpan = timer.querySelector('span[id^="timer-"]');
      if (timerSpan) {
        timerSpan.textContent = text;
      } else {
        timer.innerHTML = `<span class="time-left">Tersisa <span id="timer-${timer.closest(".card")?.dataset.booking || "x"}">${text}</span></span>`;
      }

      // Ganti warna jadi merah kalau < 1 jam
      if (distance < 60 * 60 * 1000) {
        timer.style.color = "#e74c3c";
        timer.style.fontWeight = "bold";
      }
    };

    updateTimer();
    setInterval(updateTimer, 1000);
  });
});

// === 4. KODE MODAL DETAIL FUNCTIONS ===
function showDetail(id, lapangan, tanggal, jam, total, status, alasanPenolakan = "", uniqueId = "") {
  console.log("showDetail called with status:", status);

  const modal = document.getElementById("detailModal");
  const content = document.getElementById("detailContent");
  const qrContainer = document.getElementById("qrcode");

  if (!modal || !content) {
    console.error("Modal element tidak ditemukan");
    return;
  }

  qrContainer.innerHTML = "";

  const date = new Date(tanggal);
  const formattedDate = date.toLocaleDateString("id-ID", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });

  let detailHTML = `
        <div class="detail-info">
            <p><strong>ID Booking:</strong> ${uniqueId || "#" + id}</p>
            <p><strong>Lapangan:</strong> ${lapangan}</p>
            <p><strong>Tanggal Booking:</strong> ${formattedDate}</p>
            <p><strong>Jam:</strong> ${jam || "-"}</p>
            <p><strong>Total:</strong> Rp ${parseInt(total).toLocaleString("id-ID")}</p>
            <p><strong>Status:</strong> <span class="status ${getStatusClass(status)}">${status.charAt(0).toUpperCase() + status.slice(1)}</span></p>
    `;

  // Keterangan berdasarkan status
  if (status === "ditolak" && alasanPenolakan) {
    detailHTML += `<p><strong>Alasan Penolakan:</strong> ${alasanPenolakan}</p>`;
    detailHTML += `<p><strong>Keterangan:</strong> Booking ditolak. Silakan buat booking baru.</p>`;
  } else if (status === "menunggu") {
    detailHTML += `<p><strong>Keterangan:</strong> Menunggu verifikasi admin. Anda masih bisa mengubah jadwal sampai H-5 jam dari waktu booking.</p>`;
  } else if (status === "disetujui") {
    detailHTML += `<p><strong>Keterangan:</strong> Booking disetujui. Silakan tunjukkan QR code saat di tempat.</p>`;
  } else if (status === "dibatalkan") {
    detailHTML += `<p><strong>Keterangan:</strong> Booking dibatalkan. Tidak dapat mengubah jadwal.</p>`;
  }

  detailHTML += `</div>`;
  content.innerHTML = detailHTML;

  // Generate QR Code hanya untuk status disetujui
  if (status === "disetujui") {
    if (typeof QRCode !== "undefined") {
      try {
        new QRCode(qrContainer, {
          text: `BOOKING-${id}-${lapangan}`,
          width: 150,
          height: 150,
          colorDark: "#1e3a8a",
          colorLight: "#ffffff",
          correctLevel: QRCode.CorrectLevel.H,
        });
      } catch (error) {
        console.error("QR Code generation error:", error);
        qrContainer.innerHTML = "<p>Error generating QR code</p>";
      }
    } else {
      qrContainer.innerHTML = "<p>QR Code functionality not available</p>";
    }
  }

  // Show modal
  modal.style.display = "flex";
  console.log("Detail modal shown for status:", status);
}

function showMemberDetail(id, lapangan, durasi, mulai, berakhir, total, status, jadwal, ubahCount, maxUbah, uniqueId = "") {
  console.log("showMemberDetail called with:", { id, lapangan, durasi, mulai, berakhir, total, status, ubahCount, maxUbah });

  const modal = document.getElementById("detailModal");
  const content = document.getElementById("detailContent");
  const qrContainer = document.getElementById("qrcode");

  if (!modal || !content) {
    console.error("Modal elements not found");
    return;
  }

  qrContainer.innerHTML = "";

  // Format dates
  const startDate = new Date(mulai);
  const endDate = new Date(berakhir);
  const startFormatted = startDate.toLocaleDateString("id-ID", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });
  const endFormatted = endDate.toLocaleDateString("id-ID", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });

  let detailHTML = `
        <div class="detail-info">
            <p><strong>ID Member:</strong> ${uniqueId || "#" + id}</p>
            <p><strong>Lapangan:</strong> ${lapangan}</p>
            <p><strong>Durasi:</strong> ${durasi} bulan</p>
            <p><strong>Periode:</strong> ${startFormatted} - ${endFormatted}</p>
            <p><strong>Total Bayar:</strong> Rp ${parseInt(total).toLocaleString("id-ID")}</p>
            <p><strong>Status:</strong> <span class="status ${getStatusClass(status)}">${status.charAt(0).toUpperCase() + status.slice(1)}</span></p>
            <p><strong>Sisa Ubah Jadwal:</strong> ${maxUbah - ubahCount} dari ${maxUbah} kali</p>
    `;

  if (status === "pending") {
    detailHTML += `<p><strong>Keterangan:</strong> Mohon tunggu verifikasi admin. Cek secara berkala.</p>`;
  } else if (status === "aktif") {
    detailHTML += `<p><strong>Keterangan:</strong> Membership aktif. Silakan gunakan QR code untuk check-in.</p>`;
  }

  detailHTML += `</div>`;

  // Add schedule if available
  if (jadwal && jadwal !== "" && jadwal !== "null") {
    detailHTML += `<div class="schedule-info">`;
    detailHTML += `<p><strong>Jadwal Terjadwal:</strong></p>`;
    const jadwalList = jadwal.split("; ");
    detailHTML += '<ul style="margin-left: 20px;">';
    jadwalList.forEach((j) => {
      if (j.trim() !== "") {
        detailHTML += `<li>${j}</li>`;
      }
    });
    detailHTML += "</ul>";
    detailHTML += `</div>`;
  }

  content.innerHTML = detailHTML;

  // Generate QR Code for active members
  if (status === "aktif") {
    if (typeof QRCode !== "undefined") {
      new QRCode(qrContainer, {
        text: `MEMBER-${id}-${lapangan}`,
        width: 150,
        height: 150,
        colorDark: "#1e3a8a",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H,
      });
    }
  }

  modal.style.display = "flex";
  console.log("Member detail modal shown");
}

// === 5. KODE HELPER FUNCTIONS ===
function getStatusClass(status) {
  if (status === "menunggu" || status === "pending") return "menunggu";
  if (status === "disetujui" || status === "aktif") return "disetujui";
  if (status === "ditolak" || status === "nonaktif") return "ditolak";
  return "";
}

// === 6. KODE TOMBOL DISABLED REASON ===
function showDisabledReason(reason) {
  const messages = {
    already_used: "Anda sudah menggunakan kesempatan ubah jadwal (maksimal 1x)",
    time_expired: "Waktu ubah jadwal sudah habis (H-5 jam dari booking)",
    member_not_active: "Membership tidak aktif. Tidak dapat mengubah jadwal",
    quota_exceeded: "Kuota ubah jadwal member sudah habis (maksimal 3x)",
    booking_rejected: "Booking ditolak. Tidak dapat mengubah jadwal",
    booking_cancelled: "Booking dibatalkan. Tidak dapat mengubah jadwal",
  };

  const message = messages[reason] || "Tidak dapat mengubah jadwal";
  const type = reason.includes("expired") || reason.includes("exceeded") || reason.includes("rejected") || reason.includes("cancelled") ? "error" : "warning";

  showNotification(message, type);
}

// === 7. KODE UBAH JADWAL REGULER ===
function showUbahJadwalReguler(bookingId, currentDate, currentTime, currentLapangan, lapanganId) {
  console.log("Membuka modal ubah jadwal untuk booking:", bookingId);

  // Validasi H-5 jam
  const now = new Date();
  const jamMulai = currentTime.split("-")[0].trim();
  const bookingDateTime = new Date(currentDate + "T" + jamMulai + ":00");
  const deadline = new Date(bookingDateTime.getTime() - 5 * 60 * 60 * 1000);

  if (now > deadline) {
    showNotification("Waktu ubah jadwal sudah habis (H-5 jam dari booking)", "error");
    return;
  }

  const card = document.querySelector(`[data-booking="${bookingId}"]`);
  const ubahBtn = card?.querySelector(".btn-ubah");
  if (ubahBtn?.classList.contains("disabled")) {
    showNotification("Tidak dapat mengubah jadwal saat ini", "error");
    return;
  }

  // INI YANG BENAR: panggil loadUbahJadwalForm, bukan display langsung!
  loadUbahJadwalForm(bookingId, currentDate, currentTime, currentLapangan, lapanganId);
}

function loadUbahJadwalForm(bookingId, currentDate, currentTime, currentLapangan, lapanganId) {
  const modalContent = document.getElementById("ubahJadwalContent");

  // Show loading
  modalContent.innerHTML = '<div class="loading">Memuat form ubah jadwal...</div>';

  // Fetch available sessions
  fetch(`riwayat.php?action=get_available_sessions&lapangan_id=${lapanganId}&current_date=${currentDate}&booking_id=${bookingId}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        displayUbahJadwalForm(bookingId, currentDate, currentTime, currentLapangan, data.available_sessions, lapanganId);
      } else {
        modalContent.innerHTML = `<div class="error-state">${data.message}</div>`;
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      modalContent.innerHTML = '<div class="error-state">Gagal memuat jadwal available</div>';
    });

  // Show modal
  document.getElementById("ubahJadwalModal").style.display = "flex";
}

function displayUbahJadwalForm(bookingId, currentDate, currentTime, currentLapangan, availableSessions, lapanganId) {
  const modalContent = document.getElementById("ubahJadwalContent");

  const currentDateObj = new Date(currentDate);
  const formattedDate = currentDateObj.toLocaleDateString("id-ID", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });

  let formHTML = `
        <form id="ubahJadwalForm">
            <input type="hidden" name="action" value="ubah_jadwal_reguler">
            <input type="hidden" name="booking_id" value="${bookingId}">
            
            <div class="detail-pesanan">
                <h4>Detail Booking Saat Ini</h4>
                <p><strong>ID Booking:</strong> #${bookingId}</p>
                <p><strong>Lapangan:</strong> ${currentLapangan}</p>
                <p><strong>Tanggal:</strong> ${formattedDate}</p>
                <p><strong>Jam:</strong> ${currentTime}</p>
                <p><strong>Status:</strong> Dapat Diubah</p>
            </div>
            
            <div class="form-group">
                <label for="newDate">Pilih Tanggal Baru:</label>
                <input type="date" name="new_date" id="newDate" class="select-input" required 
                       min="${new Date().toISOString().split("T")[0]}" 
                       max="${getMaxDate()}">
            </div>
            
            <div class="form-group">
                <label>Pilih Jam Baru:</label>
                <div id="availableSessionsList" class="session-list">
                    ${displayAvailableSessions(availableSessions)}
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeUbahJadwalModal()">Batal</button>
                <button type="submit" class="btn-primary" id="submitUbahJadwal">Simpan Perubahan</button>
            </div>
        </form>
    `;

  modalContent.innerHTML = formHTML;

  // Add event listener for form submission
  document.getElementById("ubahJadwalForm").addEventListener("submit", function (e) {
    e.preventDefault();
    submitUbahJadwalReguler(this);
  });

  // FIX: Pastikan lapanganId tersedia di sini!
  document.getElementById("newDate").addEventListener("change", function () {
    loadAvailableSessionsForDate(bookingId, lapanganId, this.value);
  });
}

function displayAvailableSessions(sessions) {
  if (!sessions || sessions.length === 0) {
    return '<div class="empty-state">Tidak ada jadwal available untuk tanggal ini</div>';
  }

  let sessionsHTML = "";
  sessions.forEach((session) => {
    const date = new Date(session.tanggal);
    const formattedDate = date.toLocaleDateString("id-ID", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    });

    sessionsHTML += `
            <div class="session-item">
                <input type="radio" name="selected_session" value="${session.id_jadwal_waktu}" 
                       data-tanggal="${session.tanggal}" data-jam="${session.jam_mulai}-${session.jam_selesai}"
                       id="session-${session.id_jadwal_waktu}" required>
                <div class="session-info">
                    <div class="session-date">${formattedDate}</div>
                    <div class="session-time">${session.jam_mulai} - ${session.jam_selesai}</div>
                    <div class="session-price">Rp ${parseInt(session.harga).toLocaleString("id-ID")}</div>
                </div>
            </div>
        `;
  });

  return sessionsHTML;
}

function loadAvailableSessionsForDate(bookingId, lapanganId, selectedDate) {
  const sessionsList = document.getElementById("availableSessionsList");
  sessionsList.innerHTML = '<div class="loading">Memuat jadwal available...</div>';

  // TAMBAH &booking_id= DI SINI !!!
  fetch(`riwayat.php?action=get_available_sessions&lapangan_id=${lapanganId}&selected_date=${selectedDate}&booking_id=${bookingId}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        sessionsList.innerHTML = displayAvailableSessions(data.available_sessions);
      } else {
        sessionsList.innerHTML = `<div class="error-state">${data.message}</div>`;
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      sessionsList.innerHTML = '<div class="error-state">Gagal memuat jadwal</div>';
    });
}

function getMaxDate() {
  const maxDate = new Date();
  maxDate.setDate(maxDate.getDate() + 7); // Maksimal 7 hari ke depan
  return maxDate.toISOString().split("T")[0];
}

function submitUbahJadwalReguler(form) {
  const submitBtn = document.getElementById("submitUbahJadwal");
  const originalText = submitBtn.innerHTML;

  submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';
  submitBtn.disabled = true;

  const formData = new FormData(form);

  fetch("riwayat.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        showNotification("Jadwal berhasil diubah!", "success");
        closeUbahJadwalModal();
        // Refresh page setelah 2 detik
        setTimeout(() => {
          window.location.reload();
        }, 2000);
      } else {
        showNotification(data.message, "error");
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification("Terjadi kesalahan saat mengubah jadwal", "error");
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
    });
}

// === 8. KODE UBAH JADWAL MEMBER ===
function showUbahJadwalMember(memberId, lapanganName, sisaKuota, lapanganId) {
  console.log("Membuka modal ubah jadwal member:", memberId);

  // Validasi sebelum buka modal
  const card = document.querySelector(`[data-member="${memberId}"]`);
  const ubahBtn = card?.querySelector(".btn-ubah");

  if (ubahBtn?.classList.contains("disabled")) {
    showNotification("Tidak dapat mengubah jadwal member saat ini", "error");
    return;
  }

  // Load form ubah jadwal member
  loadUbahJadwalMemberForm(memberId, lapanganName, sisaKuota, lapanganId);
}

function loadUbahJadwalMemberForm(memberId, lapanganName, sisaKuota, lapanganId) {
  const modalContent = document.getElementById("ubahJadwalMemberContent");

  // Show loading
  modalContent.innerHTML = '<div class="loading">Memuat form ubah jadwal member...</div>';

  // Fetch member sessions
  fetch(`riwayat.php?action=get_member_sessions&member_id=${memberId}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        displayUbahJadwalMemberForm(memberId, lapanganName, sisaKuota, lapanganId, data.member_sessions);
      } else {
        modalContent.innerHTML = `<div class="error-state">${data.message}</div>`;
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      modalContent.innerHTML = '<div class="error-state">Gagal memuat sesi member</div>';
    });

  // Show modal
  document.getElementById("ubahJadwalMemberModal").style.display = "flex";
}

function displayUbahJadwalMemberForm(memberId, lapanganName, sisaKuota, lapanganId, memberSessions) {
  const modalContent = document.getElementById("ubahJadwalMemberContent");

  let formHTML = `
        <form id="ubahJadwalMemberForm">
            <input type="hidden" name="action" value="ubah_jadwal_member">
            <input type="hidden" name="member_id" value="${memberId}">
            <input type="hidden" name="lapangan_id" value="${lapanganId}">
            
            <div class="detail-pesanan">
                <h4>Detail Membership</h4>
                <p><strong>ID Member:</strong> #${memberId}</p>
                <p><strong>Lapangan:</strong> ${lapanganName}</p>
                <p><strong>Sisa Ubah Jadwal:</strong> ${sisaKuota} kali</p>
                <p><strong>Status:</strong> Aktif</p>
            </div>
            
            <div class="form-group">
                <label>Pilih Sesi yang Ingin Diubah:</label>
                <div id="memberSessionsList" class="session-list">
                    ${displayMemberSessions(memberSessions)}
                </div>
            </div>
            
            <div class="form-group">
                <label for="newDateMember">Pilih Tanggal Baru:</label>
                <input type="date" name="new_date" id="newDateMember" class="select-input" required 
                       min="${new Date().toISOString().split("T")[0]}" 
                       max="${getMaxDate()}">
            </div>
            
            <div class="form-group">
                <label>Pilih Jam Baru:</label>
                <div id="availableMemberSessions" class="session-list">
                    <div class="empty-state">Pilih tanggal terlebih dahulu</div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeUbahJadwalMemberModal()">Batal</button>
                <button type="submit" class="btn-primary" id="submitUbahJadwalMember">Simpan Perubahan</button>
            </div>
        </form>
    `;

  modalContent.innerHTML = formHTML;

  // Add event listeners
  document.getElementById("ubahJadwalMemberForm").addEventListener("submit", function (e) {
    e.preventDefault();
    submitUbahJadwalMember(this);
  });

  document.getElementById("newDateMember").addEventListener("change", function () {
    loadAvailableSessionsForMember(lapanganId, this.value);
  });
}

function displayMemberSessions(sessions) {
  if (!sessions || sessions.length === 0) {
    return '<div class="empty-state">Tidak ada sesi member yang dapat diubah</div>';
  }

  let sessionsHTML = "";
  const now = new Date();

  sessions.forEach((session) => {
    const sessionDateTime = new Date(session.tanggal_booking + "T" + session.jam_mulai);
    const timeDiff = (sessionDateTime - now) / (1000 * 60 * 60);
    const isWithin5Hours = timeDiff <= 5;
    const canEdit = !isWithin5Hours;

    const date = new Date(session.tanggal_booking);
    const formattedDate = date.toLocaleDateString("id-ID", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    });

    sessionsHTML += `
            <div class="session-item">
                <input type="checkbox" name="member_session_ids[]" value="${session.id_member_jadwal}" 
                       id="member-session-${session.id_member_jadwal}" 
                       ${canEdit ? "" : "disabled"}>
                <div class="session-info">
                    <div class="session-date">${formattedDate}</div>
                    <div class="session-time">${session.jam_mulai} - ${session.jam_selesai}</div>
                    ${!canEdit ? '<div style="color: #e53e3e; font-size: 0.8rem;">Tidak dapat diubah (H-5 jam)</div>' : ""}
                </div>
            </div>
        `;
  });

  return sessionsHTML;
}

function loadAvailableSessionsForMember(lapanganId, selectedDate) {
  const sessionsList = document.getElementById("availableMemberSessions");
  sessionsList.innerHTML = '<div class="loading">Memuat jadwal available...</div>';

  fetch(`riwayat.php?action=get_available_sessions&lapangan_id=${lapanganId}&selected_date=${selectedDate}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        sessionsList.innerHTML = displayAvailableSessions(data.available_sessions);
      } else {
        sessionsList.innerHTML = `<div class="error-state">${data.message}</div>`;
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      sessionsList.innerHTML = '<div class="error-state">Gagal memuat jadwal</div>';
    });
}

function submitUbahJadwalMember(form) {
  const submitBtn = document.getElementById("submitUbahJadwalMember");
  const originalText = submitBtn.innerHTML;

  // Validasi: minimal pilih 1 sesi
  const checkedSessions = document.querySelectorAll('input[name="member_session_ids[]"]:checked');
  if (checkedSessions.length === 0) {
    showNotification("Pilih minimal satu sesi member untuk diubah", "error");
    return;
  }

  submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';
  submitBtn.disabled = true;

  const formData = new FormData(form);

  // Convert array to string for form data
  const sessionIds = Array.from(checkedSessions).map((session) => session.value);
  sessionIds.forEach((id) => {
    formData.append("member_session_ids[]", id);
  });

  fetch("riwayat.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        showNotification("Jadwal member berhasil diubah!", "success");
        closeUbahJadwalMemberModal();
        // Refresh page setelah 2 detik
        setTimeout(() => {
          window.location.reload();
        }, 2000);
      } else {
        showNotification(data.message, "error");
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification("Terjadi kesalahan saat mengubah jadwal member", "error");
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
    });
}

// === 9. KODE MODAL CLOSE FUNCTIONS ===
function closeModal() {
  const modal = document.getElementById("detailModal");
  if (modal) {
    modal.style.display = "none";
  }
}

function closeUbahJadwalModal() {
  const modal = document.getElementById("ubahJadwalModal");
  if (modal) {
    modal.style.display = "none";
  }
}

function closeUbahJadwalMemberModal() {
  const modal = document.getElementById("ubahJadwalMemberModal");
  if (modal) {
    modal.style.display = "none";
  }
}

// === 10. KODE EVENT LISTENERS DAN ERROR HANDLING ===
document.addEventListener("DOMContentLoaded", function () {
  // Close modal when clicking outside
  window.addEventListener("click", function (event) {
    const modals = ["detailModal", "ubahJadwalModal", "ubahJadwalMemberModal"];
    modals.forEach((modalId) => {
      const modal = document.getElementById(modalId);
      if (event.target === modal) {
        modal.style.display = "none";
      }
    });
  });

  // Close modal with ESC key
  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      closeModal();
      closeUbahJadwalModal();
      closeUbahJadwalMemberModal();
    }
  });

  console.log("All event listeners initialized");
});

// Global error handler
window.addEventListener("error", function (e) {
  console.error("Global error:", e.error);
  showNotification("Terjadi kesalahan pada halaman", "error");
});
