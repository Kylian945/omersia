document.addEventListener("DOMContentLoaded", () => {
    function safeShowToast(message) {
        if (typeof window.showToast === "function") {
            window.showToast(message);
        }
    }

    function doCopy(value, successMsg = "Copié dans le presse-papiers") {
        if (!value) return;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard
                .writeText(value)
                .then(() => safeShowToast(successMsg))
                .catch(() => fallbackCopy(value, successMsg));
        } else {
            fallbackCopy(value, successMsg);
        }
    }

    function fallbackCopy(value, successMsg) {
        const textarea = document.createElement("textarea");
        textarea.value = value;
        textarea.style.position = "fixed";
        textarea.style.left = "-9999px";
        document.body.appendChild(textarea);
        textarea.select();

        try {
            document.execCommand("copy");
            safeShowToast(successMsg);
        } catch (e) {
            console.error("Copy failed", e);
        }

        document.body.removeChild(textarea);
    }

    // Exposé global pour les onclick des Blade
    window.copyToClipboard = function (value) {
        doCopy(value, "Clé copiée dans le presse-papiers");
    };

    window.copyNewApiKey = function () {
        const input = document.getElementById("new-api-key-input");
        if (!input) return;
        const value = input.value || "";
        doCopy(value, "Nouvelle clé copiée dans le presse-papiers");
    };
});
