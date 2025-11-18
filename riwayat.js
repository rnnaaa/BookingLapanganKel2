// Modal Detail
function showDetail(id, lapangan, tanggal, jam, total, tipeUser, durasiMember, tanggalMulai, tanggalBerakhir, status, deskripsi) {
  const modal = document.getElementById("detailModal");
  const content = document.getElementById("detailContent");
  const qrContainer = document.getElementById("qrcode");

  qrContainer.innerHTML = "";

  // Format tanggal
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
    `;

  if (tipeUser === "member" && durasiMember) {
    const startDate = new Date(tanggalMulai);
    const endDate = new Date(tanggalBerakhir);
    const startFormatted = startDate.toLocaleDateString("id-ID", { day: "numeric", month: "long", year: "numeric" });
    const endFormatted = endDate.toLocaleDateString("id-ID", { day: "numeric", month: "long", year: "numeric" });

    detailHTML += `
            <p><strong>Durasi Member:</strong> ${durasiMember} bulan</p>
            <p><strong>Periode Member:</strong> ${startFormatted} - ${endFormatted}</p>
        `;
  }

  detailHTML += `
        <p><strong>Status:</strong> ${status.charAt(0).toUpperCase() + status.slice(1)}</p>
        <p><strong>Keterangan:</strong> ${deskripsi || "-"}</p>
    `;

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

function closeModal() {
  document.getElementById("detailModal").style.display = "none";
}

// Modal Ubah Jadwal
function showUbahJadwal(bookingId, tipeBooking) {
  document.getElementById("formBookingId").value = bookingId;
  document.getElementById("formTipeBooking").value = tipeBooking;

  // Load sessions dari server via AJAX
  loadBookingSessions(bookingId, tipeBooking);
  document.getElementById("ubahJadwalModal").style.display = "flex";
}

function loadBookingSessions(bookingId, tipeBooking) {
  const sessionList = document.getElementById("sessionList");
  sessionList.innerHTML = '<div class="loading">Memuat sesi...</div>';

  // Simulasi AJAX call - dalam real app, ganti dengan fetch ke endpoint PHP
  setTimeout(() => {
    // Demo data berdasarkan tipe booking
    let sessions = [];

    if (tipeBooking === "member") {
      // Untuk member, tampilkan multiple sessions
      sessions = [
        { id: 1, tanggal: "2025-11-16", jam_mulai: "08:00", jam_selesai: "09:00" },
        { id: 2, tanggal: "2025-11-23", jam_mulai: "08:00", jam_selesai: "09:00" },
        { id: 3, tanggal: "2025-11-30", jam_mulai: "08:00", jam_selesai: "09:00" },
      ];
    } else {
      // Untuk reguler, satu session saja
      sessions = [{ id: 1, tanggal: "2025-11-12", jam_mulai: "14:00", jam_selesai: "16:00" }];
    }

    displaySessionList(sessions);
  }, 1000);
}

function displaySessionList(sessions) {
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
            <input type="checkbox" name="session_ids[]" value="${session.id}" 
                   id="session-${session.id}" checked>
            <div class="session-info">
                <div class="session-date">${formattedDate}</div>
                <div class="session-time">${session.jam_mulai} - ${session.jam_selesai}</div>
            </div>
        `;
    sessionList.appendChild(sessionItem);
  });

  updateSubmitButton();
}

function updateSubmitButton() {
  const checkedSessions = document.querySelectorAll('#sessionList input[type="checkbox"]:checked');
  const submitBtn = document.getElementById("submitUbahJadwal");

  if (checkedSessions.length > 0) {
    submitBtn.disabled = false;
    submitBtn.textContent = `Simpan Perubahan (${checkedSessions.length} sesi)`;
  } else {
    submitBtn.disabled = true;
    submitBtn.textContent = "Simpan Perubahan";
  }
}

function closeUbahJadwalModal() {
  document.getElementById("ubahJadwalModal").style.display = "none";
}

// Handle form submission
document.getElementById("ubahJadwalForm").addEventListener("submit", function (e) {
  const checkedSessions = document.querySelectorAll('input[name="session_ids[]"]:checked');
  if (checkedSessions.length === 0) {
    e.preventDefault();
    alert("Pilih minimal satu sesi untuk diubah");
    return;
  }

  // Form akan di-submit secara normal ke proses_ubah_jadwal.php
});

// Close modal ketika klik di luar content
window.onclick = function (event) {
  const detailModal = document.getElementById("detailModal");
  const ubahModal = document.getElementById("ubahJadwalModal");

  if (event.target === detailModal) {
    closeModal();
  }
  if (event.target === ubahModal) {
    closeUbahJadwalModal();
  }
};

// Handle escape key
document.addEventListener("keydown", function (event) {
  if (event.key === "Escape") {
    closeModal();
    closeUbahJadwalModal();
  }
});
