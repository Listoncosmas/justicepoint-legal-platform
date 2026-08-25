(() => {
  const forms = document.querySelectorAll("[data-jp-consultation-form]");
  if (!forms.length) return;

  const query = new URLSearchParams(window.location.search);
  forms.forEach((form) => {
    form.querySelectorAll("[data-capture]").forEach((input) => {
      const key = input.dataset.capture;
      input.value =
        key === "landing_page"
          ? window.location.href
          : key === "referrer"
            ? document.referrer
            : query.get(key) || "";
    });

    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      const status = form.querySelector("[data-form-status]");
      const button = form.querySelector("[data-submit-button]");
      form.querySelectorAll("[data-error-for]").forEach((element) => {
        element.textContent = "";
      });
      form
        .querySelectorAll('[aria-invalid="true"]')
        .forEach((element) => element.removeAttribute("aria-invalid"));

      if (!form.checkValidity()) {
        const invalidFields = [...form.querySelectorAll(":invalid")];
        invalidFields.forEach((field) => {
          field.setAttribute("aria-invalid", "true");
          const error = form.querySelector(
            `[data-error-for="${CSS.escape(field.name)}"]`,
          );
          if (error) error.textContent = field.validationMessage;
        });
        form.reportValidity();
        status.textContent = "Please complete the required fields.";
        invalidFields[0]?.focus();
        return;
      }

      button.disabled = true;
      button.setAttribute("aria-busy", "true");
      status.textContent = "Securely sending your request…";
      try {
        const response = await fetch(form.dataset.endpoint, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(
            Object.fromEntries(new FormData(form).entries()),
          ),
        });
        const data = await response.json();
        if (!response.ok) {
          const fields = data?.data?.fields || {};
          Object.entries(fields).forEach(([name, message]) => {
            const field = form.elements.namedItem(name);
            const error = form.querySelector(
              `[data-error-for="${CSS.escape(name)}"]`,
            );
            if (field) field.setAttribute("aria-invalid", "true");
            if (error) error.textContent = message;
          });
          throw new Error(data.message || "The request could not be sent.");
        }
        form.reset();
        status.innerHTML = `<strong>Request confirmed.</strong> ${data.message}`;
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
          event: "justicepoint_form_success",
          form_id: "consultation",
          delivery_mode: data.mock ? "mock_crm" : "crm_webhook",
        });
      } catch (error) {
        status.textContent =
          error.message || "Something went wrong. Please call an office.";
      } finally {
        button.disabled = false;
        button.removeAttribute("aria-busy");
      }
    });
  });
})();
