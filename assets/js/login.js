// app.js - Simple redirect functionality
document.addEventListener("DOMContentLoaded", function () {
  console.log("SportField App Loaded");

  // Handle Login buttons - redirect to login.html
  const loginButtons = document.querySelectorAll("button");
  loginButtons.forEach((button) => {
    if (button.textContent.includes("Masuk") || button.getAttribute("data-modal-open") === "loginModal") {
      button.addEventListener("click", function (e) {
        e.preventDefault();
        console.log("Redirecting to login page");
        window.location.href = "login.html";
      });
    }
  });

  // Handle Register buttons - redirect to register.html
  const registerButtons = document.querySelectorAll("button");
  registerButtons.forEach((button) => {
    if (button.textContent.includes("Daftar") || button.getAttribute("data-modal-open") === "registerModal") {
      button.addEventListener("click", function (e) {
        e.preventDefault();
        console.log("Redirecting to register page");
        window.location.href = "register.html";
      });
    }
  });

  // Mobile menu functionality
  const mobileBtn = document.getElementById("mobileBtn");
  const mobileNav = document.getElementById("mobileNav");

  if (mobileBtn && mobileNav) {
    mobileBtn.addEventListener("click", function () {
      mobileNav.classList.toggle("hidden");
      console.log("Mobile menu toggled");
    });
  }

  // Close mobile menu when clicking outside
  document.addEventListener("click", function (e) {
    if (mobileNav && !mobileNav.contains(e.target) && !mobileBtn.contains(e.target)) {
      mobileNav.classList.add("hidden");
    }
  });
});

// Other functions
function scrollToSection(sectionId) {
  const element = document.getElementById(sectionId);
  if (element) {
    element.scrollIntoView({ behavior: "smooth" });
  }
}

function handleBookingClick(fieldName, price) {
  // Redirect to login first before booking
  window.location.href = "login.html";
}

function showFieldDetail(fieldId) {
  // Implementation for field details
  const detailSection = document.getElementById("fieldDetail");
  const detailContent = document.getElementById("fieldDetailContent");

  if (detailSection && detailContent) {
    // Show details based on fieldId
    detailContent.innerHTML = `
            <h3 class="text-2xl font-bold mb-4">Detail ${fieldId}</h3>
            <p>Informasi detail tentang lapangan akan ditampilkan di sini.</p>
        `;
    detailSection.classList.remove("hidden");
    detailSection.scrollIntoView({ behavior: "smooth" });
  }
}

function openMap(location) {
  window.open(`https://maps.google.com/?q=${encodeURIComponent(location)}`, "_blank");
}

function openEventInfo() {
  alert("Info lengkap promo event: Diskon 20% untuk paket turnamen, free konsumsi, dan sponsor kit tersedia. Hubungi admin untuk detail lebih lanjut.");
}

function contactAdmin() {
  window.open("https://wa.me/6281234567890?text=Halo%20Admin%20SportField,%20saya%20ingin%20konsultasi%20tentang%20promo%20event", "_blank");
}

function openWhatsApp() {
  window.open("https://wa.me/6281234567890?text=Halo%20Admin%20SportField,%20saya%20ingin%20booking%20lapangan", "_blank");
}
