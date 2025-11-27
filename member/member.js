let currentStep = 1;
let selectedSlots = []; 
let maxQuota = 0;
let hargaPerJam = 0;

// --- FUNGSI UTAMA: HITUNG NOMOR MINGGU (ISO-8601) ---
function getISOWeek(date) {
    const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
    const dayNum = d.getUTCDay() || 7;
    d.setUTCDate(d.getUTCDate() + 4 - dayNum);
    const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
    return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
}

// --- NAVIGASI WIZARD ---
function nextStep(step) {
    if (step === 2) {
        // Validasi Step 1
        const paketEl = document.querySelector('input[name="paket"]:checked');
        const lapEl = document.getElementById('inputLapangan');
        
        if (!paketEl) return Swal.fire('Pilih Paket', 'Silakan pilih durasi paket.', 'warning');
        
        maxQuota = parseInt(paketEl.dataset.quota);
        hargaPerJam = parseInt(lapEl.options[lapEl.selectedIndex].dataset.harga);
        
        document.getElementById('maxQuota').textContent = maxQuota;
        renderSelectedList(); 
    }
    
    if (step === 3) {
        // Validasi Step 2
        if (selectedSlots.length !== maxQuota) {
            return Swal.fire('Jadwal Belum Lengkap', `Anda harus memilih <b>${maxQuota}</b> slot jadwal (1x per Minggu).`, 'warning');
        }
        updateSummary();
    }

    // Pindah UI
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

// --- LOGIC PILIH JADWAL ---
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
                const isSelected = selectedSlots.some(s => s.id_waktu === slot.id_waktu && s.tanggal === tanggal);
                const statusClass = slot.status === 'tersedia' ? 'available' : 'booked disabled';
                const selectedClass = isSelected ? 'selected' : '';
                
                html += `<div class="slot-btn ${statusClass} ${selectedClass}" 
                              onclick="toggleSlot('${slot.id_waktu}', '${tanggal}', '${slot.jam}', this)">
                              ${slot.jam}
                         </div>`;
            });
            html += '</div>';
            container.innerHTML = html;
        }
    });
});

function toggleSlot(id_waktu, tanggal, jam, el) {
    if (el.classList.contains('disabled')) return;

    // Cek apakah slot ini sudah dipilih?
    const index = selectedSlots.findIndex(s => s.id_waktu === id_waktu && s.tanggal === tanggal);

    if (index > -1) {
        // Jika sudah, HAPUS (Unselect)
        selectedSlots.splice(index, 1);
        el.classList.remove('selected');
    } else {
        // Jika belum, TAMBAH (Select) dengan Validasi
        
        // 1. Cek Kuota
        if (selectedSlots.length >= maxQuota) {
            return Swal.fire('Kuota Penuh', `Maksimal ${maxQuota} slot untuk paket ini.`, 'info');
        }

        // 2. VALIDASI INTI: Cek apakah Minggu ini sudah ada slot yang dipilih?
        const targetDate = new Date(tanggal);
        const targetWeek = getISOWeek(targetDate);
        const targetYear = targetDate.getFullYear(); // Penting agar minggu 1 tahun ini beda dgn tahun depan
        
        // Cek apakah ada slot lain yang punya (Tahun sama DAN Minggu sama)
        const isWeekOccupied = selectedSlots.some(s => {
            const d = new Date(s.tanggal);
            return getISOWeek(d) === targetWeek && d.getFullYear() === targetYear;
        });

        if (isWeekOccupied) {
            return Swal.fire('Jadwal Bentrok', 'Anda hanya boleh memilih <b>1 jadwal</b> dalam minggu ini.', 'warning');
        }

        // Jika lolos, masukkan
        selectedSlots.push({ id_waktu, tanggal, jam });
        el.classList.add('selected');
    }
    renderSelectedList();
}

function renderSelectedList() {
    const listEl = document.getElementById('selectedList');
    const countEl = document.getElementById('countSelected');
    const btnPay = document.getElementById('btnToPayment');

    countEl.textContent = selectedSlots.length;
    
    if (selectedSlots.length === maxQuota) {
        btnPay.disabled = false;
        btnPay.classList.remove('opacity-50', 'cursor-not-allowed');
    } else {
        btnPay.disabled = true;
        btnPay.classList.add('opacity-50', 'cursor-not-allowed');
    }

    if (selectedSlots.length === 0) {
        listEl.innerHTML = '<p class="text-xs text-slate-400 text-center mt-10">Belum ada jadwal dipilih.</p>';
        return;
    }

    selectedSlots.sort((a,b) => new Date(a.tanggal) - new Date(b.tanggal));

    listEl.innerHTML = selectedSlots.map((s, i) => `
        <div class="selected-item">
            <div>
                <div class="text-xs font-bold text-slate-700">${formatDate(s.tanggal)}</div>
                <div class="text-xs text-slate-500">${s.jam}</div>
            </div>
            <button onclick="removeSlot(${i})" class="text-red-500 hover:text-red-700"><i class="fa-solid fa-trash"></i></button>
        </div>
    `).join('');
}

function removeSlot(index) {
    const removed = selectedSlots[index];
    selectedSlots.splice(index, 1);
    renderSelectedList();
    
    const currentTanggal = document.getElementById('inputTanggal').value;
    if (currentTanggal === removed.tanggal) {
        document.getElementById('inputTanggal').dispatchEvent(new Event('change'));
    }
}

function updateSummary() {
    const lapEl = document.getElementById('inputLapangan');
    const namaLap = lapEl.options[lapEl.selectedIndex].text.split('(')[0];
    const totalBayar = selectedSlots.length * hargaPerJam; // Harga per jam x Total Sesi

    document.getElementById('summLap').textContent = namaLap;
    document.getElementById('summSesi').textContent = selectedSlots.length + ' x Pertemuan';
    document.getElementById('summTotal').textContent = 'Rp ' + totalBayar.toLocaleString('id-ID');
}

function submitMember() {
    const fileInput = document.getElementById('inputBukti');
    if (fileInput.files.length === 0) return Swal.fire('Upload Bukti', 'Bukti transfer wajib diupload.', 'warning');

    const btn = document.getElementById('btnFinalSubmit');
    btn.disabled = true;
    btn.innerText = 'Memproses...';

    const formData = new FormData();
    formData.append('action', 'submit_member');
    formData.append('id_lapangan', document.getElementById('inputLapangan').value);
    formData.append('paket_bulan', document.querySelector('input[name="paket"]:checked').value);
    formData.append('total_bayar', selectedSlots.length * hargaPerJam);
    formData.append('metode_pembayaran', document.querySelector('input[name="metode"]:checked').value);
    formData.append('selected_slots', JSON.stringify(selectedSlots));
    formData.append('bukti_transfer', fileInput.files[0]);

    fetch('member.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire('Berhasil!', 'Pendaftaran member berhasil. Menunggu verifikasi admin.', 'success')
            .then(() => window.location.href = '../DashPengguna.php');
        } else {
            Swal.fire('Gagal', data.message, 'error');
            btn.disabled = false;
            btn.innerText = 'Kirim & Daftar';
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'Terjadi kesalahan koneksi.', 'error');
        btn.disabled = false;
        btn.innerText = 'Kirim & Daftar';
    });
}

function formatDate(dateString) {
    const options = { day: 'numeric', month: 'short', year: 'numeric' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
}