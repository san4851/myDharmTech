const appointmentForm = document.getElementById("appointmentForm");
const appointmentDate = document.getElementById("appointmentDate");
const appointmentSuccess = document.getElementById("appointmentSuccess");

function getNextAvailableDate() {
  const date = new Date();

  if (date.getDay() === 0) {
    date.setDate(date.getDate() + 1);
  }

  return date.toISOString().split("T")[0];
}

function isSunday(dateString) {
  return new Date(dateString).getDay() === 0;
}

if (appointmentDate) {
  appointmentDate.min = getNextAvailableDate();
  appointmentDate.addEventListener("input", () => {
    if (isSunday(appointmentDate.value)) {
      appointmentDate.setCustomValidity("Consultations are available Monday to Saturday only.");
    } else {
      appointmentDate.setCustomValidity("");
    }
  });
}

if (appointmentForm) {
  appointmentForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    event.stopPropagation();

    if (appointmentDate && isSunday(appointmentDate.value)) {
      appointmentDate.setCustomValidity("Consultations are available Monday to Saturday only.");
    } else if (appointmentDate) {
      appointmentDate.setCustomValidity("");
    }

    if (!appointmentForm.checkValidity()) {
      appointmentForm.classList.add("was-validated");
      return;
    }

    const config = window.aarohAppointment || {};
    const formData = new FormData(appointmentForm);
    formData.append("action", "aaroh_submit_appointment");
    formData.append("nonce", config.nonce || "");

    try {
      const response = await fetch(config.ajaxUrl, {
        method: "POST",
        credentials: "same-origin",
        body: formData
      });
      const payload = await response.json();
      const ok = payload && payload.success;
      const message = ok
        ? payload.data.message
        : (payload && payload.data && payload.data.message) || "Unable to submit the appointment request.";

      if (!ok) {
        appointmentSuccess.classList.add("d-none");
        appointmentForm.classList.add("was-validated");
        return;
      }

      appointmentSuccess.textContent = message;
      appointmentSuccess.classList.remove("d-none");
      appointmentForm.reset();
      appointmentForm.classList.remove("was-validated");
      if (appointmentDate) {
        appointmentDate.min = getNextAvailableDate();
        appointmentDate.setCustomValidity("");
      }
    } catch (error) {
      appointmentSuccess.classList.add("d-none");
    }
  });
}


function initArticleCarousel() {
  const root = document.querySelector("[data-article-carousel]");
  if (!root) {
    return;
  }

  const track = root.querySelector(".article-carousel-track");
  const section = root.closest(".article-carousel-section");
  const prev = section ? section.querySelector(".article-carousel-prev") : null;
  const next = section ? section.querySelector(".article-carousel-next") : null;

  if (!track || !prev || !next) {
    return;
  }

  const step = () => {
    const slide = track.querySelector(".article-carousel-slide");
    if (!slide) {
      return 320;
    }
    const styles = window.getComputedStyle(track);
    const gap = parseFloat(styles.columnGap || styles.gap || "16") || 16;
    return slide.getBoundingClientRect().width + gap;
  };

  const updateButtons = () => {
    const max = track.scrollWidth - track.clientWidth - 4;
    prev.disabled = track.scrollLeft <= 4;
    next.disabled = track.scrollLeft >= max;
  };

  prev.addEventListener("click", () => {
    track.scrollBy({ left: -step(), behavior: "smooth" });
  });

  next.addEventListener("click", () => {
    track.scrollBy({ left: step(), behavior: "smooth" });
  });

  track.addEventListener("scroll", updateButtons, { passive: true });
  window.addEventListener("resize", updateButtons);
  updateButtons();
}

initArticleCarousel();
