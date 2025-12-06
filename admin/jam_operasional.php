<?php
//jam_operasional.php
require_once 'auth_check.php';
ob_start();
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/sidebar.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ($_POST['jam_buka'] as $hari => $jam_buka) {
    $jam_tutup = $_POST['jam_tutup'][$hari];
    $stmt = $conn->prepare("UPDATE jam_operasional SET jam_buka=?, jam_tutup=? WHERE hari=?");
    $stmt->bind_param("sss", $jam_buka, $jam_tutup, $hari);
    $stmt->execute();
    $stmt->close();
  }

  // Jalankan sinkronisasi otomatis setelah ubah jam operasional
  include 'jadwal_sinkronisasi.php';

  $_SESSION['toast_success'] = '✅ Jam operasional berhasil diperbarui dan sinkronisasi berjalan!';
  header('Location: jam_operasional.php');
  exit;
}
?>

<style>
/* Professional Operating Hours Styling */
:root {
    --primary-gradient: linear-gradient(135deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
    --success-gradient: linear-gradient(135deg, #28a745 0%, #218838 100%);
    --card-shadow: 0 4px 20px rgba(14, 92, 145, 0.15);
    --card-hover-shadow: 0 8px 30px rgba(14, 92, 145, 0.25);
}

.animate-fade-in {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Content Header Enhancement */
.content-header {
    margin-bottom: 2rem;
}

.content-header h1 {
    font-size: 1.875rem;
    font-weight: 700;
    color: #2c3e50;
    letter-spacing: -0.5px;
    margin: 0;
}

.content-header h1 i {
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Main Card Enhancement */
.hours-card {
    border-radius: 20px;
    border: none;
    box-shadow: var(--card-shadow);
    overflow: hidden;
    transition: all 0.3s ease;
}

.hours-card:hover {
    box-shadow: var(--card-hover-shadow);
}

.hours-card .card-header {
    background: var(--primary-gradient);
    padding: 1.75rem;
    border: none;
}

.hours-card .card-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    letter-spacing: 0.3px;
}

.hours-card .card-body {
    padding: 2rem;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fc 100%);
}

/* Enhanced Table Styling */
.table-hours {
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
}

.table-hours thead {
    background: linear-gradient(135deg, #f8f9fc 0%, #f1f3f9 100%);
}

.table-hours thead th {
    font-size: 0.875rem;
    font-weight: 700;
    color: #5a5c69;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1rem;
    border-bottom: 2px solid #e3e6f0;
    vertical-align: middle;
}

.table-hours tbody tr {
    transition: all 0.2s ease;
}

.table-hours tbody tr:hover {
    background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
}

.table-hours tbody td {
    padding: 1rem;
    vertical-align: middle;
    font-size: 0.938rem;
    border-bottom: 1px solid #f1f3f9;
}

.table-hours tbody td strong {
    color: #2c3e50;
    font-weight: 600;
    font-size: 1rem;
}

/* Time Input Enhancement */
.table-hours input[type="time"] {
    border: 2px solid #e3e6f0;
    border-radius: 10px;
    padding: 0.75rem 1rem;
    font-size: 0.938rem;
    transition: all 0.3s ease;
    background: #ffffff;
    font-weight: 600;
    color: #2c3e50;
    width: 100%;
    max-width: 200px;
}

.table-hours input[type="time"]:focus {
    border-color: #2196f3;
    box-shadow: 0 0 0 4px rgba(33, 150, 243, 0.1);
    background: #ffffff;
}

/* Day Icons */
.day-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--primary-gradient);
    color: white;
    font-weight: 700;
    margin-right: 0.75rem;
    font-size: 0.875rem;
}

/* Button Enhancement */
.btn-save {
    background: var(--success-gradient);
    border: none;
    color: #fff;
    font-weight: 600;
    border-radius: 10px;
    padding: 0.75rem 2rem;
    font-size: 0.938rem;
    letter-spacing: 0.3px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    color: #fff;
}

.btn-save i {
    margin-right: 0.5rem;
}

/* Alert Info Box */
.info-box {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-left: 4px solid #2196f3;
    border-radius: 12px;
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.5rem;
}

.info-box i {
    color: #2196f3;
    font-size: 1.25rem;
    margin-right: 0.75rem;
}

.info-box p {
    margin: 0;
    color: #1976d2;
    font-size: 0.938rem;
    font-weight: 500;
}

/* Toast Notification */
.toast {
    border-radius: 12px;
    box-shadow: 0 6px 30px rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(10px);
    border: none;
}

.toast-body {
    padding: 1rem 1.25rem;
    font-size: 0.938rem;
    font-weight: 600;
}

.toast.text-bg-success {
    background: var(--success-gradient) !important;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .content-header h1 {
        font-size: 1.5rem;
    }
    
    .hours-card .card-body {
        padding: 1.5rem;
    }
    
    .table-hours thead th,
    .table-hours tbody td {
        font-size: 0.813rem;
        padding: 0.75rem;
    }
    
    .table-hours input[type="time"] {
        max-width: 100%;
    }
    
    .day-icon {
        width: 35px;
        height: 35px;
        font-size: 0.813rem;
        margin-right: 0.5rem;
    }
}
</style>

<div class="content-wrapper animate-fade-in">
  <!-- Header -->
  <section class="content-header">
    <div class="container-fluid">
      <h1><i class="fas fa-clock me-2"></i> Jam Operasional</h1>
      <p class="text-muted mb-0 mt-2">Atur jam buka dan tutup lapangan untuk setiap hari</p>
    </div>
  </section>

  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card hours-card">
        <div class="card-header text-white">
          <h3 class="card-title mb-0">
            <i class="fas fa-business-time me-2"></i> Atur Jam Buka & Tutup
          </h3>
        </div>
        <div class="card-body">
          
          <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <p>
              <strong>Penting:</strong> Setelah mengubah jam operasional, sistem akan otomatis melakukan sinkronisasi jadwal untuk memperbarui slot waktu.
            </p>
          </div>

          <form method="POST">
            <div class="table-responsive">
              <table class="table table-hours">
                <thead class="text-center">
                  <tr>
                    <th style="width: 30%">Hari</th>
                    <th style="width: 35%">Jam Buka</th>
                    <th style="width: 35%">Jam Tutup</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $result = mysqli_query($conn, "SELECT * FROM jam_operasional ORDER BY FIELD(hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu')");
                  $days_icon = [
                    'Senin' => 'Sen',
                    'Selasa' => 'Sel',
                    'Rabu' => 'Rab',
                    'Kamis' => 'Kam',
                    'Jumat' => 'Jum',
                    'Sabtu' => 'Sab',
                    'Minggu' => 'Min'
                  ];
                  
                  while ($row = mysqli_fetch_assoc($result)):
                  ?>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center">
                          <span class="day-icon"><?= $days_icon[$row['hari']] ?></span>
                          <strong><?= $row['hari'] ?></strong>
                        </div>
                      </td>
                      <td class="text-center">
                        <input type="time" 
                               name="jam_buka[<?= $row['hari'] ?>]" 
                               value="<?= $row['jam_buka'] ?>" 
                               class="form-control"
                               required>
                      </td>
                      <td class="text-center">
                        <input type="time" 
                               name="jam_tutup[<?= $row['hari'] ?>]" 
                               value="<?= $row['jam_tutup'] ?>" 
                               class="form-control"
                               required>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
            
            <div class="text-end mt-4">
              <button type="submit" class="btn btn-save">
                <i class="fas fa-save"></i> Simpan Perubahan
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- Bootstrap Toast Notification -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1080">
  <div id="toastNotif" class="toast align-items-center text-white border-0" 
       role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">
    <div class="d-flex">
      <div class="toast-body" id="toastMessage"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<?php include('../includes/footer.php'); ?>

<script>
$(function() {
  // Toast Notification
  <?php if (!empty($_SESSION['toast_success'])): ?>
    showToast("<?= addslashes($_SESSION['toast_success']) ?>", "success");
    <?php unset($_SESSION['toast_success']); ?>
  <?php endif; ?>

  <?php if (!empty($_SESSION['toast_error'])): ?>
    showToast("<?= addslashes($_SESSION['toast_error']) ?>", "danger");
    <?php unset($_SESSION['toast_error']); ?>
  <?php endif; ?>

  function showToast(message, type) {
    const toastEl = document.getElementById('toastNotif');
    const toastBody = document.getElementById('toastMessage');
    toastBody.textContent = message;

    toastEl.classList.remove('text-bg-success', 'text-bg-danger');
    toastEl.classList.add(type === 'success' ? 'text-bg-success' : 'text-bg-danger');

    const toast = new bootstrap.Toast(toastEl);
    toast.show();
  }
});
</script>