const toggleBtn = document.getElementById("btnToggleSidebar");
const sidebar = document.querySelector(".sidebar");
const content = document.querySelector(".content");
if (toggleBtn) {
    toggleBtn.addEventListener("click", () => {
        sidebar.classList.toggle("collapsed");
        content.classList.toggle("expanded");
    });
}
