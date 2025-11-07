// js/auth.js
document.addEventListener("DOMContentLoaded", function () {
  const pekerjaan = document.getElementById("pekerjaan");
  const wrapper = document.getElementById("pekerjaan_lain_wrapper");
  if (pekerjaan) {
    pekerjaan.addEventListener("change", function () {
      wrapper.style.display = this.value === "Lainnya" ? "block" : "none";
    });
  }
});
