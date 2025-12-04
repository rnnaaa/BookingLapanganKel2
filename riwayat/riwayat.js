// riwayat.js

// --- UTILS ---
function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) modal.classList.remove("active");
}

window.onclick = function (event) {
  if (event.target.classList.contains("modal")) {
    event.target.classList.remove("active");
  }
};

document.addEventListener("DOMContentLoaded", function () {
  // 1. Tab Switching
  const tabs = document.querySelectorAll(".tab-button");
  const contents = document.querySelectorAll(".tab-content");

  tabs.forEach((btn) => {
    btn.addEventListener("click", () => {
      tabs.forEach((t) => t.classList.remove("active"));
      contents.forEach((c) => c.classList.remove("active"));

      btn.classList.add("active");
      const targetId = btn.dataset.tab + "-tab";
      const targetContent = document.getElementById(targetId);
      if (targetContent) targetContent.classList.add("active");
    });
  });

  // 2. Timer Countdown
  setInterval(updateCountdowns, 1000);
  updateCountdowns();
});

// --- TIMER LOGIC ---
function updateCountdowns() {
  const timers = document.querySelectorAll(".countdown-timer[data-deadline]");
  timers.forEach((timerEl) => {
    const deadlineAttr = timerEl.getAttribute("data-deadline");
    if (!deadlineAttr) return;

    const deadline = new Date(deadlineAttr);
    const now = new Date();
    const diff = deadline - now;

    if (diff <= 0) {
      timerEl.innerText = "00:00:00";
      const card = timerEl.closest(".card");
      if (card) {
        const btns = card.querySelectorAll(".btn-solid.orange, .btn-solid.red");
        btns.forEach((b) => {
          b.disabled = true;
          b.style.opacity = "0.5";
          b.style.cursor = "not-allowed";
          b.title = "Batas waktu perubahan telah habis";
        });
      }
      return;
    }

    const hours = Math.floor(diff / (1000 * 60 * 60))
      .toString()
      .padStart(2, "0");
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
      .toString()
      .padStart(2, "0");
    const seconds = Math.floor((diff % (1000 * 60)) / 1000)
      .toString()
      .padStart(2, "0");
    timerEl.innerText = `${hours}:${minutes}:${seconds}`;
  });
}

// =================================================================
//  1. FITUR LIHAT DETAIL (QR CODE / BUKTI REFUND)
// =================================================================
function openDetailBooking(idBooking) {
  const modal = document.getElementById("modalDetail");
  const content = document.getElementById("detailContent");
  const dynamicArea = document.getElementById("dynamic-auth-area");

  modal.classList.add("active");
  content.innerHTML = '<div class="text-center py-8 text-slate-500"><i class="fa-solid fa-circle-notch fa-spin fa-2x mb-2"></i><p>Memuat data...</p></div>';
  dynamicArea.style.display = "none";

  const formData = new FormData();
  formData.append("action", "get_booking_detail");
  formData.append("id_booking", idBooking);

  fetch("riwayat_api.php", { method: "POST", body: formData })
    .then((res) => res.json())
    .then((resp) => {
      if (resp.status === "success") {
        const d = resp.data;

        // Styling Badge Status
        let statusClass = "bg-slate-100 text-slate-600";
        const st = d.status_booking.toLowerCase();
        if (st === "disetujui" || st === "selesai" || st === "lunas") statusClass = "bg-green-100 text-green-700";
        else if (st === "ditolak" || st === "dibatalkan") statusClass = "bg-red-100 text-red-700";
        else if (st === "menunggu" || st === "belum lunas") statusClass = "bg-yellow-100 text-yellow-700";

        // [BARU] LOGIKA TAMPILAN DP / SISA TAGIHAN
        let extraPaymentInfo = "";
        // Cek jika status pembayaran DP atau Belum Lunas, dan ada sisa tagihan
        if (d.payment_status === "dp_bayar" || d.payment_status === "belum_lunas" || parseInt(d.sisa_raw) > 0) {
          extraPaymentInfo = `
                    <div class="flex justify-between items-center text-xs mt-2 pt-2 border-t border-slate-100">
                        <span class="text-blue-600">Sudah Bayar (DP)</span>
                        <span class="font-semibold text-blue-600">${d.dp}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs mt-1">
                        <span class="text-red-500 font-bold">Kekurangan</span>
                        <span class="font-bold text-red-500">${d.sisa}</span>
                    </div>
                `;
        }

        // Render Konten Utama Modal
        content.innerHTML = `
                <div class="bg-slate-50 p-5 rounded-xl border border-slate-200 shadow-sm mb-4">
                    <div class="flex justify-between items-start mb-4 border-b border-slate-200 pb-3">
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-bold tracking-wider">Kode Booking</p>
                            <p class="font-mono text-xl font-bold text-slate-800 mt-1 tracking-wide">${d.kode_booking}</p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase ${statusClass}">${d.status_booking}</span>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-y-3 gap-x-2 text-sm">
                        <div><p class="text-slate-400 text-xs">Lapangan</p><p class="font-semibold text-slate-700">${d.nama_lapangan}</p></div>
                        <div class="text-right"><p class="text-slate-400 text-xs">Tanggal</p><p class="font-semibold text-slate-700">${d.tanggal}</p></div>
                        
                        <div><p class="text-slate-400 text-xs">Jam Main</p><p class="font-semibold text-slate-700 font-mono">${d.jam}</p></div>
                        <div class="text-right"><p class="text-slate-400 text-xs">Atas Nama</p><p class="font-semibold text-slate-700">${d.user}</p></div>

                        <div class="col-span-2 border-t border-dashed border-slate-300 pt-3 mt-2">
                            <div class="flex justify-between items-center">
                                <p class="text-slate-500 font-bold">Total Harga</p>
                                <p class="font-bold text-slate-800 text-lg">${d.total_harga}</p>
                            </div>
                            ${extraPaymentInfo}
                        </div>
                    </div>
                </div>
            `;

        // === LOGIKA AREA DINAMIS (BAWAH) ===
        dynamicArea.innerHTML = "";
        dynamicArea.style.display = "block";

        if (d.status_refund === "approved" && d.bukti_refund) {
          dynamicArea.innerHTML = `
                    <div class="text-center">
                        <div class="inline-block px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold mb-3">
                            <i class="fa-solid fa-check-circle"></i> REFUND DISETUJUI
                        </div>
                        <p class="text-xs text-slate-500 mb-2 font-medium">Bukti Transfer Pengembalian Dana:</p>
                        <div class="border rounded-lg overflow-hidden p-1 bg-white shadow-sm inline-block">
                            <img src="../uploads/bukti_refund/${d.bukti_refund}" alt="Bukti Refund" class="w-full h-auto object-contain" style="max-height: 250px; max-width: 100%;">
                        </div>
                        <br>
                        <a href="../uploads/bukti_refund/${d.bukti_refund}" target="_blank" class="text-xs text-blue-500 mt-3 inline-block hover:underline">
                            <i class="fa-solid fa-magnifying-glass-plus"></i> Lihat Ukuran Penuh
                        </a>
                    </div>
                `;
        } else if (d.status_refund === "rejected") {
          dynamicArea.innerHTML = `
                    <div class="bg-red-50 border border-red-100 p-4 rounded-lg text-center">
                        <i class="fa-solid fa-circle-xmark text-red-500 text-2xl mb-2"></i>
                        <h4 class="font-bold text-red-700 text-sm">Pengajuan Refund Ditolak</h4>
                        <p class="text-xs text-red-600 mt-1">Silakan hubungi admin untuk informasi lebih lanjut.</p>
                    </div>
                `;
        } else if (d.status_refund === "pending") {
          dynamicArea.innerHTML = `
                    <div class="bg-yellow-50 border border-yellow-100 p-4 rounded-lg text-center">
                        <i class="fa-solid fa-clock text-yellow-600 text-2xl mb-2 animate-pulse"></i>
                        <h4 class="font-bold text-yellow-800 text-sm">Menunggu Proses Verifikasi</h4>
                        <p class="text-xs text-yellow-700 mt-1">Admin sedang memproses pengajuan pembatalan Anda.</p>
                    </div>
                `;
        } else if (["disetujui", "selesai", "belum lunas"].includes(st) || d.payment_status === "dp_bayar") {
          dynamicArea.innerHTML = `
                    <p class="mb-3 font-bold text-slate-700">QR Code Check-in</p>
                    <div id="qrcode-container" class="flex justify-center bg-white p-3 rounded-xl border border-slate-200 shadow-sm inline-block"></div>
                    <p class="text-xs text-slate-400 mt-3 font-medium">Tunjukkan ke petugas lapangan</p>
                `;
          new QRCode(document.getElementById("qrcode-container"), {
            text: `BOOKING:${d.id_booking}|VALID`,
            width: 150,
            height: 150,
            colorDark: "#1e293b",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H,
          });
        } else {
          dynamicArea.style.display = "none";
        }
      } else {
        content.innerHTML = `<div class="text-center py-4 text-red-500"><p>${resp.message}</p></div>`;
      }
    })
    .catch((err) => {
      console.error(err);
      content.innerHTML = `<div class="text-center py-4 text-red-500"><p>Gagal memuat data.</p></div>`;
    });
}

// =================================================================
//  2. FITUR PENGAJUAN PEMBATALAN (BARU & LENGKAP)
// =================================================================
function openAjukanBatal(idSesi, lapangan, tanggal, jam, refundAmount) {
  // Isi input hidden ID
  document.getElementById("batal_id_sesi").value = idSesi;

  // Isi Teks Detail di Modal (Visual untuk User)
  document.getElementById("batal_lapangan").innerText = lapangan;
  document.getElementById("batal_tanggal").innerText = tanggal;
  document.getElementById("batal_jam").innerText = jam;

  // Tampilkan Dana Pengembalian (Estimasi)
  document.getElementById("batal_refund_amount").innerText = "Rp " + (refundAmount || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");

  // Tampilkan Modal
  document.getElementById("modalBatal").classList.add("active");
}

// Handle Submit Form Pembatalan
const formBatal = document.getElementById("formBatal");
if (formBatal) {
  formBatal.addEventListener("submit", function (e) {
    e.preventDefault();

    Swal.fire({
      title: "Yakin batalkan jadwal?",
      text: "Jadwal akan dilepas dan pengajuan refund akan dikirim ke admin.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#ef4444",
      cancelButtonColor: "#64748b",
      confirmButtonText: "Ya, Batalkan",
      cancelButtonText: "Kembali",
    }).then((result) => {
      if (result.isConfirmed) {
        const formData = new FormData(this);
        formData.append("action", "ajukan_pembatalan");

        Swal.fire({
          title: "Mengirim...",
          didOpen: () => {
            Swal.showLoading();
          },
        });

        fetch("riwayat_api.php", { method: "POST", body: formData })
          .then((r) => r.json())
          .then((res) => {
            if (res.status === "success") {
              Swal.fire({
                title: "Terkirim!",
                text: "Pengajuan berhasil dikirim. Menunggu verifikasi admin.",
                icon: "success",
              }).then(() => {
                location.reload();
              });
            } else {
              Swal.fire("Gagal", res.message, "error");
            }
          })
          .catch((err) => {
            Swal.fire("Error", "Terjadi kesalahan server", "error");
          });
      }
    });
  });
}

// =================================================================
//  3. FITUR UBAH JADWAL (RESCHEDULE)
// =================================================================
function openUbahJadwal(idSesi, idLapangan) {
  document.getElementById("ubah_id_sesi").value = idSesi;
  document.getElementById("ubah_id_lapangan").value = idLapangan;
  document.getElementById("new_date").value = "";
  const select = document.getElementById("ubah_jam");
  select.innerHTML = "<option>Pilih tanggal dulu...</option>";
  select.disabled = true;
  document.getElementById("modalUbah").classList.add("active");
}

const dateInput = document.getElementById("new_date");
if (dateInput) {
  dateInput.addEventListener("change", function () {
    const lapanganId = document.getElementById("ubah_id_lapangan").value;
    loadAvailableSlots(lapanganId, this.value);
  });
}

function loadAvailableSlots(lapanganId, date) {
  const select = document.getElementById("ubah_jam");
  select.innerHTML = "<option>Memuat...</option>";
  select.disabled = true;

  fetch(`riwayat_api.php?action=get_available_sessions&lapangan_id=${lapanganId}&selected_date=${date}`)
    .then((r) => r.json())
    .then((res) => {
      select.innerHTML = "";
      if (res.status === "success") {
        if (res.available_sessions.length === 0) {
          select.add(new Option("Penuh / Tidak tersedia", ""));
        } else {
          select.add(new Option("Pilih Jam Baru", ""));
          res.available_sessions.forEach((slot) => {
            if (slot.available) {
              select.add(new Option(`${slot.jam_mulai} - ${slot.jam_selesai}`, slot.id_jadwal_waktu));
            }
          });
          select.disabled = false;
        }
      } else {
        select.add(new Option("Gagal memuat", ""));
      }
    });
}

const formUbah = document.getElementById("formUbahJadwal");
if (formUbah) {
  formUbah.addEventListener("submit", function (e) {
    e.preventDefault();
    Swal.fire({
      title: "Simpan Perubahan?",
      text: "Jadwal lama akan diganti dengan jadwal baru. Aksi ini tidak bisa dibatalkan.",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Ya, Ubah Jadwal",
      confirmButtonColor: "#f97316", // Orange
    }).then((result) => {
      if (result.isConfirmed) {
        const formData = new FormData(this);
        formData.append("action", "ubah_jadwal_sesi");
        Swal.fire({ title: "Memproses...", didOpen: () => Swal.showLoading() });

        fetch("riwayat_api.php", { method: "POST", body: formData })
          .then((r) => r.json())
          .then((res) => {
            if (res.status === "success") {
              Swal.fire("Berhasil", res.message, "success").then(() => location.reload());
            } else {
              Swal.fire("Gagal", res.message, "error");
            }
          });
      }
    });
  });
}

// =================================================================
//  4. FITUR DETAIL MEMBER
// =================================================================
function openDetailMember(idMember) {
  const modal = document.getElementById("modalDetail");
  const content = document.getElementById("detailContent");
  const dynamicArea = document.getElementById("dynamic-auth-area");

  modal.classList.add("active");
  content.innerHTML = '<div class="text-center py-8 text-slate-500"><i class="fa-solid fa-circle-notch fa-spin fa-2x mb-2"></i><p>Memuat data member...</p></div>';
  dynamicArea.style.display = "none";

  const formData = new FormData();
  formData.append("action", "get_member_detail");
  formData.append("id_member", idMember);

  fetch("riwayat_api.php", { method: "POST", body: formData })
    .then((res) => res.json())
    .then((resp) => {
      if (resp.status === "success") {
        const d = resp.data;

        let jadwalHtml = "";
        if (d.jadwal_list.length > 0) {
          jadwalHtml = `<ul class="mt-2 space-y-2 max-h-40 overflow-y-auto pr-2">
                    ${d.jadwal_list
                      .map(
                        (j) => `
                        <li class="text-xs flex justify-between bg-white p-2 border rounded shadow-sm">
                            <span class="font-medium text-slate-600"><i class="fa-regular fa-calendar"></i> ${j.tanggal}</span>
                            <span class="font-mono font-bold text-slate-800">${j.jam}</span>
                        </li>`
                      )
                      .join("")}
                </ul>`;
        } else {
          jadwalHtml = '<p class="text-xs text-slate-400 italic mt-2">Belum ada jadwal sesi.</p>';
        }

        content.innerHTML = `
                <div class="space-y-4">
                    <div class="bg-orange-50 p-5 rounded-xl border border-orange-100 shadow-sm">
                        <div class="flex justify-between items-start mb-4 border-b border-orange-200 pb-3">
                            <div>
                                <p class="text-xs text-orange-400 uppercase font-bold font-poppins">ID Membership</p>
                                <p class="font-mono text-xl font-bold text-slate-800 mt-1">${d.kode_member}</p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase bg-green-100 text-green-700">${d.status}</span>
                        </div>
                        <div class="text-sm space-y-2">
                            <div class="flex justify-between"><span class="text-slate-500">Lapangan</span><span class="font-semibold">${d.nama_lapangan}</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">Periode</span><span class="font-semibold">${d.periode}</span></div>
                        </div>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <div class="flex justify-between items-center border-b border-slate-200 pb-2 mb-1">
                            <h4 class="text-sm font-bold text-slate-700">Jadwal Bermain</h4>
                            <span class="text-xs text-slate-500">Sisa Ubah: <strong class="text-blue-600">${d.sisa_ubah}x</strong></span>
                        </div>
                        ${jadwalHtml}
                    </div>
                </div>
            `;

        dynamicArea.innerHTML = "";
        if (d.status === "AKTIF") {
          dynamicArea.style.display = "block";
          dynamicArea.innerHTML = '<p class="mb-3 font-bold text-slate-700">QR Code Member</p><div id="qrcode-member" class="flex justify-center bg-white p-3 rounded-xl border border-slate-200 shadow-sm inline-block"></div>';
          new QRCode(document.getElementById("qrcode-member"), {
            text: `MEMBER:${d.id_member}|VALID`,
            width: 140,
            height: 140,
            colorDark: "#c2410c",
          });
        }
      } else {
        content.innerHTML = `<p class="text-red-500 text-center">${resp.message}</p>`;
      }
    });
}
