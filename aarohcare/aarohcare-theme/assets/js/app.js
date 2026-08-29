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
