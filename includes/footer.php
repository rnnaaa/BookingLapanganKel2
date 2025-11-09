<footer class="main-footer text-sm d-flex justify-content-between align-items-center flex-wrap">
  <div class="footer-left">
    <strong>Copyright &copy; 2021 - <?= date('Y'); ?> 
      <a href="#" class="text-primary">Badmintoon</a>.
    </strong> 
    All rights reserved.
  </div>

  <div class="footer-right text-muted">
    <b>Version</b> 3.2.0
  </div>
</footer>


<aside class="control-sidebar control-sidebar-dark"></aside>
</div>
<!-- ./wrapper -->

<!-- URUTAN BENAR JAVASCRIPT LIBRARY -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="../public/asseth/tampilan_admin/plugins/jquery-ui/jquery-ui.min.js"></script>
<script>$.widget.bridge('uibutton', $.ui.button)</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../public/asseth/tampilan_admin/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<script src="../public/asseth/tampilan_admin/dist/js/adminlte.min.js"></script>

<link rel="stylesheet" href="../public/asseth/tampilan_admin/plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="../public/asseth/tampilan_admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
<script src="../public/asseth/tampilan_admin/plugins/select2/js/select2.full.min.js"></script>

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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css"/>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- SCRIPT GLOBAL: LOADER, DATATABLES, DARK MODE, TOASTR -->
<script>
$(function () {
  const $loader = $("#tableLoader");
  const tables = $('table[id^="tbl"], table[id^="example"], table[id^="tabel"], table.dataTable');
  let initialized = 0;

  if (tables.length > 0) $loader.fadeIn(200);

  tables.each(function () {
    const $tbl = $(this);
    const id = $tbl.attr('id') || 'dataTable_' + Math.random().toString(36).substr(2, 5);

    if ($.fn.DataTable.isDataTable($tbl)) return;

    const dt = $tbl.DataTable({
      responsive: true,
      autoWidth: false,
      pageLength: 10,
      lengthChange: true,
      dom: '<"row mb-3"<"col-sm-6"B><"col-sm-6"f>>rtip',
      buttons: [
        { extend: 'copy', text: '<i class="fas fa-copy"></i> Salin', className: 'btn btn-light border me-1' },
        { extend: 'excel', text: '<i class="fas fa-file-excel text-success"></i> Excel', className: 'btn btn-light border me-1' },
        { extend: 'pdf', text: '<i class="fas fa-file-pdf text-danger"></i> PDF', className: 'btn btn-light border me-1' },
        { extend: 'print', text: '<i class="fas fa-print text-primary"></i> Cetak', className: 'btn btn-light border me-1' },
        { extend: 'colvis', text: '<i class="fas fa-columns"></i> Kolom', className: 'btn btn-light border' }
      ],
      language: { url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" },
      initComplete: function () {
        initialized++;
        animateRows($tbl);
        if (initialized === tables.length) $loader.fadeOut(400);
      }
    });

    dt.on('draw.dt', function () { animateRows($tbl); });
    dt.buttons().container().appendTo('#' + id + '_wrapper .col-sm-6:eq(0)');
  });

  function animateRows($tbl) {
    $tbl.find('tbody tr').each((i, el) => {
      $(el).removeClass("appear");
      setTimeout(() => $(el).addClass("appear"), 50 * i);
    });
  }
});

// TOASTR
<?php if (!empty($_SESSION['toast_success'])): ?>
  toastr.success("<?= $_SESSION['toast_success'] ?>", "Sukses!");
  <?php unset($_SESSION['toast_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['toast_error'])): ?>
  toastr.error("<?= $_SESSION['toast_error'] ?>", "Gagal!");
  <?php unset($_SESSION['toast_error']); ?>
<?php endif; ?>

// DARK MODE
$(function () {
  const $sidebar = $(".control-sidebar");
  $sidebar.empty();
  const $container = $("<div />", { class: "p-3 control-sidebar-content" });
  $sidebar.append($container);
  $container.append('<h5><i class="fas fa-cog mr-2"></i>Pengaturan Tampilan</h5><hr class="mb-3"/>');

  const darkModeHtml = `
    <div class="form-group">
      <div class="custom-control custom-switch custom-switch-off-light custom-switch-on-dark">
        <input type="checkbox" class="custom-control-input" id="dark-mode-switch">
        <label class="custom-control-label" for="dark-mode-switch">
          <i class="fas fa-moon mr-2"></i>Mode Gelap
        </label>
      </div>
    </div>`;
  $container.append(darkModeHtml);
  $container.append('<hr class="my-3"/><small class="text-muted"><i class="fas fa-info-circle mr-1"></i>Pengaturan akan tersimpan otomatis</small>');

  if (localStorage.getItem('darkMode') === 'enabled') {
    $('body').addClass('dark-mode');
    $('#dark-mode-switch').prop('checked', true);
  }
  $('#dark-mode-switch').on('change', function () {
    if ($(this).is(':checked')) {
      $('body').addClass('dark-mode');
      localStorage.setItem('darkMode', 'enabled');
    } else {
      $('body').removeClass('dark-mode');
      localStorage.setItem('darkMode', 'disabled');
    }
  });
});
</script>

<style>
  #tableLoader {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 1050;
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(3px);
    align-items: center;
    justify-content: center;
  }
  .spinner-border {
    width: 3rem;
    height: 3rem;
    color: #1874ad;
  }
  table.dataTable tbody tr {
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.3s ease;
  }
  table.dataTable tbody tr.appear {
    opacity: 1;
    transform: translateY(0);
  }
</style>

<div id="tableLoader">
  <div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>
</div>