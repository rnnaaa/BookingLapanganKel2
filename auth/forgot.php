<?php
session_start(); // Diperlukan untuk menampilkan pesan error/sukses
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Lupa Password — BookingLapangan</title>
  
  <link rel="stylesheet" href="../assets/css/auth.css">
  
</head>
<body>

  <main>
    <div class="auth-card max-w-md">

      <h1>Lupa Password</h1>
      <p class="subtitle">Masukkan email Anda untuk menerima link reset password.</p>

      <?php if(!empty($_SESSION['error'])){ echo "<div class='alert alert-error'>".htmlspecialchars($_SESSION['error'])."</div>"; unset($_SESSION['error']); } ?>
      <?php if(!empty($_SESSION['success'])){ echo "<div class='alert alert-success'>".htmlspecialchars($_SESSION['success'])."</div>"; unset($_SESSION['success']); } ?>

      <form method="POST" action="php/send_reset_link.php">
        <div class="form-group">
          <label for="email">Email Terdaftar</label>
          <input id="email" name="email" type="email" class="form-control" required>
        </div>

        <div>
          <button type="submit" class="btn-primary">
            Kirim Link Reset
          </button>
        </div>
      </form>

      <p class="auth-footer">
        Ingat passwordnya? <a href="login.php" class="form-link" style="font-weight: 500;">Login di sini</a>
      </p>

    </div>
  </main>

  </body>
</html>