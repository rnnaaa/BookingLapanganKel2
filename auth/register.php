<?php
session_start();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Daftar — BookingLapangan</title>
  
  <link rel="stylesheet" href="../assets/css/auth.css">
  <style>
    .error-message {
        color: #dc2626;
        font-size: 0.875rem;
        margin-top: 0.25rem;
        display: none;
    }
    .input-error {
        border-color: #dc2626 !important;
    }
    button:disabled {
        background-color: #9ca3af;
        cursor: not-allowed;
    }
  </style>
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
            <small id="usernameError" class="error-message">Username sudah digunakan.</small>
          </div>
          <div>
            <label for="phone">No. HP (WhatsApp)</label>
            <input id="phone" name="phone" class="form-control" required placeholder="08...">
          </div>
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" class="form-control" required placeholder="contoh@gmail.com">
          <small id="emailError" class="error-message">Email tidak valid.</small>
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
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A.75.75 0 003 9v.75a.75.75 0 001.5 0v-.75A.75.75 0 003.98 8.223zM3.98 15.75A.75.75 0 003 16.5v.75a.75.75 0 001.5 0v-.75a.75.75 0 00-.52-.727zM6.02 5.03A.75.75 0 004.5 5.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 006.02 5.03zM6.02 18.97A.75.75 0 004.5 19.75v.75a.75.75 0 001.5 0v-.75a.75.75 0 00-.52-.727zM9.02 2.03A.75.75 0 007.5 2.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 009.02 2.03zM9.02 21.97A.75.75 0 007.5 22.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727zM12.02 0A.75.75 0 0010.5.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0012.02 0zM12.02 24A.75.75 0 0010.5 24.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0012.02 24zM15.02 2.03A.75.75 0 0013.5 2.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0015.02 2.03zM15.02 21.97A.75.75 0 0013.5 22.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727zM18.02 5.03A.75.75 0 0016.5 5.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0018.02 5.03zM18.02 18.97A.75.75 0 0016.5 19.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727zM21.02 8.223A.75.75 0 0019.5 9v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727zM21.02 15.75A.75.75 0 0019.5 16.5v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727z" />
                </svg>
              </button>
            </div>
            <small id="passwordError" class="error-message" style="line-height: 1.4;">
                Password harus mengandung huruf Besar, huruf kecil, dan angka.
            </small>
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
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A.75.75 0 003 9v.75a.75.75 0 001.5 0v-.75A.75.75 0 003.98 8.223zM3.98 15.75A.75.75 0 003 16.5v.75a.75.75 0 001.5 0v-.75a.75.75 0 00-.52-.727zM6.02 5.03A.75.75 0 004.5 5.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 006.02 5.03zM6.02 18.97A.75.75 0 004.5 19.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727zM9.02 2.03A.75.75 0 007.5 2.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 009.02 2.03zM9.02 21.97A.75.75 0 007.5 22.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727zM12.02 0A.75.75 0 0010.5.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0012.02 0zM12.02 24A.75.75 0 0010.5 24.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0012.02 24zM15.02 2.03A.75.75 0 0013.5 2.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0015.02 2.03zM15.02 21.97A.75.75 0 0013.5 22.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727zM18.02 5.03A.75.75 0 0016.5 5.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0018.02 5.03zM18.02 18.97A.75.75 0 0016.5 19.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727zM21.02 8.223A.75.75 0 0019.5 9v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727zM21.02 15.75A.75.75 0 0019.5 16.5v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727z" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <div style="margin-top: 1.5rem;">
          <button type="submit" id="submitBtn" class="btn-primary">
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
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Elements
        const emailInput = document.getElementById('email');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const submitBtn = document.getElementById('submitBtn');
        
        const emailError = document.getElementById('emailError');
        const usernameError = document.getElementById('usernameError');
        const passwordError = document.getElementById('passwordError');
        
        // Validation Flags (Semua harus true agar tombol nyala)
        let state = {
            isDomainValid: false,
            isEmailAvail: false,
            isUsernameAvail: false,
            isPasswordComplex: false
        };

        const allowedDomains = ['gmail.com', 'student.polije.ac.id'];

        // === MASTER CHECKER ===
        function updateSubmitButton() {
            // Cek apakah semua kondisi terpenuhi
            if (state.isDomainValid && state.isEmailAvail && state.isUsernameAvail && state.isPasswordComplex) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }

        // === 1. VALIDASI EMAIL ===
        function validateEmail() {
            const email = emailInput.value.trim().toLowerCase();
            if(email === '') { hideError(emailInput, emailError); return; }

            const parts = email.split('@');
            if (parts.length === 2 && allowedDomains.includes(parts[1])) {
                state.isDomainValid = true;
                checkEmailAvailability(email); // AJAX Check
            } else {
                showError(emailInput, emailError, "Gunakan @gmail.com atau @student.polije.ac.id");
                state.isDomainValid = false;
                updateSubmitButton();
            }
        }

        function checkEmailAvailability(email) {
            const formData = new FormData();
            formData.append('email', email);
            fetch('php/check_email.php', { method: 'POST', body: formData })
            .then(r => r.text())
            .then(status => {
                if (status === 'taken') {
                    showError(emailInput, emailError, "Email sudah terdaftar.");
                    state.isEmailAvail = false;
                } else {
                    hideError(emailInput, emailError);
                    state.isEmailAvail = true;
                }
                updateSubmitButton();
            });
        }

        // === 2. VALIDASI USERNAME ===
        function validateUsername() {
            const username = usernameInput.value.trim();
            if(username === '') { hideError(usernameInput, usernameError); return; }
            
            // Cek panjang minimal (opsional)
            if(username.length < 4) {
                showError(usernameInput, usernameError, "Username minimal 4 karakter.");
                state.isUsernameAvail = false;
                updateSubmitButton();
                return;
            }

            const formData = new FormData();
            formData.append('username', username);
            fetch('php/check_username.php', { method: 'POST', body: formData })
            .then(r => r.text())
            .then(status => {
                if (status === 'taken') {
                    showError(usernameInput, usernameError, "Username sudah terpakai.");
                    state.isUsernameAvail = false;
                } else {
                    hideError(usernameInput, usernameError);
                    state.isUsernameAvail = true;
                }
                updateSubmitButton();
            });
        }

        // === 3. VALIDASI PASSWORD (KOMPLEKSITAS) ===
        function validatePassword() {
            const pass = passwordInput.value;
            
            // Regex: Minimal 1 Huruf Kecil, 1 Huruf Besar, 1 Angka
            const hasLower = /[a-z]/.test(pass);
            const hasUpper = /[A-Z]/.test(pass);
            const hasNumber = /\d/.test(pass);
            const minLength = pass.length >= 6;

            if (hasLower && hasUpper && hasNumber && minLength) {
                hideError(passwordInput, passwordError);
                state.isPasswordComplex = true;
            } else {
                let msg = "Kombinasi harus: ";
                if(!minLength) msg += "Min 6 char. ";
                if(!hasLower) msg += "Huruf kecil. ";
                if(!hasUpper) msg += "Huruf besar. ";
                if(!hasNumber) msg += "Angka.";
                
                showError(passwordInput, passwordError, msg);
                state.isPasswordComplex = false;
            }
            updateSubmitButton();
        }

        // === HELPER UI ===
        function showError(input, element, msg) {
            element.textContent = msg;
            element.style.display = 'block';
            input.classList.add('input-error');
        }
        function hideError(input, element) {
            element.style.display = 'none';
            input.classList.remove('input-error');
        }

        // === EVENT LISTENERS ===
        emailInput.addEventListener('blur', validateEmail);
        emailInput.addEventListener('input', () => { hideError(emailInput, emailError); submitBtn.disabled = true; });

        usernameInput.addEventListener('blur', validateUsername);
        usernameInput.addEventListener('input', () => { hideError(usernameInput, usernameError); submitBtn.disabled = true; });

        passwordInput.addEventListener('input', validatePassword);
        passwordInput.addEventListener('blur', validatePassword);
        
        // Init: Matikan tombol saat load
        submitBtn.disabled = true;
    });
  </script>

</body>
</html>