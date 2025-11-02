<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              primary: "#0b63d6",
              primaryDark: "#094ea8",
              softGray: "#f6f8fb",
              accent: "#ffb500",
            },
            boxShadow: {
              lift: "0 18px 40px rgba(11,26,54,0.10)",
              soft: "0 8px 24px rgba(11,26,54,0.06)",
            },
            borderRadius: {
              xlcard: "14px",
            },
          },
        },
      };
    </script>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet" />
    <style>
      body {
        font-family: "Inter", sans-serif;
        background: linear-gradient(135deg, #f6f8fb 0%, #e3edff 100%);
        min-height: 100vh;
      }
      .font-poppins {
        font-family: "Poppins", sans-serif;
      }
      .btn-primary {
        background: #0b63d6;
        color: white;
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
      }
      .btn-primary:hover {
        background: #094ea8;
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(11, 99, 214, 0.3);
      }
      .btn-outline {
        border: 1px solid #0b63d6;
        color: #0b63d6;
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
      }
      .btn-outline:hover {
        background: #0b63d6;
        color: white;
        transform: translateY(-2px);
      }
      .modal-backdrop {
        background: rgba(2, 6, 23, 0.45);
      }
      .modal-panel {
        background: white;
        border-radius: 14px;
        box-shadow: 0 18px 40px rgba(11, 26, 54, 0.1);
      }
      /* Custom alert style */
      .custom-alert {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
        max-width: 400px;
      }
    </style>
  </head>
  <body class="flex items-center justify-center min-h-screen p-4">
    <!-- Custom Alert -->
    <div id="customAlert" class="custom-alert hidden">
      <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-xl shadow-lg">
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
              <p class="font-semibold" id="alertTitle">Error</p>
              <p class="text-sm mt-1" id="alertMessage">Pesan error</p>
            </div>
          </div>
          <button onclick="hideAlert()" class="text-red-500 hover:text-red-700 ml-4">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="mt-3 flex gap-2">
          <button onclick="hideAlert()" class="flex-1 btn-outline py-2 text-sm">Tutup</button>
          <button onclick="redirectToRegister()" class="flex-1 btn-primary py-2 text-sm">Daftar Sekarang</button>
        </div>
      </div>
    </div>

    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <a href="index.php" class="inline-flex items-center gap-3 mb-6">
          <div class="w-14 h-14 rounded-xl bg-white flex items-center justify-center text-white shadow-lg">
              <img src="assets/images/LogoRush.png" alt="SportField Logo" class="w-14 h-14 object-contain rounded-xl shadow-md">
          </div>
        <div>
          <div class="font-poppins font-semibold text-lg leading-tight">Rush Badminton Academy</div>
          <div class="text-xs text-slate-500 -mt-0.5">Booking Lapangan Online</div>
        </div>
        </a>
      </div>

      <!-- Login Card -->
      <div class="bg-white rounded-2xl shadow-lift p-8">
        <div class="text-center mb-8">
          <h1 class="font-poppins font-bold text-2xl text-gray-900 mb-2">Masuk ke Rush Badminton Academy </h1>
          <p class="text-gray-600">Selamat datang kembali! Silakan masuk ke akun Anda.</p>
        </div>

        <form id="loginForm">
          <div class="space-y-4">
            <div>
              <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username atau Nama</label>
              <input
                type="text"
                id="username"
                name="username"
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200"
                placeholder="Masukkan username atau nama Anda"
                required
              />
            </div>
            <div>
              <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
              <input
                type="password"
                id="password"
                name="password"
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200"
                placeholder="Masukkan password Anda"
                required
              />
              <div class="text-right mt-2">
                <a href="#" class="text-sm text-primary hover:text-primaryDark transition-colors" id="forgotPassword">Lupa password?</a>
              </div>
            </div>
          </div>

          <button type="submit" class="w-full btn-primary mt-6">Masuk</button>
        </form>

        <div class="text-center mt-6 pt-6 border-t border-gray-200">
          <p class="text-gray-600">
            Belum punya akun?
            <a href="register.php" class="text-primary font-semibold hover:text-primaryDark transition-colors">Daftar di sini</a>
          </p>
        </div>
      </div>
    </div>

    <!-- Modal Lupa Password -->
    <div id="forgotPasswordModal" class="modal-backdrop hidden fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="modal-panel w-full max-w-md">
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-poppins font-bold text-xl text-gray-900">Lupa Password</h3>
            <button class="modal-close text-gray-400 hover:text-gray-600 transition-colors" data-modal-close="forgotPasswordModal">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <p class="text-gray-600 mb-4">Masukkan username atau email Anda untuk menerima kode verifikasi</p>

          <form id="forgotPasswordForm">
            <div class="space-y-4">
              <div>
                <label for="resetUsername" class="block text-sm font-medium text-gray-700 mb-2">Username atau Email</label>
                <input
                  type="text"
                  id="resetUsername"
                  name="resetUsername"
                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200"
                  placeholder="Masukkan username atau email Anda"
                  required
                />
              </div>
            </div>

            <div class="flex gap-3 mt-6">
              <button type="button" class="flex-1 btn-outline" data-modal-close="forgotPasswordModal">Batal</button>
              <button type="submit" class="flex-1 btn-primary">Kirim Kode</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script>
      // Database simulasi (dalam implementasi nyata, ini dari backend)
      const registeredUsers = [
        { username: "admin", password: "admin123", role: "admin" },
        { username: "budi", password: "budi123", role: "user" },
        { username: "siti", password: "siti123", role: "user" },
        { username: "andi", password: "andi123", role: "user" },
        { username: "rudi", password: "rudi123", role: "user" },
      ];

      // Fungsi untuk menampilkan alert custom
      function showAlert(title, message) {
        const alert = document.getElementById("customAlert");
        const alertTitle = document.getElementById("alertTitle");
        const alertMessage = document.getElementById("alertMessage");

        alertTitle.textContent = title;
        alertMessage.textContent = message;
        alert.classList.remove("hidden");
      }

      function hideAlert() {
        const alert = document.getElementById("customAlert");
        alert.classList.add("hidden");
      }

      function redirectToRegister() {
        hideAlert();
        window.location.href = "register.php";
      }

      // Modal functionality
      function toggleModal(modalId, show) {
        const modal = document.getElementById(modalId);
        if (modal) {
          modal.classList.toggle("hidden", !show);
        }
      }

      // Event listeners
      document.addEventListener("DOMContentLoaded", function () {
        const forgotPasswordLink = document.getElementById("forgotPassword");
        const forgotPasswordModal = document.getElementById("forgotPasswordModal");
        const closeButtons = document.querySelectorAll("[data-modal-close]");
        const loginForm = document.getElementById("loginForm");
        const forgotPasswordForm = document.getElementById("forgotPasswordForm");

        // Forgot password modal
        if (forgotPasswordLink) {
          forgotPasswordLink.addEventListener("click", function (e) {
            e.preventDefault();
            toggleModal("forgotPasswordModal", true);
          });
        }

        // Close modal buttons
        closeButtons.forEach((button) => {
          button.addEventListener("click", function () {
            const modalId = this.getAttribute("data-modal-close");
            toggleModal(modalId, false);
          });
        });

        // Close modal on backdrop click
        if (forgotPasswordModal) {
          forgotPasswordModal.addEventListener("click", function (e) {
            if (e.target === this) {
              toggleModal("forgotPasswordModal", false);
            }
          });
        }

        // Login form submission
        if (loginForm) {
          loginForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const username = document.getElementById("username").value;
            const password = document.getElementById("password").value;

            // Validasi input
            if (!username || !password) {
              showAlert("Error", "Harap isi username dan password!");
              return;
            }

            // Cek apakah user ada di database simulasi
            const user = registeredUsers.find((u) => u.username === username && u.password === password);

            if (user) {
              // Login berhasil
              if (user.role === "admin") {
                alert("Login berhasil! Redirect ke dashboard Admin...");
                window.location.href = "admin-dashboard.html";
              } else {
                alert("Login berhasil! Redirect ke dashboard User...");
                window.location.href = "user-dashboard.html";
              }
            } else {
              // Cek apakah username ada tapi password salah
              const usernameExists = registeredUsers.find((u) => u.username === username);

              if (usernameExists) {
                showAlert("Password Salah", "Password yang Anda masukkan salah. Silakan coba lagi.");
              } else {
                // Username tidak terdaftar
                showAlert("Akun Tidak Ditemukan", `Username "${username}" tidak terdaftar. Apakah Anda ingin membuat akun baru?`);
              }
            }
          });
        }

        // Forgot password form
        if (forgotPasswordForm) {
          forgotPasswordForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const resetUsername = document.getElementById("resetUsername").value;

            if (resetUsername) {
              // Cek apakah username/email terdaftar
              const userExists = registeredUsers.find((u) => u.username === resetUsername || resetUsername.includes("@"));

              if (userExists) {
                alert("Kode verifikasi telah dikirim ke email/telepon yang terdaftar!");
              } else {
                alert("Username/email tidak terdaftar. Silakan daftar terlebih dahulu.");
              }
              toggleModal("forgotPasswordModal", false);
            }
          });
        }
      });
    </script>
  </body>
</html>
