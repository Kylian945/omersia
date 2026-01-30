document.addEventListener("DOMContentLoaded", () => {
    function getToastContainer() {
        let container = document.getElementById("toast-container");

        // Si pas présent dans le DOM, on le crée (optionnel mais safe)
        if (!container) {
            container = document.createElement("div");
            container.id = "toast-container";
            container.className =
                "fixed bottom-4 left-1/2 -translate-x-1/2 flex flex-col gap-2 z-50";
            document.body.appendChild(container);
        }

        return container;
    }

    function show(message) {
        const toastContainer = getToastContainer();
        if (!toastContainer) return;

        const el = document.createElement("div");
        el.className =
            "animate-fade-in rounded-full bg-black text-white text-xs px-4 py-1.5 shadow-lg";
        el.textContent = message;

        toastContainer.appendChild(el);

        setTimeout(() => {
            el.style.opacity = "0";
            el.style.transform = "translateY(4px)";
            el.style.transition = "all 150ms ease-out";
            setTimeout(() => el.remove(), 180);
        }, 1600);
    }

    // 🔥 On expose en global
    window.showToast = show;
});
