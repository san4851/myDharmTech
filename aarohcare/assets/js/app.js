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
  appointmentForm.addEventListener("submit", (event) => {
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

    const formData = new FormData(appointmentForm);
    const appointmentRecord = {
      patientName: formData.get("patientName"),
      patientPhone: formData.get("patientPhone"),
      patientEmail: formData.get("patientEmail"),
      healthIssue: formData.get("healthIssue"),
      appointmentDate: formData.get("appointmentDate"),
      appointmentTime: formData.get("appointmentTime"),
      requestedAt: new Date().toISOString()
    };

    const existingRequests = JSON.parse(localStorage.getItem("aarohAppointments") || "[]");
    existingRequests.push(appointmentRecord);
    localStorage.setItem("aarohAppointments", JSON.stringify(existingRequests));

    appointmentSuccess.textContent = `${appointmentRecord.patientName}, your online consultation request for ${appointmentRecord.appointmentDate} at ${appointmentRecord.appointmentTime} has been captured. The Aaroh Care team will review the request and confirm the appointment.`;
    appointmentSuccess.classList.remove("d-none");

    appointmentForm.reset();
    appointmentForm.classList.remove("was-validated");
    appointmentDate.min = getNextAvailableDate();
    appointmentDate.setCustomValidity("");
  });
}
