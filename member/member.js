/**
 * member.js
 * Logic utama sistem booking membership (Full Feature)
 */

// --- GLOBAL VARIABLES ---
let currentStep = 1;
let selectedSlots = [];     
let maxQuota = 0;           
let durationMonths = 0;     // Durasi paket (bulan)
let selectedLapId = null;   
let selectedLapPrice = 0;   
let holdTimerInterval = null; 
let isLoggedIn = false;     

// Variable Khusus Validasi Bulan (Dari kode asli Anda)
let lockedMonth = null; 
let lockedYear = null;

document.addEventListener('DOMContentLoaded', () => {
    // Ambil status login dari PHP
    if (typeof IS_LOGGED_IN !== 'undefined') isLoggedIn = IS_LOGGED_IN;
    
    // Inisialisasi
    initPackageSelection();
    checkExistingTimer();

    // Listener Input File (Preview)
    const fileInput = document.getElementById('inputBukti');
    if (fileInput) {
        fileInput.addEventListener('change', function() { handleFileUpload(this); });
    }

    // Listener Input Tanggal (Validasi Bulan)
    const dateInput = document.getElementById('inputTanggal');
    if (dateInput) {
        dateInput.addEventListener('change', handleDateChange);
    }
});

// --- STEP 1: PILIH PAKET ---
function initPackageSelection() {
    const radios = document.querySelectorAll('input[name="paket"]');
    
    radios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            // Jika ganti paket saat sudah ada slot dipilih, reset dulu
            if (selectedSlots.length > 0) {
                Swal.fire({
                    title: 'Ganti Paket?',
                    text: 'Slot yang sudah dipilih akan direset karena aturan paket berubah.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Ganti',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        resetTimer(); // Reset semua
                        applyPackageRules(e.target);
                    } else {
                        // Kembalikan ke radio sebelumnya (opsional)
                        e.target.checked = false;
                    }
                });
            } else {
                applyPackageRules(e.target);
            }
        });
    });

    // Set nilai awal saat load
    const checked = document.querySelector('input[name="paket"]:checked');
    if(checked) applyPackageRules(checked);
}

function applyPackageRules(radio) {
    maxQuota = parseInt(radio.dataset.quota);
    durationMonths = parseInt(radio.dataset.months);
    
    // Update UI Teks
    const q1 = document.getElementById('maxQuota');
    const q2 = document.getElementById('quotaText');
    if(q1) q1.textContent = maxQuota;
    if(q2) q2.textContent = maxQuota;
    
    updateCartUI(); // Re-validasi tombol next
}

// --- LOGIKA VALIDASI TANGGAL (Fitur Asli Anda) ---
function handleDateChange(e) {
    const dateVal = e.target.value;
    if (!dateVal) return;

    const selectedDate = new Date(dateVal);
    const sMonth = selectedDate.getMonth();
    const sYear = selectedDate.getFullYear();

    // Logika Penguncian Bulan (Khusus Paket 1 Bulan / Sesuai logic lama)
    // Jika durationMonths == 1, kita kunci bulan berdasarkan slot pertama yang dipilih
    if (selectedSlots.length > 0 && lockedMonth !== null) {
        if (sMonth !== lockedMonth || sYear !== lockedYear) {
            Swal.fire({
                title: 'Bulan Tidak Sesuai',
                text: 'Untuk paket ini, semua jadwal harus berada di bulan yang sama.',
                icon: 'warning'
            });
            e.target.value = ''; // Reset tanggal
            return;
        }
    }

    fetchSlots(dateVal);
}

// --- NAVIGASI STEP ---
function nextStep(targetStep) {
    // Validasi Step 1 -> 2
    if (targetStep === 2) {
        const lapSelect = document.getElementById('inputLapangan');
        selectedLapId = lapSelect.value;
        selectedLapPrice = parseInt(lapSelect.options[lapSelect.selectedIndex].dataset.harga);
    }

    // Validasi Step 2 -> 3 (Cek Kuota)
    if (targetStep === 3) {
        if (selectedSlots.length !== maxQuota) {
            Swal.fire({ 
                title: 'Jadwal Belum Lengkap', 
                text: `Anda wajib memilih ${maxQuota} slot jadwal untuk paket ini!`, 
                icon: 'warning',
                confirmButtonColor: '#F59E0B'
            });
            return;
        }
        renderReview();
    }

    // Validasi Step 3 -> 4
    if (targetStep === 4) {
        updatePaymentInfo();
    }

    // Transisi UI
    document.querySelectorAll('.step-content').forEach(el => el.classList.add('hidden'));
    document.getElementById(`step${targetStep}`).classList.remove('hidden');
    
    document.querySelectorAll('.step-item').forEach((el, idx) => {
        if (idx + 1 <= targetStep) el.classList.add('active');
        else el.classList.remove('active');
    });

    // Update Progress Bar
    const progressMap = {1: '0%', 2: '33%', 3: '66%', 4: '100%'};
    const pLine = document.getElementById('progressLine');
    if(pLine) pLine.style.width = progressMap[targetStep];
    
    currentStep = targetStep;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function prevStep(targetStep) {
    document.querySelectorAll('.step-content').forEach(el => el.classList.add('hidden'));
    document.getElementById(`step${targetStep}`).classList.remove('hidden');
    
    document.querySelectorAll('.step-item').forEach((el, idx) => {
        if (idx + 1 <= targetStep) el.classList.add('active');
        else el.classList.remove('active');
    });

    const progressMap = {1: '0%', 2: '33%', 3: '66%', 4: '100%'};
    document.getElementById('progressLine').style.width = progressMap[targetStep];
    
    currentStep = targetStep;
}

// --- API: FETCH SLOTS ---
async function fetchSlots(date) {
    const container = document.getElementById('slotContainer');
    container.innerHTML = '<div class="col-span-full text-center py-10"><i class="fa-solid fa-spinner fa-spin text-2xl text-amber-500"></i><div class="mt-2 text-slate-400">Memuat jadwal...</div></div>';
    
    const formData = new FormData();
    formData.append('action', 'get_slots');
    formData.append('id_lapangan', selectedLapId);
    formData.append('tanggal', date);

    try {
        const res = await fetch('member.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.status === 'success') {
            renderSlots(data.slots, date);
        } else {
            container.innerHTML = `<div class="col-span-full text-red-500 text-center">${data.message}</div>`;
        }
    } catch (e) { 
        container.innerHTML = '<div class="col-span-full text-center text-red-500">Gagal memuat slot.</div>'; 
    }
}

function renderSlots(slots, date) {
    const container = document.getElementById('slotContainer');
    container.innerHTML = '';
    
    if (slots.length === 0) {
        container.innerHTML = '<div class="col-span-full text-slate-400 text-center py-8 border-2 border-dashed border-slate-100 rounded-xl">Tidak ada jadwal tersedia / Libur.</div>';
        return;
    }

    slots.forEach(slot => {
        const isSelected = selectedSlots.some(s => s.id_waktu == slot.id_waktu && s.tanggal === date);
        let btnClass = 'bg-white border-slate-200 text-slate-700 hover:border-amber-500 hover:text-amber-600';
        let onClick = `holdSlot(${slot.id_waktu}, '${slot.jam}', '${date}')`;
        let icon = '';

        if (slot.status === 'dibooking' && !slot.is_my_hold) {
            btnClass = 'bg-slate-100 text-slate-400 cursor-not-allowed opacity-60';
            onClick = '';
            icon = '<i class="fa-solid fa-lock text-xs mr-1"></i>';
        } else if (slot.is_my_hold || isSelected) {
            btnClass = 'bg-amber-500 text-white border-amber-500 shadow-md shadow-amber-200';
            onClick = `unholdSlot(${slot.id_waktu}, '${date}')`;
            icon = '<i class="fa-solid fa-check text-xs mr-1"></i>';
        }

        const div = document.createElement('div');
        div.className = `border rounded-xl p-3 text-center font-bold text-sm cursor-pointer select-none flex items-center justify-center transition-all ${btnClass}`;
        if (onClick) div.setAttribute('onclick', onClick);
        div.innerHTML = `${icon} ${slot.jam}`;
        container.appendChild(div);
    });
}

// --- API: HOLD SLOT ---
async function holdSlot(idWaktu, jam, tanggal) {
    // Cek Login
    if (!isLoggedIn) { Swal.fire('Login Diperlukan', 'Silakan login terlebih dahulu.', 'error'); return; }
    
    // Cek Kuota
    if (selectedSlots.length >= maxQuota) { 
        Swal.fire('Kuota Penuh', `Maksimal ${maxQuota} pertemuan untuk paket ini.`, 'warning'); 
        return; 
    }

    const formData = new FormData();
    formData.append('action', 'hold_slot');
    formData.append('id_waktu', idWaktu);
    formData.append('tanggal', tanggal);
    formData.append('id_lapangan', selectedLapId);

    try {
        const res = await fetch('member.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.status === 'success') {
            // Set Lock Bulan jika ini slot pertama
            if (selectedSlots.length === 0) {
                const d = new Date(tanggal);
                lockedMonth = d.getMonth();
                lockedYear = d.getFullYear();
            }

            selectedSlots.push({ id_waktu: idWaktu, jam: jam, tanggal: tanggal });
            updateCartUI();
            fetchSlots(tanggal);
            startTimer(15 * 60); 
        } else {
            Swal.fire('Gagal', data.message, 'error');
            fetchSlots(tanggal);
        }
    } catch (e) { console.error(e); }
}

// --- API: UNHOLD SLOT ---
async function unholdSlot(idWaktu, tanggal) {
    const formData = new FormData();
    formData.append('action', 'unhold_slot');
    formData.append('id_waktu', idWaktu);
    formData.append('tanggal', tanggal);
    formData.append('id_lapangan', selectedLapId);

    try {
        await fetch('member.php', { method: 'POST', body: formData });
        selectedSlots = selectedSlots.filter(s => !(s.id_waktu == idWaktu && s.tanggal === tanggal));
        
        // Reset Lock Bulan jika keranjang kosong
        if (selectedSlots.length === 0) {
            lockedMonth = null;
            lockedYear = null;
            resetTimer();
        }

        updateCartUI();
        fetchSlots(tanggal);
    } catch (e) { console.error(e); }
}

function updateCartUI() {
    const list = document.getElementById('selectedList');
    const countEl = document.getElementById('countSelected');
    const btnNext = document.getElementById('btnToStep3');
    const totalEl = document.getElementById('cartTotal');

    if(countEl) countEl.textContent = selectedSlots.length;
    
    // Sort tanggal
    selectedSlots.sort((a, b) => new Date(a.tanggal) - new Date(b.tanggal));

    if (selectedSlots.length === 0) {
        if(list) list.innerHTML = '<p class="text-xs text-white/40 text-center mt-10 italic">Belum ada jadwal dipilih.</p>';
        if(btnNext) { btnNext.disabled = true; btnNext.classList.add('opacity-50', 'cursor-not-allowed'); }
        if(totalEl) totalEl.textContent = 'Rp 0';
    } else {
        if(list) list.innerHTML = '';
        let total = 0;
        selectedSlots.forEach((s, idx) => {
            total += selectedLapPrice;
            const item = document.createElement('div');
            item.className = 'bg-white/10 rounded-lg p-3 flex justify-between items-center text-sm border border-white/5 animate-fade-in-up';
            item.innerHTML = `
                <div>
                    <div class="text-amber-400 font-bold text-[10px] uppercase">Pertemuan ${idx + 1}</div>
                    <div class="text-white font-bold">${formatDateIndo(s.tanggal)}</div>
                    <div class="text-white/70 text-xs">${s.jam}</div>
                </div>
                <button onclick="unholdSlot(${s.id_waktu}, '${s.tanggal}')" class="text-red-400 hover:text-red-200 p-2">
                    <i class="fa-solid fa-trash"></i>
                </button>
            `;
            list.appendChild(item);
        });
        if(totalEl) totalEl.textContent = formatRupiah(total);
        
        // Cek apakah kuota sudah terpenuhi
        if(btnNext) {
            if (selectedSlots.length === maxQuota) { 
                btnNext.disabled = false; 
                btnNext.classList.remove('opacity-50', 'cursor-not-allowed'); 
            } else { 
                btnNext.disabled = true; 
                btnNext.classList.add('opacity-50', 'cursor-not-allowed'); 
            }
        }
    }
}

// --- STEP 3: REVIEW ---
function renderReview() {
    const tableBody = document.getElementById('reviewTableBody');
    if(!tableBody) return;
    tableBody.innerHTML = '';
    let total = 0;
    
    selectedSlots.forEach((s, i) => {
        total += selectedLapPrice;
        tableBody.innerHTML += `
            <tr class="bg-white border-b border-slate-100">
                <td class="px-4 py-3 font-medium text-slate-900">${i + 1}</td>
                <td class="px-4 py-3 text-slate-600">${formatDateIndo(s.tanggal)}</td>
                <td class="px-4 py-3 text-center">
                    <span class="bg-amber-50 text-amber-700 rounded px-2 py-1 text-xs font-bold border border-amber-100">${s.jam}</span>
                </td>
            </tr>`;
    });
    
    // Ambil nama paket
    let namaPaket = "Custom";
    const checkedRadio = document.querySelector('input[name="paket"]:checked');
    if(checkedRadio) {
        namaPaket = checkedRadio.nextElementSibling.querySelector('h4').innerText;
    }
    
    // Ambil nama lapangan
    const lapSelect = document.getElementById('inputLapangan');
    const namaLapangan = lapSelect.options[lapSelect.selectedIndex].text.split('—')[0].trim();

    document.getElementById('reviewPaketName').innerText = namaPaket;
    document.getElementById('reviewLapanganName').innerText = namaLapangan;
    document.getElementById('reviewTotalMeet').innerText = selectedSlots.length + 'x Pertemuan';
    document.getElementById('reviewGrandTotal').innerText = formatRupiah(total);
}

// --- STEP 4: PAYMENT ---
function updatePaymentInfo() {
    const methodEl = document.querySelector('input[name="metode_bayar"]:checked');
    if(!methodEl) return;
    const method = methodEl.value;
    
    const container = document.getElementById('paymentDetails');
    let totalBayar = selectedSlots.length * selectedLapPrice;
    
    let html = '';
    if (method === 'qris') {
        html = `
            <div class="flex flex-col items-center animate-fade-in">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=BayarMember${totalBayar}" class="w-48 h-48 object-contain mx-auto mb-4 border p-2 rounded-lg">
                <p class="font-bold text-slate-800">Scan QRIS</p>
                <div class="bg-amber-100 text-amber-900 px-6 py-2 rounded-xl font-bold text-xl inline-block mt-2">Total: ${formatRupiah(totalBayar)}</div>
            </div>`;
    } else {
        let rek = method === 'bca' ? '123 456 7890' : '133 000 999 888';
        let bank = method === 'bca' ? 'Bank BCA' : 'Bank Mandiri';
        html = `
            <div class="flex flex-col items-center animate-fade-in">
                <p class="text-slate-500 mb-1">${bank}</p>
                <p class="text-2xl font-mono font-bold text-slate-800 mb-2 bg-slate-100 px-4 py-2 rounded-lg">${rek}</p>
                <p class="text-sm font-bold text-slate-600 mb-4">a.n Rush Badminton</p>
                <div class="bg-amber-100 text-amber-900 px-6 py-2 rounded-xl font-bold text-xl inline-block">Total: ${formatRupiah(totalBayar)}</div>
            </div>`;
    }
    container.innerHTML = html;
}

function handleFileUpload(input) {
    if (input.files && input.files[0]) {
        if(input.files[0].size > 2 * 1024 * 1024) {
            Swal.fire('File Besar', 'Maksimal 2MB', 'error');
            input.value = '';
            return;
        }
        document.getElementById('uploadPlaceholder').classList.add('hidden');
        document.getElementById('filePreview').classList.remove('hidden');
        document.getElementById('filePreview').classList.add('flex');
        document.getElementById('fileName').innerText = input.files[0].name;
    }
}

// --- API: SUBMIT FINAL (PERBAIKAN STATUS) ---
async function submitMember() {
    const fileInput = document.getElementById('inputBukti');
    if (!fileInput.files || !fileInput.files[0]) { 
        Swal.fire({ title: 'Bukti Kosong', text: 'Upload bukti transfer.', icon: 'warning' }); 
        return; 
    }

    const btn = document.getElementById('btnFinalSubmit');
    const originalText = btn.innerHTML;
    
    // Loading State
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Memproses...';
    btn.disabled = true;
    btn.classList.add('opacity-70', 'cursor-not-allowed');

    const formData = new FormData();
    formData.append('action', 'submit_member');
    formData.append('id_lapangan', selectedLapId);
    formData.append('paket_bulan', durationMonths);
    formData.append('total_bayar', selectedSlots.length * selectedLapPrice);
    formData.append('metode_pembayaran', document.querySelector('input[name="metode_bayar"]:checked').value);
    formData.append('selected_slots', JSON.stringify(selectedSlots));
    formData.append('bukti_transfer', fileInput.files[0]);

    try {
        const res = await fetch('member.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.status === 'success') {
            Swal.fire({
                title: 'Pendaftaran Berhasil!',
                text: 'Status Membership: PENDING. Admin akan segera memverifikasi.',
                icon: 'success',
                confirmButtonText: 'Lihat Riwayat',
                allowOutsideClick: false
            }).then(() => { 
                window.location.href = '../riwayat/riwayat.php'; 
            });
        } else {
            throw new Error(data.message);
        }
    } catch (e) {
        Swal.fire('Gagal', e.message || 'Terjadi kesalahan sistem.', 'error');
        // Restore button
        btn.innerHTML = originalText;
        btn.disabled = false;
        btn.classList.remove('opacity-70', 'cursor-not-allowed');
    }
}

// --- TIMER & UTILITIES ---
function startTimer(seconds) {
    clearInterval(holdTimerInterval);
    const display = document.getElementById('countdownDisplay');
    const bar = document.getElementById('memberTimerBar');
    
    if(!bar) return;
    let rem = seconds;
    bar.classList.remove('hidden'); bar.classList.add('flex');
    
    holdTimerInterval = setInterval(() => {
        rem--;
        let m = Math.floor(rem / 60); 
        let s = rem % 60;
        display.innerText = `${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
        
        if (rem <= 0) {
            clearInterval(holdTimerInterval);
            Swal.fire({
                title: 'Waktu Habis', 
                text: 'Slot dilepas karena waktu habis.', 
                icon: 'warning'
            }).then(() => resetTimer());
        }
    }, 1000);
}

async function checkExistingTimer() {
    try {
        const res = await fetch('member.php', { method: 'POST', body: new URLSearchParams({'action':'start_timer'}) });
        const data = await res.json();
        if (data.status === 'success' && data.remaining > 0) {
            startTimer(data.remaining);
            // Anda bisa menambahkan logika fetchSlots di sini jika ingin otomatis load grid
        }
    } catch (e) {}
}

async function resetTimer() {
    clearInterval(holdTimerInterval);
    document.getElementById('memberTimerBar').classList.add('hidden');
    
    selectedSlots = [];
    lockedMonth = null;
    lockedYear = null;
    
    updateCartUI();
    
    await fetch('member.php', { method: 'POST', body: new URLSearchParams({'action':'reset_timer'}) });
    
    if(document.getElementById('inputTanggal').value) {
        fetchSlots(document.getElementById('inputTanggal').value);
    }
    
    // Jika step > 2, kembali ke step 2
    if(currentStep > 2) prevStep(2);
}

function confirmCancelBooking() {
    Swal.fire({
        title: 'Batalkan Booking?', 
        text: 'Semua slot akan dihapus.', 
        icon: 'warning', 
        showCancelButton: true, 
        confirmButtonText: 'Ya', 
        cancelButtonText: 'Tidak'
    }).then((r) => { 
        if(r.isConfirmed) resetTimer(); 
    });
}

function formatRupiah(n) { 
    return 'Rp ' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "."); 
}

function formatDateIndo(d) { 
    return new Date(d).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }); 
}