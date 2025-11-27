document.addEventListener("DOMContentLoaded", () => {
    const USER = window.USER_DATA || {};
    const BOOKINGS = window.BOOKING_DATA || [];

    // --- 1. STATS & WIDGETS (REVISI: LEBIH INFORMATIF & TOTAL PENGELUARAN) ---
    const totalBooking = BOOKINGS.length;
    
    // Menghitung booking yang statusnya 'menunggu' (belum lunas/verif) atau 'disetujui' (siap main)
    const activeBooking = BOOKINGS.filter(b => ['menunggu', 'disetujui'].includes(b.status)).length;
    
    // Asumsi 1 booking = 1 jam, bisa disesuaikan dengan data real jika ada kolom durasi
    const totalHours = totalBooking * 1; 

    // === LOGIKA BARU: MENGHITUNG TOTAL PENGELUARAN SELAMA INI ===
    // Kita ambil semua booking yang statusnya TIDAK 'dibatalkan' atau 'ditolak'
    // Lalu kita jumlahkan total_amount-nya
    const totalSpend = BOOKINGS
        .filter(b => b.status !== 'dibatalkan' && b.status !== 'ditolak')
        .reduce((acc, curr) => acc + parseFloat(curr.total_amount || 0), 0);

    // Update UI Stats
    setText("statTotal", totalBooking);
    setText("statActive", activeBooking);
    setText("statHours", totalHours + " Jam"); // Menambahkan label 'Jam'
    setText("statTotalSpend", formatRupiah(totalSpend)); // Format Rupiah

    // --- WIDGET JADWAL BERIKUTNYA (LOGIKA BARU: FILTER & SORTING) ---
    
    const now = new Date();

    // 1. Filter: Ambil hanya yang 'disetujui' DAN waktunya belum lewat
    const upcomingBookings = BOOKINGS.filter(b => {
        if (b.status !== 'disetujui') return false;

        // Gabungkan Tanggal dan Jam untuk perbandingan presisi
        // Asumsi format b.tanggal: "YYYY-MM-DD" dan b.jam_mulai: "HH:MM:SS"
        const bookingTime = new Date(`${b.tanggal}T${b.jam_mulai}`);

        // Return true jika waktu booking masih di masa depan (lebih besar dari sekarang)
        return bookingTime > now;
    });

    // 2. Sort: Urutkan dari yang paling dekat (Ascending)
    upcomingBookings.sort((a, b) => {
        const timeA = new Date(`${a.tanggal}T${a.jam_mulai}`);
        const timeB = new Date(`${b.tanggal}T${b.jam_mulai}`);
        return timeA - timeB; // Ascending: Kecil (dekat) ke Besar (jauh)
    });

    // 3. Ambil item pertama (yang paling dekat)
    const nextBooking = upcomingBookings.length > 0 ? upcomingBookings[0] : null;

    // 4. Render ke HTML
    const nextBox = document.getElementById("nextBookingBox");
    if(nextBox) {
        if (nextBooking) {
            nextBox.innerHTML = `
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white/10 rounded-lg flex items-center justify-center text-xl">📅</div>
                    <div>
                        <h5 class="font-bold text-lg">${nextBooking.nama_lapangan}</h5>
                        <p class="text-white/70 text-sm">
                            ${formatDate(nextBooking.tanggal)} • ${nextBooking.jam_mulai.substring(0,5)}
                        </p>
                    </div>
                </div>`;
        } else {
            nextBox.innerHTML = `<p class="text-white/50 text-sm italic">Tidak ada jadwal mendatang.</p>`;
        }
    }

    // Widget Lapangan Favorit
    const counts = {};
    BOOKINGS.forEach(b => { counts[b.nama_lapangan] = (counts[b.nama_lapangan] || 0) + 1; });
    const favs = Object.keys(counts).sort((a,b) => counts[b] - counts[a]).slice(0, 3);
    const favContainer = document.getElementById("favFields");
    if(favContainer) {
        if(favs.length > 0) {
            favContainer.innerHTML = favs.map(name => `
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                    <span class="font-medium text-slate-700 text-sm">${name}</span>
                    <span class="text-xs bg-white border border-slate-200 px-2 py-1 rounded-md text-slate-500">${counts[name]}x main</span>
                </div>`).join('');
        } else {
            favContainer.innerHTML = `<p class="text-slate-400 text-xs text-center py-2">Belum ada riwayat bermain.</p>`;
        }
    }

    // --- 2. CHART ---
    const ctx = document.getElementById('hourChart');
    if(ctx) {
        const hoursMap = Array(24).fill(0);
        BOOKINGS.forEach(b => { if(b.jam_mulai) hoursMap[parseInt(b.jam_mulai.split(':')[0])]++; });
        const labels = [], data = [];
        for(let i=8; i<=22; i++) { labels.push(i < 10 ? `0${i}:00` : `${i}:00`); data.push(hoursMap[i]); }
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Frekuensi Main',
                    data: data,
                    backgroundColor: function(context) {
                        const chart = context.chart;
                        const {ctx, chartArea} = chart;
                        if (!chartArea) return null;
                        const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                        gradient.addColorStop(0, '#0056b3');
                        gradient.addColorStop(1, '#004494');
                        return gradient;
                    },
                    borderColor: '#003d82',
                    borderWidth: 1,
                    borderRadius: 6,
                    barThickness: 16
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1500,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#0056b3',
                        titleFont: { weight: 'bold' },
                        bodyColor: '#333333',
                        borderColor: '#dddddd',
                        borderWidth: 1,
                        cornerRadius: 6,
                        padding: 10,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.parsed.y + ' kali';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: '#666666',
                            font: { size: 12 }
                        },
                        grid: {
                            color: '#e5e7eb',
                            drawBorder: false
                        }
                    },
                    x: {
                        ticks: {
                            color: '#666666',
                            font: { size: 12 }
                        },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // --- 3. INTERAKSI MODAL & DROPDOWN ---
    const profileBtn = document.getElementById('profileMenuBtn');
    const profileDropdown = document.getElementById('profileDropdown');
    const modalOverlay = document.getElementById('modalOverlay');
    const modalContent = document.getElementById('modalContent');
    const btnEditProfile = document.getElementById('btnEditProfile');
    const btnCancelEdit = document.getElementById('btnCancelEdit');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const btnSaveProfile = document.getElementById('btnSaveProfile');
    const jobSelect = document.getElementById('inputPekerjaan');
    const customJobDiv = document.getElementById('customJobDiv');
    const customJobInput = document.getElementById('inputPekerjaanLain');
    const btnLogout = document.getElementById('btnLogout');

    if(profileBtn && profileDropdown) {
        profileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isHidden = profileDropdown.classList.contains('hidden');
            if(isHidden) {
                profileDropdown.classList.remove('hidden');
                setTimeout(() => { profileDropdown.classList.remove('scale-95', 'opacity-0'); profileDropdown.classList.add('scale-100', 'opacity-100'); }, 10);
            } else {
                closeDropdown();
            }
        });
        document.addEventListener('click', (e) => { if(!profileDropdown.contains(e.target)) closeDropdown(); });
    }

    function closeDropdown() {
        if(!profileDropdown) return;
        profileDropdown.classList.remove('scale-100', 'opacity-100');
        profileDropdown.classList.add('scale-95', 'opacity-0');
        setTimeout(() => profileDropdown.classList.add('hidden'), 200);
    }

    function openModal() {
        closeDropdown();
        modalOverlay.classList.remove('hidden');
        handleJobSelect();
        
        const inputUsername = document.getElementById('inputUsername');
        const errorText = document.getElementById('usernameError');
        if(inputUsername) {
            inputUsername.classList.remove('border-red-500', 'ring-red-200');
            errorText.classList.add('hidden');
            btnSaveProfile.disabled = false;
        }

        setTimeout(() => {
            modalOverlay.classList.remove('opacity-0');
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeModal() {
        modalOverlay.classList.add('opacity-0');
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');
        setTimeout(() => modalOverlay.classList.add('hidden'), 300);
    }

    function handleJobSelect() {
        if(jobSelect.value === 'Lainnya') {
            customJobDiv.classList.remove('hidden');
            if(USER.pekerjaan === 'Lainnya') customJobInput.value = USER.pekerjaan_lain || '';
        } else {
            customJobDiv.classList.add('hidden');
        }
    }

    if(btnEditProfile) btnEditProfile.addEventListener('click', openModal);
    if(btnCancelEdit) btnCancelEdit.addEventListener('click', closeModal);
    if(closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
    if(modalOverlay) modalOverlay.addEventListener('click', (e) => { if(e.target === modalOverlay) closeModal(); });
    if(jobSelect) jobSelect.addEventListener('change', handleJobSelect);

    // --- LOGOUT ---
    if(btnLogout) {
        btnLogout.addEventListener('click', (e) => {
            e.preventDefault();
            Swal.fire({
                title: 'Konfirmasi Keluar',
                text: "Apakah Anda yakin ingin keluar dari akun Anda?",
                icon: 'warning',
                iconColor: '#ef4444', 
                showCancelButton: true,
                confirmButtonText: 'Ya, Keluar',
                cancelButtonText: 'Batal',
                reverseButtons: true, 
                customClass: {
                    popup: 'rounded-2xl font-sans', 
                    title: 'text-xl font-bold text-slate-800',
                    htmlContainer: 'text-slate-500',
                    confirmButton: 'bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-sm mx-1',
                    cancelButton: 'bg-white hover:bg-slate-50 text-slate-600 border border-slate-300 font-bold py-2.5 px-6 rounded-lg shadow-sm mx-1'
                },
                buttonsStyling: false 
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'auth/php/logout.php';
                }
            });
        });
    }

    // --- CHECK USERNAME ---
    const inputUsername = document.getElementById('inputUsername');
    const usernameError = document.getElementById('usernameError');
    const usernameSuccess = document.getElementById('usernameSuccess');
    let usernameTimeout = null;

    if(inputUsername) {
        inputUsername.addEventListener('input', function() {
            clearTimeout(usernameTimeout);
            const val = this.value.trim();

            this.classList.remove('border-red-500', 'focus:ring-red-200', 'border-green-500');
            usernameError.classList.add('hidden');
            usernameSuccess.classList.add('hidden');
            btnSaveProfile.disabled = false;

            if(val === USER.username) return; 
            if(val.length < 3) return; 

            usernameTimeout = setTimeout(() => {
                fetch(`check_username.php?username=${val}`)
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'taken') {
                        inputUsername.classList.add('border-red-500', 'focus:ring-red-200');
                        inputUsername.classList.remove('focus:border-primary', 'focus:ring-primary/20');
                        usernameError.classList.remove('hidden');
                        btnSaveProfile.disabled = true;
                    } else {
                        inputUsername.classList.add('border-green-500');
                        btnSaveProfile.disabled = false;
                    }
                })
                .catch(err => console.error("Error check username:", err));
            }, 500); 
        });
    }

    // --- SIMPAN PROFIL & FOTO ---
    if(btnSaveProfile) {
        btnSaveProfile.addEventListener('click', () => {
            const nama = document.getElementById('inputNama').value.trim();
            const username = document.getElementById('inputUsername').value.trim();
            const hp = document.getElementById('inputHP').value.trim();
            
            const inputUsernameCheck = document.getElementById('inputUsername');
            if(inputUsernameCheck.classList.contains('border-red-500')) {
                Swal.fire({ icon: 'error', title: 'Username Tidak Valid', text: 'Harap ganti username lain.', confirmButtonColor: '#0b63d6' });
                return;
            }

            if(!nama || !username || !hp) {
                Swal.fire({ icon: 'warning', title: 'Data Belum Lengkap', text: 'Nama, Username, dan No HP harus diisi!', confirmButtonColor: '#0b63d6' });
                return;
            }

            const originalText = btnSaveProfile.innerText;
            btnSaveProfile.innerText = "Menyimpan...";
            btnSaveProfile.disabled = true;

            const formElement = document.getElementById('editProfileForm'); 
            const formData = new FormData(formElement);

            fetch('update_profile.php', {
                method: 'POST',
                body: formData 
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Profil dan foto berhasil diperbarui.',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload(); 
                    });
                } else {
                    throw new Error(data.message || 'Terjadi kesalahan server');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Menyimpan',
                    text: err.message,
                    confirmButtonColor: '#0b63d6'
                });
                btnSaveProfile.innerText = originalText;
                btnSaveProfile.disabled = false;
            });
        });
    }
    
    // --- PREVIEW FOTO ---
    const inputFoto = document.getElementById('inputFoto');
    const previewAvatar = document.getElementById('previewAvatar');
    const previewAvatarDiv = document.getElementById('previewAvatarDiv');
    const previewAvatarNew = document.getElementById('previewAvatarNew');
    const defaultIcon = document.getElementById('defaultIcon');

    if(inputFoto) {
        inputFoto.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                if(file.size > 2 * 1024 * 1024) {
                    Swal.fire('File Terlalu Besar', 'Maksimal ukuran foto adalah 2MB', 'warning');
                    this.value = ''; 
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    if (previewAvatar) {
                        previewAvatar.src = e.target.result;
                    } 
                    else if (previewAvatarDiv) {
                        if(defaultIcon) defaultIcon.classList.add('hidden');
                        if(previewAvatarNew) {
                            previewAvatarNew.classList.remove('hidden');
                            previewAvatarNew.src = e.target.result;
                        }
                    }
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // Helpers
    function setText(id, val) { const el = document.getElementById(id); if(el) el.textContent = val; }
    function formatRupiah(num) { return "Rp " + parseInt(num).toLocaleString('id-ID'); }
    function formatDate(dateString) { 
        const date = new Date(dateString);
        const hariIndo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const bulanIndo = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        const hari = hariIndo[date.getDay()];
        const tgl = date.getDate();
        const bulan = bulanIndo[date.getMonth()];
        const tahun = date.getFullYear();

        return `${hari}, ${tgl} ${bulan} ${tahun}`;
    }
    
});