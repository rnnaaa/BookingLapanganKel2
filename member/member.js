// member.js (REVISED) - dengan KALENDER ELEGAN & PROFESIONAL dan POPUP KONSISTEN
document.addEventListener("DOMContentLoaded", () => {
  console.log("Member JS Loaded - Elegant Calendar Version");

  // ----- Elements -----
  const openFlowBtn = document.getElementById("openFlowBtn");
  const flowPopup = document.getElementById("flowPopup");
  const closeFlow = document.getElementById("closeFlow");

  const sectionA = document.getElementById("sectionA");
  const sectionB = document.getElementById("sectionB");
  const sectionC = document.getElementById("sectionC");

  const toScheduleBtn = document.getElementById("toScheduleBtn");
  const toPaymentBtn = document.getElementById("toPaymentBtn");
  const backToA = document.getElementById("backToA");
  const backToB = document.getElementById("backToB");

  const paket = document.getElementById("paket");
  const startMonth = document.getElementById("startMonth");
  const court = document.getElementById("court");
  const nameInput = document.getElementById("name");
  const emailInput = document.getElementById("email");

  const monthFlowWrap = document.getElementById("monthFlowWrap");
  const selected_dates_input = document.getElementById("selected_dates");

  const payment_method = document.getElementById("payment_method");
  const paymentDetails = document.getElementById("paymentDetails");
  const transfer_display = document.getElementById("transfer_display");
  const transfer_amount = document.getElementById("transfer_amount");
  const submitBtn = document.getElementById("submitBtn");

  const confirmPopup = document.getElementById("confirmPopup");
  const confirmContent = document.getElementById("confirmContent");
  const confirmClose = document.getElementById("confirmClose");
  const editBtn = document.getElementById("editBtn");
  const confirmSendBtn = document.getElementById("confirmSendBtn");

  const smallPopup = document.getElementById("smallPopup");
  const smallPopupMessage = document.getElementById("smallPopupMessage");
  const smallPopupClose = document.getElementById("smallPopupClose");

  // prices (match PHP)
  const prices = { 1: 100000, 2: 200000, 3: 300000 };

  // ----- State -----
  let monthCount = 1;
  let monthList = [];
  let selectedDates = {};
  let bookedSlotsCache = {};
  let currentMonthIndex = 0;

  // Allowed hours (1-hour slots)
  const HOURS = [];
  for (let h = 6; h <= 21; h++) {
    HOURS.push(String(h).padStart(2, "0") + ":00");
  }

  // ----- Helper Functions -----
  function formatMonthYear(y, m) {
    return new Date(y, m - 1, 1).toLocaleString("id-ID", { month: "long", year: "numeric" });
  }

  function formatRupiah(n) {
    return "Rp " + Number(n).toLocaleString("id-ID");
  }

  function getWeekIndexInMonthFromDateObj(d) {
    const first = new Date(d.getFullYear(), d.getMonth(), 1);
    const offset = first.getDay();
    return Math.floor((d.getDate() + offset - 1) / 7);
  }

  function weekCountOfMonth(year, month) {
    const first = new Date(year, month - 1, 1);
    const last = new Date(year, month, 0);
    const offset = first.getDay();
    return Math.ceil((offset + last.getDate()) / 7);
  }

  // ========= FUNGSI BARU UNTUK KALENDER ELEGAN =========
  function renderElegantCalendar(mm, index, totalWeeks, bookedMap) {
    const container = document.createElement("div");
    container.className = "calendar-popup-container";

    // Header dengan navigasi
    const header = document.createElement("div");
    header.className = "calendar-header";
    header.innerHTML = `
      <div class="calendar-title">${formatMonthYear(mm.year, mm.month)}</div>
      <div class="calendar-subtitle">
        Pilih <strong>minimal 2 tanggal</strong> (bebas minggu ke berapa) • 
        <strong>Maksimal 5 tanggal</strong> • Klik tanggal untuk memilih jam
      </div>
    `;
    container.appendChild(header);

    // Navigation
    const navigation = document.createElement("div");
    navigation.className = "calendar-navigation";

    const prevBtn = document.createElement("button");
    prevBtn.className = "calendar-nav-btn";
    prevBtn.innerHTML = "‹ Prev";
    prevBtn.onclick = () => {
      if (index > 0) openMonthPicker(index - 1);
    };

    const nextBtn = document.createElement("button");
    nextBtn.className = "calendar-nav-btn";
    nextBtn.innerHTML = "Next ›";
    nextBtn.onclick = () => {
      if (index < monthList.length - 1) openMonthPicker(index + 1);
    };

    const monthDisplay = document.createElement("div");
    monthDisplay.className = "calendar-month-year";
    monthDisplay.textContent = `Bulan ${index + 1} dari ${monthList.length}`;

    navigation.appendChild(prevBtn);
    navigation.appendChild(monthDisplay);
    navigation.appendChild(nextBtn);
    container.appendChild(navigation);

    // Day headers
    const grid = document.createElement("div");
    grid.className = "calendar-grid-elegant";

    const dayNames = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
    dayNames.forEach((day) => {
      const dayHeader = document.createElement("div");
      dayHeader.className = "calendar-day-header";
      dayHeader.textContent = day;
      grid.appendChild(dayHeader);
    });

    const first = new Date(mm.year, mm.month - 1, 1);
    const startWeekday = first.getDay();
    const daysInMonth = new Date(mm.year, mm.month, 0).getDate();

    // Blank cells for days before month start
    for (let i = 0; i < startWeekday; i++) {
      const blank = document.createElement("div");
      blank.className = "calendar-day-elegant";
      blank.style.visibility = "hidden";
      grid.appendChild(blank);
    }

    // Days of the month
    for (let d = 1; d <= daysInMonth; d++) {
      const cell = document.createElement("div");
      cell.className = "calendar-day-elegant";

      const dayNumber = document.createElement("div");
      dayNumber.textContent = d;
      dayNumber.style.fontSize = "1.1rem";
      dayNumber.style.fontWeight = "600";
      cell.appendChild(dayNumber);

      const iso = `${mm.year}-${String(mm.month).padStart(2, "0")}-${String(d).padStart(2, "0")}`;
      const weekIndex = getWeekIndexInMonthFromDateObj(new Date(iso + "T00:00:00"));
      const weekNum = weekIndex + 1;
      const isOptional = weekNum === 5 && totalWeeks === 5;

      // Add week indicator
      const weekIndicator = document.createElement("div");
      weekIndicator.className = "week-indicator-elegant";
      weekIndicator.textContent = weekNum;
      if (isOptional) {
        weekIndicator.style.background = "#f59e0b";
      }
      cell.appendChild(weekIndicator);

      if (isOptional) {
        cell.classList.add("optional");
      }

      // Check availability
      const bookedTimes = bookedMap[iso] || [];
      const isFullyBooked = bookedTimes.length >= HOURS.length;

      if (isFullyBooked) {
        cell.classList.add("disabled");
        cell.title = "Tidak tersedia";
      }

      // Check if this date is selected
      const key = `${mm.year}-${String(mm.month).padStart(2, "0")}`;
      const existingSelections = selectedDates[key] || {};
      const isSelected = Object.values(existingSelections).some((item) => item.date === iso);

      if (isSelected) {
        cell.classList.add("selected");
        // Add time indicator for selected dates
        const selectedTime = Object.values(existingSelections).find((item) => item.date === iso)?.time;
        if (selectedTime) {
          const timeIndicator = document.createElement("div");
          timeIndicator.style.fontSize = "0.7rem";
          timeIndicator.style.marginTop = "2px";
          timeIndicator.style.background = "rgba(255,255,255,0.9)";
          timeIndicator.style.color = "#2563eb";
          timeIndicator.style.padding = "1px 4px";
          timeIndicator.style.borderRadius = "4px";
          timeIndicator.style.fontWeight = "600";
          timeIndicator.textContent = selectedTime;
          cell.appendChild(timeIndicator);
        }
      }

      // Click handler
      if (!isFullyBooked) {
        cell.addEventListener("click", () => {
          openTimePickerForDate(mm, iso, index, weekNum, bookedMap);
        });
      }

      grid.appendChild(cell);
    }

    container.appendChild(grid);

    // Legend
    const legend = document.createElement("div");
    legend.className = "calendar-legend";
    legend.innerHTML = `
      <div class="legend-item">
        <div class="legend-color available"></div>
        <span>Tersedia</span>
      </div>
      <div class="legend-item">
        <div class="legend-color selected"></div>
        <span>Terpilih</span>
      </div>
      <div class="legend-item">
        <div class="legend-color optional"></div>
        <span>Minggu 5 (Opsional)</span>
      </div>
      <div class="legend-item">
        <div class="legend-color disabled"></div>
        <span>Penuh</span>
      </div>
    `;
    container.appendChild(legend);

    // Footer dengan info seleksi
    const footer = document.createElement("div");
    footer.style.marginTop = "24px";
    footer.style.paddingTop = "20px";
    footer.style.borderTop = "2px solid #e5e7eb";

    const key = `${mm.year}-${String(mm.month).padStart(2, "0")}`;
    const currentSelections = selectedDates[key] || {};
    const selectedCount = Object.keys(currentSelections).length;

    const selectionInfo = document.createElement("div");
    selectionInfo.innerHTML = `
      <div style="text-align:center;padding:20px;background:${selectedCount >= 2 ? "#f0f9ff" : "#fef3f2"};border-radius:16px;border:2px solid ${selectedCount >= 2 ? "#0ea5e9" : "#ef4444"}">
        <div style="font-size:16px;color:${selectedCount >= 2 ? "#0ea5e9" : "#ef4444"};margin-bottom:8px">STATUS PEMILIHAN</div>
        <div style="font-size:28px;font-weight:700;color:${selectedCount >= 2 ? "#0ea5e9" : "#ef4444"}">${selectedCount}/5 tanggal</div>
        <div style="font-size:14px;color:${selectedCount >= 2 ? "#0ea5e9" : "#ef4444"}">${selectedCount >= 2 ? "✅ Minimal terpenuhi" : "❌ Pilih minimal 2 tanggal"}</div>
      </div>
    `;
    footer.appendChild(selectionInfo);

    // Tampilkan daftar tanggal yang sudah dipilih
    if (selectedCount > 0) {
      const selectedList = document.createElement("div");
      selectedList.style.marginTop = "20px";
      selectedList.style.padding = "16px";
      selectedList.style.background = "#f8fafc";
      selectedList.style.borderRadius = "12px";
      selectedList.style.border = "1px solid #e5e7eb";

      const listTitle = document.createElement("div");
      listTitle.style.fontWeight = "600";
      listTitle.style.marginBottom = "12px";
      listTitle.style.color = "#374151";
      listTitle.style.fontSize = "16px";
      listTitle.textContent = "📅 Tanggal Terpilih:";
      selectedList.appendChild(listTitle);

      Object.values(currentSelections).forEach((item) => {
        const dateItem = document.createElement("div");
        dateItem.style.display = "flex";
        dateItem.style.justifyContent = "space-between";
        dateItem.style.alignItems = "center";
        dateItem.style.padding = "12px";
        dateItem.style.background = "white";
        dateItem.style.borderRadius = "8px";
        dateItem.style.marginBottom = "8px";
        dateItem.style.border = "1px solid #e5e7eb";

        const dateObj = new Date(item.date + "T00:00:00");
        const weekNum = getWeekIndexInMonthFromDateObj(dateObj) + 1;

        dateItem.innerHTML = `
          <div style="flex: 1;">
            <div style="font-weight: 600; color: #1f2937;">${item.date}</div>
            <div style="display: flex; gap: 12px; margin-top: 4px;">
              <span style="background: #10b981; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">Minggu ${weekNum}</span>
              <span style="color: #2563eb; font-weight: 600;">${item.time}</span>
            </div>
          </div>
          <div style="display: flex; gap: 8px;">
            <button class="btn-small outline" style="padding: 6px 12px; font-size: 12px; border: 1px solid #2563eb; color: #2563eb; background: white; border-radius: 6px; cursor: pointer;">✏️ Edit</button>
            <button class="btn-small error" style="padding: 6px 12px; font-size: 12px; border: 1px solid #ef4444; color: #ef4444; background: white; border-radius: 6px; cursor: pointer;">🗑️ Hapus</button>
          </div>
        `;

        const editBtn = dateItem.querySelector(".btn-small.outline");
        const deleteBtn = dateItem.querySelector(".btn-small.error");

        editBtn.onclick = function () {
          editSelectedDate(index, item.date);
        };

        deleteBtn.onclick = function () {
          deleteSelectedDate(index, item.date);
        };

        selectedList.appendChild(dateItem);
      });

      footer.appendChild(selectedList);
    }

    const actions = document.createElement("div");
    actions.style.display = "flex";
    actions.style.gap = "12px";
    actions.style.justifyContent = "space-between";
    actions.style.marginTop = "24px";

    const btnClose = document.createElement("button");
    btnClose.type = "button";
    btnClose.className = "btn outline";
    btnClose.innerText = "Tutup Kalender";
    btnClose.addEventListener("click", () => {
      smallPopup.setAttribute("aria-hidden", "true");
    });

    const btnNext = document.createElement("button");
    btnNext.type = "button";
    btnNext.className = "btn primary";
    btnNext.innerText = index < monthList.length - 1 ? `Lanjut ke ${formatMonthYear(monthList[index + 1].year, monthList[index + 1].month)}` : "Simpan Jadwal";
    btnNext.addEventListener("click", () => {
      if (selectedCount < 2) {
        showIncompleteWarning(mm, index, selectedCount, currentSelections);
        return;
      }

      // Update display
      const infoBox = document.getElementById("month-info-" + index);
      if (infoBox) {
        updateMonthInfoDisplay(mm, index, infoBox);
      }

      smallPopup.setAttribute("aria-hidden", "true");

      // Auto-open next month
      if (index < monthList.length - 1) {
        setTimeout(() => openMonthPicker(index + 1), 500);
      }
    });

    actions.appendChild(btnClose);
    actions.appendChild(btnNext);
    footer.appendChild(actions);
    container.appendChild(footer);

    return container;
  }

  // ========= FUNGSI BARU UNTUK EDIT/HAPUS =========
  function editSelectedDate(monthIndex, targetDate) {
    const mm = monthList[monthIndex];
    const key = `${mm.year}-${String(mm.month).padStart(2, "0")}`;

    // Cari jam yang sudah dipilih untuk date ini
    const existingSelection = Object.values(selectedDates[key]).find((item) => item.date === targetDate);
    const weekNum = getWeekIndexInMonthFromDateObj(new Date(targetDate + "T00:00:00")) + 1;

    // Fetch availability dan buka time picker
    fetchAvailabilityForMonth(Number(court.value), key)
      .then((bookedMap) => {
        openTimePickerForDate(mm, targetDate, monthIndex, weekNum, bookedMap, true); // true = edit mode
      })
      .catch((error) => {
        console.error("Error loading calendar for edit:", error);
        showSmallPopup("Error loading calendar", "error");
      });
  }

  function deleteSelectedDate(monthIndex, targetDate) {
    const mm = monthList[monthIndex];
    const key = `${mm.year}-${String(mm.month).padStart(2, "0")}`;

    // Konfirmasi hapus
    const confirmDelete = confirm(`Hapus jadwal untuk ${targetDate}?`);
    if (!confirmDelete) return;

    // Hapus dari selectedDates
    Object.keys(selectedDates[key]).forEach((selKey) => {
      if (selectedDates[key][selKey] && selectedDates[key][selKey].date === targetDate) {
        delete selectedDates[key][selKey];
      }
    });

    // Update real-time display
    updateScheduleDisplayRealTime();

    // Update UI di popup juga (jika sedang terbuka)
    const infoBox = document.getElementById("month-info-" + monthIndex);
    if (infoBox) {
      updateMonthInfoDisplay(mm, monthIndex, infoBox);
    }

    showSmallPopup("✅ Jadwal berhasil dihapus", "info");
  }

  // ========= FUNGSI UPDATE REAL-TIME SECTION B =========
  function updateScheduleDisplayRealTime() {
    if (!monthFlowWrap) return;

    // Update semua month card di Section B
    monthList.forEach((mm, idx) => {
      const infoBox = document.getElementById("month-info-" + idx);
      if (infoBox) {
        updateMonthInfoDisplay(mm, idx, infoBox);
      }
    });
  }

  // ========= FUNGSI VALIDASI MAKSIMAL =========
  function hasExceededMaximum(monthKey) {
    if (!selectedDates[monthKey]) return false;
    return Object.keys(selectedDates[monthKey]).length >= 5;
  }

  // ----- Email Formatting -----
  function formatEmail(input) {
    let value = input.value.trim();

    if (value.includes("@")) {
      value = value.split("@")[0];
    }

    value = value.replace(/[^a-zA-Z0-9.]/g, "");
    value = value.replace(/^\.+|\.+$/g, "");

    if (value.length > 30) {
      value = value.substring(0, 30);
    }

    input.value = value;

    if (value && !input.value.includes("@")) {
      input.value = value + "@gmail.com";
    }
  }

  // Real-time email formatting
  if (emailInput) {
    emailInput.addEventListener("input", () => {
      let value = emailInput.value;
      if (value.includes("@")) return;
      value = value.replace(/[^a-zA-Z0-9.]/g, "");
      emailInput.value = value;
    });

    emailInput.addEventListener("blur", () => {
      formatEmail(emailInput);
    });

    emailInput.addEventListener("focus", () => {
      let value = emailInput.value;
      if (value.includes("@gmail.com")) {
        emailInput.value = value.replace("@gmail.com", "");
      }
    });
  }

  // ----- Bank Number Validation -----
  const bankNumberInput = document.getElementById("bank_from_number");
  if (bankNumberInput) {
    bankNumberInput.addEventListener("input", (e) => {
      e.target.value = e.target.value.replace(/\D/g, "");
    });

    bankNumberInput.addEventListener("blur", (e) => {
      const value = e.target.value.replace(/\s/g, "");
      if (value.length > 0) {
        e.target.value = value.replace(/(\d{4})(?=\d)/g, "$1 ");
      }
    });

    bankNumberInput.addEventListener("focus", (e) => {
      e.target.value = e.target.value.replace(/\s/g, "");
    });
  }

  // ----- Small Popup Management -----
  let smallPopupTimer = null;
  let currentSmallPopupType = "info";

  function showSmallPopup(msg, type = "info") {
    if (!smallPopup || !smallPopupMessage) {
      alert(msg);
      return;
    }

    currentSmallPopupType = type;
    smallPopupMessage.innerText = msg;
    smallPopup.className = `small-popup ${type}-popup`;
    smallPopup.setAttribute("aria-hidden", "false");

    if (smallPopupTimer) clearTimeout(smallPopupTimer);
    smallPopupTimer = setTimeout(
      () => {
        smallPopup.setAttribute("aria-hidden", "true");
      },
      type === "warning" ? 5000 : 4000
    );
  }

  // Close popup when clicking outside
  if (smallPopup) {
    smallPopup.addEventListener("click", (e) => {
      if (e.target === smallPopup) {
        smallPopup.setAttribute("aria-hidden", "true");
        if (smallPopupTimer) clearTimeout(smallPopupTimer);
      }
    });
  }

  if (smallPopupClose) {
    smallPopupClose.addEventListener("click", () => {
      smallPopup.setAttribute("aria-hidden", "true");
      if (smallPopupTimer) clearTimeout(smallPopupTimer);
    });
  }

  // Close flow popup when clicking outside
  if (flowPopup) {
    flowPopup.addEventListener("click", (e) => {
      if (e.target === flowPopup) {
        flowPopup.setAttribute("aria-hidden", "true");
      }
    });
  }

  // Close confirm popup when clicking outside
  if (confirmPopup) {
    confirmPopup.addEventListener("click", (e) => {
      if (e.target === confirmPopup) {
        confirmPopup.setAttribute("aria-hidden", "true");
      }
    });
  }

  // ----- Flow open/close -----
  if (openFlowBtn && flowPopup && closeFlow) {
    openFlowBtn.addEventListener("click", () => {
      flowPopup.setAttribute("aria-hidden", "false");

      if (!startMonth.value) {
        const now = new Date();
        startMonth.value = now.toISOString().slice(0, 7);
      }
      if (!paket.value) paket.value = "1";

      updatePrice();
    });

    closeFlow.addEventListener("click", () => {
      flowPopup.setAttribute("aria-hidden", "true");
    });
  }

  // Make month input clickable everywhere
  if (startMonth) {
    const monthWrapper = startMonth.parentElement;
    if (monthWrapper) {
      monthWrapper.style.cursor = "pointer";
      monthWrapper.addEventListener("click", () => {
        startMonth.focus();
        startMonth.showPicker && startMonth.showPicker();
      });
    }
  }

  // Price updates
  if (paket) {
    paket.addEventListener("change", updatePrice);
  }

  function updatePrice() {
    const p = parseInt(paket.value) || 1;
    if (transfer_amount) transfer_amount.value = prices[p];
    if (transfer_display) transfer_display.value = formatRupiah(prices[p]);
  }

  // Payment details update
  if (payment_method) {
    payment_method.addEventListener("change", updatePaymentDetails);
  }

  function updatePaymentDetails() {
    if (!paymentDetails) return;

    const v = payment_method.value;
    if (v === "qris") {
      paymentDetails.innerHTML = `
        <div style="text-align:center">
          <div style="width:160px;height:160px;border-radius:12px;border:2px dashed #cfe3ff;display:inline-flex;align-items:center;justify-content:center;color:#2563eb;font-weight:700;font-size:18px;background:#f8fbff">
            QRIS CODE
          </div>
          <div class="muted" style="margin-top:12px">Scan QRIS untuk melakukan pembayaran</div>
        </div>`;
    } else if (v === "bca") {
      paymentDetails.innerHTML = `
        <div style="text-align:center">
          <div style="font-size:18px;font-weight:700;color:#2563eb;margin-bottom:8px">BCA</div>
          <div style="font-size:24px;font-weight:800;color:#1f2937;margin-bottom:8px">123 456 7890</div>
          <div class="muted">a.n. Lapangan Badminton</div>
        </div>`;
    } else if (v === "mandiri") {
      paymentDetails.innerHTML = `
        <div style="text-align:center">
          <div style="font-size:18px;font-weight:700;color:#2563eb;margin-bottom:8px">Mandiri</div>
          <div style="font-size:24px;font-weight:800;color:#1f2937;margin-bottom:8px">098 765 4321</div>
          <div class="muted">a.n. Lapangan Badminton</div>
        </div>`;
    } else {
      paymentDetails.innerHTML = '<div class="muted" style="text-align:center">Pilih metode pembayaran untuk melihat detail</div>';
    }
  }

  // ----- Navigation between sections -----
  if (toScheduleBtn) {
    toScheduleBtn.addEventListener("click", () => {
      if (!validateA()) return;

      monthCount = parseInt(paket.value) || 1;
      buildMonthList();
      renderMonthFlow();

      sectionA.style.display = "none";
      sectionB.style.display = "block";
      sectionC.style.display = "none";
    });
  }

  if (backToA) {
    backToA.addEventListener("click", () => {
      sectionA.style.display = "block";
      sectionB.style.display = "none";
      sectionC.style.display = "none";
    });
  }

  if (toPaymentBtn) {
    toPaymentBtn.addEventListener("click", () => {
      if (!allMonthsHaveMinimum()) {
        showSmallPopup("Masih ada bulan yang belum memilih minimal 2 tanggal. Lengkapi pemilihan tanggal untuk setiap bulan.", "warning");
        return;
      }

      const arr = gatherSelectedAsArray();
      if (selected_dates_input) {
        selected_dates_input.value = JSON.stringify(arr);
      }

      sectionA.style.display = "none";
      sectionB.style.display = "none";
      sectionC.style.display = "block";
      updatePaymentDetails();
      updatePrice();
    });
  }

  if (backToB) {
    backToB.addEventListener("click", () => {
      sectionA.style.display = "none";
      sectionB.style.display = "block";
      sectionC.style.display = "none";
    });
  }

  // Submit -> show confirmation popup
  if (submitBtn) {
    submitBtn.addEventListener("click", (e) => {
      e.preventDefault();
      if (!validateC()) return;
      showConfirmationPopup();
    });
  }

  function showConfirmationPopup() {
    confirmContent.innerHTML = "";
    const arr = gatherSelectedAsArray();

    const rows = [
      { label: "Nama Lengkap", value: nameInput.value.trim() },
      { label: "Email", value: emailInput.value.trim() },
      { label: "Paket Member", value: `${paket.value} Bulan (${formatRupiah(prices[parseInt(paket.value) || 1])})` },
      { label: "Mulai Bulan", value: startMonth.value },
      { label: "Lapangan", value: court.options[court.selectedIndex]?.text || court.value },
      { label: "Metode Pembayaran", value: payment_method.value.toUpperCase() },
      { label: "Total Pembayaran", value: formatRupiah(transfer_amount.value || 0) },
      { label: "Jadwal Terpilih", value: arr, isDates: true },
    ];

    rows.forEach((row) => {
      const div = document.createElement("div");
      div.className = "confirm-row";

      if (row.isDates) {
        div.innerHTML = `
          <div class="confirm-label">${row.label}</div>
          <div class="confirm-value">
            <div class="confirm-dates">
              ${row.value
                .map(
                  (item) => `
                <div class="confirm-date-item">
                  <strong>${item.date}</strong> - <span class="time">${item.time}</span>
                </div>
              `
                )
                .join("")}
            </div>
          </div>
        `;
      } else {
        div.innerHTML = `
          <div class="confirm-label">${row.label}</div>
          <div class="confirm-value">${row.value}</div>
        `;
      }

      confirmContent.appendChild(div);
    });

    confirmPopup.setAttribute("aria-hidden", "false");
  }

  // Confirmation popup handlers
  if (confirmClose) {
    confirmClose.addEventListener("click", () => {
      confirmPopup.setAttribute("aria-hidden", "true");
    });
  }

  if (editBtn) {
    editBtn.addEventListener("click", () => {
      confirmPopup.setAttribute("aria-hidden", "true");
      sectionA.style.display = "none";
      sectionB.style.display = "block";
      sectionC.style.display = "none";
    });
  }

  if (confirmSendBtn) {
    confirmSendBtn.addEventListener("click", () => {
      showSmallPopup("✅ Data berhasil dikirim! Menunggu verifikasi admin...", "info");
      setTimeout(() => {
        const arr = gatherSelectedAsArray();
        if (selected_dates_input) {
          selected_dates_input.value = JSON.stringify(arr);
        }

        const form = document.getElementById("memberForm");
        if (form) {
          form.submit();
        }
      }, 1500);
    });
  }

  // ----- Form Reset Function -----
  function resetForm() {
    if (nameInput) nameInput.value = "";
    if (emailInput) emailInput.value = "";
    if (paket) paket.value = "";
    if (startMonth) {
      const now = new Date();
      startMonth.value = now.toISOString().slice(0, 7);
    }
    if (court) court.value = "";
    if (payment_method) payment_method.value = "";
    if (transfer_display) transfer_display.value = "";
    if (transfer_amount) transfer_amount.value = "";

    const buktiInput = document.getElementById("bukti");
    if (buktiInput) buktiInput.value = "";

    const bankName = document.getElementById("bank_from_name");
    const bankNumber = document.getElementById("bank_from_number");
    if (bankName) bankName.value = "";
    if (bankNumber) bankNumber.value = "";

    selectedDates = {};
    bookedSlotsCache = {};
    monthList = [];

    if (monthFlowWrap) monthFlowWrap.innerHTML = "";
    if (paymentDetails) paymentDetails.innerHTML = "";

    sectionA.style.display = "block";
    sectionB.style.display = "none";
    sectionC.style.display = "none";
  }

  // Check if we're coming back from successful submission
  if (window.location.search.includes("submitted=true")) {
    resetForm();
    window.history.replaceState({}, document.title, window.location.pathname);
  }

  // ----- Data Management -----
  function gatherSelectedAsArray() {
    const arr = [];
    Object.keys(selectedDates).forEach((ym) => {
      const selections = selectedDates[ym];
      Object.values(selections).forEach((item) => {
        if (item && item.date && item.time) {
          arr.push({ date: item.date, time: item.time });
        }
      });
    });

    arr.sort((a, b) => {
      const dateCompare = a.date.localeCompare(b.date);
      return dateCompare !== 0 ? dateCompare : a.time.localeCompare(b.time);
    });

    return arr;
  }

  function buildMonthList() {
    monthList = [];
    selectedDates = {};
    bookedSlotsCache = {};

    const [y, m] = startMonth.value.split("-").map((x) => parseInt(x, 10));
    const count = parseInt(paket.value) || 1;

    for (let i = 0; i < count; i++) {
      const dt = new Date(y, m - 1 + i, 1);
      const ym = `${dt.getFullYear()}-${String(dt.getMonth() + 1).padStart(2, "0")}`;
      monthList.push({
        year: dt.getFullYear(),
        month: dt.getMonth() + 1,
        ym,
      });
    }
  }

  function renderMonthFlow() {
    if (!monthFlowWrap) return;

    monthFlowWrap.innerHTML = "";

    monthList.forEach((mm, idx) => {
      const wrapper = document.createElement("div");
      wrapper.className = "month-card";

      const title = document.createElement("div");
      title.innerHTML = `
        <strong style="font-size:1.3rem">${formatMonthYear(mm.year, mm.month)}</strong>
        <div class="muted" style="margin-top:4px">Pilih minimal 2 tanggal, maksimal 5 tanggal (bebas minggu ke berapa)</div>
      `;
      wrapper.appendChild(title);

      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "btn outline";
      btn.style.marginTop = "16px";
      btn.innerText = "📅 Pilih Jadwal untuk Bulan Ini";
      btn.addEventListener("click", () => openMonthPicker(idx));
      wrapper.appendChild(btn);

      const info = document.createElement("div");
      info.className = "month-info";
      info.id = "month-info-" + idx;

      updateMonthInfoDisplay(mm, idx, info);
      wrapper.appendChild(info);

      monthFlowWrap.appendChild(wrapper);
    });
  }

  function updateMonthInfoDisplay(mm, index, infoElement) {
    const key = `${mm.year}-${String(mm.month).padStart(2, "0")}`;
    const selections = selectedDates[key] || {};
    const selectedCount = Object.keys(selections).length;
    const totalWeeks = weekCountOfMonth(mm.year, mm.month);

    let statusText = "";
    let statusClass = "";

    if (selectedCount === 0) {
      statusText = "❌ Belum memilih tanggal";
      statusClass = "error";
    } else if (selectedCount < 2) {
      statusText = `⚠️ ${selectedCount}/2 tanggal terpenuhi`;
      statusClass = "warning";
    } else if (selectedCount >= 5) {
      statusText = `✅ ${selectedCount}/5 tanggal (MAKSIMAL)`;
      statusClass = "success";
    } else {
      statusText = `✅ ${selectedCount}/5 tanggal`;
      statusClass = "success";
    }

    if (selectedCount > 0) {
      const selectedList = document.createElement("div");
      selectedList.className = "selected-dates-list";
      selectedList.style.marginTop = "12px";

      Object.values(selections).forEach((item) => {
        const dateItem = document.createElement("div");
        dateItem.className = "selected-date-item";
        dateItem.style.display = "flex";
        dateItem.style.justifyContent = "space-between";
        dateItem.style.alignItems = "center";
        dateItem.style.padding = "8px 12px";

        const dateObj = new Date(item.date + "T00:00:00");
        const weekNum = getWeekIndexInMonthFromDateObj(dateObj) + 1;
        const isOptional = weekNum === 5 && totalWeeks === 5;

        dateItem.innerHTML = `
          <div>
            <div style="font-weight: 500;">${item.date}</div>
            <div class="time">${item.time} (Minggu ${weekNum}${isOptional ? " - Opsional" : ""})</div>
          </div>
          <div style="display: flex; gap: 4px;">
            <button class="btn-small-outline" style="padding: 2px 6px; font-size: 11px; border: 1px solid #2563eb; color: #2563eb; background: white; border-radius: 4px; cursor: pointer;">✏️</button>
            <button class="btn-small-error" style="padding: 2px 6px; font-size: 11px; border: 1px solid #ef4444; color: #ef4444; background: white; border-radius: 4px; cursor: pointer;">🗑️</button>
          </div>
        `;

        // Tambah event listener
        const editBtn = dateItem.querySelector(".btn-small-outline");
        const deleteBtn = dateItem.querySelector(".btn-small-error");

        editBtn.onclick = function () {
          editSelectedDate(index, item.date);
        };

        deleteBtn.onclick = function () {
          deleteSelectedDate(index, item.date);
        };

        selectedList.appendChild(dateItem);
      });

      infoElement.innerHTML = `
        <div style="font-weight:600;margin-bottom:8px;color:${statusClass === "error" ? "#ef4444" : statusClass === "warning" ? "#f59e0b" : "#10b981"}">
          ${statusText}
        </div>
      `;
      infoElement.appendChild(selectedList);
    } else {
      infoElement.innerHTML = `
        <div style="font-weight:600;color:#ef4444">${statusText}</div>
      `;
    }
  }

  // ----- Availability Fetch -----
  async function fetchAvailabilityForMonth(lapanganId, ym) {
    if (bookedSlotsCache[ym]) {
      return bookedSlotsCache[ym];
    }

    try {
      const url = `member.php?action=availability&lapangan=${lapanganId}&ym=${ym}`;
      const resp = await fetch(url);
      if (!resp.ok) throw new Error(`HTTP ${resp.status}`);

      const j = await resp.json();
      if (j && j.success) {
        bookedSlotsCache[ym] = j.booked || {};
        return bookedSlotsCache[ym];
      } else {
        throw new Error(j.errors ? j.errors.join(", ") : "Unknown error");
      }
    } catch (e) {
      console.error("Error fetching availability:", e);
      showSmallPopup("Gagal memuat data ketersediaan", "error");
      bookedSlotsCache[ym] = {};
      return {};
    }
  }

  // ----- Month Picker dengan KALENDER ELEGAN -----
  function openMonthPicker(index) {
    if (index < 0 || index >= monthList.length) return;

    currentMonthIndex = index;
    const mm = monthList[index];
    const ymKey = mm.ym;
    const totalWeeks = weekCountOfMonth(mm.year, mm.month);

    fetchAvailabilityForMonth(Number(court.value), ymKey)
      .then((bookedMap) => {
        const calendarContainer = renderElegantCalendar(mm, index, totalWeeks, bookedMap);

        // Show in small popup
        if (smallPopupMessage) {
          smallPopupMessage.innerHTML = "";
          smallPopupMessage.appendChild(calendarContainer);
          smallPopup.setAttribute("aria-hidden", "false");
        }
      })
      .catch((error) => {
        console.error("Error in month picker:", error);
        showSmallPopup("Error loading calendar", "error");
      });
  }

  // ----- Warning untuk pemilihan kurang dari 2 tanggal -----
  function showIncompleteWarning(mm, index, selectedCount, selections) {
    const container = document.createElement("div");
    container.className = "calendar-popup-container";
    container.style.padding = "0";

    const icon = document.createElement("div");
    icon.style.fontSize = "48px";
    icon.style.marginBottom = "16px";
    icon.style.textAlign = "center";
    icon.textContent = "⚠️";
    container.appendChild(icon);

    const title = document.createElement("div");
    title.style.fontSize = "1.3rem";
    title.style.fontWeight = "700";
    title.style.marginBottom = "12px";
    title.style.textAlign = "center";
    title.textContent = "Pemilihan Belum Lengkap";
    container.appendChild(title);

    const message = document.createElement("div");
    message.style.marginBottom = "20px";
    message.style.color = "#6b7280";
    message.style.textAlign = "center";

    if (selectedCount === 0) {
      message.innerHTML = `Anda belum memilih tanggal untuk <strong>${formatMonthYear(mm.year, mm.month)}</strong>.`;
    } else {
      message.innerHTML = `Anda hanya memilih <strong>${selectedCount} tanggal</strong> untuk <strong>${formatMonthYear(mm.year, mm.month)}</strong>.`;
    }

    message.innerHTML += `<br><strong>Sistem fleksibel:</strong> Pilih minimal 2 tanggal dari minggu mana saja (maksimal 5).`;
    container.appendChild(message);

    // Tampilkan tanggal yang sudah dipilih
    if (selectedCount > 0) {
      const selectedDatesList = document.createElement("div");
      selectedDatesList.style.background = "#f8fafc";
      selectedDatesList.style.padding = "12px";
      selectedDatesList.style.borderRadius = "8px";
      selectedDatesList.style.marginBottom = "20px";
      selectedDatesList.style.textAlign = "left";

      const listTitle = document.createElement("div");
      listTitle.style.fontWeight = "600";
      listTitle.style.marginBottom = "8px";
      listTitle.textContent = "Tanggal yang sudah dipilih:";
      selectedDatesList.appendChild(listTitle);

      Object.values(selections).forEach((item) => {
        const dateItem = document.createElement("div");
        dateItem.style.display = "flex";
        dateItem.style.justifyContent = "space-between";
        dateItem.style.padding = "4px 0";
        dateItem.style.fontSize = "14px";

        dateItem.innerHTML = `
          <span>${item.date}</span>
          <span>${item.time}</span>
        `;
        selectedDatesList.appendChild(dateItem);
      });

      container.appendChild(selectedDatesList);
    }

    const actions = document.createElement("div");
    actions.style.display = "flex";
    actions.style.gap = "12px";
    actions.style.justifyContent = "center";

    const btnEdit = document.createElement("button");
    btnEdit.type = "button";
    btnEdit.className = "btn outline";
    btnEdit.textContent = "Ubah Jadwal";
    btnEdit.addEventListener("click", () => {
      smallPopup.setAttribute("aria-hidden", "true");
      setTimeout(() => openMonthPicker(index), 300);
    });

    const btnConfirm = document.createElement("button");
    btnConfirm.type = "button";
    btnConfirm.className = "btn primary";
    btnConfirm.textContent = "Yakin, Lanjutkan";
    btnConfirm.addEventListener("click", () => {
      // Update display
      const infoBox = document.getElementById("month-info-" + index);
      if (infoBox) {
        updateMonthInfoDisplay(mm, index, infoBox);
      }

      smallPopup.setAttribute("aria-hidden", "true");

      // Auto-open next month
      if (index < monthList.length - 1) {
        setTimeout(() => openMonthPicker(index + 1), 500);
      }
    });

    actions.appendChild(btnEdit);
    actions.appendChild(btnConfirm);
    container.appendChild(actions);

    // Show in small popup
    if (smallPopupMessage) {
      smallPopupMessage.innerHTML = "";
      smallPopupMessage.appendChild(container);
      smallPopup.setAttribute("aria-hidden", "false");
    }
  }

  function openTimePickerForDate(mm, isoDate, monthIndex, weekNum, bookedMap, isEditMode = false) {
    const key = `${mm.year}-${String(mm.month).padStart(2, "0")}`;

    // Validasi maksimal (hanya untuk mode tambah baru)
    if (!isEditMode && hasExceededMaximum(key)) {
      showSmallPopup(`❌ Sudah mencapai batas maksimal 5 tanggal untuk bulan ${formatMonthYear(mm.year, mm.month)}`, "warning");
      return;
    }

    if (!selectedDates[key]) selectedDates[key] = {};

    const bookedTimes = bookedMap[isoDate] || [];
    const available = HOURS.filter((h) => !bookedTimes.includes(h));

    const container = document.createElement("div");
    container.className = "time-picker-container";

    const totalWeeks = weekCountOfMonth(mm.year, mm.month);
    const isOptional = weekNum === 5 && totalWeeks === 5;

    const title = document.createElement("div");
    title.className = "time-picker-title";
    title.innerHTML = `${isEditMode ? "✏️ Edit Jam" : "Pilih Jam"}`;
    container.appendChild(title);

    const subtitle = document.createElement("div");
    subtitle.className = "time-picker-subtitle";
    subtitle.innerHTML = `Untuk tanggal ${isoDate} ${isOptional ? '<span style="color:#f59e0b">(Minggu 5 - Opsional/Bonus)</span>' : `<span style="color:#10b981">(Minggu ${weekNum})</span>`}`;
    container.appendChild(subtitle);

    if (available.length === 0) {
      const warning = document.createElement("div");
      warning.style.background = "#fef2f2";
      warning.style.color = "#dc2626";
      warning.style.padding = "16px";
      warning.style.borderRadius = "12px";
      warning.style.textAlign = "center";
      warning.style.border = "1px solid #fecaca";
      warning.innerHTML = `
        <div style="font-size:18px;margin-bottom:8px">😔</div>
        <strong>Slot Penuh</strong>
        <div style="font-size:14px;margin-top:4px">Tidak ada jam tersedia untuk tanggal ini</div>
      `;
      container.appendChild(warning);

      const backBtn = document.createElement("button");
      backBtn.type = "button";
      backBtn.className = "btn outline";
      backBtn.textContent = "Kembali ke Kalender";
      backBtn.style.marginTop = "16px";
      backBtn.style.width = "100%";

      backBtn.onclick = function () {
        smallPopup.setAttribute("aria-hidden", "true");
        setTimeout(() => openMonthPicker(monthIndex), 300);
      };

      container.appendChild(backBtn);
    } else {
      const select = document.createElement("select");
      select.className = "time-select";
      select.style.width = "100%";

      const placeholder = document.createElement("option");
      placeholder.value = "";
      placeholder.textContent = isEditMode ? "-- Pilih Jam Baru --" : "-- Pilih Jam --";
      select.appendChild(placeholder);

      available.forEach((h) => {
        const opt = document.createElement("option");
        opt.value = h;
        opt.textContent = `${h} - ${addHourToTime(h)}`;
        select.appendChild(opt);
      });

      // Check if this date already selected (untuk edit mode)
      const existingSelection = Object.values(selectedDates[key]).find((item) => item.date === isoDate);
      if (existingSelection) {
        select.value = existingSelection.time;
      }

      container.appendChild(select);

      const actions = document.createElement("div");
      actions.className = "time-picker-actions";

      const btnCancel = document.createElement("button");
      btnCancel.type = "button";
      btnCancel.className = "btn outline";
      btnCancel.textContent = "Batal";

      btnCancel.onclick = function () {
        smallPopup.setAttribute("aria-hidden", "true");
        setTimeout(() => openMonthPicker(monthIndex), 300);
      };

      // TAMBAH TOMBOL HAPUS JIKA EDIT MODE ATAU SUDAH ADA SELECTION
      if (isEditMode || existingSelection) {
        const btnDelete = document.createElement("button");
        btnDelete.type = "button";
        btnDelete.className = "btn error";
        btnDelete.textContent = "🗑️ Hapus";

        btnDelete.onclick = function () {
          // Hapus dari selectedDates
          Object.keys(selectedDates[key]).forEach((selKey) => {
            if (selectedDates[key][selKey] && selectedDates[key][selKey].date === isoDate) {
              delete selectedDates[key][selKey];
            }
          });

          // UPDATE REAL-TIME SECTION B
          updateScheduleDisplayRealTime();

          smallPopup.setAttribute("aria-hidden", "true");
          setTimeout(() => openMonthPicker(monthIndex), 300);
        };

        actions.appendChild(btnDelete);
      }

      const btnSave = document.createElement("button");
      btnSave.type = "button";
      btnSave.className = "btn primary";
      btnSave.textContent = isEditMode ? "💾 Update Jam" : "💾 Simpan Jam";

      btnSave.onclick = function () {
        if (!select.value) {
          showSmallPopup("Pilih jam terlebih dahulu", "warning");
          select.focus();
          return;
        }

        // Remove any existing selection for this date
        Object.keys(selectedDates[key]).forEach((selKey) => {
          if (selectedDates[key][selKey] && selectedDates[key][selKey].date === isoDate) {
            delete selectedDates[key][selKey];
          }
        });

        // Save selection dengan key unik
        const uniqueKey = Date.now().toString();
        selectedDates[key][uniqueKey] = {
          date: isoDate,
          time: select.value,
        };

        // UPDATE REAL-TIME SECTION B
        updateScheduleDisplayRealTime();

        smallPopup.setAttribute("aria-hidden", "true");
        setTimeout(() => openMonthPicker(monthIndex), 300);
      };

      actions.appendChild(btnCancel);
      actions.appendChild(btnSave);
      container.appendChild(actions);
    }

    // Show in small popup
    if (smallPopupMessage) {
      smallPopupMessage.innerHTML = "";
      smallPopupMessage.appendChild(container);
      smallPopup.setAttribute("aria-hidden", "false");
    }
  }

  function addHourToTime(timeStr) {
    const [hours, minutes] = timeStr.split(":").map(Number);
    const newHours = (hours + 1) % 24;
    return `${String(newHours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}`;
  }

  // ----- Validation -----
  function validateA() {
    if (!nameInput.value.trim()) {
      showSmallPopup("Nama wajib diisi", "warning");
      nameInput.focus();
      return false;
    }

    if (!emailInput.value.trim()) {
      showSmallPopup("Email wajib diisi", "warning");
      emailInput.focus();
      return false;
    }

    formatEmail(emailInput);

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(emailInput.value)) {
      showSmallPopup("Format email tidak valid", "warning");
      emailInput.focus();
      return false;
    }

    if (!paket.value) {
      showSmallPopup("Pilih paket", "warning");
      paket.focus();
      return false;
    }

    if (!startMonth.value) {
      showSmallPopup("Pilih bulan mulai", "warning");
      startMonth.focus();
      return false;
    }

    if (!court.value) {
      showSmallPopup("Pilih lapangan", "warning");
      court.focus();
      return false;
    }

    return true;
  }

  function validateC() {
    if (!payment_method.value) {
      showSmallPopup("Pilih metode pembayaran", "warning");
      payment_method.focus();
      return false;
    }

    const bukti = document.getElementById("bukti");
    if (!bukti || !bukti.files || bukti.files.length === 0) {
      showSmallPopup("Upload bukti transfer wajib", "warning");
      bukti?.focus();
      return false;
    }

    const allowedTypes = ["image/jpeg", "image/png", "application/pdf"];
    if (!allowedTypes.includes(bukti.files[0].type)) {
      showSmallPopup("Bukti harus format JPG, PNG, atau PDF", "warning");
      return false;
    }

    const bankNum = document.getElementById("bank_from_number");
    if (bankNum && bankNum.value && !/^\d+$/.test(bankNum.value.replace(/\s/g, ""))) {
      showSmallPopup("Nomor rekening harus angka", "warning");
      bankNum.focus();
      return false;
    }

    if (!allMonthsHaveMinimum()) {
      showSmallPopup("Lengkapi pemilihan tanggal untuk semua bulan terlebih dahulu.", "warning");
      return false;
    }

    return true;
  }

  function allMonthsHaveMinimum() {
    for (let i = 0; i < monthList.length; i++) {
      const mm = monthList[i];
      const key = `${mm.year}-${String(mm.month).padStart(2, "0")}`;

      if (!selectedDates[key]) return false;

      const selectedCount = Object.keys(selectedDates[key]).length;
      if (selectedCount < 2) return false;
    }
    return true;
  }

  // ----- Initialize -----
  function initialize() {
    if (smallPopup) smallPopup.setAttribute("aria-hidden", "true");
    if (confirmPopup) confirmPopup.setAttribute("aria-hidden", "true");
    if (flowPopup) flowPopup.setAttribute("aria-hidden", "true");

    if (startMonth && !startMonth.value) {
      const now = new Date();
      startMonth.value = now.toISOString().slice(0, 7);
    }

    updatePrice();
    updatePaymentDetails();
  }

  // Start initialization
  initialize();
});
