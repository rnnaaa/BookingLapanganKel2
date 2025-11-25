// Tab functionality
document.addEventListener("DOMContentLoaded", function () {
  const tabButtons = document.querySelectorAll(".tab-button");
  const tabContents = document.querySelectorAll(".tab-content");

  tabButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const tabId = button.getAttribute("data-tab");

      // Update buttons
      tabButtons.forEach((btn) => btn.classList.remove("active"));
      button.classList.add("active");

      // Update contents
      tabContents.forEach((content) => content.classList.remove("active"));
      document.getElementById(tabId + "-tab").classList.add("active");
    });
  });
});

// Modal Detail for Regular Booking
function showDetail(id, lapangan, tanggal, jam, total, tipeUser, durasiMember, tanggalMulai, tanggalBerakhir, status, deskripsi) {
  const modal = document.getElementById("detailModal");
  const content = document.getElementById("detailContent");
  const qrContainer = document.getElementById("qrcode");

  qrContainer.innerHTML = "";

  const date = new Date(tanggal);
  const formattedDate = date.toLocaleDateString("id-ID", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });

  let detailHTML = `
        <p><strong>ID Booking:</strong> #${id}</p>
        <p><strong>Lapangan:</strong> ${lapangan}</p>
        <p><strong>Tanggal Booking:</strong> ${formattedDate}</p>
        <p><strong>Jam:</strong> ${jam || "-"}</p>
        <p><strong>Total:</strong> Rp ${parseInt(total).toLocaleString("id-ID")}</p>
        <p><strong>Tipe Booking:</strong> ${tipeUser.toUpperCase()}</p>
        <p><strong>Status:</strong> ${status.charAt(0).toUpperCase() + status.slice(1)}</p>
    `;

  if (status === "ditolak" && deskripsi) {
    detailHTML += `<p><strong>Alasan Penolakan:</strong> ${deskripsi}</p>`;
  } else if (status === "menunggu") {
    detailHTML += `<p><strong>Keterangan:</strong> Mohon tunggu verifikasi admin. Cek secara berkala.</p>`;
  } else if (status === "disetujui") {
    detailHTML += `<p><strong>Keterangan:</strong> Silakan tunjukkan QR code saat di tempat dan lakukan pelunasan.</p>`;
  }

  content.innerHTML = detailHTML;

  if (status === "disetujui") {
    new QRCode(qrContainer, {
      text: `https://badmintoon.com/verify/${id}`,
      width: 150,
      height: 150,
      colorDark: "#1e3a8a",
      colorLight: "#ffffff",
      correctLevel: QRCode.CorrectLevel.H,
    });
  }

  modal.style.display = "flex";
}

// Modal Detail for Member
function showMemberDetail(id, lapangan, durasi, mulai, berakhir, total, status, jadwal, ubahCount, maxUbah) {
  const modal = document.getElementById("detailModal");
  const content = document.getElementById("detailContent");
  const qrContainer = document.getElementById("qrcode");

  qrContainer.innerHTML = "";

  const startDate = new Date(mulai);
  const endDate = new Date(berakhir);
  const startFormatted = startDate.toLocaleDateString("id-ID", { day: "numeric", month: "long", year: "numeric" });
  const endFormatted = endDate.toLocaleDateString("id-ID", { day: "numeric", month: "long", year: "numeric" });

  let detailHTML = `
        <p><strong>ID Member:</strong> #${id}</p>
        <p><strong>Lapangan:</strong> ${lapangan}</p>
        <p><strong>Durasi:</strong> ${durasi} bulan</p>
        <p><strong>Periode:</strong> ${startFormatted} - ${endFormatted}</p>
        <p><strong>Total Bayar:</strong> Rp ${parseInt(total).toLocaleString("id-ID")}</p>
        <p><strong>Status:</strong> ${status.charAt(0).toUpperCase() + status.slice(1)}</p>
        <p><strong>Sisa Ubah Jadwal:</strong> ${maxUbah - ubahCount} dari ${maxUbah} kali</p>
    `;

  if (status === "pending") {
    detailHTML += `<p><strong>Keterangan:</strong> Mohon tunggu verifikasi admin. Cek secara berkala.</p>`;
  } else if (status === "aktif") {
    detailHTML += `<p><strong>Keterangan:</strong> Membership aktif. Silakan gunakan QR code untuk check-in.</p>`;

    new QRCode(qrContainer, {
      text: `https://badmintoon.com/verify/member/${id}`,
      width: 150,
      height: 150,
      colorDark: "#1e3a8a",
      colorLight: "#ffffff",
      correctLevel: QRCode.CorrectLevel.H,
    });
  }

  if (jadwal) {
    detailHTML += `<p><strong>Jadwal:</strong></p>`;
    const jadwalList = jadwal.split("; ");
    detailHTML += '<ul style="margin-left: 20px;">';
    jadwalList.forEach((j) => {
      detailHTML += `<li>${j}</li>`;
    });
    detailHTML += "</ul>";
  }

  content.innerHTML = detailHTML;
  modal.style.display = "flex";
}

function closeModal() {
  document.getElementById("detailModal").style.display = "none";
}

// Ubah Jadwal for Regular Booking
function showUbahJadwal(bookingId, tipeBooking) {
  document.getElementById("formBookingId").value = bookingId;
  document.getElementById("formTipeBooking").value = tipeBooking;

  // Show detail pesanan
  const detailPesanan = document.getElementById("detailPesanan");
  detailPesanan.innerHTML = `
        <h4>Detail Pesanan</h4>
        <p><strong>ID Booking:</strong> #${bookingId}</p>
        <p><strong>Tipe:</strong> ${tipeBooking.toUpperCase()}</p>
        <p><strong>Validasi:</strong> Dapat diubah H-5 jam dari waktu booking</p>
        <p><strong>Batas:</strong> 1x ubah jadwal</p>
    `;

  loadBookingSessions(bookingId, tipeBooking);
  document.getElementById("ubahJadwalModal").style.display = "flex";
}

function loadBookingSessions(bookingId, tipeBooking) {
  const sessionList = document.getElementById("sessionList");
  sessionList.innerHTML = '<div class="loading">Memuat sesi...</div>';

  // Simulate AJAX call - replace with actual API call
  setTimeout(() => {
    let sessions = [];

    if (tipeBooking === "member") {
      sessions = [
        { id: 1, tanggal: new Date().toISOString().split("T")[0], jam_mulai: "08:00", jam_selesai: "09:00" },
        { id: 2, tanggal: new Date(Date.now() + 86400000).toISOString().split("T")[0], jam_mulai: "08:00", jam_selesai: "09:00" },
      ];
    } else {
      sessions = [{ id: 1, tanggal: new Date().toISOString().split("T")[0], jam_mulai: "14:00", jam_selesai: "16:00" }];
    }

    displaySessionList(sessions, tipeBooking);
  }, 1000);
}

function displaySessionList(sessions, tipeBooking) {
  const sessionList = document.getElementById("sessionList");
  sessionList.innerHTML = "";

  if (sessions.length === 0) {
    sessionList.innerHTML = '<div class="empty-state">Tidak ada sesi yang dapat diubah</div>';
    return;
  }

  sessions.forEach((session) => {
    const sessionItem = document.createElement("div");
    sessionItem.className = "session-item";

    const date = new Date(session.tanggal);
    const formattedDate = date.toLocaleDateString("id-ID", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    });

    sessionItem.innerHTML = `
            <input type="${tipeBooking === "member" ? "checkbox" : "radio"}" 
                   name="session_ids" value="${session.id}" 
                   id="session-${session.id}" ${tipeBooking === "reguler" ? "checked" : ""}>
            <div class="session-info">
                <div class="session-date">${formattedDate}</div>
                <div class="session-time">${session.jam_mulai} - ${session.jam_selesai}</div>
            </div>
        `;
    sessionList.appendChild(sessionItem);
  });

  updateSubmitButton(tipeBooking);
}

function updateSubmitButton(tipeBooking) {
  const submitBtn = document.getElementById("submitUbahJadwal");

  if (tipeBooking === "member") {
    const checkedSessions = document.querySelectorAll('#sessionList input[type="checkbox"]:checked');
    if (checkedSessions.length > 0) {
      submitBtn.disabled = false;
      submitBtn.textContent = `Simpan Perubahan (${checkedSessions.length} sesi)`;
    } else {
      submitBtn.disabled = true;
      submitBtn.textContent = "Simpan Perubahan";
    }
  } else {
    submitBtn.disabled = false;
    submitBtn.textContent = "Simpan Perubahan";
  }
}

function closeUbahJadwalModal() {
  document.getElementById("ubahJadwalModal").style.display = "none";
}

// Ubah Jadwal for Member
function showUbahJadwalMember(memberId) {
  document.getElementById("formMemberId").value = memberId;

  // Show detail pesanan member
  const detailPesanan = document.getElementById("detailPesananMember");
  detailPesanan.innerHTML = `
        <h4>Detail Membership</h4>
        <p><strong>ID Member:</strong> #${memberId}</p>
        <p><strong>Validasi:</strong> Dapat diubah H-5 jam dari jadwal terdekat</p>
        <p><strong>Batas:</strong> Maksimal 3x ubah jadwal selama periode member</p>
        <p><strong>Catatan:</strong> Dapat memilih multiple sesi untuk diubah</p>
    `;

  loadMemberSessions(memberId);
  document.getElementById("ubahJadwalMemberModal").style.display = "flex";
}

function loadMemberSessions(memberId) {
  const sessionList = document.getElementById("memberSessionList");
  sessionList.innerHTML = '<div class="loading">Memuat sesi member...</div>';

  // Simulate AJAX call - replace with actual API call
  setTimeout(() => {
    const sessions = [
      { id: 1, tanggal: new Date(Date.now() + 86400000).toISOString().split("T")[0], jam_mulai: "08:00", jam_selesai: "09:00" },
      { id: 2, tanggal: new Date(Date.now() + 172800000).toISOString().split("T")[0], jam_mulai: "08:00", jam_selesai: "09:00" },
      { id: 3, tanggal: new Date(Date.now() + 259200000).toISOString().split("T")[0], jam_mulai: "08:00", jam_selesai: "09:00" },
    ];

    displayMemberSessionList(sessions);
  }, 1000);
}

function displayMemberSessionList(sessions) {
  const sessionList = document.getElementById("memberSessionList");
  sessionList.innerHTML = "";

  sessions.forEach((session) => {
    const sessionItem = document.createElement("div");
    sessionItem.className = "session-item";

    const date = new Date(session.tanggal);
    const formattedDate = date.toLocaleDateString("id-ID", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    });

    // Check if session is within 5 hours (for demo purposes)
    const now = new Date();
    const sessionDateTime = new Date(session.tanggal + "T" + session.jam_mulai);
    const timeDiff = (sessionDateTime - now) / (1000 * 60 * 60);
    const isWithin5Hours = timeDiff <= 5;

    sessionItem.innerHTML = `
            <input type="checkbox" name="member_session_ids[]" value="${session.id}" 
                   id="member-session-${session.id}" ${isWithin5Hours ? "disabled" : "checked"}>
            <div class="session-info">
                <div class="session-date">${formattedDate}</div>
                <div class="session-time">${session.jam_mulai} - ${session.jam_selesai}</div>
                ${isWithin5Hours ? '<div style="color: #e53e3e; font-size: 0.8rem;">Tidak dapat diubah (H-5 jam)</div>' : ""}
            </div>
        `;
    sessionList.appendChild(sessionItem);
  });

  updateMemberSubmitButton();
}

function updateMemberSubmitButton() {
  const submitBtn = document.getElementById("submitUbahJadwalMember");
  const checkedSessions = document.querySelectorAll('#memberSessionList input[type="checkbox"]:checked');

  if (checkedSessions.length > 0) {
    submitBtn.disabled = false;
    submitBtn.textContent = `Simpan Perubahan (${checkedSessions.length} sesi)`;
  } else {
    submitBtn.disabled = true;
    submitBtn.textContent = "Simpan Perubahan";
  }
}

function closeUbahJadwalMemberModal() {
  document.getElementById("ubahJadwalMemberModal").style.display = "none";
}

// Event listeners for checkboxes
document.addEventListener("change", function (e) {
  if (e.target.name === "session_ids[]") {
    updateMemberSubmitButton();
  }
});

// Close modals when clicking outside
window.onclick = function (event) {
  const modals = ["detailModal", "ubahJadwalModal", "ubahJadwalMemberModal"];
  modals.forEach((modalId) => {
    const modal = document.getElementById(modalId);
    if (event.target === modal) {
      modal.style.display = "none";
    }
  });
};

// Handle escape key
document.addEventListener("keydown", function (event) {
  if (event.key === "Escape") {
    closeModal();
    closeUbahJadwalModal();
    closeUbahJadwalMemberModal();
  }
});

// Form submission handlers
document.getElementById("ubahJadwalForm")?.addEventListener("submit", function (e) {
  const tipeBooking = document.getElementById("formTipeBooking").value;

  if (tipeBooking === "member") {
    const checkedSessions = document.querySelectorAll('input[name="session_ids[]"]:checked');
    if (checkedSessions.length === 0) {
      e.preventDefault();
      alert("Pilih minimal satu sesi untuk diubah");
      return;
    }
  }

  // Continue with form submission
});

document.getElementById("ubahJadwalMemberForm")?.addEventListener("submit", function (e) {
  const checkedSessions = document.querySelectorAll('input[name="member_session_ids[]"]:checked');
  if (checkedSessions.length === 0) {
    e.preventDefault();
    alert("Pilih minimal satu sesi member untuk diubah");
    return;
  }

  // Continue with form submission
});
