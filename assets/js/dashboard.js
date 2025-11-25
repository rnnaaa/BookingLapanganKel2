document.addEventListener("DOMContentLoaded", () => {
    const USER = window.USER_DATA || {};
    const BOOKINGS = window.BOOKING_DATA || [];

    // --- 1. STATS & WIDGETS (Sama) ---
    const totalBooking = BOOKINGS.length;
    const activeBooking = BOOKINGS.filter(b => ['menunggu', 'disetujui'].includes(b.status)).length;
    const totalHours = totalBooking * 1; 
    const lastPayment = BOOKINGS.find(b => parseFloat(b.total_amount) > 0);

    setText("statTotal", totalBooking);
    setText("statActive", activeBooking);
    setText("statHours", totalHours + "+");
    setText("statLastPayment", lastPayment ? formatRupiah(lastPayment.total_amount) : "-");

    const nextBooking = BOOKINGS.find(b => b.status === 'disetujui' && new Date(b.tanggal) >= new Date());
    const nextBox = document.getElementById("nextBookingBox");
    if(nextBox) {
        if (nextBooking) {
            nextBox.innerHTML = `
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white/10 rounded-lg flex items-center justify-center text-xl">📅</div>
                    <div>
                        <h5 class="font-bold text-lg">${nextBooking.nama_lapangan}</h5>
                        <p class="text-white/70 text-sm">${formatDate(nextBooking.tanggal)} • ${nextBooking.jam_mulai.substring(0,5)}</p>
                    </div>
                </div>`;
        } else {
            nextBox.innerHTML = `<p class="text-white/50 text-sm italic">Tidak ada jadwal mendatang.</p>`;
        }
    }

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

    // --- 2. CHART (Sama) ---
    const ctx = document.getElementById('hourChart');
    if(ctx) {
        const hoursMap = Array(24).fill(0);
        BOOKINGS.forEach(b => { if(b.jam_mulai) hoursMap[parseInt(b.jam_mulai.split(':')[0])]++; });
        const labels = [], data = [];
        for(let i=8; i<=22; i++) { labels.push(i < 10 ? `0${i}:00` : `${i}:00`); data.push(hoursMap[i]); }
        new Chart(ctx, {
            type: 'bar',
            data: { labels: labels, datasets: [{ label: 'Frekuensi Main', data: data, backgroundColor: '#0b63d6', borderRadius: 4, barThickness: 12 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { display: true, drawBorder: false } }, x: { grid: { display: false } } } }
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
        // Reset Username State saat buka modal
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

    // --- TAMBAHAN: LOGOUT DENGAN SWEETALERT ---
    if(btnLogout) {
        btnLogout.addEventListener('click', (e) => {
            e.preventDefault();
            Swal.fire({
                title: 'Konfirmasi Keluar',
                text: "Apakah Anda yakin ingin keluar dari akun Anda?",
                icon: 'warning',
                iconColor: '#ef4444', // Merah sesuai gambar warning
                showCancelButton: true,
                confirmButtonText: 'Ya, Keluar',
                cancelButtonText: 'Batal',
                reverseButtons: true, // Agar tombol Batal di kiri, Keluar di kanan (opsional, sesuaikan selera)
                
                // Kustomisasi Tombol agar mirip Gambar (Putih & Merah)
                customClass: {
                    popup: 'rounded-2xl font-sans', 
                    title: 'text-xl font-bold text-slate-800',
                    htmlContainer: 'text-slate-500',
                    confirmButton: 'bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-sm mx-1',
                    cancelButton: 'bg-white hover:bg-slate-50 text-slate-600 border border-slate-300 font-bold py-2.5 px-6 rounded-lg shadow-sm mx-1'
                },
                buttonsStyling: false // Mematikan style bawaan SweetAlert agar class Tailwind di atas bekerja
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'auth/php/logout.php';
                }
            });
        });
    }

    // --- TAMBAHAN: CEK USERNAME REAL-TIME ---
    const inputUsername = document.getElementById('inputUsername');
    const usernameError = document.getElementById('usernameError');
    const usernameSuccess = document.getElementById('usernameSuccess');
    let usernameTimeout = null;

    if(inputUsername) {
        inputUsername.addEventListener('input', function() {
            clearTimeout(usernameTimeout);
            const val = this.value.trim();

            // Reset visual
            this.classList.remove('border-red-500', 'focus:ring-red-200', 'border-green-500');
            usernameError.classList.add('hidden');
            usernameSuccess.classList.add('hidden');
            btnSaveProfile.disabled = false;

            if(val === USER.username) return; // Jika sama dengan username sendiri, abaikan
            if(val.length < 3) return; // Jangan cek jika terlalu pendek

            usernameTimeout = setTimeout(() => {
                fetch(`check_username.php?username=${val}`)
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'taken') {
                        // USERNAME SUDAH DIPAKAI (MERAH SEPERTI GAMBAR)
                        inputUsername.classList.add('border-red-500', 'focus:ring-red-200');
                        inputUsername.classList.remove('focus:border-primary', 'focus:ring-primary/20');
                        usernameError.classList.remove('hidden');
                        btnSaveProfile.disabled = true;
                    } else {
                        // TERSEDIA (HIJAU/NORMAL)
                        inputUsername.classList.add('border-green-500');
                        // usernameSuccess.classList.remove('hidden'); // Opsional jika mau menampilkan teks tersedia
                        btnSaveProfile.disabled = false;
                    }
                })
                .catch(err => console.error("Error check username:", err));
            }, 500); // Delay 500ms agar tidak spam request
        });
    }


    // --- 4. SIMPAN PROFIL DENGAN SWEETALERT ---
    // --- 4. SIMPAN PROFIL (FIXED: MENDUKUNG UPLOAD FOTO) ---
    if(btnSaveProfile) {
        btnSaveProfile.addEventListener('click', () => {
            // 1. Validasi Input Sederhana
            const nama = document.getElementById('inputNama').value.trim();
            const username = document.getElementById('inputUsername').value.trim();
            const hp = document.getElementById('inputHP').value.trim();
            
            // Cek username error (jika ada class merah)
            const inputUsername = document.getElementById('inputUsername');
            if(inputUsername.classList.contains('border-red-500')) {
                Swal.fire({ icon: 'error', title: 'Username Tidak Valid', text: 'Harap ganti username lain.', confirmButtonColor: '#0b63d6' });
                return;
            }

            if(!nama || !username || !hp) {
                Swal.fire({ icon: 'warning', title: 'Data Belum Lengkap', text: 'Nama, Username, dan No HP harus diisi!', confirmButtonColor: '#0b63d6' });
                return;
            }

            // 2. UI Loading State
            const originalText = btnSaveProfile.innerText;
            btnSaveProfile.innerText = "Menyimpan...";
            btnSaveProfile.disabled = true;

            // 3. CONSTRUCT FORM DATA (PERBAIKAN UTAMA DI SINI)
            // Menggunakan 'new FormData(formElement)' otomatis mengambil SEMUA input termasuk FILE gambar
            const formElement = document.getElementById('editProfileForm'); 
            const formData = new FormData(formElement);

            // 4. Kirim ke Backend
            fetch('update_profile.php', {
                method: 'POST',
                body: formData // Jangan set Content-Type header, biarkan browser mengaturnya untuk Multipart
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
                        location.reload(); // Reload agar foto header berubah
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
    
    // --- 5. LOGIC PREVIEW FOTO (PASTIKAN INI ADA) ---
    const inputFoto = document.getElementById('inputFoto');
    const previewAvatar = document.getElementById('previewAvatar');
    const previewAvatarDiv = document.getElementById('previewAvatarDiv');
    const previewAvatarNew = document.getElementById('previewAvatarNew');
    const defaultIcon = document.getElementById('defaultIcon');

    if(inputFoto) {
        inputFoto.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Validasi ukuran di Client (Max 2MB)
                if(file.size > 2 * 1024 * 1024) {
                    Swal.fire('File Terlalu Besar', 'Maksimal ukuran foto adalah 2MB', 'warning');
                    this.value = ''; 
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    // Jika user sudah punya foto sebelumnya
                    if (previewAvatar) {
                        previewAvatar.src = e.target.result;
                    } 
                    // Jika user belum punya foto (tampilan default)
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