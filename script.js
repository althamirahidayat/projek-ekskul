document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector(".signup-form");
  const inputs = document.querySelectorAll(".form-input");
  const submitBtn = document.querySelector(".btn-submit");

  submitBtn.addEventListener("click", (e) => {
    let allFilled = true;
    inputs.forEach((input) => {
      if (input.value.trim() === "") {
        allFilled = false;
        input.classList.add("error");
      } else {
        input.classList.remove("error");
      }
    });

    if (!allFilled) {
      e.preventDefault(); // hanya hentikan submit kalau ada field kosong
      alert("⚠️ Harap isi semua data terlebih dahulu!");
      return;
    }

    // Form langsung submit ke PHP tanpa loader
  });

  // Animasi hover logo
  const logo = document.querySelector(".logo");
  logo.addEventListener("mouseover", () => {
    logo.style.transform = "scale(1.08) rotate(10deg)";
    logo.style.transition = "0.3s";
  });
  logo.addEventListener("mouseout", () => {
    logo.style.transform = "scale(1) rotate(0)";
  });

  // Tombol mode gelap / terang
  const modeBtn = document.createElement("button");
  modeBtn.textContent = "🌙 Mode Gelap";
  modeBtn.id = "modeBtn";
  modeBtn.style.position = "fixed";
  modeBtn.style.top = "20px";
  modeBtn.style.right = "20px";
  modeBtn.style.padding = "8px 14px";
  modeBtn.style.border = "none";
  modeBtn.style.borderRadius = "20px";
  modeBtn.style.background = "#fff";
  modeBtn.style.cursor = "pointer";
  modeBtn.style.fontWeight = "600";
  modeBtn.style.boxShadow = "0 3px 8px rgba(0,0,0,0.25)";
  document.body.appendChild(modeBtn);

  modeBtn.addEventListener("click", () => {
    document.body.classList.toggle("dark-mode");
    const dark = document.body.classList.contains("dark-mode");
    modeBtn.textContent = dark ? "☀️ Mode Terang" : "🌙 Mode Gelap";
  });

  // Counter karakter nama
  const namaInput = document.getElementById("nama");
  const counter = document.createElement("small");
  counter.style.color = "#e0f2ff";
  counter.style.fontSize = "0.8rem";
  counter.style.display = "block";
  counter.style.marginTop = "4px";
  namaInput.insertAdjacentElement("afterend", counter);

  namaInput.addEventListener("input", () => {
    counter.textContent = `Panjang nama: ${namaInput.value.length} karakter`;
  });
});
