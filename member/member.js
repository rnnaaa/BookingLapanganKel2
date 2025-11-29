// member.js

// Variable global (isLoggedIn diambil dari PHP di file member.php)
let currentStep = 1;
let selectedSlots = []; 
let maxQuota = 0;
let hargaPerJam = 0;
let selectedMethod = 'qris';
let timerInterval;

// Helper: Format Tanggal
function formatDate(dateString) { 
    return new Date(dateString).toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' }); 
}

// Helper: Get ISO Week
function getISOWeek(date) {
    const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
    const dayNum = d.getUTCDay() || 7;
    d.setUTCDate(d.getUTCDate() + 4 - dayNum);
    const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
    return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
}

// Helper: Update Progress Bar
function updateProgressLine(step) {
    const progress = document.getElementById('progressLine');
    if(step === 1) progress.style.width = '0%';
    if(step === 2) progress.style.width = '33%';
    if(step === 3) progress.style.width = '66%';
    if(step === 4) progress.style.width = '100%';
}

// ----------------------------------------------------
// 1. NAVIGATION GUARD & CANCEL LOGIC (FITUR BARU)
// ----------------------------------------------------

// A. Fungsi Tombol Batal di sebelah Timer
function confirmCancelBooking() {
    Swal.fire({
        title: 'Batalkan Booking?',
        text: "Semua jadwal yang sudah dipilih akan dilepas dan Anda akan kembali ke halaman awal.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444', // Red-500
        cancelButtonColor: '#64748b', // Slate-500
        confirmButtonText: 'Ya, Batalkan',
        cancelButtonText: 'Lanjut Booking'
    }).then((result) => {
        if (result.isConfirmed) {
            resetTimer(); // Reset dan lepas slot
        }
    });
}

// B. Intercept Link Klik (Navbar, Footer, Logo)
document.addEventListener('click', function(e) {
    // Jika timer aktif (Step > 1) dan yang diklik adalah link (<a>)
    const targetLink = e.target.closest('a');
    if (currentStep > 1 && targetLink) {
        // Jangan cegah jika link cuma '#' atau javascript:void(0)
        const href = targetLink.getAttribute('href');
        if (!href || href === '#' || href.startsWith('javascript')) return;

        e.preventDefault(); // Stop navigasi
        
        Swal.fire({
            title: 'Anda sedang dalam proses booking!',
            text: "Jika Anda keluar sekarang, slot yang sudah dipilih akan dilepas otomatis.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Keluar',
            cancelButtonText: 'Tetap di Sini'
        }).then((result) => {
            if (result.isConfirmed) {
                // Lepas slot secara background
                const formData = new FormData();
                formData.append('action', 'reset_timer');
                fetch('member.php', { method: 'POST', body: formData }).then(() => {
                    window.location.href = href; // Lanjut navigasi manual
                });
            }
        });
    }
}, true); // Use capture phase

// C. Intercept Browser Back Button
window.addEventListener('popstate', function(event) {
    if (currentStep > 1) {
        // Push state lagi agar URL tidak berubah dulu (tetap stay)
        history.pushState(null, null, window.location.href); 
        
        Swal.fire({
            title: 'Batalkan Proses?',
            text: "Menekan tombol kembali akan membatalkan proses booking Anda.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Keluar',
            cancelButtonText: 'Lanjut Booking'
        }).then((result) => {
            if (result.isConfirmed) {
                resetTimer(); // Reset UI & Backend
                // Optional: Go back for real if needed, or just stay at step 1
                // history.back(); 
            }
        });
    }
});

// D. Intercept Refresh / Close Tab
window.addEventListener('beforeunload', function (e) {
    if (currentStep > 1) {
        // Kirim sinyal ke backend untuk lepas slot (menggunakan Beacon API agar tetap terkirim saat browser tutup)
        const formData = new FormData();
        formData.append('action', 'reset_timer');
        navigator.sendBeacon('member.php', formData);

        // Munculkan dialog native browser (text custom tidak didukung browser modern, tapi dialog akan muncul)
        e.preventDefault();
        e.returnValue = ''; 
    }
});

// ----------------------------------------------------
// 2. CORE WIZARD LOGIC
// ----------------------------------------------------

function nextStep(step) {
    // Cek Login saat masuk Step 2
    if (step === 2 && !isLoggedIn) {
         Swal.fire({
            icon: 'warning',
            title: 'Anda Belum Login',
            text: 'Silakan login terlebih dahulu untuk melanjutkan pendaftaran member.',
            confirmButtonColor: '#d97706',
            confirmButtonText: 'Login Sekarang',
            showCancelButton: true,
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) window.location.href = '../auth/login.php'; 
        });
        return; 
    }

    // Logic Step 2 (Pilih Paket)
    if (step === 2) {
        const paketEl = document.querySelector('input[name="paket"]:checked');
        const lapEl = document.getElementById('inputLapangan');
        if (!paketEl) return Swal.fire({ icon: 'warning', title: 'Pilih Paket', text: 'Silakan pilih durasi paket terlebih dahulu.', confirmButtonColor: '#d97706' });
        
        maxQuota = parseInt(paketEl.dataset.quota);
        hargaPerJam = parseInt(lapEl.options[lapEl.selectedIndex].dataset.harga);
        document.getElementById('maxQuota').textContent = maxQuota;

        startCountdown();
        renderSelectedList(); 
        
        // PUSH STATE untuk Back Button Interceptor
        history.pushState({step: 2}, "Step 2", "?step=2");
    }
    
    // Logic Step 3 (Review)
    if (step === 3) {
        if (selectedSlots.length !== maxQuota) return Swal.fire({icon: 'warning', title: 'Jadwal Belum Lengkap', html: `Anda harus memilih <b>${maxQuota}</b> slot jadwal.`, confirmButtonColor: '#d97706'});
        renderReviewStep();
        history.pushState({step: 3}, "Step 3", "?step=3");
    }

    // Logic Step 4 (Bayar)
    if (step === 4) {
        selectedMethod = document.querySelector('input[name="metode"]:checked').value;
        renderPaymentInstruction();
        history.pushState({step: 4}, "Step 4", "?step=4");
    }

    // UI Transition
    document.querySelectorAll('.step-content').forEach(el => el.classList.add('hidden'));
    document.getElementById(`step${step}`).classList.remove('hidden');
    
    document.querySelectorAll('.step-item').forEach(el => el.classList.remove('active'));
    for(let i=1; i<=step; i++) {
        document.getElementById(`step${i}-ind`).classList.add('active');
    }
    updateProgressLine(step);
    
    currentStep = step;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function prevStep(step) {
    document.querySelectorAll('.step-content').forEach(el => el.classList.add('hidden'));
    document.getElementById(`step${step}`).classList.remove('hidden');
    
    document.querySelectorAll('.step-item').forEach(el => el.classList.remove('active'));
    for(let i=1; i<=step; i++) {
        document.getElementById(`step${i}-ind`).classList.add('active');
    }
    updateProgressLine(step);

    currentStep = step;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ----------------------------------------------------
// 3. TIMER & SLOT LOGIC
// ----------------------------------------------------

function startCountdown() {
    const timerBar = document.getElementById('memberTimerBar');
    const timerDisplay = document.getElementById('countdownDisplay');
    const timerContainer = document.getElementById('timerContainer');

    const formData = new FormData();
    formData.append('action', 'start_timer');

    fetch('member.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            let timeLeft = data.remaining;
            timerBar.classList.remove('hidden');

            clearInterval(timerInterval);
            timerInterval = setInterval(() => {
                if (timeLeft <= 0) {
                    handleTimeOut();
                } else {
                    const m = Math.floor(timeLeft / 60).toString().padStart(2, '0');
                    const s = (timeLeft % 60).toString().padStart(2, '0');
                    timerDisplay.innerText = `${m}:${s}`;
                    
                    if (timeLeft < 60) {
                        timerContainer.classList.remove('bg-amber-600');
                        timerContainer.classList.add('bg-red-600', 'animate-pulse');
                    }
                    timeLeft--;
                }
            }, 1000);
        }
    });
}

function handleTimeOut() {
    clearInterval(timerInterval);
    Swal.fire({
        icon: 'error', title: 'Waktu Habis!',
        text: 'Sesi pendaftaran member telah berakhir.',
        confirmButtonColor: '#d97706', allowOutsideClick: false
    }).then(() => {
        resetTimer();
    });
}

function resetTimer() {
    document.getElementById('memberTimerBar').classList.add('hidden');
    clearInterval(timerInterval);
    selectedSlots = [];
    
    // UI Reset to Step 1
    document.querySelectorAll('.step-content').forEach(el => el.classList.add('hidden'));
    document.getElementById('step1').classList.remove('hidden');
    
    // Reset indicators
    document.querySelectorAll('.step-item').forEach(el => el.classList.remove('active'));
    document.getElementById('step1-ind').classList.add('active');
    updateProgressLine(1);
    
    // Reset Container Timer Color
    document.getElementById('timerContainer').classList.add('bg-amber-600');
    document.getElementById('timerContainer').classList.remove('bg-red-600', 'animate-pulse');

    currentStep = 1;

    // Call Backend Release
    const formData = new FormData();
    formData.append('action', 'reset_timer');
    fetch('member.php', { method: 'POST', body: formData });
    
    // Reset URL History agar bersih dari state step 2/3/4
    history.replaceState(null, null, window.location.pathname);
}

// --- LOAD SLOTS ---
document.getElementById('inputTanggal').addEventListener('change', function() {
    const tanggal = this.value;
    const id_lapangan = document.getElementById('inputLapangan').value;
    const container = document.getElementById('slotContainer');
    
    container.innerHTML = '<div class="text-center py-10"><i class="fa-solid fa-circle-notch fa-spin text-amber-600 text-3xl"></i><p class="text-slate-500 mt-2 text-sm">Memuat jadwal...</p></div>';

    const formData = new FormData();
    formData.append('action', 'get_slots');
    formData.append('id_lapangan', id_lapangan);
    formData.append('tanggal', tanggal);

    fetch('member.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success') {
            let html = '<div class="slot-grid animate-fade">';
            data.slots.forEach(slot => {
                const isLocal = selectedSlots.some(s => s.id_waktu === slot.id_waktu && s.tanggal === tanggal);
                const isMyHold = slot.is_my_hold;
                const isSelected = isLocal || isMyHold;

                let statusClass = 'available';
                let disabledAttr = '';
                let icon = '';

                if (slot.status === 'dibooking') {
                    statusClass = 'booked disabled';
                    disabledAttr = 'disabled';
                    icon = '<i class="fa-solid fa-lock text-xs ml-1"></i>';
                }

                const selectedClass = isSelected ? 'selected' : '';
                
                html += `<div class="slot-btn ${statusClass} ${selectedClass}" ${disabledAttr}
                              onclick="toggleSlot('${slot.id_waktu}', '${tanggal}', '${slot.jam}', this)">
                              ${slot.jam} ${icon}
                         </div>`;
            });
            html += '</div>';
            container.innerHTML = html;
        }
    });
});

// --- TOGGLE SLOT (ASYNC HOLD/UNHOLD) ---
async function toggleSlot(id_waktu, tanggal, jam, el) {
    if (el.classList.contains('disabled')) return;

    const id_lapangan = document.getElementById('inputLapangan').value;
    const index = selectedSlots.findIndex(s => s.id_waktu === id_waktu && s.tanggal === tanggal);

    // UNHOLD
    if (index > -1) {
        el.classList.remove('selected');
        el.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        const formData = new FormData();
        formData.append('action', 'unhold_slot');
        formData.append('id_waktu', id_waktu);
        formData.append('tanggal', tanggal);
        formData.append('id_lapangan', id_lapangan);

        try {
            const res = await fetch('member.php', { method: 'POST', body: formData });
            const data = await res.json();
            if(data.status === 'success') {
                selectedSlots.splice(index, 1);
                el.innerText = jam;
                renderSelectedList();
            } else {
                el.classList.add('selected');
                el.innerText = jam;
                Swal.fire('Error', 'Gagal melepas slot.', 'error');
            }
        } catch(e) {
            el.classList.add('selected');
            el.innerText = jam;
        }
    } 
    // HOLD
    else {
        if (selectedSlots.length >= maxQuota) return Swal.fire({icon: 'info', title: 'Kuota Terpenuhi', text: `Anda sudah memilih maksimal ${maxQuota} slot.`});
        
        const targetDate = new Date(tanggal);
        const targetWeek = getISOWeek(targetDate);
        const targetYear = targetDate.getFullYear();
        
        const isWeekOccupied = selectedSlots.some(s => {
            const d = new Date(s.tanggal);
            return getISOWeek(d) === targetWeek && d.getFullYear() === targetYear;
        });
        if (isWeekOccupied) return Swal.fire({icon: 'warning', title: 'Jadwal Bentrok', text: 'Sesuai aturan member, Anda hanya boleh memilih 1 jadwal per minggu.'});

        el.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';
        
        const formData = new FormData();
        formData.append('action', 'hold_slot');
        formData.append('id_waktu', id_waktu);
        formData.append('tanggal', tanggal);
        formData.append('id_lapangan', id_lapangan);

        try {
            const res = await fetch('member.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.status === 'success') {
                selectedSlots.push({ id_waktu, tanggal, jam });
                el.classList.add('selected');
                el.innerText = jam;
                if(selectedSlots.length === 1) startCountdown();
                renderSelectedList();
            } else {
                el.innerText = jam;
                el.classList.add('disabled', 'booked');
                Swal.fire('Gagal', data.message, 'error');
            }
        } catch(e) {
            el.innerText = jam;
            Swal.fire('Error', 'Koneksi bermasalah.', 'error');
        }
    }
}

function renderSelectedList() {
    const listEl = document.getElementById('selectedList');
    const btnTo3 = document.getElementById('btnToStep3');
    document.getElementById('countSelected').textContent = selectedSlots.length;
    
    if (selectedSlots.length === maxQuota) {
        btnTo3.disabled = false;
        btnTo3.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-gray-400');
        btnTo3.classList.add('bg-amber-600', 'hover:bg-amber-700', 'shadow-lg');
        btnTo3.classList.remove('bg-indigo-600', 'hover:bg-indigo-700'); // Clean old classes
    } else {
        btnTo3.disabled = true;
        btnTo3.classList.add('opacity-50', 'cursor-not-allowed', 'bg-gray-400');
        btnTo3.classList.remove('bg-amber-600', 'hover:bg-amber-700', 'shadow-lg');
    }

    if (selectedSlots.length === 0) listEl.innerHTML = '<div class="flex flex-col items-center justify-center h-40 text-slate-400"><i class="fa-regular fa-calendar-xmark text-3xl mb-2"></i><p class="text-xs">Belum ada jadwal dipilih</p></div>';
    else {
        selectedSlots.sort((a,b) => new Date(a.tanggal) - new Date(b.tanggal));
        listEl.innerHTML = selectedSlots.map((s, i) => `
            <div class="flex justify-between items-center p-3 bg-white rounded-xl border border-slate-100 shadow-sm mb-2 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center text-xs font-bold">${i+1}</div>
                    <div>
                        <div class="text-xs font-bold text-slate-800">${formatDate(s.tanggal)}</div>
                        <div class="text-xs text-slate-500 font-mono">${s.jam}</div>
                    </div>
                </div>
                <button onclick="toggleSlot('${s.id_waktu}', '${s.tanggal}', '${s.jam}', document.querySelector('.slot-btn.selected'))" 
                        class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-red-500 hover:bg-red-50 transition-colors">
                    <i class="fa-solid fa-trash-can text-sm"></i>
                </button>
            </div>`).join('');
    }
}

function renderReviewStep() {
    const tbody = document.getElementById('reviewTableBody');
    const totalEl = document.getElementById('reviewTotalPrice');
    const totalBayar = selectedSlots.length * hargaPerJam;
    
    tbody.innerHTML = selectedSlots.map((s, i) => `
        <tr class="hover:bg-slate-50 transition-colors">
            <td class="py-3 px-4 text-slate-500 font-medium">${i+1}</td>
            <td class="py-3 px-4 font-semibold text-slate-700">${formatDate(s.tanggal)}</td>
            <td class="py-3 px-4 font-mono text-amber-600">${s.jam}</td>
        </tr>`).join('');
        
    totalEl.textContent = 'Rp ' + totalBayar.toLocaleString('id-ID');
}

function renderPaymentInstruction() {
    // ... (Kode renderPaymentInstruction sama seperti sebelumnya, hanya ganti warna ke amber jika perlu) ...
    const container = document.getElementById('paymentInstruction');
    const totalBayar = selectedSlots.length * hargaPerJam;
    const totalFormatted = 'Rp ' + totalBayar.toLocaleString('id-ID');
    
    let content = `
        <div class="mb-6">
            <p class="text-slate-500 text-sm mb-1">Total yang harus dibayar</p>
            <strong class="text-3xl text-slate-800 tracking-tight">${totalFormatted}</strong>
        </div>
        <div class="border-t border-slate-200 my-4"></div>`;
    
    if (selectedMethod === 'qris') {
        content += `<div class="bg-white p-4 rounded-xl border border-slate-200 inline-block shadow-sm">
            <img src="../assets/images/qris_rush.jpg" alt="QRIS" class="mx-auto w-48 rounded-lg mb-3">
            <p class="text-sm text-slate-600 mt-3"><i class="fa-solid fa-scan mr-1"></i> Scan kode di atas.</p>
        </div>`;
    } else {
        // ... (BCA/Mandiri blocks remain similar) ...
         content += `<div class="p-4 bg-slate-50 rounded-lg text-center"><p class="text-slate-600">Metode ${selectedMethod.toUpperCase()} dipilih.</p></div>`;
    }
    container.innerHTML = content;
}

function submitMember() {
    const fileInput = document.getElementById('inputBukti');
    if (fileInput.files.length === 0) return Swal.fire({icon: 'warning', title: 'Upload Bukti', text: 'Bukti transfer wajib diupload.', confirmButtonColor: '#d97706'});

    const btn = document.getElementById('btnFinalSubmit');
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Memproses...';

    const formData = new FormData();
    formData.append('action', 'submit_member');
    formData.append('id_lapangan', document.getElementById('inputLapangan').value);
    formData.append('paket_bulan', document.querySelector('input[name="paket"]:checked').value);
    formData.append('total_bayar', selectedSlots.length * hargaPerJam);
    formData.append('metode_pembayaran', selectedMethod);
    formData.append('selected_slots', JSON.stringify(selectedSlots));
    formData.append('bukti_transfer', fileInput.files[0]);

    fetch('member.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            // Bersihkan history saat sukses agar user tidak bisa back ke proses pembayaran
            history.replaceState(null, null, window.location.pathname);
            Swal.fire({icon: 'success', title: 'Berhasil!', text: 'Pendaftaran member berhasil dikirim.', confirmButtonColor: '#d97706'}).then(() => window.location.href = '../DashPengguna.php');
        } else { 
            Swal.fire('Gagal', data.message, 'error'); 
            btn.disabled = false; 
            btn.innerHTML = originalContent; 
        }
    })
    .catch(err => { 
        console.error(err); 
        Swal.fire('Error', 'Kesalahan koneksi.', 'error'); 
        btn.disabled = false; 
        btn.innerHTML = originalContent; 
    });
}