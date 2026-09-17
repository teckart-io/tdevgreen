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

function bindAjaxForm(formId, noteId, successMessage) {
  const form = document.getElementById(formId);
  const note = document.getElementById(noteId);
  if (!form) return;

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
      note.textContent = successMessage;
      note.classList.add("success");
    } catch (err) {
      note.textContent = "Une erreur est survenue. Contactez-nous directement à tdevgreen@gmail.com.";
      note.classList.add("error");
    } finally {
      note.hidden = false;
      submitBtn.disabled = false;
    }
  });
}

bindAjaxForm(
  "contact-form",
  "form-note",
  "Merci, votre demande a bien été envoyée. Nous revenons vers vous rapidement."
);
bindAjaxForm(
  "devis-form",
  "devis-form-note",
  "Merci, votre demande de devis a bien été envoyée. Nous revenons vers vous rapidement."
);

/* Modale "Demander un devis" */
const devisModal = document.getElementById("devis-modal");

function openDevisModal() {
  devisModal.classList.add("is-open");
  devisModal.setAttribute("aria-hidden", "false");
  document.body.style.overflow = "hidden";
}

function closeDevisModal() {
  devisModal.classList.remove("is-open");
  devisModal.setAttribute("aria-hidden", "true");
  document.body.style.overflow = "";
}

document.querySelectorAll("[data-open-devis]").forEach((btn) => {
  btn.addEventListener("click", openDevisModal);
});
devisModal.querySelectorAll("[data-close-devis]").forEach((btn) => {
  btn.addEventListener("click", closeDevisModal);
});

/* Lightbox galerie "Réalisations" */
const galleryItems = Array.from(document.querySelectorAll("#gallery-grid .gallery-item"));
const lightbox = document.getElementById("lightbox");
const lightboxImg = document.getElementById("lightbox-img");
const lightboxCaption = document.getElementById("lightbox-caption");
let lightboxIndex = 0;

function showLightboxImage(index) {
  lightboxIndex = (index + galleryItems.length) % galleryItems.length;
  const item = galleryItems[lightboxIndex];
  const img = item.querySelector("img");
  lightboxImg.src = img.src;
  lightboxImg.alt = img.alt;
  lightboxCaption.textContent = item.querySelector("figcaption").textContent;
}

function openLightbox(index) {
  showLightboxImage(index);
  lightbox.classList.add("is-open");
  lightbox.setAttribute("aria-hidden", "false");
  document.body.style.overflow = "hidden";
}

function closeLightbox() {
  lightbox.classList.remove("is-open");
  lightbox.setAttribute("aria-hidden", "true");
  lightboxImg.src = "";
  document.body.style.overflow = "";
}

galleryItems.forEach((item, index) => {
  item.addEventListener("click", () => openLightbox(index));
  item.addEventListener("keydown", (event) => {
    if (event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      openLightbox(index);
    }
  });
});

lightbox.querySelectorAll("[data-close-lightbox]").forEach((btn) => {
  btn.addEventListener("click", closeLightbox);
});
document.getElementById("lightbox-prev").addEventListener("click", () => showLightboxImage(lightboxIndex - 1));
document.getElementById("lightbox-next").addEventListener("click", () => showLightboxImage(lightboxIndex + 1));

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    if (lightbox.classList.contains("is-open")) closeLightbox();
    if (devisModal.classList.contains("is-open")) closeDevisModal();
  }
  if (lightbox.classList.contains("is-open")) {
    if (event.key === "ArrowRight") showLightboxImage(lightboxIndex + 1);
    if (event.key === "ArrowLeft") showLightboxImage(lightboxIndex - 1);
  }
});
