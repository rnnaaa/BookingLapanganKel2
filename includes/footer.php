<footer class="main-footer text-sm footer-custom" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-top: 3px solid #1874ad; padding: 20px 24px; box-shadow: 0 -4px 20px rgba(0,0,0,0.05);">
  <div class="d-flex justify-content-between align-items-center flex-wrap">
    <div class="footer-left d-flex align-items-center">
      <img src="../uploads/bukti_pembayaran/LogoRush2.png" alt="Logo" class="footer-logo" style="width: 32px; height: 32px; object-fit: cover; border-radius: 50%; margin-right: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); transition: transform 0.5s ease;">
      <div>
        <strong style="color: #1874ad; font-weight: 700; font-size: 15px; letter-spacing: 0.3px;">
          Rush Badminton Academy
        </strong>
        <p class="m-0" style="color: #6c757d; font-size: 12px; margin-top: 2px !important;">
          Professional Booking System &copy; <?= date('Y') ?>
        </p>
      </div>
    </div>
    <div class="footer-right text-right">
    </div>
  </div>
</footer>

<aside class="control-sidebar control-sidebar-dark"></aside>
</div>

<!-- ================= CORE SCRIPTS ================= -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="../public/asseth/tampilan_admin/plugins/jquery-ui/jquery-ui.min.js"></script>
<script>
  $.widget.bridge('uibutton', $.ui.button)
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../public/asseth/tampilan_admin/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<script src="../public/asseth/tampilan_admin/dist/js/adminlte.min.js"></script>

<!-- ================= SELECT2 ================= -->
<link rel="stylesheet" href="../public/asseth/tampilan_admin/plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="../public/asseth/tampilan_admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
<script src="../public/asseth/tampilan_admin/plugins/select2/js/select2.full.min.js"></script>

<!-- ================= DATATABLES ================= -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.colVis.min.js"></script>

<!-- ================= NOTIFICATIONS ================= -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css"/>



<!-- ================= BOOTSTRAP 4/5 COMPATIBILITY ================= -->
<script>
$(document).ready(function() {
    $('[data-bs-toggle]').each(function() {
        $(this).attr('data-toggle', $(this).attr('data-bs-toggle'));
    });
    
    $('[data-bs-target]').each(function() {
        $(this).attr('data-target', $(this).attr('data-bs-target'));
    });
    
    $('[data-bs-dismiss]').each(function() {
        $(this).attr('data-dismiss', $(this).attr('data-bs-dismiss'));
    });
});
</script>

<!-- ================= OPTIMIZED DATATABLE INIT (DESKTOP + MOBILE) ================= -->
<script>
$(function () {
  const $loader = $("#tableLoader");
  const tables = $('table[id^="tbl"], table[id^="example"], table[id^="tabel"], table.dataTable');
  let initialized = 0;

  if (tables.length > 0) {
    $loader.fadeIn(200);
  }

  toastr.options = {
    "closeButton": true,
    "progressBar": true,
    "positionClass": "toast-top-right",
    "timeOut": "3000",
    "showDuration": "300",
    "hideDuration": "300",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut"
  };

  tables.each(function () {
    const $tbl = $(this);
    const id = $tbl.attr('id') || 'dataTable_' + Math.random().toString(36).substr(2, 5);

    if ($.fn.DataTable.isDataTable($tbl)) return;

    // Wrap table in responsive div only for mobile
    if (window.innerWidth <= 768 && !$tbl.parent().hasClass('table-responsive')) {
      $tbl.wrap('<div class="table-responsive"></div>');
    }

    const isMobile = window.innerWidth <= 768;

    const dt = $tbl.DataTable({
      responsive: false,
      autoWidth: false,
      pageLength: 10,
      lengthChange: true,
      scrollX: isMobile,
      scrollCollapse: isMobile,
      dom: '<"row mb-3"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>rtip',
      buttons: [
        { 
          extend: 'copy', 
          text: '<i class="fas fa-copy mr-1"></i> Salin', 
          className: 'btn btn-sm btn-outline-dark rounded-pill shadow-sm mr-1 mb-1 btn-copy-custom',
          exportOptions: { columns: ':visible' }
        },
        { 
          extend: 'excel', 
          text: '<i class="fas fa-file-excel mr-1"></i> Excel', 
          className: 'btn btn-sm btn-outline-success rounded-pill shadow-sm mr-1 mb-1',
          exportOptions: { columns: ':visible' }
        },
        { 
          extend: 'pdf', 
          text: '<i class="fas fa-file-pdf mr-1"></i> PDF', 
          className: 'btn btn-sm btn-outline-danger rounded-pill shadow-sm mr-1 mb-1',
          exportOptions: { columns: ':visible' },
          customize: function(doc) {
            doc.styles.tableHeader = {
              bold: true,
              fontSize: 11,
              color: 'white',
              fillColor: '#1874ad'
            };
          }
        },
        { 
          extend: 'print', 
          text: '<i class="fas fa-print mr-1"></i> Cetak', 
          className: 'btn btn-sm btn-outline-primary rounded-pill shadow-sm mr-1 mb-1',
          exportOptions: { columns: ':visible' }
        },
        { 
          extend: 'colvis', 
          text: '<i class="fas fa-columns mr-1"></i> Kolom', 
          className: 'btn btn-sm btn-outline-info rounded-pill shadow-sm mb-1' 
        }
      ],
      language: { 
        url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json",
        paginate: {
          previous: '<i class="fas fa-chevron-left"></i>',
          next: '<i class="fas fa-chevron-right"></i>'
        }
      },
      initComplete: function () {
        initialized++;
        animateRows($tbl);
        if (initialized === tables.length) {
          $loader.fadeOut(300);
        }
      },
      drawCallback: function() {
        $('.dataTables_paginate .pagination').addClass('pagination-rounded');
      }
    });

    dt.on('draw.dt', function () { animateRows($tbl); });
    dt.buttons().container().appendTo('#' + id + '_wrapper .col-md-6:eq(0)');
  });

  function animateRows($tbl) {
    $tbl.find('tbody tr').each((i, el) => {
      $(el).removeClass("appear");
      setTimeout(() => $(el).addClass("appear"), 25 * i);
    });
  }

  // Handle window resize
  $(window).on('resize', function() {
    if (window.innerWidth <= 768) {
      tables.each(function() {
        const $tbl = $(this);
        if (!$tbl.parent().hasClass('table-responsive')) {
          $tbl.wrap('<div class="table-responsive"></div>');
        }
      });
    }
  });
});

// ================= TOASTR NOTIFICATIONS =================
<?php if (!empty($_SESSION['toast_success'])): ?>
  toastr.success("<?= $_SESSION['toast_success'] ?>", "Berhasil!");
  <?php unset($_SESSION['toast_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['toast_error'])): ?>
  toastr.error("<?= $_SESSION['toast_error'] ?>", "Gagal!");
  <?php unset($_SESSION['toast_error']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['toast_warning'])): ?>
  toastr.warning("<?= $_SESSION['toast_warning'] ?>", "Peringatan!");
  <?php unset($_SESSION['toast_warning']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['toast_info'])): ?>
  toastr.info("<?= $_SESSION['toast_info'] ?>", "Informasi!");
  <?php unset($_SESSION['toast_info']); ?>
<?php endif; ?>

// ================= DARK MODE CONTROL =================
$(function () {
  const $sidebar = $(".control-sidebar");
  $sidebar.empty();
  const $container = $("<div />", { class: "p-4 control-sidebar-content" });
  $sidebar.append($container);
  
  $container.append(`
    <div class="mb-4">
      <h5 class="mb-3" style="color: #1874ad; font-weight: 700;">
        <i class="fas fa-palette mr-2"></i>Pengaturan Tampilan
      </h5>
      <hr class="mb-3" style="border-color: #1874ad; opacity: 0.3;"/>
    </div>
  `);

  const darkModeHtml = `
    <div class="form-group mb-4">
      <div class="custom-control custom-switch custom-switch-off-light custom-switch-on-dark">
        <input type="checkbox" class="custom-control-input" id="dark-mode-switch">
        <label class="custom-control-label" for="dark-mode-switch" style="font-weight: 600; color: #2c3e50;">
          <i class="fas fa-moon mr-2" style="color: #1874ad;"></i>Mode Gelap
        </label>
      </div>
      <small class="form-text text-muted">
        <i class="fas fa-info-circle mr-1"></i>Aktifkan untuk mengurangi kelelahan mata
      </small>
    </div>`;
    
  $container.append(darkModeHtml);
  
  $container.append(`
    <hr class="my-3" style="border-color: #dee2e6;"/>
    <div class="text-center">
      <small class="text-muted d-block mb-2">
        <i class="fas fa-save mr-1"></i>Pengaturan tersimpan otomatis
      </small>
      <span class="badge badge-primary px-3 py-2" style="background: linear-gradient(135deg, #1874ad 0%, #2196f3 100%); font-weight: 600;">
        <i class="fas fa-check-circle mr-1"></i>Rush Badminton v2.0
      </span>
    </div>
  `);

  if (localStorage.getItem('darkMode') === 'enabled') {
    $('body').addClass('dark-mode');
    $('#dark-mode-switch').prop('checked', true);
  }
  
  $('#dark-mode-switch').on('change', function () {
    if ($(this).is(':checked')) {
      $('body').addClass('dark-mode');
      localStorage.setItem('darkMode', 'enabled');
      toastr.info('Mode gelap diaktifkan', 'Pengaturan Disimpan');
    } else {
      $('body').removeClass('dark-mode');
      localStorage.setItem('darkMode', 'disabled');
      toastr.info('Mode terang diaktifkan', 'Pengaturan Disimpan');
    }
  });
});
</script>

<!-- ================= COMPLETE RESPONSIVE STYLES (DESKTOP + MOBILE) ================= -->
<style>
  /* ===== DATATABLE WRAPPER - DESKTOP ===== */
  .dataTables_wrapper {
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  }
  
  .dataTables_filter input {
    border: 2px solid #e9ecef;
    border-radius: 20px;
    padding: 8px 16px;
    transition: all 0.3s ease;
    margin-left: 8px;
  }
  
  .dataTables_filter input:focus {
    border-color: #1874ad;
    box-shadow: 0 0 0 0.2rem rgba(24, 116, 173, 0.15);
    outline: none;
  }
  
  .dataTables_length select {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 6px 12px;
    transition: all 0.3s ease;
    margin: 0 8px;
  }
  
  .dataTables_length select:focus {
    border-color: #1874ad;
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(24, 116, 173, 0.15);
  }

  /* ===== TABLE HEADER ===== */
  table.dataTable thead th {
    background: linear-gradient(135deg, #1874ad 0%, #2196f3 100%) !important;
    color: white !important;
    border: none !important;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.8px;
    padding: 16px !important;
    position: relative;
    transition: all 0.3s ease;
  }
  
  table.dataTable thead th:hover {
    background: linear-gradient(135deg, #0d5a8f 0%, #1874ad 100%) !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(24, 116, 173, 0.3);
  }

  /* ===== TABLE BODY ===== */
  table.dataTable tbody tr {
    opacity: 0;
    transform: translateY(8px);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    border-bottom: 1px solid #e9ecef;
  }
  
  table.dataTable tbody tr.appear {
    opacity: 1;
    transform: translateY(0);
  }
  
  table.dataTable tbody tr:hover {
    background: linear-gradient(90deg, rgba(24, 116, 173, 0.04) 0%, transparent 100%);
    transform: translateX(4px) scale(1.005);
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  }
  
  table.dataTable tbody td {
    padding: 14px 16px;
    vertical-align: middle;
    transition: all 0.2s ease;
  }

  /* ===== PAGINATION ===== */
  .dataTables_paginate .pagination {
    margin-top: 20px;
    justify-content: center;
  }
  
  .pagination-rounded .page-link {
    border-radius: 50% !important;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 4px;
    border: 2px solid #e9ecef;
    color: #1874ad;
    font-weight: 600;
    transition: all 0.3s ease;
  }
  
  .pagination-rounded .page-link:hover {
    background: linear-gradient(135deg, #1874ad 0%, #2196f3 100%);
    color: white;
    border-color: #1874ad;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(24, 116, 173, 0.3);
  }
  
  .pagination-rounded .page-item.active .page-link {
    background: linear-gradient(135deg, #1874ad 0%, #2196f3 100%);
    border-color: #1874ad;
    box-shadow: 0 4px 12px rgba(24, 116, 173, 0.3);
  }
  
  .pagination-rounded .page-item.disabled .page-link {
    opacity: 0.5;
    cursor: not-allowed;
  }

  /* ===== DATATABLE INFO ===== */
  .dataTables_info {
    padding: 12px 0;
    color: #6c757d;
    font-weight: 600;
    font-size: 13px;
  }

  /* ===== EXPORT BUTTONS ===== */
  .dt-buttons {
    margin-bottom: 16px;
  }
  
  .dt-buttons .btn {
    font-weight: 600;
    font-size: 13px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-width: 2px;
  }
  
  .dt-buttons .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
  }
  
  .dt-buttons .btn:active {
    transform: translateY(0);
  }

  /* Custom style for Copy button */
  .btn-copy-custom {
    background-color: #6c757d !important;
    color: white !important;
    border-color: #6c757d !important;
  }

  .btn-copy-custom:hover {
    background-color: #5a6268 !important;
    color: white !important;
    border-color: #545b62 !important;
  }

  /* Make all export buttons visible and styled */
  .dt-buttons .btn-outline-dark {
    color: #343a40;
    border-color: #6c757d;
  }

  .dt-buttons .btn-outline-dark:hover {
    background-color: #6c757d;
    color: white;
  }

  /* ===== TOASTR CUSTOM ===== */
  .toast-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
  }
  
  .toast-error {
    background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%) !important;
  }
  
  .toast-warning {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%) !important;
  }
  
  .toast-info {
    background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%) !important;
  }
  
  #toast-container > div {
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
  }
  
  #toast-container .toast-progress {
    opacity: 0.6;
    height: 4px;
  }

  /* ===== FOOTER STYLING ===== */
  .footer-custom:hover {
    box-shadow: 0 -6px 30px rgba(0,0,0,0.08);
    transition: box-shadow 0.3s ease;
  }
  
  .footer-logo:hover {
    transform: rotate(360deg) scale(1.08);
  }

  /* ===== MOBILE RESPONSIVE FIX ===== */
  @media (max-width: 768px) {
    /* Table responsive wrapper */
    .table-responsive {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      margin-bottom: 1rem;
      display: block;
      width: 100%;
    }

    .dataTables_wrapper {
      padding: 12px;
    }
    
    /* Buttons */
    .dt-buttons {
      text-align: center;
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 4px;
    }
    
    .dt-buttons .btn {
      margin-bottom: 6px;
      font-size: 10px;
      padding: 5px 8px;
      margin-right: 0;
    }
    
    /* Table */
    table.dataTable {
      font-size: 11px;
      width: 100% !important;
    }
    
    table.dataTable thead th {
      font-size: 10px;
      padding: 10px 8px !important;
      white-space: nowrap;
    }
    
    table.dataTable tbody td {
      padding: 8px 8px;
      font-size: 11px;
    }
    
    /* Pagination */
    .pagination-rounded .page-link {
      width: 32px;
      height: 32px;
      font-size: 11px;
      margin: 0 2px;
    }
    
    /* Search and Length */
    .dataTables_filter {
      text-align: center !important;
      margin-bottom: 10px;
    }
    
    .dataTables_filter input {
      width: 100%;
      max-width: 180px;
      margin: 8px auto 0;
      font-size: 12px;
      padding: 6px 12px;
    }
    
    .dataTables_length {
      text-align: center !important;
      margin-bottom: 10px;
    }
    
    .dataTables_length select {
      font-size: 12px;
      padding: 4px 8px;
    }
    
    /* Info */
    .dataTables_info {
      font-size: 10px;
      text-align: center;
      padding: 8px 0;
    }
    
    /* Scroll indicator */
    .table-responsive::after {
      content: '← Geser untuk melihat lebih banyak →';
      display: block;
      text-align: center;
      color: #6c757d;
      font-size: 10px;
      padding: 6px;
      background: linear-gradient(90deg, transparent, rgba(24, 116, 173, 0.05), transparent);
      border-radius: 4px;
      margin-top: 8px;
      font-style: italic;
    }
    
    /* Footer */
    .footer-custom {
      padding: 15px 12px !important;
    }
    
    .footer-custom .d-flex {
      flex-direction: column;
      align-items: center !important;
      text-align: center;
    }
    
    .footer-left {
      margin-bottom: 10px;
    }

    .footer-logo {
      width: 28px !important;
      height: 28px !important;
    }

    .footer-custom strong {
      font-size: 13px !important;
    }

    .footer-custom p {
      font-size: 11px !important;
    }
  }
  
  /* ===== EXTRA SMALL MOBILE ===== */
  @media (max-width: 576px) {
    .dataTables_wrapper {
      padding: 8px;
    }
    
    table.dataTable {
      font-size: 10px;
    }
    
    table.dataTable thead th {
      font-size: 9px;
      padding: 8px 6px !important;
    }
    
    table.dataTable tbody td {
      padding: 6px 6px;
      font-size: 10px;
    }
    
    .dt-buttons .btn {
      font-size: 9px;
      padding: 4px 6px;
    }

    .pagination-rounded .page-link {
      width: 28px;
      height: 28px;
      font-size: 10px;
    }
  }

  /* ===== TABLET LANDSCAPE ===== */
  @media (min-width: 769px) and (max-width: 1024px) {
    .dataTables_wrapper {
      padding: 16px;
    }

    table.dataTable thead th {
      font-size: 11px;
      padding: 14px !important;
    }

    table.dataTable tbody td {
      padding: 12px 14px;
      font-size: 12px;
    }

    .dt-buttons .btn {
      font-size: 12px;
    }
  }

  /* ===== SMOOTH SCROLLING ===== */
  html {
    scroll-behavior: smooth;
  }

  /* ===== SELECTION ===== */
  ::selection {
    background: rgba(24, 116, 173, 0.3);
    color: #1874ad;
  }

  /* ===== REMOVE SCROLL INDICATOR ON DESKTOP ===== */
  @media (min-width: 769px) {
    .dataTables_wrapper::after,
    .table-responsive::after {
      display: none !important;
    }
  }
</style>