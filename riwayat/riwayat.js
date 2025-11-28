// ==============================================
// riwayat.js - VERSI REVISI 100% SYNC DENGAN PHP
// ==============================================

document.addEventListener("DOMContentLoaded", function () {
  console.log("Riwayat page loaded - Revised Version");

  initializeTabs();
  initializeCountdownTimers();
  initializeModalEvents();
  initializeDisabledButtonHandlers();
});

function initializeTabs() {
  document.querySelectorAll(".tab-button").forEach((btn) => {
    btn.addEventListener("click", () => {
      document.querySelectorAll(".tab-button").forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");

      document.querySelectorAll(".tab-content").forEach((c) => c.classList.remove("active"));
      document.getElementById(btn.dataset.tab + "-tab").classList.add("active");
    });
  });
}

function initializeCountdownTimers() {
  document.querySelectorAll(".countdown-timer[data-deadline]").forEach((timer) => {
    const deadline = new Date(timer.dataset.deadline).getTime();

    function update() {
      const now = new Date().getTime();
      const distance = deadline - now;

      if (distance <= 0) {
        timer.innerHTML = '<small class="expired-text">Waktu habis</small>';
        timer.classList.add("expired");

        const card = timer.closest(".card");
        card?.querySelectorAll(".btn-ubah, .btn-batal").forEach((btn) => {
          if (!btn.classList.contains("disabled")) {
            btn.classList.add("disabled");
            btn.disabled = true;

            // Update button text based on which deadline expired
            if (btn.classList.contains("btn-ubah")) {
              btn.innerHTML = '<i class="fa-solid fa-calendar-times"></i> Ubah Jadwal';
            } else if (btn.classList.contains("btn-batal")) {
              btn.innerHTML = '<i class="fa-solid fa-ban"></i> Batalkan';
            }
          }
        });
        return;
      }

      const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((distance % (1000 * 60)) / 1000);

      timer.innerHTML = `<small>
                <i class="fa-solid fa-clock"></i> Tersisa: 
                <strong>${hours}j ${minutes}m ${seconds}d</strong>
            </small>`;

      if (distance < 3600000) {
        // Kurang dari 1 jam
        timer.style.color = "#e74c3c";
        timer.style.fontWeight = "bold";
      }

      setTimeout(update, 1000);
    }
    update();
  });
}

function initializeModalEvents() {
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      if (typeof Swal !== "undefined") {
        Swal.close();
      }
    }
  });
}

function initializeDisabledButtonHandlers() {
  // Handle klik tombol disabled dengan pesan error
  document.addEventListener("click", function (e) {
    if (e.target.classList.contains("disabled")) {
      const btn = e.target;
      if (btn.classList.contains("btn-ubah")) {
        showDisabledMessage("ubah");
      } else if (btn.classList.contains("btn-batal")) {
        showDisabledMessage("batal");
      }
    }
  });
}

// Mencegah error jika Swal belum diload
if (typeof Swal === "undefined") {
  console.warn("SweetAlert2 tidak ter-load! Beberapa fitur mungkin tidak bekerja.");
  // Fallback untuk modal basic
  window.Swal = {
    fire: function (options) {
      if (typeof options === "string") {
        alert(options);
      } else {
        alert(options.title || "Info");
      }
      return Promise.resolve({ isConfirmed: false });
    },
    close: function () {},
    showLoading: function () {},
    showValidationMessage: function (msg) {
      alert(msg);
    },
  };
}

function formatTanggal(tanggal) {
  const options = { weekday: "long", year: "numeric", month: "long", day: "numeric" };
  return new Date(tanggal).toLocaleDateString("id-ID", options);
}

// === FUNGSI AJUKAN PEMBATALAN - DENGAN AUTO-FILL USERNAME ===
// === FUNGSI AJUKAN PEMBATALAN - FIXED AUTO-FILL ===
function ajukanBatal(id_sesi, tanggal, jam) {
  // Langsung tampilkan form, jika error tetap lanjut tanpa auto-fill
  showBatalForm(id_sesi, tanggal, jam);
}

function showBatalForm(id_sesi, tanggal, jam, username = "", nama = "") {
  Swal.fire({
    title: "Ajukan Pembatalan",
    html: `
      <div class="text-start p-3 bg-light rounded mb-3">
        <p class="mb-1"><strong>Tanggal:</strong> ${formatTanggal(tanggal)}</p>
        <p class="mb-0"><strong>Jam:</strong> ${jam}</p>
      </div>

      <div class="form-group mb-3">
        <label class="form-label">Username</label>
        <input type="text" id="username" class="form-control" value="${username}" readonly disabled>
        <small class="form-text text-muted">Auto-filled dari akun Anda</small>
      </div>

      <div class="form-group mb-3">
        <label class="form-label">Nama Penerima <span class="text-danger">*</span></label>
        <input type="text" id="nama_penerima" class="form-control" placeholder="Masukkan nama lengkap penerima transfer" value="${nama}" required>
      </div>

      <div class="form-group mb-3">
        <label class="form-label">No. Rekening / E-Wallet <span class="text-danger">*</span></label>
        <input type="text" id="no_rekening" class="form-control" placeholder="Contoh: 081234567890 atau 1234567890" required>
      </div>

      <div class="form-group mb-3">
        <label class="form-label">Bank / E-Wallet <span class="text-danger">*</span></label>
        <select id="bank_ewallet" class="form-control" required>
          <option value="">Pilih Bank / E-Wallet</option>
          <option value="BCA">BCA</option>
          <option value="BNI">BNI</option>
          <option value="BRI">BRI</option>
          <option value="Mandiri">Mandiri</option>
          <option value="CIMB Niaga">CIMB Niaga</option>
          <option value="OVO">OVO</option>
          <option value="GoPay">GoPay</option>
          <option value="DANA">DANA</option>
        </select>
      </div>

      <div class="alert alert-warning">
        <small>
          <i class="fas fa-exclamation-triangle"></i>
          <strong>Perhatian:</strong> Pengajuan pembatalan hanya dapat dilakukan minimal H-12 jam sebelum jadwal booking.
          Dana akan direfund 100% jika disetujui admin.
        </small>
      </div>
    `,
    width: "600px",
    showCancelButton: true,
    confirmButtonText: "Kirim Pengajuan",
    cancelButtonText: "Batal",
    didOpen: () => {
      // Coba ambil data user, jika berhasil update form
      fetch(`riwayat.php?action=get_user_info`)
        .then((response) => response.json())
        .then((userData) => {
          if (userData.status === "success") {
            document.getElementById("username").value = userData.username || "";
            if (!document.getElementById("nama_penerima").value) {
              document.getElementById("nama_penerima").value = userData.nama || "";
            }
          }
        })
        .catch((error) => {
          console.log("Auto-fill optional failed, continuing without it");
        });
    },
    preConfirm: () => {
      const nama_penerima = document.getElementById("nama_penerima").value.trim();
      const no_rekening = document.getElementById("no_rekening").value.trim();
      const bank_ewallet = document.getElementById("bank_ewallet").value;

      if (!nama_penerima) {
        Swal.showValidationMessage("Nama penerima harus diisi");
        return false;
      }
      if (!no_rekening) {
        Swal.showValidationMessage("Nomor rekening/ewallet harus diisi");
        return false;
      }
      if (!bank_ewallet) {
        Swal.showValidationMessage("Pilih bank/ewallet terlebih dahulu");
        return false;
      }

      return { id_sesi, nama_penerima, no_rekening, bank_ewallet };
    },
  }).then((result) => {
    if (!result.isConfirmed) return;
    processAjukanBatal(result.value);
  });
}

function processAjukanBatal(data) {
  const { id_sesi, nama_penerima, no_rekening, bank_ewallet } = data;

  console.log("Submitting cancellation:", data);

  Swal.fire({
    title: "Mengirim Pengajuan...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  // Buat form data dengan encoding yang benar
  const formData = new URLSearchParams();
  formData.append("action", "ajukan_pembatalan");
  formData.append("id_sesi", id_sesi);
  formData.append("nama_penerima", nama_penerima);
  formData.append("no_rekening", no_rekening);
  formData.append("bank_ewallet", bank_ewallet);

  console.log("FormData:", formData.toString());

  fetch("riwayat.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
    },
    body: formData,
  })
    .then((response) => {
      console.log("Response status:", response.status);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then((res) => {
      console.log("Cancellation API Response:", res);

      Swal.fire({
        icon: res.status === "success" ? "success" : "error",
        title: res.status === "success" ? "Pengajuan Terkirim!" : "Gagal",
        text: res.message || "Terjadi kesalahan",
        confirmButtonText: "OK",
      }).then(() => {
        if (res.status === "success") {
          location.reload();
        }
      });
    })
    .catch((error) => {
      console.error("Fetch error details:", error);
      Swal.fire({
        icon: "error",
        title: "Error Koneksi",
        html: `
          <div class="text-start">
            <p>Gagal terhubung ke server. Kemungkinan penyebab:</p>
            <ul class="text-sm">
              <li>• Koneksi internet terputus</li>
              <li>• Server sedang maintenance</li>
              <li>• Terjadi error di sistem</li>
            </ul>
            <p class="mt-2">Silakan coba beberapa saat lagi.</p>
          </div>
        `,
        confirmButtonText: "Mengerti",
      });
    });
}
// === NOTIFICATION SYSTEM ===
function showNotification(message, type = "info") {
  // Hapus notif lama
  const oldNotif = document.querySelector(".notif-toast");
  if (oldNotif) oldNotif.remove();

  const notif = document.createElement("div");
  notif.className = `notif-toast ${type}`;
  notif.innerHTML = `
    <i class="fa-solid ${type === "success" ? "fa-circle-check" : type === "error" ? "fa-circle-exclamation" : "fa-info-circle"}"></i>
    <span>${message}</span>
  `;

  document.body.appendChild(notif);

  // Auto remove
  setTimeout(() => notif.remove(), 5000);
}

// === UBAH JADWAL REGULER (PER SESI) ===
function ubahRegulerSesi(id_sesi, lapangan_id, currentDate, currentTime, nama_lapangan) {
  console.log("Ubah jadwal reguler:", { id_sesi, lapangan_id, currentDate, currentTime });

  Swal.fire({
    title: "Ubah Jadwal Booking",
    html: `
      <div class="text-start">
        <div class="current-booking-info mb-3 p-3 bg-light rounded">
          <h6>Booking Saat Ini:</h6>
          <p class="mb-1"><strong>Lapangan:</strong> ${nama_lapangan}</p>
          <p class="mb-1"><strong>Tanggal:</strong> ${formatTanggal(currentDate)}</p>
          <p class="mb-0"><strong>Jam:</strong> ${currentTime}</p>
        </div>
        
        <label class="form-label">Tanggal Baru:</label>
        <input type="date" id="new_date" class="form-control" 
               min="${new Date().toISOString().split("T")[0]}" 
               max="${getMaxDate(7)}" required>
        
        <label class="form-label mt-3">Jam Baru:</label>
        <select id="new_jam" class="form-control" disabled required>
          <option value="">Pilih tanggal terlebih dahulu</option>
        </select>
        
        <div class="alert alert-info mt-3">
          <small>
            <i class="fas fa-info-circle"></i>
            <strong>Batas Waktu:</strong> Hanya bisa mengubah jadwal maksimal H-5 jam sebelum waktu booking.
            <strong>Batas Tanggal:</strong> Maksimal 7 hari dari hari ini.
          </small>
        </div>
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: "Simpan Perubahan",
    cancelButtonText: "Batal",
    didOpen: () => {
      const tglInput = document.getElementById("new_date");
      const jamSelect = document.getElementById("new_jam");

      // Set default tanggal
      tglInput.value = new Date().toISOString().split("T")[0];

      // Load jam saat tanggal berubah
      tglInput.addEventListener("change", function () {
        if (this.value) {
          loadAvailableSessions(lapangan_id, this.value, id_sesi, jamSelect);
        }
      });

      // Load jam awal
      loadAvailableSessions(lapangan_id, tglInput.value, id_sesi, jamSelect);
    },
    preConfirm: () => {
      const tgl = document.getElementById("new_date").value;
      const jam = document.getElementById("new_jam").value;

      if (!tgl) {
        Swal.showValidationMessage("Pilih tanggal baru terlebih dahulu");
        return false;
      }
      if (!jam) {
        Swal.showValidationMessage("Pilih jam baru terlebih dahulu");
        return false;
      }

      return { id_sesi, new_date: tgl, new_jam: jam };
    },
  }).then((result) => {
    if (!result.isConfirmed || !result.value) return;

    const { id_sesi, new_date, new_jam } = result.value;

    // Konfirmasi akhir
    Swal.fire({
      title: "Konfirmasi Perubahan",
      html: `
        <div class="text-start">
          <p>Anda akan mengubah jadwal booking menjadi:</p>
          <div class="p-3 bg-light rounded">
            <p class="mb-1"><strong>Tanggal:</strong> ${formatTanggal(new_date)}</p>
            <p class="mb-0"><strong>Jam:</strong> ${getJamTextFromSelect(new_jam)}</p>
          </div>
          <p class="text-warning mt-2">
            <small><i class="fas fa-exclamation-triangle"></i> Pastikan jadwal baru sudah sesuai. Perubahan tidak dapat dibatalkan!</small>
          </p>
        </div>
      `,
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Ya, Ubah Jadwal",
      cancelButtonText: "Batal",
    }).then((confirmResult) => {
      if (confirmResult.isConfirmed) {
        processUbahJadwalReguler(id_sesi, new_date, new_jam);
      }
    });
  });
}

// === PROSES UBAH JADWAL REGULER ===
function processUbahJadwalReguler(id_sesi, new_date, new_jam) {
  Swal.fire({
    title: "Memproses...",
    text: "Sedang mengubah jadwal booking",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  fetch("riwayat.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: new URLSearchParams({
      action: "ubah_jadwal_sesi",
      id_sesi: id_sesi,
      new_date: new_date,
      new_jadwal_waktu: new_jam,
    }),
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.json();
    })
    .then((data) => {
      Swal.close();

      if (data.status === "success") {
        Swal.fire({
          icon: "success",
          title: "Berhasil!",
          text: data.message,
          confirmButtonText: "OK",
        }).then(() => {
          window.location.reload();
        });
      } else {
        Swal.fire({
          icon: "error",
          title: "Gagal",
          text: data.message || "Terjadi kesalahan yang tidak diketahui",
          confirmButtonText: "OK",
        });
      }
    })
    .catch((error) => {
      Swal.close();
      console.error("Error:", error);
      Swal.fire({
        icon: "error",
        title: "Error Jaringan",
        text: "Terjadi kesalahan jaringan: " + error.message,
        confirmButtonText: "OK",
      });
    });
}

// === UBAH JADWAL MEMBER ===
function showUbahJadwalMember(member_id, nama_lapangan, sisa_kuota, lapangan_id) {
  console.log("Ubah jadwal member:", { member_id, sisa_kuota });

  // Loading pertama
  Swal.fire({
    title: "Memuat...",
    text: "Sedang memuat data sesi member",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  // Load member sessions
  fetch(`riwayat.php?action=get_member_sessions&member_id=${member_id}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.json();
    })
    .then((data) => {
      Swal.close();

      if (data.status !== "success" || !data.member_sessions || data.member_sessions.length === 0) {
        Swal.fire({
          icon: "error",
          title: "Gagal",
          text: data.message || "Tidak ada sesi member yang dapat diubah",
          confirmButtonText: "OK",
        });
        return;
      }

      showMemberSessionSelection(member_id, nama_lapangan, sisa_kuota, lapangan_id, data.member_sessions);
    })
    .catch((error) => {
      Swal.close();
      console.error("Error:", error);
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Gagal memuat data member: " + error.message,
        confirmButtonText: "OK",
      });
    });
}

// === PILIH SESI MEMBER ===
function showMemberSessionSelection(member_id, nama_lapangan, sisa_kuota, lapangan_id, sessions) {
  const sessionsHTML = sessions
    .map((session) => {
      const canEdit = validateH12Jam(session.tanggal_booking, session.jam_mulai);
      const dateFormatted = formatTanggal(session.tanggal_booking);
      const isActive = session.status_jadwal === "aktif";

      return `
        <div class="form-check mb-2">
          <input class="form-check-input session-checkbox" type="checkbox" 
                 value="${session.id_member_jadwal}" 
                 id="session-${session.id_member_jadwal}"
                 ${canEdit && isActive ? "" : "disabled"}>
          <label class="form-check-label ${canEdit && isActive ? "" : "text-muted"}" 
                 for="session-${session.id_member_jadwal}">
            ${dateFormatted} - ${session.jam_mulai} sampai ${session.jam_selesai}
            ${!isActive ? '<small class="text-danger ms-2">(Sesi nonaktif)</small>' : !canEdit ? '<small class="text-danger ms-2">(Tidak dapat diubah - kurang dari 12 jam)</small>' : ""}
          </label>
        </div>
      `;
    })
    .join("");

  Swal.fire({
    title: "Ubah Jadwal Member",
    html: `
      <div class="text-start">
        <div class="member-info mb-3 p-3 bg-light rounded">
          <h6>Detail Membership:</h6>
          <p class="mb-1"><strong>Lapangan:</strong> ${nama_lapangan}</p>
          <p class="mb-0"><strong>Sisa Kuota Ubah:</strong> ${sisa_kuota} kali</p>
        </div>
        
        <p class="fw-bold">Pilih sesi yang ingin diubah:</p>
        <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 5px;">
          ${sessionsHTML}
        </div>
        
        <div class="row mt-3">
          <div class="col-md-6">
            <label class="form-label">Tanggal Baru:</label>
            <input type="date" id="tgl_baru_member" class="form-control" 
                   min="${new Date().toISOString().split("T")[0]}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Jam Baru:</label>
            <select id="jam_baru_member" class="form-control" disabled>
              <option value="">Pilih tanggal dulu</option>
            </select>
          </div>
        </div>
        
        <div class="alert alert-info mt-3">
          <small>
            <i class="fas fa-info-circle"></i>
            <strong>Info Member:</strong> 
            • Bisa mengubah multiple sesi sekaligus ke tanggal & jam yang sama
            • Kuota ubah: ${sisa_kuota} dari 3 kali
            • Batas waktu: H-12 jam sebelum sesi
          </small>
        </div>
      </div>
    `,
    width: "800px",
    showCancelButton: true,
    confirmButtonText: "Lanjutkan",
    cancelButtonText: "Batal",
    didOpen: () => {
      const tglInput = document.getElementById("tgl_baru_member");
      const jamSelect = document.getElementById("jam_baru_member");

      tglInput.value = new Date().toISOString().split("T")[0];

      tglInput.addEventListener("change", function () {
        if (this.value) {
          loadAvailableSessions(lapangan_id, this.value, 0, jamSelect);
        }
      });

      loadAvailableSessions(lapangan_id, tglInput.value, 0, jamSelect);
    },
    preConfirm: () => {
      const selectedSessions = Array.from(document.querySelectorAll(".session-checkbox:checked")).map((cb) => cb.value);

      const tgl = document.getElementById("tgl_baru_member").value;
      const jam = document.getElementById("jam_baru_member").value;

      if (selectedSessions.length === 0) {
        Swal.showValidationMessage("Pilih minimal satu sesi member");
        return false;
      }
      if (!tgl) {
        Swal.showValidationMessage("Pilih tanggal baru");
        return false;
      }
      if (!jam) {
        Swal.showValidationMessage("Pilih jam baru");
        return false;
      }

      return {
        member_id: member_id,
        lapangan_id: lapangan_id,
        member_session_ids: selectedSessions,
        new_date: tgl,
        selected_session: jam,
      };
    },
  }).then((result) => {
    if (!result.isConfirmed || !result.value) return;

    processUbahJadwalMember(result.value);
  });
}

// === PROSES UBAH JADWAL MEMBER ===
function processUbahJadwalMember(data) {
  const { member_id, lapangan_id, member_session_ids, new_date, selected_session } = data;

  Swal.fire({
    title: "Memproses...",
    text: `Sedang mengubah ${member_session_ids.length} sesi member`,
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  const formData = new URLSearchParams({
    action: "ubah_jadwal_member",
    member_id: member_id,
    lapangan_id: lapangan_id,
    new_date: new_date,
    selected_session: selected_session,
  });

  member_session_ids.forEach((id, index) => {
    formData.append(`member_session_ids[${index}]`, id);
  });

  fetch("riwayat.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: formData,
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.json();
    })
    .then((data) => {
      Swal.close();

      if (data.status === "success") {
        Swal.fire({
          icon: "success",
          title: "Berhasil!",
          text: data.message,
          confirmButtonText: "OK",
        }).then(() => {
          window.location.reload();
        });
      } else {
        Swal.fire({
          icon: "error",
          title: "Gagal",
          text: data.message,
          confirmButtonText: "OK",
        });
      }
    })
    .catch((error) => {
      Swal.close();
      console.error("Error:", error);
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Terjadi kesalahan jaringan: " + error.message,
        confirmButtonText: "OK",
      });
    });
}

// === LOAD AVAILABLE SESSIONS - FIXED ===
// === LOAD AVAILABLE SESSIONS - COMPLETELY FIXED ===
function loadAvailableSessions(lapangan_id, tanggal, booking_id, selectElement) {
  console.log("Loading sessions for:", { lapangan_id, tanggal, booking_id });

  if (!tanggal || !lapangan_id) {
    selectElement.innerHTML = '<option value="">Pilih tanggal terlebih dahulu</option>';
    selectElement.disabled = true;
    return;
  }

  selectElement.innerHTML = '<option value="">Memuat jam tersedia...</option>';
  selectElement.disabled = true;

  // Validasi tanggal
  const today = new Date();
  const selectedDate = new Date(tanggal);
  const maxDate = new Date();
  maxDate.setDate(today.getDate() + 7);

  // Reset ke hari ini jika tanggal invalid
  if (selectedDate < today) {
    selectedDate.setDate(today.getDate());
  }

  if (selectedDate > maxDate) {
    selectElement.innerHTML = '<option value="">Tanggal maksimal 7 hari dari sekarang</option>';
    selectElement.disabled = true;
    return;
  }

  // Format tanggal untuk API
  const formattedDate = selectedDate.toISOString().split("T")[0];

  const url = `riwayat.php?action=get_available_sessions&lapangan_id=${lapangan_id}&selected_date=${formattedDate}&booking_id=${booking_id || 0}&t=${Date.now()}`;

  console.log("Fetching URL:", url);

  fetch(url)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      console.log("Sessions API Response:", data);

      if (data.status === "success" && data.available_sessions && data.available_sessions.length > 0) {
        selectElement.innerHTML =
          '<option value="">Pilih jam</option>' +
          data.available_sessions
            .map(
              (session) =>
                `<option value="${session.id_jadwal_waktu}">
                  ${session.jam_mulai} - ${session.jam_selesai} 
                  ${session.harga ? `(Rp ${Number(session.harga).toLocaleString("id-ID")})` : ""}
                </option>`
            )
            .join("");
        selectElement.disabled = false;
        console.log("Sessions loaded successfully:", data.available_sessions.length + " sessions");
      } else {
        const errorMsg = data.message || "Tidak ada jam tersedia untuk tanggal ini";
        selectElement.innerHTML = `<option value="">${errorMsg}</option>`;
        selectElement.disabled = true;
        console.warn("No sessions available:", data.message);
      }
    })
    .catch((error) => {
      console.error("Error loading sessions:", error);
      selectElement.innerHTML = '<option value="">Gagal memuat jam. Coba refresh halaman.</option>';
      selectElement.disabled = true;
    });
}

// === LIHAT DETAIL FUNCTIONS ===
function showDetail(id_booking, lapangan, tanggal, jam, total, status, alasanPenolakan = "", uniqueId = "") {
  const formattedDate = formatTanggal(tanggal);
  const statusClass = getStatusClass(status);

  let detailHTML = `
    <div class="text-start">
      <div class="detail-section">
        <p><strong>ID Booking:</strong> ${uniqueId || "#" + id_booking}</p>
        <p><strong>Lapangan:</strong> ${lapangan}</p>
        <p><strong>Tanggal Booking:</strong> ${formattedDate}</p>
        <p><strong>Jam:</strong> ${jam || "-"}</p>
        <p><strong>Total:</strong> Rp ${parseInt(total).toLocaleString("id-ID")}</p>
        <p><strong>Status:</strong> <span class="status-badge ${statusClass}">${status.toUpperCase()}</span></p>
      </div>
  `;

  if (status === "ditolak" && alasanPenolakan) {
    detailHTML += `
      <div class="alert alert-warning mt-3">
        <strong>Alasan Penolakan:</strong> ${alasanPenolakan}
      </div>
    `;
  }

  if (status === "disetujui") {
    detailHTML += `
      <div class="alert alert-success mt-3">
        <i class="fas fa-qrcode"></i> Tunjukkan QR code di tempat untuk check-in
      </div>
      <div id="qrcode-container" class="text-center mt-3"></div>
    `;
  }

  if (status === "Pembatalan Disetujui") {
    detailHTML += `
      <div class="alert alert-info mt-3">
        <i class="fas fa-money-bill-wave"></i> Dana sudah dikembalikan
        <div class="mt-2">
          <small>Bukti transfer refund telah diupload oleh admin</small>
        </div>
      </div>
    `;
  }

  detailHTML += `</div>`;

  Swal.fire({
    title: "Detail Booking",
    html: detailHTML,
    width: 600,
    confirmButtonText: "Tutup",
    didOpen: () => {
      // Generate QR Code untuk booking yang disetujui
      if (status === "disetujui") {
        const qrContainer = document.getElementById("qrcode-container");
        if (qrContainer) {
          new QRCode(qrContainer, {
            text: `BOOKING-${uniqueId || id_booking}`,
            width: 128,
            height: 128,
          });
        }
      }
    },
  });
}

function showMemberDetail(id_member, lapangan, durasi, mulai, berakhir, total, status, jadwal, ubahCount, maxUbah, uniqueId = "") {
  const startFormatted = formatTanggal(mulai);
  const endFormatted = formatTanggal(berakhir);
  const statusClass = getStatusClass(status);

  let detailHTML = `
    <div class="text-start">
      <div class="detail-section">
        <p><strong>ID Member:</strong> ${uniqueId || "#" + id_member}</p>
        <p><strong>Lapangan:</strong> ${lapangan}</p>
        <p><strong>Durasi:</strong> ${durasi} bulan</p>
        <p><strong>Periode:</strong> ${startFormatted} - ${endFormatted}</p>
        <p><strong>Total Bayar:</strong> Rp ${parseInt(total).toLocaleString("id-ID")}</p>
        <p><strong>Status:</strong> <span class="status-badge ${statusClass}">${status.toUpperCase()}</span></p>
        <p><strong>Sisa Ubah Jadwal:</strong> ${maxUbah - ubahCount} dari ${maxUbah} kali</p>
      </div>
  `;

  if (jadwal && jadwal !== "" && jadwal !== "null") {
    detailHTML += `
      <div class="schedule-section mt-3">
        <strong>Jadwal Terjadwal:</strong>
        <div style="max-height: 150px; overflow-y: auto; margin-top: 10px;">
    `;

    const jadwalList = jadwal.split("; ");
    jadwalList.forEach((j) => {
      if (j.trim() !== "") {
        detailHTML += `<div class="schedule-item">📅 ${j}</div>`;
      }
    });

    detailHTML += `</div></div>`;
  }

  detailHTML += `</div>`;

  Swal.fire({
    title: "Detail Membership",
    html: detailHTML,
    width: 600,
    confirmButtonText: "Tutup",
  });
}

// === HELPER FUNCTIONS ===
function formatTanggal(tanggal) {
  const date = new Date(tanggal);
  return date.toLocaleDateString("id-ID", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });
}

function getMaxDate(days = 7) {
  const maxDate = new Date();
  maxDate.setDate(maxDate.getDate() + days);
  return maxDate.toISOString().split("T")[0];
}

function getJamTextFromSelect(optionValue) {
  const select = document.getElementById("new_jam");
  const selectedOption = select?.options[select.selectedIndex];
  return selectedOption ? selectedOption.text.split(" (")[0] : "";
}

function validateH12Jam(tanggal, jam) {
  if (!tanggal || !jam) return false;

  try {
    const bookingTime = new Date(tanggal + "T" + jam + ":00+07:00"); // Timezone Jakarta
    const now = new Date();
    const deadline = new Date(bookingTime);
    deadline.setHours(deadline.getHours() - 12);

    return now <= deadline;
  } catch (error) {
    console.error("Error validating H12:", error);
    return false;
  }
}

function getStatusClass(status) {
  const statusMap = {
    menunggu: "menunggu",
    pending: "menunggu",
    disetujui: "disetujui",
    aktif: "disetujui",
    ditolak: "ditolak",
    nonaktif: "ditolak",
    dibatalkan: "ditolak",
    "menunggu pengajuan": "orange",
    "pembatalan ditolak": "ditolak",
    "pembatalan disetujui": "primary",
  };
  return statusMap[status.toLowerCase()] || "menunggu";
}

function showDisabledMessage(type) {
  const messages = {
    ubah: "Maaf, waktu ubah jadwal telah habis (H-5 jam dari waktu pemesanan). Anda sudah tidak dapat mengubah jadwal.",
    batal: "Maaf, waktu ajukan pembatalan telah habis (H-12 jam dari waktu pemesanan). Anda sudah tidak dapat membatalkan booking.",
  };

  Swal.fire({
    icon: "warning",
    title: "Tidak Dapat Dilakukan",
    text: messages[type] || "Tombol ini tidak dapat digunakan",
    confirmButtonText: "Mengerti",
  });
}

function showDisabledReason(reason) {
  const messages = {
    already_used: "Anda sudah menggunakan kesempatan ubah jadwal (maksimal 1x per sesi)",
    time_expired: "Waktu ubah jadwal sudah habis (H-5 jam dari booking)",
    member_not_active: "Membership tidak aktif. Tidak dapat mengubah jadwal",
    quota_exceeded: "Kuota ubah jadwal member sudah habis (maksimal 3x)",
    booking_rejected: "Booking ditolak. Tidak dapat mengubah jadwal",
    booking_cancelled: "Booking dibatalkan. Tidak dapat mengubah jadwal",
  };

  const message = messages[reason] || "Tidak dapat mengubah jadwal";

  Swal.fire({
    icon: "warning",
    title: "Tidak Dapat Diubah",
    text: message,
    confirmButtonText: "Mengerti",
  });
}
// === DEBUG FUNCTION ===
function debugSessions() {
  console.log("=== DEBUG SESSIONS ===");
  const cards = document.querySelectorAll(".card");
  cards.forEach((card, index) => {
    const sesi = card.dataset.sesi;
    const lapangan = card.querySelector("h3").textContent;
    console.log(`Card ${index + 1}:`, { sesi, lapangan });
  });
}

// Panggil saat page load
document.addEventListener("DOMContentLoaded", function () {
  console.log("Riwayat page loaded - Debug Version");
  debugSessions();

  initializeTabs();
  initializeCountdownTimers();
  initializeModalEvents();
  initializeDisabledButtonHandlers();
});
// === GLOBAL ERROR HANDLER ===
window.addEventListener("error", function (e) {
  console.error("Global error:", e.error);
  showNotification("Terjadi kesalahan pada halaman", "error");
});

// Prevent form resubmission
if (window.history.replaceState) {
  window.history.replaceState(null, null, window.location.href);
}

// Export functions for global access
window.ubahRegulerSesi = ubahRegulerSesi;
window.ajukanBatal = ajukanBatal;
window.showDetail = showDetail;
window.showMemberDetail = showMemberDetail;
window.showUbahJadwalMember = showUbahJadwalMember;
window.showDisabledReason = showDisabledReason;
