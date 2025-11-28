const isLoggedIn = true; // PHP variable ini bisa diambil dari global variable di header jika ada

let currentStep = 1;
let selectedSlots = []; 
let maxQuota = 0;
let hargaPerJam = 0;
let selectedMethod = 'qris';
let timerInterval;

function getISOWeek(date) {
    const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
    const dayNum = d.getUTCDay() || 7;
    d.setUTCDate(d.getUTCDate() + 4 - dayNum);
    const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
    return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
}

function nextStep(step) {
    if (step === 2) {
        // Validasi Login
        // (Sebaiknya dicek dari variabel PHP yang dilempar ke JS)
        
        const paketEl = document.querySelector('input[name="paket"]:checked');
        const lapEl = document.getElementById('inputLapangan');
        if (!paketEl) return Swal.fire('Pilih Paket', 'Silakan pilih durasi paket.', 'warning');
        
        maxQuota = parseInt(paketEl.dataset.quota);
        hargaPerJam = parseInt(lapEl.options[lapEl.selectedIndex].dataset.harga);
        document.getElementById('maxQuota').textContent = maxQuota;

        // START TIMER & RESET LIST
        startCountdown();
        renderSelectedList(); 
    }
    
    if (step === 3) {
        if (selectedSlots.length !== maxQuota) return Swal.fire('Jadwal Belum Lengkap', `Anda harus memilih <b>${maxQuota}</b> slot jadwal.`, 'warning');
        renderReviewStep();
    }

    if (step === 4) {
        selectedMethod = document.querySelector('input[name="metode"]:checked').value;
        renderPaymentInstruction();
    }

    document.querySelectorAll('.step-content').forEach(el => el.classList.add('hidden'));
    document.getElementById(`step${step}`).classList.remove('hidden');
    document.querySelectorAll('.step-item').forEach(el => el.classList.remove('active'));
    document.getElementById(`step${step}-ind`).classList.add('active');
    currentStep = step;
}

function prevStep(step) {
    document.querySelectorAll('.step-content').forEach(el => el.classList.add('hidden'));
    document.getElementById(`step${step}`).classList.remove('hidden');
    document.querySelectorAll('.step-item').forEach(el => el.classList.remove('active'));
    document.getElementById(`step${step}-ind`).classList.add('active');
    currentStep = step;
}

// --- TIMER LOGIC ---
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
                        timerContainer.classList.remove('bg-indigo-600');
                        timerContainer.classList.add('animate-pulse-red');
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
        confirmButtonColor: '#0b63d6', allowOutsideClick: false
    }).then(() => {
        resetTimer();
    });
}

function resetTimer() {
    document.getElementById('memberTimerBar').classList.add('hidden');
    clearInterval(timerInterval);
    selectedSlots = [];
    
    // UI Reset
    document.querySelectorAll('.step-content').forEach(el => el.classList.add('hidden'));
    document.getElementById('step1').classList.remove('hidden');
    document.querySelectorAll('.step-item').forEach(el => el.classList.remove('active'));
    document.getElementById('step1-ind').classList.add('active');
    currentStep = 1;

    // Call Backend Release
    const formData = new FormData();
    formData.append('action', 'reset_timer');
    fetch('member.php', { method: 'POST', body: formData });
}

// --- LOAD SLOTS ---
document.getElementById('inputTanggal').addEventListener('change', function() {
    const tanggal = this.value;
    const id_lapangan = document.getElementById('inputLapangan').value;
    const container = document.getElementById('slotContainer');
    
    container.innerHTML = '<div class="text-center py-10"><i class="fa-solid fa-circle-notch fa-spin text-primary text-2xl"></i></div>';

    const formData = new FormData();
    formData.append('action', 'get_slots');
    formData.append('id_lapangan', id_lapangan);
    formData.append('tanggal', tanggal);

    fetch('member.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success') {
            let html = '<div class="slot-grid">';
            data.slots.forEach(slot => {
                // Check Logic: Is local array OR Is Hold from server
                const isLocal = selectedSlots.some(s => s.id_waktu === slot.id_waktu && s.tanggal === tanggal);
                const isMyHold = slot.is_my_hold;
                const isSelected = isLocal || isMyHold;

                let statusClass = 'available';
                let disabledAttr = '';

                // Jika dibooking orang lain
                if (slot.status === 'dibooking') {
                    statusClass = 'booked disabled';
                    disabledAttr = 'disabled';
                }

                const selectedClass = isSelected ? 'selected' : '';
                
                html += `<div class="slot-btn ${statusClass} ${selectedClass}" ${disabledAttr}
                              onclick="toggleSlot('${slot.id_waktu}', '${tanggal}', '${slot.jam}', this)">
                              ${slot.jam}
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

    // UNHOLD (LEPAS)
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
    // HOLD (KUNCI)
    else {
        if (selectedSlots.length >= maxQuota) return Swal.fire('Kuota Penuh', `Maksimal ${maxQuota} slot.`, 'info');
        
        const targetDate = new Date(tanggal);
        const targetWeek = getISOWeek(targetDate);
        const targetYear = targetDate.getFullYear();
        
        const isWeekOccupied = selectedSlots.some(s => {
            const d = new Date(s.tanggal);
            return getISOWeek(d) === targetWeek && d.getFullYear() === targetYear;
        });
        if (isWeekOccupied) return Swal.fire('Jadwal Bentrok', 'Anda hanya boleh memilih <b>1 jadwal</b> dalam minggu ini.', 'warning');

        // Call API Hold
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
        btnTo3.classList.remove('opacity-50', 'cursor-not-allowed');
    } else {
        btnTo3.disabled = true;
        btnTo3.classList.add('opacity-50', 'cursor-not-allowed');
    }

    if (selectedSlots.length === 0) listEl.innerHTML = '<p class="text-xs text-slate-400 text-center mt-10">Belum ada jadwal dipilih.</p>';
    else {
        selectedSlots.sort((a,b) => new Date(a.tanggal) - new Date(b.tanggal));
        listEl.innerHTML = selectedSlots.map((s, i) => `<div class="selected-item"><div><div class="text-xs font-bold text-slate-700">${formatDate(s.tanggal)}</div><div class="text-xs text-slate-500">${s.jam}</div></div><button onclick="toggleSlot('${s.id_waktu}', '${s.tanggal}', '${s.jam}', document.querySelector('.slot-btn.selected'))" class="text-red-500 hover:text-red-700"><i class="fa-solid fa-trash"></i></button></div>`).join('');
    }
}

function renderReviewStep() {
    const tbody = document.getElementById('reviewTableBody');
    const totalEl = document.getElementById('reviewTotalPrice');
    const totalBayar = selectedSlots.length * hargaPerJam;
    tbody.innerHTML = selectedSlots.map((s, i) => `<tr><td>${i+1}</td><td>${formatDate(s.tanggal)}</td><td>${s.jam}</td></tr>`).join('');
    totalEl.textContent = 'Rp ' + totalBayar.toLocaleString('id-ID');
}

function renderPaymentInstruction() {
    const container = document.getElementById('paymentInstruction');
    const totalBayar = selectedSlots.length * hargaPerJam;
    const totalFormatted = 'Rp ' + totalBayar.toLocaleString('id-ID');
    let content = `<p class="text-slate-600 mb-2">Total Pembayaran: <strong class="text-lg text-slate-800">${totalFormatted}</strong></p>`;
    if (selectedMethod === 'qris') content += `<div class="mt-4"><img src="../assets/images/qris_rush.jpg" alt="QRIS" class="mx-auto w-48 rounded-lg border p-2 mb-2"><p class="text-xs font-mono text-slate-500">NMID: ID1025384582157</p><p class="text-sm text-slate-600 mt-2">Scan QRIS di atas.</p></div>`;
    else if (selectedMethod === 'bca') content += `<div class="mt-4 bg-blue-100 p-4 rounded-xl inline-block"><h5 class="font-bold text-blue-900">Bank BCA</h5><p class="text-2xl font-bold text-slate-800 my-2 tracking-widest">123 456 7890</p><p class="text-sm text-slate-600">a.n Rush Badminton Academy</p></div>`;
    else if (selectedMethod === 'mandiri') content += `<div class="mt-4 bg-yellow-100 p-4 rounded-xl inline-block"><h5 class="font-bold text-yellow-900">Bank Mandiri</h5><p class="text-2xl font-bold text-slate-800 my-2 tracking-widest">098 765 4321</p><p class="text-sm text-slate-600">a.n Rush Badminton Academy</p></div>`;
    container.innerHTML = content;
}

function submitMember() {
    const fileInput = document.getElementById('inputBukti');
    if (fileInput.files.length === 0) return Swal.fire('Upload Bukti', 'Bukti transfer wajib diupload.', 'warning');

    const btn = document.getElementById('btnFinalSubmit');
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
        if (data.status === 'success') Swal.fire('Berhasil!', 'Pendaftaran berhasil.', 'success').then(() => window.location.href = '../DashPengguna.php');
        else { Swal.fire('Gagal', data.message, 'error'); btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-check-circle"></i> Konfirmasi Pembayaran'; }
    })
    .catch(err => { console.error(err); Swal.fire('Error', 'Kesalahan koneksi.', 'error'); btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-check-circle"></i> Konfirmasi Pembayaran'; });
}

function formatDate(dateString) { return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }); }