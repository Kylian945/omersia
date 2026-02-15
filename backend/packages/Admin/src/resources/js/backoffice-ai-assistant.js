document.addEventListener("DOMContentLoaded", () => {
    const root = document.getElementById("bo-ai-assistant");
    if (!root) {
        return;
    }

    const endpoint = (root.dataset.endpoint || "").trim();
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");

    const drawer = root.querySelector("[data-ai-assistant-drawer]");
    const backdrop = root.querySelector("[data-ai-assistant-backdrop]");
    const messagesContainer = root.querySelector("[data-ai-assistant-messages]");
    const emptyState = root.querySelector("[data-ai-assistant-empty]");
    const form = root.querySelector("[data-ai-assistant-form]");
    const input = root.querySelector("[data-ai-assistant-input]");
    const sendButton = root.querySelector("[data-ai-assistant-send]");
    const statusNode = root.querySelector("[data-ai-assistant-status]");
    const resetButton = root.querySelector("[data-ai-assistant-reset]");
    const providerHint = root.querySelector("[data-ai-assistant-provider-hint]");

    if (
        !(drawer instanceof HTMLElement) ||
        !(backdrop instanceof HTMLElement) ||
        !(messagesContainer instanceof HTMLElement) ||
        !(form instanceof HTMLFormElement) ||
        !(input instanceof HTMLTextAreaElement) ||
        !(sendButton instanceof HTMLButtonElement)
    ) {
        return;
    }

    const STORAGE_KEY = "omersia:bo-ai-assistant:messages:v1";
    const openButtons = Array.from(
        document.querySelectorAll("[data-ai-assistant-toggle]")
    );
    const closeButtons = Array.from(
        root.querySelectorAll("[data-ai-assistant-close]")
    );
    const quickQuestionButtons = Array.from(
        root.querySelectorAll("[data-ai-assistant-quick-question]")
    );
    const searchInputs = Array.from(
        document.querySelectorAll("[data-ai-assistant-search-input]")
    );
    const aiProviderReady =
        (root.dataset.providerReady ||
            document.body?.dataset.aiProviderReady ||
            "") === "1";
    let hasShownProviderToast = false;

    let isOpen = false;
    let isLoading = false;
    let messages = loadMessages();

    function getProviderHintMessage() {
        return "Aucun provider IA actif. Configurez-en un dans Paramètres > IA.";
    }

    function ensureProviderConfigured({ notify = true } = {}) {
        if (aiProviderReady) {
            return true;
        }

        const message = getProviderHintMessage();
        setStatus(message, "info");

        if (providerHint instanceof HTMLElement) {
            providerHint.classList.remove("hidden");
        }

        if (
            notify &&
            !hasShownProviderToast &&
            typeof window.showToast === "function"
        ) {
            window.showToast(message, "info");
            hasShownProviderToast = true;
        }

        return false;
    }

    if (!aiProviderReady) {
        const message = getProviderHintMessage();
        openButtons.forEach((button) => {
            button.title = message;
        });

        searchInputs.forEach((searchInput) => {
            if (searchInput instanceof HTMLInputElement) {
                searchInput.title = message;
            }
        });
    }

    function loadMessages() {
        try {
            const raw = window.localStorage.getItem(STORAGE_KEY);
            if (!raw) {
                return [];
            }

            const parsed = JSON.parse(raw);
            if (!Array.isArray(parsed)) {
                return [];
            }

            return parsed
                .filter((entry) => {
                    return (
                        entry &&
                        typeof entry === "object" &&
                        ["user", "assistant"].includes(entry.role) &&
                        typeof entry.content === "string" &&
                        entry.content.trim() !== ""
                    );
                })
                .slice(-30);
        } catch (_error) {
            return [];
        }
    }

    function persistMessages() {
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(messages));
        } catch (_error) {
            // Ignore storage errors (private mode / quota).
        }
    }

    function setStatus(message = "", type = "info") {
        if (!(statusNode instanceof HTMLElement)) {
            return;
        }

        const trimmed = message.trim();
        if (trimmed === "") {
            statusNode.classList.add("hidden");
            statusNode.textContent = "";
            statusNode.classList.remove(
                "text-red-600",
                "text-emerald-600",
                "text-slate-500"
            );
            return;
        }

        statusNode.textContent = trimmed;
        statusNode.classList.remove("hidden");
        statusNode.classList.remove(
            "text-red-600",
            "text-emerald-600",
            "text-slate-500"
        );

        if (type === "error") {
            statusNode.classList.add("text-red-600");
            return;
        }

        if (type === "success") {
            statusNode.classList.add("text-emerald-600");
            return;
        }

        statusNode.classList.add("text-slate-500");
    }

    function renderMessages() {
        messagesContainer.innerHTML = "";

        if (messages.length === 0) {
            if (emptyState instanceof HTMLElement) {
                emptyState.classList.remove("hidden");
            }
            return;
        }

        if (emptyState instanceof HTMLElement) {
            emptyState.classList.add("hidden");
        }

        messages.forEach((message) => {
            const wrapper = document.createElement("div");
            wrapper.className = `bo-ai-assistant-message ${
                message.role === "user"
                    ? "bo-ai-assistant-message--user"
                    : "bo-ai-assistant-message--assistant"
            }`;

            const bubble = document.createElement("div");
            bubble.className = "bo-ai-assistant-bubble";
            bubble.textContent = message.content;

            wrapper.appendChild(bubble);
            messagesContainer.appendChild(wrapper);
        });

        window.requestAnimationFrame(() => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        });
    }

    function pushMessage(role, content) {
        const nextContent = String(content || "").trim();
        if (nextContent === "") {
            return;
        }

        messages.push({
            role,
            content: nextContent,
            created_at: new Date().toISOString(),
        });

        if (messages.length > 30) {
            messages = messages.slice(-30);
        }

        persistMessages();
        renderMessages();
    }

    function setLoading(loading) {
        isLoading = loading;

        if (!aiProviderReady) {
            sendButton.disabled = true;
            sendButton.textContent = "Configurer IA";
            input.disabled = true;
            return;
        }

        sendButton.disabled = loading;
        sendButton.textContent = loading ? "Envoi..." : "Envoyer";
        input.disabled = loading;
    }

    function setOpen(nextOpen) {
        isOpen = nextOpen;

        if (nextOpen) {
            root.classList.remove("pointer-events-none");
            backdrop.classList.remove("opacity-0");
            backdrop.classList.add("opacity-100");
            drawer.classList.remove("translate-x-full");
            root.setAttribute("aria-hidden", "false");
            document.body.classList.add("overflow-hidden");

            if (!aiProviderReady) {
                ensureProviderConfigured({ notify: false });
                return;
            }

            window.setTimeout(() => {
                input.focus();
            }, 60);

            return;
        }

        backdrop.classList.add("opacity-0");
        backdrop.classList.remove("opacity-100");
        drawer.classList.add("translate-x-full");
        root.setAttribute("aria-hidden", "true");
        document.body.classList.remove("overflow-hidden");
        root.classList.add("pointer-events-none");
    }

    async function sendMessage(text) {
        const message = String(text || "").trim();

        if (isLoading) {
            return;
        }

        if (!ensureProviderConfigured()) {
            return;
        }

        if (message.length < 3) {
            setStatus("Saisissez une question d’au moins 3 caractères.", "error");
            return;
        }

        if (endpoint === "" || !csrfToken) {
            setStatus("Configuration IA incomplète.", "error");
            return;
        }

        setStatus("");
        pushMessage("user", message);
        setLoading(true);

        try {
            const response = await fetch(endpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({
                    message,
                    history: messages.slice(-12).map((entry) => ({
                        role: entry.role,
                        content: entry.content,
                    })),
                }),
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(
                    payload.message ||
                        "L’assistant IA n’a pas pu traiter votre question."
                );
            }

            const reply = payload?.data?.reply;
            if (typeof reply !== "string" || reply.trim() === "") {
                throw new Error("Réponse IA invalide.");
            }

            pushMessage("assistant", reply.trim());
            setStatus("", "success");
        } catch (error) {
            const messageError =
                error instanceof Error
                    ? error.message
                    : "Erreur inconnue pendant la conversation IA.";

            setStatus(messageError, "error");
            pushMessage(
                "assistant",
                `Je n’ai pas pu répondre pour le moment. ${messageError}`
            );
        } finally {
            setLoading(false);
        }
    }

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (!ensureProviderConfigured()) {
            return;
        }

        const text = input.value.trim();
        if (text === "") {
            return;
        }

        input.value = "";
        await sendMessage(text);
    });

    openButtons.forEach((button) => {
        button.addEventListener("click", () => {
            setOpen(true);
            ensureProviderConfigured();
        });
    });

    closeButtons.forEach((button) => {
        button.addEventListener("click", () => {
            setOpen(false);
        });
    });

    if (resetButton instanceof HTMLButtonElement) {
        resetButton.addEventListener("click", () => {
            messages = [];
            persistMessages();
            renderMessages();
            setStatus("Conversation réinitialisée.", "success");
        });
    }

    quickQuestionButtons.forEach((button) => {
        button.addEventListener("click", async () => {
            if (!ensureProviderConfigured()) {
                setOpen(true);
                return;
            }

            const question = (button.dataset.aiAssistantQuickQuestion || "").trim();
            if (question === "") {
                return;
            }

            setOpen(true);
            await sendMessage(question);
        });
    });

    searchInputs.forEach((searchInput) => {
        if (!(searchInput instanceof HTMLInputElement)) {
            return;
        }

        searchInput.addEventListener("keydown", async (event) => {
            if (event.key !== "Enter") {
                return;
            }

            event.preventDefault();

            if (!ensureProviderConfigured()) {
                setOpen(true);
                return;
            }

            const question = searchInput.value.trim();
            if (question === "") {
                setOpen(true);
                return;
            }

            searchInput.value = "";
            setOpen(true);
            await sendMessage(question);
        });
    });

    backdrop.addEventListener("click", () => {
        setOpen(false);
    });

    document.addEventListener("keydown", (event) => {
        const key = event.key.toLowerCase();
        if ((event.metaKey || event.ctrlKey) && key === "k") {
            event.preventDefault();
            setOpen(true);
            ensureProviderConfigured({ notify: false });
            return;
        }

        if (event.key === "Escape" && isOpen) {
            setOpen(false);
        }
    });

    renderMessages();
    setLoading(false);
});
