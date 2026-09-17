document.getElementById("year").textContent = new Date().getFullYear();

const navToggle = document.getElementById("nav-toggle");
const mainNav = document.getElementById("main-nav");

navToggle.addEventListener("click", () => {
  const isOpen = mainNav.classList.toggle("open");
  navToggle.setAttribute("aria-expanded", String(isOpen));
});

mainNav.querySelectorAll("a").forEach((link) => {
  link.addEventListener("click", () => {
    mainNav.classList.remove("open");
    navToggle.setAttribute("aria-expanded", "false");
  });
});

const form = document.getElementById("contact-form");
const note = document.getElementById("form-note");

form.addEventListener("submit", async (event) => {
  event.preventDefault();
  note.hidden = true;
  note.className = "form-note";

  const submitBtn = form.querySelector("button[type=submit]");
  submitBtn.disabled = true;

  try {
    const response = await fetch(form.action, {
      method: "POST",
      body: new FormData(form),
      headers: { Accept: "application/json" },
    });

    if (!response.ok) throw new Error("request-failed");

    form.reset();
    note.textContent = "Merci, votre demande a bien été envoyée. Nous revenons vers vous rapidement.";
    note.classList.add("success");
  } catch (err) {
    note.textContent = "Une erreur est survenue. Contactez-nous directement à tdevgreen@gmail.com.";
    note.classList.add("error");
  } finally {
    note.hidden = false;
    submitBtn.disabled = false;
  }
});
