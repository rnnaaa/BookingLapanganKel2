# 🕐 Setup Cron Job - Release Expired Bookings

## Deskripsi
Sistem booking member memiliki timeout **10 menit**. Jika pengguna tidak melakukan pembayaran dalam 10 menit setelah memilih tanggal, slot booking akan dirilis (dihapus) sehingga pengguna lain bisa mengakses jam tersebut.

---

## 📋 Komponen Sistem

### 1. **Database Schema** ✅
- Tabel `member_jadwal` sudah memiliki kolom:
  - `created_at` (TIMESTAMP) - Waktu slot dipilih
  - `status` (VARCHAR) - Status booking (pending, aktif, dll)

### 2. **Cron Script** ✅
- File: `cron/cron_release_expired_bookings.php`
- Fungsi: Menjalankan query untuk mencari dan menghapus booking yang expired
- Frekuensi: Setiap 1-2 menit

### 3. **API Endpoint** ✅
- File: `member/member.php`
- Endpoint: `member.php?action=check_booking_expiry&member_id={id}`
- Fungsi: Check status booking dan countdown waktu

### 4. **Frontend Timer** ✅
- File: `member/member.js`
- Fungsi: Tampilkan countdown 10 menit di UI
- Warning: Berubah merah ketika sisa waktu < 2 menit

---

## 🔧 Setup Cron Job

### **Opsi 1: Windows Task Scheduler** (Jika menggunakan Windows/Laragon)

#### Langkah:
1. Buka **Task Scheduler** (Tekan Windows + R, ketik `taskschd.msc`)
2. Klik **Create Basic Task**
3. Nama: `Release Expired Bookings`
4. Pilih **Trigger**: `Repeat task every 2 minutes` (or 1 minute)
5. **Action** → **Start a program**:
   ```
   Program: C:\laragon\bin\php\php-8.1.0-Win32-vs16-x64\php.exe
   (sesuaikan versi PHP Anda)
   
   Add arguments: C:\laragon\www\BookingLapanganKel2\cron\cron_release_expired_bookings.php
   ```
6. Click **OK**

### **Opsi 2: cPanel Cron (Jika menggunakan shared hosting)**

1. Login ke cPanel
2. Buka **Cron Jobs**
3. Tambah entry baru:
   ```
   Common Settings: Every 2 minutes
   atau
   Minute: */2
   
   Command: php /home/username/public_html/BookingLapanganKel2/cron/cron_release_expired_bookings.php
   ```

### **Opsi 3: Linux/Mac (Menggunakan crontab)**

```bash
# Edit crontab
crontab -e

# Tambahkan baris ini (jalankan setiap 2 menit):
*/2 * * * * php /var/www/BookingLapanganKel2/cron/cron_release_expired_bookings.php >> /var/log/cron_release_bookings.log 2>&1
```

---

## 📊 How It Works

### **Flow Pengguna:**

1. **User membuka member form** → Auto-fill nama & email ✅
2. **User memilih paket, lapangan, tanggal, jam**
3. **User klik "Lanjut ke Pembayaran"** → Slot booking disimpan dengan status `pending`
4. **Timer countdown muncul** (pojok kanan atas) ⏱️
5. **User punya 10 menit untuk bayar** 💰
   - Jika bayar dalam 10 menit → Booking tetap aktif ✅
   - Jika tidak bayar dalam 10 menit → Slot dirilis otomatis 🔓

### **Cron Job Logic:**

```
Setiap 2 menit:
  1. Query: SELECT member_jadwal WHERE status='pending' AND created_at <= (NOW - 10 menit)
  2. Untuk setiap hasil:
     - Check: Apakah member sudah bayar? (SELECT FROM member WHERE status != 'pending')
     - Jika BELUM bayar → DELETE dari member_jadwal
     - Log hasil ke error_log
  3. Selesai
```

---

## 🎨 UI/UX Changes

### **Countdown Timer Display**
- **Posisi**: Pojok kanan atas (fixed position)
- **Background**: Gradient purple (normal) → gradient orange-red (< 2 menit)
- **Info**: Menampilkan format `MM:SS` dan status
- **Auto-remove**: Otomatis hilang ketika close popup

### **Expired Warning**
- Jika waktu habis → Popup warning
- Form reset ke section A
- User harus mulai lagi dari awal

---

## 📝 Database Queries

### Query Check Expired:
```sql
SELECT id_member_jadwal, id_member, id_lapangan, tanggal_booking, jam_mulai, created_at
FROM member_jadwal
WHERE status = 'pending' 
AND created_at <= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
ORDER BY created_at ASC;
```

### Query Check Payment Status:
```sql
SELECT status, total_bayar FROM member WHERE id_member = ? LIMIT 1;
```

---

## ⚙️ Konfigurasi

### **Ubah Timeout Duration:**

Jika ingin ubah dari 10 menit ke X menit:

1. **File**: `cron/cron_release_expired_bookings.php`
   - Ubah: `$TIMEOUT_MINUTES = 10;` → `$TIMEOUT_MINUTES = X;`

2. **File**: `member/member.php` (endpoint check_booking_expiry)
   - Ubah: `$TIMEOUT_MINUTES = 10;` → `$TIMEOUT_MINUTES = X;`
   - Ubah: `600` (detik) → `X * 60` (untuk detik)

3. **File**: `member/member.js`
   - Ubah: Nilai dalam `setInterval` dari `600` ke `X * 60`

---

## 🔍 Monitoring & Logs

### **Check log messages:**

1. **File**: `/var/log/cron_release_bookings.log` (Linux/Mac)
   atau 
   **Event Viewer** (Windows Task Scheduler)

2. **Output example:**
   ```
   CRON: Released booking - ID:123, Member:45, Lapangan:2, Date:2025-11-20 14:00, Created:2025-11-20 13:50
   CRON COMPLETE: Released 2 expired bookings at 2025-11-20 14:00:00
   ```

---

## 🐛 Troubleshooting

### **Cron tidak berjalan?**

1. **Check if PHP CLI tersedia:**
   ```bash
   php --version
   ```

2. **Test script manual:**
   ```bash
   php /path/to/cron_release_expired_bookings.php
   ```

3. **Check file permissions:**
   ```bash
   chmod 755 cron/cron_release_expired_bookings.php
   ```

4. **Check log untuk error:**
   ```bash
   tail -f /var/log/cron_release_bookings.log
   ```

### **Booking tidak terhapus meski sudah 10 menit?**

1. Check: Apakah cron job berjalan?
2. Check: Database connection valid?
3. Check: Status member masih 'pending'?

---

## 📚 Related Files

- `member/member.php` - Main form & API endpoints
- `member/member.js` - Frontend timer & countdown
- `cron/cron_release_expired_bookings.php` - Cron script
- Database: `bookinglapanganb2.member_jadwal`

---

**Setup Selesai! ✅** Sistem akan otomatis release booking yang tidak dibayar dalam 10 menit.
