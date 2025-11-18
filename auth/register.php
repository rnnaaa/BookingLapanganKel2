<?php
session_start(); // Diperlukan untuk menampilkan pesan error/sukses
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Daftar — BookingLapangan</title>
  
  <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>

  <main>
    <div class="auth-card">

      <h1>Buat Akun Baru</h1>

      <?php if(!empty($_SESSION['error'])){ echo "<div class='alert alert-error'>".htmlspecialchars($_SESSION['error'])."</div>"; unset($_SESSION['error']); } ?>
      <?php if(!empty($_SESSION['success'])){ echo "<div class='alert alert-success'>".htmlspecialchars($_SESSION['success'])."</div>"; unset($_SESSION['success']); } ?>

      <form id="regForm" method="POST" action="php/register_process.php">
        <div class="form-group">
          <label for="nama">Nama Lengkap</label>
          <input id="nama" name="nama" class="form-control" required>
        </div>

        <div class="form-grid">
          <div>
            <label for="username">Username</label>
            <input id="username" name="username" class="form-control" required>
          </div>
          <div>
            <label for="phone">No. HP (WhatsApp)</label>
            <input id="phone" name="phone" class="form-control" required placeholder="08...">
          </div>
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" class="form-control" required>
        </div>

        <div class="form-grid">
          <div>
            <label for="pekerjaan">Pekerjaan</label>
            <select id="pekerjaan" name="pekerjaan" class="form-control">
              <option value="Pelajar">Pelajar</option>
              <option value="Mahasiswa">Mahasiswa</option>
              <option value="Wirausaha">Wirausaha</option>
              <option value="Lainnya">Lainnya</option>
            </select>
          </div>
          <div id="pekerjaan_lain_wrapper" style="display:none;">
            <label for="pekerjaan_lain">Jika Lainnya, sebutkan</label>
            <input name="pekerjaan_lain" id="pekerjaan_lain" class="form-control">
          </div>
        </div>

        <div class="form-grid">
          
          <div>
            <label for="password">Password</label>
            <div class="password-wrapper">
              <input id="password" name="password" type="password" class="form-control" required>
              <button type="button" class="password-toggle" data-toggle-password>
                <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.25rem; height:1.25rem;">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <svg class="icon-eye-slash" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.25rem; height:1.25rem; display: none;">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A.75.75 0 003 9v.75a.75.75 0 001.5 0v-.75A.75.75 0 003.98 8.223zM3.98 15.75A.75.75 0 003 16.5v.75a.75.75 0 001.5 0v-.75a.75.75 0 00-.52-.727zM6.02 5.03A.75.75 0 004.5 5.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 006.02 5.03zM6.02 18.97A.75.75 0 004.5 19.75v.75a.75.75 0 001.5 0v-.75a.75.75 0 00-.52-.727zM9.02 2.03A.75.75 0 007.5 2.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 009.02 2.03zM9.02 21.97A.75.75 0 007.5 22.75v.75a.75.75 0 001.5 0v-.75a.75.75 0 00-.52-.727zM12.02 0A.75.75 0 0010.5.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0012.02 0zM12.02 24A.75.75 0 0010.5 24.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0012.02 24zM15.02 2.03A.75.75 0 0013.5 2.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0015.02 2.03zM15.02 21.97A.75.75 0 0013.5 22.75v.75a.75.75 0 001.5 0v-.75a.75.75 0 00-.52-.727zM18.02 5.03A.75.75 0 0016.5 5.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0018.02 5.03zM18.02 18.97A.75.75 0 0016.5 19.75v.75a.75.75 0 001.5 0v-.75a.75.75 0 00-.52-.727zM21.02 8.223A.75.75 0 0019.5 9v.75a.75.75 0 001.5 0v-.75a.75.75 0 00-.52-.727zM21.02 15.75A.75.75 0 0019.5 16.5v.75a.75.75 0 001.5 0v-.75a.75.75 0 00-.52-.727z" />
                </svg>
              </button>
            </div>
          </div>
          
          <div>
            <label for="password2">Ulangi Password</label>
            <div class="password-wrapper">
              <input id="password2" name="password2" type="password" class="form-control" required>
              <button type="button" class="password-toggle" data-toggle-password>
                <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.25rem; height:1.25rem;">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <svg class="icon-eye-slash" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.25rem; height:1.25rem; display: none;">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A.75.75 0 003 9v.75a.75.75 0 001.5 0v-.75A.75.75 0 003.98 8.223zM3.98 15.75A.75.75 0 003 16.5v.75a.75.75 0 001.5 0v-.75a.75.75 0 00-.52-.727zM6.02 5.03A.75.75 0 004.5 5.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 006.02 5.03zM6.02 18.97A.75.75 0 004.5 19.75v.75a.75.75 0 001.5 0v-.75a.75.75 0 00-.52-.727zM9.02 2.03A.75.75 0 007.5 2.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 009.02 2.03zM9.02 21.97A.75.75 0 007.5 22.75v.75a.75.75 0 001.5 0v-.75a.75.75 0 00-.52-.727zM12.02 0A.75.75 0 0010.5.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0012.02 0zM12.02 24A.75.75 0 0010.5 24.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0012.02 24zM15.02 2.03A.75.75 0 0013.5 2.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0015.02 2.03zM15.02 21.97A.75.75 0 0013.5 22.75v.75a.75.75 0 001.5 0v-.75a.75.75 0 00-.52-.727zM18.02 5.03A.75.75 0 0016.5 5.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0018.02 5.03zM18.02 18.97A.75.75 0 0016.5 19.75v.75a.75.75 0 001.5 0v-.75a.75.75 0 00-.52-.727zM21.02 8.223A.75.75 0 0019.5 9v.75a.75.75 0 001.5 0v-.75a.75.75 0 00-.52-.727zM21.02 15.75A.75.75 0 0019.5 16.5v.75a.75.75 0 001.5 0v-.75a.75.75 0 00-.52-.727z" />
                </svg>
              </button>
            </div>
          </div>
          
        </div>
        <div>
          <button type="submit" class="btn-primary">
            Daftar
          </button>
        </div>
      </form>

      <p class="auth-footer">
        Sudah punya akun? <a href="login.php" class="form-link" style="font-weight: 500;">Login di sini</a>
      </p>

    </div>
  </main>

  <script src="../assets/js/auth.js"></script>

</body>
</html>