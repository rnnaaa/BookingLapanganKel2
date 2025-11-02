// Simple UI interactions: mobile nav, modals, booking/login flow, nav indicator
document.addEventListener("DOMContentLoaded", function () {
  // Mobile nav toggle
  const mobileBtn = document.getElementById("mobileBtn");
  const mobileNav = document.getElementById("mobileNav");
  if (mobileBtn) mobileBtn.addEventListener("click", () => mobileNav.classList.toggle("hidden"));

  // Modal open triggers (data-modal-open)
  document.querySelectorAll("[data-modal-open]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const id = btn.getAttribute("data-modal-open");
      openModalById(id);
    });
  });

  // Modal close triggers (data-modal-close)
  document.querySelectorAll("[data-modal-close]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const id = btn.getAttribute("data-modal-close");
      closeModalById(id);
    });
  });

  // close modal when clicking outside panel
  document.querySelectorAll(".modal-backdrop").forEach((back) => {
    back.addEventListener("click", (e) => {
      if (e.target === back) back.classList.add("hidden");
    });
  });

  // close modal buttons (x)
  document.querySelectorAll(".modal-close").forEach((btn) => {
    btn.addEventListener("click", () => {
      const parent = btn.closest(".modal-backdrop");
      if (parent) parent.classList.add("hidden");
    });
  });

  // nav underline indicator (desktop) - REVISI
  setupNavIndicator();
  window.addEventListener("resize", setupNavIndicator);
  window.addEventListener("scroll", highlightCurrentSection);

  // quick anchor behaviour dengan update underline
  document.querySelectorAll('a[href^="#"]').forEach((a) => {
    a.addEventListener("click", (e) => {
      const href = a.getAttribute("href");
      if (href && href.startsWith("#") && href.length > 1) {
        e.preventDefault();
        const el = document.querySelector(href);
        if (el) {
          el.scrollIntoView({ behavior: "smooth", block: "start" });

          // UPDATE: Set active link immediately on click
          setTimeout(() => {
            updateActiveLink(href.substring(1)); // Remove # symbol
            setupNavIndicator(); // Refresh underline position
          }, 100);
        }
      }
    });
  });
});

// -----------------------------
// Simple state: is user logged in?
let isLoggedIn = false; // demo flag; integrate with backend auth later

// -----------------------------
// Modals
function openModalById(id) {
  const el = document.getElementById(id);
  if (!el) return;
  el.classList.remove("hidden");
  setTimeout(() => {
    const focusable = el.querySelector("input, textarea, button");
    if (focusable) focusable.focus();
  }, 120);
}
function closeModalById(id) {
  const el = document.getElementById(id);
  if (!el) return;
  el.classList.add("hidden");
}

// -----------------------------
// Login / Register demo (sets isLoggedIn = true)
function loginDemo(e) {
  e.preventDefault();
  // very simple demo: mark logged in
  isLoggedIn = true;
  closeModalById("loginModal");
  alert("Login demo berhasil — sekarang bisa booking. (Ini demo; hubungkan backend untuk autentikasi sebenarnya.)");
}
function registerDemo(e) {
  e.preventDefault();
  isLoggedIn = true;
  closeModalById("registerModal");
  alert("Akun demo dibuat & masuk. (Ini demo; hubungkan backend untuk menyimpan data.)");
}

// -----------------------------
// Booking flow
function handleBookingClick(name = "Lapangan", price = 0) {
  if (!isLoggedIn) {
    // redirect to login modal
    openModalById("loginModal");
    alert("Silakan login dulu untuk lanjut booking.");
    return;
  }
  // if logged in, open booking modal prefilled
  openBookingModal(name, price, `Rp ${Number(price).toLocaleString("id-ID")} / jam`);
}

function openBookingModal(name = "Lapangan", price = 0, label) {
  const modal = document.getElementById("bookingModal");
  const title = document.getElementById("bookingTitle");
  const priceEl = document.getElementById("bookingPrice");
  if (!modal || !title || !priceEl) return;
  title.textContent = `Booking — ${name}`;
  priceEl.textContent = `Harga: ${label || "Rp " + Number(price).toLocaleString("id-ID") + " / jam"}`;
  modal.dataset.itemName = name;
  modal.dataset.itemPrice = price;
  openModalById("bookingModal");
}

function submitBooking(e) {
  e.preventDefault();
  const modal = document.getElementById("bookingModal");
  const name = modal?.dataset?.itemName || "Lapangan";
  const price = Number(modal?.dataset?.itemPrice || 0);
  const cust = document.getElementById("custName")?.value.trim();
  const slot = document.getElementById("slot")?.value;
  if (!cust || !slot) {
    alert("Lengkapi nama & tanggal/jam booking.");
    return;
  }
  alert(`Booking (demo) diterima\nLapangan: ${name}\nPemesan: ${cust}\nWaktu: ${slot}\nHarga: Rp ${price.toLocaleString("id-ID")}\n\nSilakan hubungi admin untuk konfirmasi & DP.`);
  document.getElementById("bookingForm")?.reset();
  closeModalById("bookingModal");
}

// -----------------------------
// Maps & contact
function openMap(name = "lapangan olahraga") {
  const q = encodeURIComponent(name + " lokasi");
  window.open(`https://www.google.com/maps/search/?api=1&query=${q}`, "_blank");
}
function openEventInfo() {
  alert("Hubungi admin: admin@sportfield.id untuk detail event & paket.");
}
function contactAdmin() {
  window.location.href = "mailto:admin@sportfield.id";
}

// -----------------------------
// NAV indicator: calculate position & width over nav links - REVISI BESAR
let currentActiveLink = null;

function setupNavIndicator() {
  try {
    const nav = document.getElementById("topNav");
    const line = document.getElementById("navLine");
    if (!nav || !line) return;

    const links = nav.querySelectorAll("a");
    if (!links.length) return;

    // Cari link aktif berdasarkan hash URL atau section yang terlihat
    let active = findActiveLink(links);
    if (!active) active = links[0];

    currentActiveLink = active;
    updateUnderlinePosition(active, nav, line);

    // Attach hover listeners
    links.forEach((a) => {
      a.addEventListener("mouseenter", () => {
        updateUnderlinePosition(a, nav, line);
      });

      a.addEventListener("mouseleave", () => {
        updateUnderlinePosition(currentActiveLink, nav, line);
      });

      // UPDATE: Handle click langsung
      a.addEventListener("click", () => {
        currentActiveLink = a;
        // Small delay untuk memastikan scroll sudah selesai
        setTimeout(() => {
          updateUnderlinePosition(a, nav, line);
        }, 150);
      });
    });
  } catch (err) {
    console.error("Nav indicator error:", err);
  }
}

// Fungsi baru: Cari link aktif
function findActiveLink(links) {
  // Priority 1: Hash URL
  const hash = window.location.hash.substring(1);
  if (hash) {
    for (const link of links) {
      if (link.getAttribute("href") === "#" + hash) {
        return link;
      }
    }
  }

  // Priority 2: Section yang sedang terlihat di viewport
  const sections = ["lapangan", "penawaran", "fasilitas", "harga", "testimoni", "faq"];
  for (const id of sections) {
    const el = document.getElementById(id);
    if (!el) continue;

    const rect = el.getBoundingClientRect();
    if (rect.top <= 150 && rect.bottom >= 100) {
      for (const link of links) {
        if (link.getAttribute("href") === "#" + id) {
          return link;
        }
      }
    }
  }

  return null;
}

// Fungsi baru: Update posisi underline
function updateUnderlinePosition(activeElement, navContainer, lineElement) {
  if (!activeElement || !navContainer || !lineElement) return;

  const parentRect = navContainer.getBoundingClientRect();
  const rect = activeElement.getBoundingClientRect();
  const left = rect.left - parentRect.left;

  lineElement.style.width = rect.width + "px";
  lineElement.style.left = left + "px";
  lineElement.style.transition = "all 0.3s ease";
}

// Fungsi baru: Update link aktif berdasarkan section
function updateActiveLink(sectionId) {
  const nav = document.getElementById("topNav");
  if (!nav) return;

  const links = nav.querySelectorAll("a");
  links.forEach((a) => {
    a.classList.remove("text-primary", "font-semibold");
    if (a.getAttribute("href") === "#" + sectionId) {
      a.classList.add("text-primary", "font-semibold");
      currentActiveLink = a;
    }
  });
}

// highlight section on scroll (changes active link)
function highlightCurrentSection() {
  const sections = ["lapangan", "penawaran", "fasilitas", "harga", "testimoni", "faq"];
  let current = null;

  for (const id of sections) {
    const el = document.getElementById(id);
    if (!el) continue;
    const r = el.getBoundingClientRect();
    if (r.top <= 120 && r.bottom > 150) {
      current = id;
      break;
    }
  }

  if (!current) return;

  updateActiveLink(current);

  // Update underline position
  const nav = document.getElementById("topNav");
  const line = document.getElementById("navLine");
  if (nav && line && currentActiveLink) {
    updateUnderlinePosition(currentActiveLink, nav, line);
  }
}
