document.addEventListener("DOMContentLoaded", function () {
    // 1. Tab Switching
    const tabs = document.querySelectorAll(".tab-button");
    const contents = document.querySelectorAll(".tab-content");

    tabs.forEach(btn => {
        btn.addEventListener("click", () => {
            tabs.forEach(t => t.classList.remove("active"));
            contents.forEach(c => c.classList.remove("active"));
            
            btn.classList.add("active");
            const targetId = btn.dataset.tab + "-tab";
            const targetContent = document.getElementById(targetId);
            if(targetContent) targetContent.classList.add("active");
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
            // Disable tombol jika waktu habis
            const card = timerEl.closest('.card');
            if(card) {
                const btns = card.querySelectorAll('.btn-solid.orange, .btn-solid.red');
                btns.forEach(b => { 
                    b.disabled = true; 
                    b.style.opacity = '0.5'; 
                    b.style.cursor = 'not-allowed';
                    b.title = "Batas waktu perubahan telah habis";
                });
            }
            return;
        }

        const hours = Math.floor(diff / (1000 * 60 * 60)).toString().padStart(2, '0');
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60)).toString().padStart(2, '0');
        const seconds = Math.floor((diff % (1000 * 60)) / 1000).toString().padStart(2, '0');
        
        timerEl.innerText = `${hours}:${minutes}:${seconds}`;
    });
}

// --- MODAL UTILS ---
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if(modal) modal.classList.remove("active");
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove("active");
    }
}

// =================================================================
//  1. FITUR LIHAT DETAIL (FIXED: QR Code untuk DP)
// =================================================================
function openDetailBooking(idBooking) {
    const modal = document.getElementById("modalDetail");
    const content = document.getElementById("detailContent");
    
    modal.classList.add("active");
    content.innerHTML = '<div class="text-center py-8 text-slate-500"><i class="fa-solid fa-circle-notch fa-spin fa-2x mb-2"></i><p>Memuat data...</p></div>';
    
    const formData = new FormData();
    formData.append('action', 'get_booking_detail');
    formData.append('id_booking', idBooking);

    fetch('riwayat_api.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(resp => {
        if(resp.status === 'success') {
            const d = resp.data;
            
            // Styling Status Badge
            let statusClass = 'bg-slate-100 text-slate-600'; // Default abu-abu
            const st = d.status_booking.toLowerCase();
            
            if(st === 'disetujui' || st === 'selesai' || st === 'belum lunas') statusClass = 'bg-green-100 text-green-700';
            else if(st === 'ditolak' || st === 'dibatalkan') statusClass = 'bg-red-100 text-red-700';
            else if(st === 'menunggu') statusClass = 'bg-yellow-100 text-yellow-700';

            // 1. LOGIKA PERINGATAN DP (Sisa Tagihan)
            let paymentWarningHtml = '';
            // Muncul jika DP Bayar ATAU ada sisa tagihan > 0 dan status bukan batal/tolak
            if(d.payment_status === 'dp_bayar' || (parseInt(d.sisa_raw) > 0 && st !== 'dibatalkan' && st !== 'ditolak')) {
                paymentWarningHtml = `
                    <div class="bg-orange-50 border-l-4 border-orange-500 p-4 mb-4 rounded-r">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fa-solid fa-triangle-exclamation text-orange-500 mt-1"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-bold text-orange-800">Belum Lunas (DP)</h3>
                                <p class="text-xs text-orange-700 mt-1">
                                    Sisa tagihan: <span class="font-bold text-lg">Rp ${d.sisa}</span>. 
                                    <br>Harap lunasi di kasir lapangan.
                                </p>
                            </div>
                        </div>
                    </div>
                `;
            }

            // Render Konten Modal
            content.innerHTML = `
                <div class="space-y-4">
                    ${paymentWarningHtml}

                    <div class="bg-slate-50 p-5 rounded-xl border border-slate-200 shadow-sm">
                        <div class="flex justify-between items-start mb-4 border-b border-slate-200 pb-3">
                            <div>
                                <p class="text-xs text-slate-400 uppercase font-bold tracking-wider">Kode Booking</p>
                                <p class="font-mono text-xl font-bold text-slate-800 mt-1 tracking-wide">${d.kode_booking}</p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase ${statusClass}">${d.status_booking}</span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-y-4 gap-x-2 text-sm">
                            <div><p class="text-slate-400 text-xs">Lapangan</p><p class="font-semibold text-slate-700">${d.nama_lapangan}</p></div>
                            <div class="text-right"><p class="text-slate-400 text-xs">Tanggal</p><p class="font-semibold text-slate-700">${d.tanggal}</p></div>
                            
                            <div><p class="text-slate-400 text-xs">Jam Main</p><p class="font-semibold text-slate-700 font-mono">${d.jam}</p></div>
                            <div class="text-right"><p class="text-slate-400 text-xs">Atas Nama</p><p class="font-semibold text-slate-700">${d.user}</p></div>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-xl border border-slate-100">
                        <div class="flex justify-between items-center mb-2 border-b border-dashed border-slate-200 pb-2">
                            <span class="text-sm text-slate-500">Total Harga</span>
                            <span class="font-bold text-slate-800">Rp ${d.total_harga}</span>
                        </div>
                        <div class="flex justify-between items-center mb-1 text-xs text-blue-600">
                            <span>Sudah Bayar (DP)</span>
                            <span class="font-bold">Rp ${d.dp}</span>
                        </div>
                         <div class="flex justify-between items-center text-xs text-red-500">
                            <span>Kekurangan</span>
                            <span class="font-bold">Rp ${d.sisa}</span>
                        </div>
                    </div>
                </div>
            `;
            
            // 2. LOGIKA QR CODE (PERBAIKAN DI SINI)
            const qrDiv = document.getElementById('qrcode');
            const qrSection = document.getElementById('qr-section');
            qrDiv.innerHTML = ''; 
            
            // Syarat QR Code Muncul:
            // 1. Status 'disetujui', 'selesai', atau 'belum lunas' (karena DP statusnya bisa belum lunas di db)
            // 2. ATAU Payment statusnya 'dp_bayar' (artinya uang masuk valid, jadi barcode boleh muncul)
            const validStatuses = ['disetujui', 'selesai', 'belum lunas'];
            const isPaidDP = (d.payment_status === 'dp_bayar');

            if (validStatuses.includes(st) || isPaidDP) {
                qrSection.style.display = 'block';
                
                // Caption dinamis
                let captionEl = document.getElementById('qr-caption');
                if(!captionEl) {
                    captionEl = document.createElement('p');
                    captionEl.id = 'qr-caption';
                    captionEl.className = 'text-xs text-slate-400 mt-2 font-medium';
                    qrSection.appendChild(captionEl);
                }

                if(isPaidDP || parseInt(d.sisa_raw) > 0) {
                    captionEl.innerText = "Scan di kasir untuk pelunasan & check-in";
                    captionEl.className = "text-xs text-orange-500 mt-2 font-bold"; // Warna oranye biar notice
                } else {
                    captionEl.innerText = "Tunjukkan ke petugas untuk check-in";
                    captionEl.className = "text-xs text-slate-400 mt-2 font-medium";
                }

                // Generate QR
                new QRCode(qrDiv, { 
                    text: `BOOKING:${d.id_booking}|VALID`, 
                    width: 140, height: 140,
                    colorDark : "#1e293b", colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.H
                });
            } else {
                qrSection.style.display = 'none';
            }

        } else {
            content.innerHTML = `<div class="text-center py-4 text-red-500"><p>${resp.message}</p></div>`;
        }
    })
    .catch(err => {
        console.error(err);
        content.innerHTML = `<div class="text-center py-4 text-red-500"><p>Gagal memuat data.</p></div>`;
    });
}

// =================================================================
//  2. FITUR UBAH JADWAL
// =================================================================
function openUbahJadwal(idSesi, idLapangan) {
    document.getElementById('ubah_id_sesi').value = idSesi;
    document.getElementById('ubah_id_lapangan').value = idLapangan;
    document.getElementById('new_date').value = '';
    const select = document.getElementById('ubah_jam');
    select.innerHTML = '<option>Pilih tanggal dulu...</option>';
    select.disabled = true;
    document.getElementById('modalUbah').classList.add('active');
}

const dateInput = document.getElementById('new_date');
if(dateInput) {
    dateInput.addEventListener('change', function() {
        const lapanganId = document.getElementById('ubah_id_lapangan').value;
        loadAvailableSlots(lapanganId, this.value);
    });
}

function loadAvailableSlots(lapanganId, date) {
    const select = document.getElementById('ubah_jam');
    select.innerHTML = '<option>Memuat...</option>';
    select.disabled = true;

    fetch(`riwayat_api.php?action=get_available_sessions&lapangan_id=${lapanganId}&selected_date=${date}`)
    .then(r => r.json())
    .then(res => {
        select.innerHTML = '';
        if(res.status === 'success') {
            if(res.available_sessions.length === 0) {
                select.add(new Option("Penuh / Tidak tersedia", ""));
            } else {
                select.add(new Option("Pilih Jam Baru", ""));
                res.available_sessions.forEach(slot => {
                    if(slot.available) {
                        select.add(new Option(`${slot.jam_mulai} - ${slot.jam_selesai}`, slot.id_jadwal_waktu));
                    }
                });
                select.disabled = false;
            }
        } else { select.add(new Option("Gagal memuat", "")); }
    });
}

const formUbah = document.getElementById('formUbahJadwal');
if(formUbah){
    formUbah.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'ubah_jadwal_sesi');
        fetch('riwayat_api.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if(res.status === 'success') Swal.fire('Berhasil', res.message, 'success').then(() => location.reload());
            else Swal.fire('Gagal', res.message, 'error');
        });
    });
}

// =================================================================
//  3. FITUR AJUKAN PEMBATALAN
// =================================================================
function openAjukanBatal(idSesi, lapangan, tanggal, jam) {
    document.getElementById('batal_id_sesi').value = idSesi;
    document.getElementById('batal_lapangan').innerText = lapangan;
    const d = new Date(tanggal);
    const opts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('batal_tanggal').innerText = d.toLocaleDateString('id-ID', opts);
    document.getElementById('batal_jam').innerText = jam;
    document.getElementById('modalBatal').classList.add('active');
}

const formBatal = document.getElementById('formBatal');
if(formBatal){
    formBatal.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'ajukan_pembatalan');
        fetch('riwayat_api.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if(res.status === 'success') Swal.fire('Terkirim', res.message, 'success').then(() => location.reload());
            else Swal.fire('Gagal', res.message, 'error');
        });
    });
}

// =================================================
// 4. FITUR MEMBER (DETAIL LENGKAP)
// =================================================
function openDetailMember(idMember) {
    const modal = document.getElementById("modalDetail");
    const content = document.getElementById("detailContent");
    
    modal.classList.add("active");
    content.innerHTML = '<div class="text-center py-8 text-slate-500"><i class="fa-solid fa-circle-notch fa-spin fa-2x mb-2"></i><p>Memuat data member...</p></div>';

    const formData = new FormData();
    formData.append('action', 'get_member_detail');
    formData.append('id_member', idMember);

    fetch('riwayat_api.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(resp => {
        if(resp.status === 'success') {
            const d = resp.data;
            
            // Buat List Jadwal Main
            let jadwalHtml = '';
            if(d.jadwal_list.length > 0) {
                jadwalHtml = '<ul class="mt-2 space-y-2 max-h-40 overflow-y-auto pr-2">';
                d.jadwal_list.forEach(j => {
                    jadwalHtml += `
                        <li class="text-xs flex justify-between bg-white p-2 border border-slate-100 rounded">
                            <span class="font-medium text-slate-600"><i class="fa-regular fa-calendar mr-1"></i> ${j.tanggal}</span>
                            <span class="font-mono font-bold text-slate-800">${j.jam}</span>
                        </li>
                    `;
                });
                jadwalHtml += '</ul>';
            } else {
                jadwalHtml = '<p class="text-xs text-slate-400 italic mt-2">Belum ada jadwal sesi.</p>';
            }

            // Render Konten Modal
            content.innerHTML = `
                <div class="space-y-4">
                    <div class="bg-orange-50 p-5 rounded-xl border border-orange-100 shadow-sm">
                        <div class="flex justify-between items-start mb-4 border-b border-orange-200 pb-3">
                            <div>
                                <p class="text-xs text-orange-400 uppercase font-bold tracking-wider">ID Membership</p>
                                <p class="font-mono text-xl font-bold text-slate-800 mt-1 tracking-wide">${d.kode_member}</p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase bg-green-100 text-green-700">${d.status}</span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-y-2 gap-x-4 text-sm">
                            <div><p class="text-slate-400 text-xs">Lapangan</p><p class="font-semibold text-slate-700">${d.nama_lapangan}</p></div>
                            <div class="text-right"><p class="text-slate-400 text-xs">Durasi</p><p class="font-semibold text-slate-700">${d.durasi}</p></div>
                            
                            <div class="col-span-2 mt-2">
                                <p class="text-slate-400 text-xs">Periode Berlaku</p>
                                <p class="font-semibold text-slate-700">${d.periode}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <div class="flex justify-between items-center border-b border-slate-200 pb-2 mb-1">
                            <h4 class="text-sm font-bold text-slate-700">Jadwal Bermain</h4>
                            <span class="text-xs text-slate-500">Sisa Ubah Jadwal: <strong class="text-blue-600">${d.sisa_ubah}x</strong></span>
                        </div>
                        ${jadwalHtml}
                    </div>
                </div>
            `;

            // QR Code Logic
            const qrDiv = document.getElementById('qrcode');
            const qrSection = document.getElementById('qr-section');
            qrDiv.innerHTML = ''; 
            
            if(d.status === 'AKTIF') {
                qrSection.style.display = 'block';
                
                // Update Caption QR
                let captionEl = document.getElementById('qr-caption');
                if(!captionEl) {
                    captionEl = document.createElement('p');
                    captionEl.id = 'qr-caption';
                    captionEl.className = 'text-xs text-slate-400 mt-2 font-medium';
                    qrSection.appendChild(captionEl);
                }
                captionEl.innerText = "Scan untuk check-in member";

                new QRCode(qrDiv, { 
                    text: `MEMBER:${d.id_member}|VALID`, 
                    width: 140, height: 140,
                    colorDark : "#c2410c", // Warna oranye tua (member)
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.H
                });
            } else {
                qrSection.style.display = 'none';
            }

        } else {
            content.innerHTML = `<div class="text-center py-4 text-red-500"><p>${resp.message}</p></div>`;
        }
    })
    .catch(err => {
        content.innerHTML = `<div class="text-center py-4 text-red-500"><p>Gagal memuat data.</p></div>`;
    });
}