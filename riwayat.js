const bookings = [
  {
    id_booking: "BK001",
    lapangan: "Lapangan A",
    tanggal: "2025-10-12",
    jam: "08:00 - 10:00",
    total: 80000,
    status: "Menunggu Verifikasi",
    deskripsi: "Bukti pembayaran sudah dikirim, menunggu persetujuan admin.",
  },
  {
    id_booking: "BK002",
    lapangan: "Lapangan B",
    tanggal: "2025-10-10",
    jam: "14:00 - 16:00",
    total: 100000,
    status: "Disetujui",
    deskripsi: "Booking disetujui. QR Code aktif sebagai bukti konfirmasi.",
  },
  {
    id_booking: "BK003",
    lapangan: "Lapangan C",
    tanggal: "2025-10-09",
    jam: "10:00 - 11:00",
    total: 50000,
    status: "Ditolak",
    deskripsi: "Bukti transfer tidak valid, silakan ulangi proses booking.",
  },
];

const container = document.getElementById("bookingContainer");

bookings.forEach((b) => {
  const card = document.createElement("div");
  card.className = "card";
  const statusClass = b.status.toLowerCase().includes("menunggu") ? "menunggu" : b.status.toLowerCase().includes("disetujui") ? "disetujui" : "ditolak";

  card.innerHTML = `
    <div class="card-header">
      <h3>${b.lapangan}</h3>
      <span class="status ${statusClass}">${b.status}</span>
    </div>
    <div class="card-body">
      <p><strong>Tanggal:</strong> ${b.tanggal}</p>
      <p><strong>Jam:</strong> ${b.jam}</p>
      <p><strong>Total:</strong> Rp ${b.total.toLocaleString()}</p>
    </div>
    <div class="card-footer">
      <button class="btn-detail" onclick="showDetail('${b.id_booking}')">Lihat Detail</button>
    </div>
  `;
  container.appendChild(card);
});

function showDetail(id) {
  const booking = bookings.find((b) => b.id_booking === id);
  const modal = document.getElementById("detailModal");
  const content = document.getElementById("detailContent");
  const qrContainer = document.getElementById("qrcode");

  qrContainer.innerHTML = ""; // reset QR setiap buka modal

  content.innerHTML = `
    <p><strong>ID Booking:</strong> ${booking.id_booking}</p>
    <p><strong>Lapangan:</strong> ${booking.lapangan}</p>
    <p><strong>Tanggal:</strong> ${booking.tanggal}</p>
    <p><strong>Jam:</strong> ${booking.jam}</p>
    <p><strong>Total:</strong> Rp ${booking.total.toLocaleString()}</p>
    <p><strong>Keterangan:</strong> ${booking.deskripsi}</p>
  `;

  if (booking.status === "Disetujui") {
    new QRCode(qrContainer, {
      text: `https://lapangan-booking.com/check/${booking.id_booking}`,
      width: 150,
      height: 150,
      colorDark: "#1e3a8a",
      colorLight: "#ffffff",
      correctLevel: QRCode.CorrectLevel.H,
    });
  }

  modal.style.display = "flex";
}

function closeModal() {
  document.getElementById("detailModal").style.display = "none";
}
