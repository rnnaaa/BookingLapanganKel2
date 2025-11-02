<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Riwayat Booking</title>
    <link rel="stylesheet" href="assets/css/riwayat.css" />
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>
  </head>
  <body>
    <header class="header">
      <h1>Riwayat Booking</h1>
      <p>Lihat status dan detail pemesanan lapangan Anda</p>
    </header>

    <main class="container" id="bookingContainer"></main>

    <!-- Modal Detail -->
    <div class="modal" id="detailModal">
      <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Detail Booking</h2>
        <div id="detailContent"></div>
        <div id="qrcode" class="qrcode"></div>
      </div>
    </div>

    <script src="assets/js/riwayat.js"></script>
  </body>
</html>
