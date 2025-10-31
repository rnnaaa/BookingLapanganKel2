document.getElementById("menu-toggle").addEventListener("click", () => {
  document.querySelector(".sidebar").classList.toggle("active");
});




// Grafik Chart.js
const ctx = document.getElementById('chartBooking');
if (ctx) {
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
      datasets: [{
        label: 'Booking Mingguan',
        data: [12, 19, 8, 15, 20, 25, 10],
        borderColor: '#0066ff',
        backgroundColor: 'rgba(0,102,255,0.1)',
        fill: true,
        tension: 0.3
      }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
  });
}

