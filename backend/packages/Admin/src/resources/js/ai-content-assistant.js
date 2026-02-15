document.addEventListener("DOMContentLoaded", () => {
    const triggerButtons = Array.from(
        document.querySelectorAll("[data-ai-content-open-modal]")
    );

    if (triggerButtons.length === 0) {
        return;
    }

    const modal = document.getElementById("content-ai-prompt-modal");
    const closeButton = document.getElementById("content-ai-modal-close-button");
    const cancelButton = document.getElementById("content-ai-modal-cancel-button");
    const submitButton = document.getElementById("content-ai-modal-submit-button");
    const promptInput = document.getElementById("content-ai-prompt-input");
    const modalError = document.getElementById("content-ai-modal-error");
    const targetLabel = document.getElementById("content-ai-target-label");

    if (
        !modal ||
        !submitButton ||
        !(promptInput instanceof HTMLTextAreaElement)
    ) {
        return;
    }

    let activeContext = "";
    let activeTargetField = "";
    let activeTargetLabel = "";
    let activeEndpoint = "";
    let activeForm = null;
    const aiProviderReady = document.body?.dataset.aiProviderReady === "1";

    const setStatus = (message, type = "info") => {
        if (typeof window.showToast === "function") {
            window.showToast(message, type);
        }
    };

    const getProviderHintMessage = () =>
        "Aucun provider IA actif. Configurez-en un dans Paramètres > IA.";

    const ensureProviderConfigured = () => {
        if (aiProviderReady) {
            return true;
        }

        const message = getProviderHintMessage();
        setStatus(message, "info");
        setModalError(message);

        return false;
    };

    if (!aiProviderReady) {
        const message = getProviderHintMessage();
        triggerButtons.forEach((button) => {
            button.title = message;
        });
    }

    const setModalError = (message = "") => {
        if (!modalError) {
            return;
        }

        if (message.trim() === "") {
            modalError.classList.add("hidden");
            modalError.textContent = "";
            return;
        }

        modalError.classList.remove("hidden");
        modalError.textContent = message;
    };

    const showModal = () => {
        modal.classList.remove("hidden");
        modal.classList.add("flex");
        document.body.classList.add("overflow-hidden");
    };

    const hideModal = () => {
        modal.classList.add("hidden");
        modal.classList.remove("flex");
        document.body.classList.remove("overflow-hidden");
    };

    const toggleControls = (isDisabled) => {
        triggerButtons.forEach((button) => {
            button.disabled = isDisabled;
        });
        submitButton.disabled = isDisabled;
    };

    const readFieldValue = (form, fieldName) => {
        if (!(form instanceof HTMLFormElement)) {
            return "";
        }

        const field = form.querySelector(`[name="${fieldName}"]`);
        if (!field || !("value" in field)) {
            return "";
        }

        return typeof field.value === "string" ? field.value : "";
    };

    const writeFieldValue = (form, fieldName, nextValue) => {
        if (!(form instanceof HTMLFormElement)) {
            return false;
        }

        const field = form.querySelector(`[name="${fieldName}"]`);
        if (!field || !("value" in field)) {
            return false;
        }

        field.value = nextValue;
        field.dispatchEvent(new Event("input", { bubbles: true }));
        field.dispatchEvent(new Event("change", { bubbles: true }));

        return true;
    };

    const openPromptModal = ({ button }) => {
        if (!ensureProviderConfigured()) {
            return;
        }

        activeForm = button.closest("form");
        activeContext = (button.dataset.aiContentContext || "").trim();
        activeTargetField = (button.dataset.aiContentTarget || "").trim();
        activeTargetLabel = (
            button.dataset.aiContentTargetLabel || "Champ"
        ).trim();
        activeEndpoint = (button.dataset.aiContentGenerateUrl || "").trim();

        if (targetLabel) {
            targetLabel.textContent = activeTargetLabel;
        }

        setModalError("");
        showModal();

        window.setTimeout(() => {
            promptInput.focus();
            const length = promptInput.value.length;
            if (length > 0) {
                promptInput.setSelectionRange(length, length);
            }
        }, 40);
    };

    const runGeneration = async ({ prompt }) => {
        if (!ensureProviderConfigured()) {
            return;
        }

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");

        if (!csrfToken || activeEndpoint === "") {
            setStatus("Configuration IA incomplète.", "error");
            return;
        }

        const payload = {
            prompt,
            context: activeContext,
            target_field: activeTargetField,
            name: readFieldValue(activeForm, "name"),
            title: readFieldValue(activeForm, "title"),
            description: readFieldValue(activeForm, "description"),
            meta_title: readFieldValue(activeForm, "meta_title"),
            meta_description: readFieldValue(activeForm, "meta_description"),
            slug: readFieldValue(activeForm, "slug"),
            type: readFieldValue(activeForm, "type"),
            locale: readFieldValue(activeForm, "locale"),
        };

        toggleControls(true);
        setStatus("Génération IA en cours...", "info");

        try {
            const response = await fetch(activeEndpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify(payload),
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(
                    result.message || "La génération IA a échoué."
                );
            }

            const generatedValue = result?.data?.[activeTargetField];
            if (typeof generatedValue !== "string") {
                throw new Error("Réponse IA invalide.");
            }

            const fieldUpdated = writeFieldValue(
                activeForm,
                activeTargetField,
                generatedValue
            );

            if (!fieldUpdated) {
                throw new Error(
                    "Champ cible introuvable dans le formulaire."
                );
            }

            setStatus(
                `${activeTargetLabel} généré. Vérifiez le contenu avant d'enregistrer.`,
                "success"
            );
        } catch (error) {
            const message =
                error instanceof Error
                    ? error.message
                    : "Erreur inconnue pendant la génération IA.";
            setStatus(message, "error");
        } finally {
            toggleControls(false);
        }
    };

    triggerButtons.forEach((button) => {
        button.addEventListener("click", () => openPromptModal({ button }));
    });

    submitButton.addEventListener("click", async () => {
        if (!ensureProviderConfigured()) {
            return;
        }

        const prompt = promptInput.value.trim();

        if (prompt.length < 3) {
            setModalError("Saisissez un prompt d'au moins 3 caractères.");
            return;
        }

        if (activeContext === "" || activeTargetField === "" || activeEndpoint === "") {
            setModalError("Configuration IA invalide.");
            return;
        }

        setModalError("");
        hideModal();

        await runGeneration({ prompt });
    });

    if (closeButton) {
        closeButton.addEventListener("click", hideModal);
    }

    if (cancelButton) {
        cancelButton.addEventListener("click", hideModal);
    }

    modal.addEventListener("click", (event) => {
        if (event.target === modal) {
            hideModal();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && !modal.classList.contains("hidden")) {
            hideModal();
        }
    });
});
