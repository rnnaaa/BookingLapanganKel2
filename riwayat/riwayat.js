document.addEventListener("DOMContentLoaded", function () {
  // Tab Switching
  document.querySelectorAll(".tab-button").forEach((button) => {
    button.addEventListener("click", () => {
      const tab = button.getAttribute("data-tab");

      // Update active tab button
      document.querySelectorAll(".tab-button").forEach((btn) => btn.classList.remove("active"));
      button.classList.add("active");

      // Show active tab content
      document.querySelectorAll(".tab-content").forEach((content) => {
        content.classList.remove("active");
      });
      document.getElementById(tab + "-tab").classList.add("active");
    });
  });

  // Timer Countdown
  function updateCountdowns() {
    document.querySelectorAll(".countdown-timer[data-deadline]").forEach((timer) => {
      const deadline = new Date(timer.getAttribute("data-deadline"));
      const now = new Date();
      const diff = deadline - now;

      if (diff <= 0) {
        timer.innerHTML = "<small>Waktu habis</small>";
        timer.classList.add("expired");
        return;
      }

      const hours = Math.floor(diff / (1000 * 60 * 60));
      const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((diff % (1000 * 60)) / 1000);

      timer.querySelector(".timer").textContent = `${hours.toString().padStart(2, "0")}:${minutes.toString().padStart(2, "0")}:${seconds.toString().padStart(2, "0")}`;
    });
  }

  setInterval(updateCountdowns, 1000);
  updateCountdowns();

  // Modal Handling
  const modals = document.querySelectorAll(".modal");
  const closeButtons = document.querySelectorAll(".close");

  closeButtons.forEach((button) => {
    button.addEventListener("click", () => {
      button.closest(".modal").style.display = "none";
    });
  });

  window.addEventListener("click", (event) => {
    modals.forEach((modal) => {
      if (event.target === modal) {
        modal.style.display = "none";
      }
    });
  });

  // Form Submission - Ubah Jadwal Reguler
  document.getElementById("formUbahReguler")?.addEventListener("submit", function (e) {
    e.preventDefault();

    const id_sesi = document.getElementById("id_sesi_ubah").value;
    const new_date = document.getElementById("new_date").value;
    const selectedSession = document.querySelector('input[name="new_session"]:checked');

    if (!selectedSession) {
      Swal.fire("Error", "Pilih jam baru terlebih dahulu", "error");
      return;
    }

    const new_jadwal_waktu = selectedSession.value;

    Swal.fire({
      title: "Konfirmasi Ubah Jadwal",
      text: "Apakah Anda yakin ingin mengubah jadwal ini?",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Ya, Ubah",
      cancelButtonText: "Batal",
    }).then((result) => {
      if (result.isConfirmed) {
        const formData = new FormData();
        formData.append("action", "ubah_jadwal_sesi");
        formData.append("id_sesi", id_sesi);
        formData.append("new_date", new_date);
        formData.append("new_jadwal_waktu", new_jadwal_waktu);

        fetch("riwayat.php", {
          method: "POST",
          body: formData,
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.status === "success") {
              Swal.fire("Berhasil", data.message, "success").then(() => {
                location.reload();
              });
            } else {
              Swal.fire("Error", data.message, "error");
            }
          })
          .catch((error) => {
            console.error("Error:", error);
            Swal.fire("Error", "Terjadi kesalahan sistem", "error");
          });
      }
    });
  });

  // Form Submission - Pembatalan Reguler
  document.getElementById("formPembatalan")?.addEventListener("submit", function (e) {
    e.preventDefault();

    const id_sesi = document.getElementById("id_sesi_batal").value;
    const nama_penerima = document.getElementById("nama_penerima").value;
    const no_rekening = document.getElementById("no_rekening").value;
    const bank_ewallet = document.getElementById("bank_ewallet").value;

    Swal.fire({
      title: "Konfirmasi Pembatalan",
      text: "Apakah Anda yakin ingin mengajukan pembatalan?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Ya, Ajukan",
      cancelButtonText: "Batal",
    }).then((result) => {
      if (result.isConfirmed) {
        const formData = new FormData();
        formData.append("action", "ajukan_pembatalan");
        formData.append("id_sesi", id_sesi);
        formData.append("nama_penerima", nama_penerima);
        formData.append("no_rekening", no_rekening);
        formData.append("bank_ewallet", bank_ewallet);

        fetch("riwayat.php", {
          method: "POST",
          body: formData,
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.status === "success") {
              Swal.fire("Berhasil", data.message, "success").then(() => {
                location.reload();
              });
            } else {
              Swal.fire("Error", data.message, "error");
            }
          })
          .catch((error) => {
            console.error("Error:", error);
            Swal.fire("Error", "Terjadi kesalahan sistem", "error");
          });
      }
    });
  });

  // Form Submission - Ubah Jadwal Member
  document.getElementById("formUbahMember")?.addEventListener("submit", function (e) {
    e.preventDefault();

    const member_id = document.getElementById("id_member_ubah").value;
    const lapangan_id = document.getElementById("id_lapangan_member_ubah").value;
    const new_date = document.getElementById("new_date_member").value;
    const selectedSession = document.querySelector('input[name="new_session_member"]:checked');

    // Get selected member sessions
    const selectedMemberSessions = [];
    document.querySelectorAll('input[name="member_session"]:checked').forEach((checkbox) => {
      selectedMemberSessions.push(checkbox.value);
    });

    if (selectedMemberSessions.length === 0) {
      Swal.fire("Error", "Pilih minimal satu sesi yang akan diubah", "error");
      return;
    }

    if (!selectedSession) {
      Swal.fire("Error", "Pilih jam baru terlebih dahulu", "error");
      return;
    }

    const selected_session = selectedSession.value;

    Swal.fire({
      title: "Konfirmasi Ubah Jadwal",
      text: `Apakah Anda yakin ingin mengubah ${selectedMemberSessions.length} sesi?`,
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Ya, Ubah",
      cancelButtonText: "Batal",
    }).then((result) => {
      if (result.isConfirmed) {
        const formData = new FormData();
        formData.append("action", "ubah_jadwal_member");
        formData.append("member_id", member_id);
        formData.append("lapangan_id", lapangan_id);
        formData.append("new_date", new_date);
        formData.append("member_session_ids[]", selectedMemberSessions);
        formData.append("selected_session", selected_session);

        fetch("riwayat.php", {
          method: "POST",
          body: formData,
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.status === "success") {
              Swal.fire("Berhasil", data.message, "success").then(() => {
                location.reload();
              });
            } else {
              Swal.fire("Error", data.message, "error");
            }
          })
          .catch((error) => {
            console.error("Error:", error);
            Swal.fire("Error", "Terjadi kesalahan sistem", "error");
          });
      }
    });
  });

  // Form Submission - Pembatalan Member
  document.getElementById("formPembatalanMember")?.addEventListener("submit", function (e) {
    e.preventDefault();

    const member_id = document.getElementById("id_member_batal").value;
    const nama_penerima = document.getElementById("nama_penerima_member").value;
    const no_rekening = document.getElementById("no_rekening_member").value;
    const bank_ewallet = document.getElementById("bank_ewallet_member").value;

    Swal.fire({
      title: "Konfirmasi Pembatalan Member",
      text: "Apakah Anda yakin ingin membatalkan membership ini?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Ya, Ajukan",
      cancelButtonText: "Batal",
    }).then((result) => {
      if (result.isConfirmed) {
        const formData = new FormData();
        formData.append("action", "ajukan_batal_member");
        formData.append("member_id", member_id);
        formData.append("nama_penerima", nama_penerima);
        formData.append("no_rekening", no_rekening);
        formData.append("bank_ewallet", bank_ewallet);

        fetch("riwayat.php", {
          method: "POST",
          body: formData,
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.status === "success") {
              Swal.fire("Berhasil", data.message, "success").then(() => {
                location.reload();
              });
            } else {
              Swal.fire("Error", data.message, "error");
            }
          })
          .catch((error) => {
            console.error("Error:", error);
            Swal.fire("Error", "Terjadi kesalahan sistem", "error");
          });
      }
    });
  });

  // Date change handler for reguler
  document.getElementById("new_date")?.addEventListener("change", function () {
    const lapanganId = document.getElementById("id_lapangan_ubah").value;
    const selectedDate = this.value;

    if (lapanganId && selectedDate) {
      loadAvailableSessions(lapanganId, selectedDate);
    }
  });

  // Date change handler for member
  document.getElementById("new_date_member")?.addEventListener("change", function () {
    const lapanganId = document.getElementById("id_lapangan_member_ubah").value;
    const selectedDate = this.value;

    if (lapanganId && selectedDate) {
      loadAvailableSessionsMember(lapanganId, selectedDate);
    }
  });
});

// Global Functions
function showAlert(message) {
  Swal.fire("Info", message, "info");
}

function ubahRegulerSesi(id_sesi, lapangan_id, tanggal_lama, jam_lama, nama_lapangan) {
  document.getElementById("id_sesi_ubah").value = id_sesi;
  document.getElementById("id_lapangan_ubah").value = lapangan_id;
  document.getElementById("nama_lapangan_ubah").value = nama_lapangan;
  document.getElementById("tanggal_lama").value = formatTanggal(tanggal_lama);
  document.getElementById("jam_lama").value = jam_lama;

  // Reset form
  document.getElementById("new_date").value = "";
  document.getElementById("session-list").innerHTML = "<p>Pilih tanggal terlebih dahulu</p>";

  // Set min/max date
  const today = new Date().toISOString().split("T")[0];
  const maxDate = new Date();
  maxDate.setDate(maxDate.getDate() + 7);
  const maxDateStr = maxDate.toISOString().split("T")[0];

  document.getElementById("new_date").min = today;
  document.getElementById("new_date").max = maxDateStr;

  document.getElementById("modalUbahReguler").style.display = "block";
}

// Fungsi ajukan pembatalan
function ajukanBatal(id_sesi, tanggal, jam, nama_lapangan, jam_selesai) {
  document.getElementById("id_sesi_batal").value = id_sesi;
  document.getElementById("tanggal_batal").value = formatTanggal(tanggal);
  document.getElementById("jam_batal").value = jam + (jam_selesai ? " - " + jam_selesai : "");
  document.getElementById("lapangan_batal").value = nama_lapangan;

  // Clear form input
  document.getElementById("nama_penerima").value = "";
  document.getElementById("no_rekening").value = "";
  document.getElementById("bank_ewallet").selectedIndex = 0;

  // Tampilkan modal
  document.getElementById("modalBatal").style.display = "block";
}

// Format tanggal function (jika belum ada)
function formatTanggal(dateStr) {
  const date = new Date(dateStr);
  return date.toLocaleDateString("id-ID", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });
}

// Submit form pembatalan
document.getElementById("formPembatalan")?.addEventListener("submit", function (e) {
  e.preventDefault();

  const formData = new FormData();
  formData.append("action", "ajukan_pembatalan");
  formData.append("id_sesi", document.getElementById("id_sesi_batal").value);
  formData.append("nama_penerima", document.getElementById("nama_penerima").value);
  formData.append("no_rekening", document.getElementById("no_rekening").value);
  formData.append("bank_ewallet", document.getElementById("bank_ewallet").value);

  fetch("riwayat.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        Swal.fire({
          icon: "success",
          title: "Berhasil",
          text: data.message,
          confirmButtonColor: "#0b63d6",
        }).then(() => {
          document.getElementById("modalBatal").style.display = "none";
          location.reload();
        });
      } else {
        Swal.fire({
          icon: "error",
          title: "Gagal",
          text: data.message,
          confirmButtonColor: "#0b63d6",
        });
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Terjadi kesalahan sistem",
        confirmButtonColor: "#0b63d6",
      });
    });
});
// Validasi input nomor rekening real-time
document.getElementById("no_rekening")?.addEventListener("input", function (e) {
  // Hanya izinkan angka
  this.value = this.value.replace(/[^0-9]/g, "");
});

// Validasi sebelum submit
function validateNoRekening(noRekening) {
  // Cek hanya angka
  if (!/^[0-9]+$/.test(noRekening)) {
    return { valid: false, message: "Nomor rekening hanya boleh berisi angka" };
  }

  // Cek panjang minimal
  if (noRekening.length < 8) {
    return { valid: false, message: "Nomor rekening minimal 8 digit" };
  }

  // Cek panjang maksimal
  if (noRekening.length > 20) {
    return { valid: false, message: "Nomor rekening maksimal 20 digit" };
  }

  return { valid: true, message: "" };
}

// Submit form pembatalan dengan validasi
document.getElementById("formPembatalan")?.addEventListener("submit", function (e) {
  e.preventDefault();

  // Validasi form
  const namaPenerima = document.getElementById("nama_penerima").value.trim();
  const noRekening = document.getElementById("no_rekening").value.trim();
  const bankEwallet = document.getElementById("bank_ewallet").value;

  // Validasi nama penerima
  if (namaPenerima.length < 3) {
    Swal.fire({
      icon: "error",
      title: "Nama Penerima Tidak Valid",
      text: "Nama penerima minimal 3 karakter",
      confirmButtonColor: "#0b63d6",
    });
    return;
  }

  // Validasi nomor rekening
  const rekeningValidation = validateNoRekening(noRekening);
  if (!rekeningValidation.valid) {
    Swal.fire({
      icon: "error",
      title: "Nomor Rekening Tidak Valid",
      text: rekeningValidation.message,
      confirmButtonColor: "#0b63d6",
    });
    return;
  }

  // Validasi bank/ewallet
  if (!bankEwallet) {
    Swal.fire({
      icon: "error",
      title: "Bank/E-Wallet Belum Dipilih",
      text: "Silakan pilih bank atau e-wallet",
      confirmButtonColor: "#0b63d6",
    });
    return;
  }

  // Kirim data jika semua valid
  const formData = new FormData();
  formData.append("action", "ajukan_pembatalan");
  formData.append("id_sesi", document.getElementById("id_sesi_batal").value);
  formData.append("nama_penerima", namaPenerima);
  formData.append("no_rekening", noRekening);
  formData.append("bank_ewallet", bankEwallet);

  // Tampilkan loading
  Swal.fire({
    title: "Mengirim Pengajuan...",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });

  fetch("riwayat.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      Swal.close();
      if (data.status === "success") {
        Swal.fire({
          icon: "success",
          title: "Berhasil",
          text: data.message,
          confirmButtonColor: "#0b63d6",
        }).then(() => {
          document.getElementById("modalBatal").style.display = "none";
          location.reload();
        });
      } else {
        Swal.fire({
          icon: "error",
          title: "Gagal",
          text: data.message,
          confirmButtonColor: "#0b63d6",
        });
      }
    })
    .catch((error) => {
      Swal.close();
      console.error("Error:", error);
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Terjadi kesalahan sistem",
        confirmButtonColor: "#0b63d6",
      });
    });
});
// Close modal function
function closeModal() {
  document.getElementById("modalBatal").style.display = "none";
}

// Close modal ketika klik X
document.querySelectorAll(".close").forEach((btn) => {
  btn.addEventListener("click", function () {
    const modal = this.closest(".modal");
    if (modal) modal.style.display = "none";
  });
});

// Close modal ketika klik di luar
window.addEventListener("click", function (event) {
  if (event.target.classList.contains("modal")) {
    event.target.style.display = "none";
  }
});

function ajukanBatalMembership(member_id, tanggal_mulai = null) {
  document.getElementById("id_member_batal").value = member_id;

  if (tanggal_mulai) {
    document.getElementById("tanggal_mulai_member").value = formatTanggal(tanggal_mulai);
  }

  document.getElementById("modalBatalMember").style.display = "block";
}

function showUbahJadwalMember(member_id, lapangan_id, nama_lapangan) {
  document.getElementById("id_member_ubah").value = member_id;
  document.getElementById("id_lapangan_member_ubah").value = lapangan_id;
  document.getElementById("nama_lapangan_member").value = nama_lapangan;

  // Load member sessions
  loadMemberSessions(member_id);

  // Reset form
  document.getElementById("new_date_member").value = "";
  document.getElementById("member-session-list-new").innerHTML = "<p>Pilih tanggal terlebih dahulu</p>";

  // Set min date to today
  document.getElementById("new_date_member").min = new Date().toISOString().split("T")[0];

  document.getElementById("modalUbahMember").style.display = "block";
}

function loadMemberSessions(member_id) {
  const sessionList = document.getElementById("member-session-list");
  sessionList.innerHTML = "<p>Memuat sesi...</p>";

  fetch(`riwayat.php?action=get_member_sessions&member_id=${member_id}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        if (data.member_sessions.length === 0) {
          sessionList.innerHTML = "<p>Tidak ada sesi terjadwal</p>";
          return;
        }

        let html = '<div class="checkbox-group">';
        data.member_sessions.forEach((session) => {
          const canSelect = session.can_change;
          html += `
                        <label class="checkbox-label ${!canSelect ? "disabled" : ""}">
                            <input type="checkbox" name="member_session" value="${session.id_member_jadwal}" 
                                   ${!canSelect ? "disabled" : ""}>
                            <span>
                                ${formatTanggal(session.tanggal_booking)} 
                                ${session.jam_mulai}-${session.jam_selesai}
                                ${!canSelect ? '<small style="color:#e74c3c">(Lewat waktu)</small>' : ""}
                            </span>
                        </label>
                    `;
        });
        html += "</div>";

        sessionList.innerHTML = html;
      } else {
        sessionList.innerHTML = `<p class="error">${data.message}</p>`;
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      sessionList.innerHTML = '<p class="error">Gagal memuat sesi</p>';
    });
}

function loadAvailableSessions(lapangan_id, selected_date) {
  const sessionList = document.getElementById("session-list");
  sessionList.innerHTML = "<p>Memuat jam tersedia...</p>";

  fetch(`riwayat.php?action=get_available_sessions&lapangan_id=${lapangan_id}&selected_date=${selected_date}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        if (data.available_sessions.length === 0) {
          sessionList.innerHTML = "<p>Tidak ada jam tersedia</p>";
          return;
        }

        let html = '<div class="session-grid">';
        data.available_sessions.forEach((session) => {
          const isDisabled = session.disabled;
          html += `
                        <label class="session-option ${isDisabled ? "disabled" : ""}">
                            <input type="radio" name="new_session" value="${session.id_jadwal_waktu}" 
                                   ${isDisabled ? "disabled" : ""}>
                            <div class="session-time">
                                <strong>${session.jam_mulai} - ${session.jam_selesai}</strong>
                                <small>Rp ${session.harga.toLocaleString()}</small>
                                ${session.disabled_reason ? `<br><small class="disabled-reason">${session.disabled_reason}</small>` : ""}
                            </div>
                        </label>
                    `;
        });
        html += "</div>";

        sessionList.innerHTML = html;
      } else {
        sessionList.innerHTML = `<p class="error">${data.message}</p>`;
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      sessionList.innerHTML = '<p class="error">Gagal memuat jam</p>';
    });
}

function loadAvailableSessionsMember(lapangan_id, selected_date) {
  const sessionList = document.getElementById("member-session-list-new");
  sessionList.innerHTML = "<p>Memuat jam tersedia...</p>";

  fetch(`riwayat.php?action=get_available_sessions&lapangan_id=${lapangan_id}&selected_date=${selected_date}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        if (data.available_sessions.length === 0) {
          sessionList.innerHTML = "<p>Tidak ada jam tersedia</p>";
          return;
        }

        let html = '<div class="session-grid">';
        data.available_sessions.forEach((session) => {
          const isDisabled = session.disabled;
          html += `
                        <label class="session-option ${isDisabled ? "disabled" : ""}">
                            <input type="radio" name="new_session_member" value="${session.id_jadwal_waktu}" 
                                   ${isDisabled ? "disabled" : ""}>
                            <div class="session-time">
                                <strong>${session.jam_mulai} - ${session.jam_selesai}</strong>
                                <small>Rp ${session.harga.toLocaleString()}</small>
                                ${session.disabled_reason ? `<br><small class="disabled-reason">${session.disabled_reason}</small>` : ""}
                            </div>
                        </label>
                    `;
        });
        html += "</div>";

        sessionList.innerHTML = html;
      } else {
        sessionList.innerHTML = `<p class="error">${data.message}</p>`;
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      sessionList.innerHTML = '<p class="error">Gagal memuat jam</p>';
    });
}

function showDetail(booking_id, lapangan, tanggal, jam, harga, status, alasan, id_booking, payment_status, dp_amount, remaining_amount, username, nama_user) {
  // Update modal content
  document.getElementById("detail-id").textContent = id_booking;
  document.getElementById("detail-lapangan").textContent = lapangan;
  document.getElementById("detail-tanggal").textContent = formatTanggal(tanggal);
  document.getElementById("detail-jam").textContent = jam;
  document.getElementById("detail-pemesan").textContent = `${nama_user} (@${username})`;
  document.getElementById("detail-status").innerHTML = `<span class="status ${getStatusClass(status)}">${status}</span>`;
  document.getElementById("detail-total").textContent = `Rp ${harga}`;
  document.getElementById("detail-payment-status").textContent = payment_status === "lunas" ? "Lunas" : "DP";
  document.getElementById("detail-dp").textContent = dp_amount > 0 ? `Rp ${parseInt(dp_amount).toLocaleString()}` : "-";
  document.getElementById("detail-sisa").textContent = remaining_amount > 0 ? `Rp ${parseInt(remaining_amount).toLocaleString()}` : "Lunas";

  // Show/hide QR section
  const qrSection = document.getElementById("qr-section");
  if (status === "Disetujui") {
    qrSection.style.display = "block";
    generateQRCode(id_booking);
  } else {
    qrSection.style.display = "none";
  }

  // Show/hide refund section
  const refundSection = document.getElementById("refund-section");
  if (status.includes("Pembatalan Disetujui")) {
    refundSection.style.display = "block";
    // Here you would fetch refund details from API
  } else {
    refundSection.style.display = "none";
  }

  document.getElementById("modalDetail").style.display = "block";
}

function showMemberDetail(member_id, lapangan, durasi, tanggal_mulai, tanggal_berakhir, total_bayar, status, jadwal, sudah_ubah, max_ubah, id_member, username, nama_user) {
  // Update modal content
  document.getElementById("detail-member-id").textContent = id_member;
  document.getElementById("detail-member-lapangan").textContent = lapangan;
  document.getElementById("detail-member-pemesan").textContent = `${nama_user} (@${username})`;
  document.getElementById("detail-member-durasi").textContent = `${durasi} bulan`;
  document.getElementById("detail-member-periode").textContent = `${formatTanggal(tanggal_mulai)} - ${formatTanggal(tanggal_berakhir)}`;
  document.getElementById("detail-member-status").innerHTML = `<span class="status ${getStatusClass(status)}">${status}</span>`;
  document.getElementById("detail-member-total").textContent = `Rp ${total_bayar}`;
  document.getElementById("detail-member-sudah-ubah").textContent = `${sudah_ubah} kali`;
  document.getElementById("detail-member-sisa-kuota").textContent = `${max_ubah - sudah_ubah} kali`;

  // Show jadwal sessions
  const jadwalList = document.getElementById("detail-member-jadwal");
  if (jadwal && jadwal !== "Belum ada jadwal") {
    const sessions = jadwal.split("; ");
    let html = "<ul>";
    sessions.forEach((session) => {
      html += `<li>${session}</li>`;
    });
    html += "</ul>";
    jadwalList.innerHTML = html;
  } else {
    jadwalList.innerHTML = "<p>Belum ada jadwal terjadwal</p>";
  }

  // Show/hide QR section
  const qrSection = document.getElementById("qr-section-member");
  if (status === "aktif") {
    qrSection.style.display = "block";
    generateQRCodeMember(id_member);
  } else {
    qrSection.style.display = "none";
  }

  document.getElementById("modalDetailMember").style.display = "block";
}

function generateQRCode(booking_id) {
  const qrcodeDiv = document.getElementById("qrcode");
  qrcodeDiv.innerHTML = "";

  const qrData = `BOOKING:${booking_id}|${new Date().toISOString()}`;
  new QRCode(qrcodeDiv, {
    text: qrData,
    width: 200,
    height: 200,
    colorDark: "#000000",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.H,
  });
}

function generateQRCodeMember(member_id) {
  const qrcodeDiv = document.getElementById("qrcode-member");
  qrcodeDiv.innerHTML = "";

  const qrData = `MEMBER:${member_id}|${new Date().toISOString()}`;
  new QRCode(qrcodeDiv, {
    text: qrData,
    width: 200,
    height: 200,
    colorDark: "#000000",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.H,
  });
}

function getStatusClass(status) {
  if (status.includes("Disetujui")) return "disetujui";
  if (status.includes("Ditolak")) return "ditolak";
  if (status.includes("Menunggu")) return "menunggu";
  if (status.includes("aktif")) return "disetujui";
  return "menunggu";
}

function formatTanggal(tanggal) {
  const date = new Date(tanggal);
  const options = { weekday: "long", year: "numeric", month: "long", day: "numeric" };
  return date.toLocaleDateString("id-ID", options);
}

function closeModal() {
  document.getElementById("modalBatal").style.display = "none";
}

function closeModalMember() {
  document.getElementById("modalBatalMember").style.display = "none";
}

function closeModalUbah() {
  document.getElementById("modalUbahReguler").style.display = "none";
}

function closeModalUbahMember() {
  document.getElementById("modalUbahMember").style.display = "none";
}
