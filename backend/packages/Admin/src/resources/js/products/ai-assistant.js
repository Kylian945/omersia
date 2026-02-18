export const initProductAiAssistant = ({
    syncWysiwygEditors,
    setFormFieldValue,
    mainImageInput,
    createInput,
    syncVariantImageChoices,
    aiGeneratedPreviewContainer,
    aiGeneratedInputsContainer,
}) => {
    const aiOpenModalButtons = Array.from(
        document.querySelectorAll("[data-ai-open-modal]")
    );
    const aiPromptModal = document.getElementById("product-ai-prompt-modal");
    const aiModalCloseButton = document.getElementById(
        "product-ai-modal-close-button"
    );
    const aiModalCancelButton = document.getElementById(
        "product-ai-modal-cancel-button"
    );
    const aiModalSubmitButton = document.getElementById(
        "product-ai-modal-submit-button"
    );
    const aiPromptInput = document.getElementById("product-ai-prompt-input");
    const aiModalError = document.getElementById("product-ai-modal-error");
    const aiTargetLabel = document.getElementById("product-ai-target-label");

    const aiImageGenerateButtons = Array.from(
        document.querySelectorAll("[data-ai-image-generate-button]")
    );
    const aiImagePromptModal = document.getElementById(
        "product-ai-image-prompt-modal"
    );
    const aiImageModalCloseButton = document.getElementById(
        "product-ai-image-modal-close-button"
    );
    const aiImageModalCancelButton = document.getElementById(
        "product-ai-image-modal-cancel-button"
    );
    const aiImageModalSubmitButton = document.getElementById(
        "product-ai-image-modal-submit-button"
    );
    const aiImagePromptInput = document.getElementById(
        "product-ai-image-prompt-input"
    );
    const aiImageModalError = document.getElementById(
        "product-ai-image-modal-error"
    );
    const aiImageReferenceOptions = document.getElementById(
        "product-ai-image-reference-options"
    );
    const aiImageReferenceEmpty = document.getElementById(
        "product-ai-image-reference-empty"
    );
    const aiImageLoadingState = document.getElementById("product-ai-image-loading");

    const resolvedPreviewContainer =
        aiGeneratedPreviewContainer ??
        document.getElementById("ai-generated-images-preview");
    const resolvedInputsContainer =
        aiGeneratedInputsContainer ??
        document.getElementById("ai-generated-images-inputs");

    const productForm = document.querySelector("form[data-product-id]");
    const productIdRaw = (productForm?.dataset.productId || "").trim();
    const productId = /^\d+$/.test(productIdRaw)
        ? Number.parseInt(productIdRaw, 10)
        : null;

    let activeAiField = null;
    let activeAiFieldLabel = "";
    let activeAiEndpoint = "";
    let activeAiImageEndpoint = "";
    let activeAiImageReferenceId = null;
    let aiImageRequireReference = false;
    let aiImageIsGenerating = false;
    const aiProviderReady = document.body?.dataset.aiProviderReady === "1";

    const getAiProviderHintMessage = () =>
        "Aucun provider IA actif. Configurez-en un dans Paramètres > IA.";

    const setAiStatus = (message, type = "info") => {
        if (typeof window.showToast === "function") {
            window.showToast(message, type);
        }
    };

    const ensureAiProviderConfigured = () => {
        if (aiProviderReady) {
            return true;
        }

        setAiStatus(getAiProviderHintMessage(), "info");
        return false;
    };

    if (!aiProviderReady) {
        const message = getAiProviderHintMessage();
        aiOpenModalButtons.forEach((button) => {
            button.title = message;
        });
        aiImageGenerateButtons.forEach((button) => {
            button.title = message;
        });
    }

    const setAiModalError = (message = "") => {
        if (!(aiModalError instanceof HTMLElement)) {
            return;
        }

        if (message.trim() === "") {
            aiModalError.classList.add("hidden");
            aiModalError.textContent = "";
            return;
        }

        aiModalError.classList.remove("hidden");
        aiModalError.textContent = message;
    };

    const setAiImageModalError = (message = "") => {
        if (!(aiImageModalError instanceof HTMLElement)) {
            return;
        }

        if (message.trim() === "") {
            aiImageModalError.classList.add("hidden");
            aiImageModalError.textContent = "";
            return;
        }

        aiImageModalError.classList.remove("hidden");
        aiImageModalError.textContent = message;
    };

    const toggleAiControls = (isDisabled) => {
        aiOpenModalButtons.forEach((button) => {
            button.disabled = isDisabled;
        });
        aiImageGenerateButtons.forEach((button) => {
            button.disabled = isDisabled;
        });

        if (aiModalSubmitButton instanceof HTMLButtonElement) {
            aiModalSubmitButton.disabled = isDisabled;
        }
        if (aiImageModalSubmitButton instanceof HTMLButtonElement) {
            aiImageModalSubmitButton.disabled = isDisabled;
        }
    };

    const showAiPromptModal = () => {
        if (!(aiPromptModal instanceof HTMLElement)) {
            return;
        }

        aiPromptModal.classList.remove("hidden");
        aiPromptModal.classList.add("flex");
        document.body.classList.add("overflow-hidden");
    };

    const hideAiPromptModal = () => {
        if (!(aiPromptModal instanceof HTMLElement)) {
            return;
        }

        aiPromptModal.classList.add("hidden");
        aiPromptModal.classList.remove("flex");
        document.body.classList.remove("overflow-hidden");
    };

    const setAiImageLoading = (isLoading) => {
        aiImageIsGenerating = isLoading;

        if (aiImageLoadingState instanceof HTMLElement) {
            if (isLoading) {
                aiImageLoadingState.classList.remove("hidden");
                aiImageLoadingState.classList.add("flex");
            } else {
                aiImageLoadingState.classList.add("hidden");
                aiImageLoadingState.classList.remove("flex");
            }
        }

        if (aiImagePromptInput instanceof HTMLTextAreaElement) {
            aiImagePromptInput.disabled = isLoading;
        }

        if (aiImageReferenceOptions instanceof HTMLElement) {
            aiImageReferenceOptions
                .querySelectorAll('input[name="product_ai_image_reference"]')
                .forEach((input) => {
                    input.disabled = isLoading;
                });
        }

        if (aiImageModalCloseButton instanceof HTMLButtonElement) {
            aiImageModalCloseButton.disabled = isLoading;
        }

        if (aiImageModalCancelButton instanceof HTMLButtonElement) {
            aiImageModalCancelButton.disabled = isLoading;
        }

        if (aiImageModalSubmitButton instanceof HTMLButtonElement) {
            if (!aiImageModalSubmitButton.dataset.defaultLabel) {
                aiImageModalSubmitButton.dataset.defaultLabel =
                    aiImageModalSubmitButton.textContent?.trim() || "Générer l’image";
            }

            aiImageModalSubmitButton.textContent = isLoading
                ? "Génération..."
                : aiImageModalSubmitButton.dataset.defaultLabel;
        }
    };

    const showAiImagePromptModal = () => {
        if (!(aiImagePromptModal instanceof HTMLElement)) {
            return;
        }

        aiImagePromptModal.classList.remove("hidden");
        aiImagePromptModal.classList.add("flex");
        document.body.classList.add("overflow-hidden");
    };

    const hideAiImagePromptModal = () => {
        if (!(aiImagePromptModal instanceof HTMLElement)) {
            return;
        }

        if (aiImageIsGenerating) {
            return;
        }

        aiImagePromptModal.classList.add("hidden");
        aiImagePromptModal.classList.remove("flex");
        document.body.classList.remove("overflow-hidden");
    };

    const collectReferenceImages = () =>
        Array.from(document.querySelectorAll("[data-product-existing-image-id]"))
            .map((element) => {
                const rawId = element.dataset.productExistingImageId || "";
                const id = /^\d+$/.test(rawId)
                    ? Number.parseInt(rawId, 10)
                    : null;
                const imageElement = element.querySelector("img");
                const rawUrl =
                    element.dataset.productExistingImageUrl ||
                    (imageElement instanceof HTMLImageElement ? imageElement.src : "");
                const imageUrl = typeof rawUrl === "string" ? rawUrl.trim() : "";

                if (!Number.isInteger(id) || imageUrl === "") {
                    return null;
                }

                return {
                    id,
                    imageUrl,
                };
            })
            .filter((item) => item !== null);

    const getSelectedExistingMainImageId = () => {
        const checkedMain = document.querySelector(
            'input[type="radio"][name="main_image"]:checked'
        );
        const checkedValue =
            checkedMain instanceof HTMLInputElement ? checkedMain.value : "";

        if (!checkedValue.startsWith("existing-")) {
            return null;
        }

        const checkedIdRaw = checkedValue.replace("existing-", "");
        if (!/^\d+$/.test(checkedIdRaw)) {
            return null;
        }

        return Number.parseInt(checkedIdRaw, 10);
    };

    const renderAiImageReferenceOptions = (referenceImages) => {
        if (!(aiImageReferenceOptions instanceof HTMLElement)) {
            return;
        }

        aiImageReferenceOptions.innerHTML = "";

        const selectedMainId = getSelectedExistingMainImageId();
        const hasReferenceImages = referenceImages.length > 0;
        const hasSelectedMain =
            Number.isInteger(selectedMainId) &&
            referenceImages.some((reference) => reference.id === selectedMainId);
        const defaultReferenceId = hasSelectedMain
            ? selectedMainId
            : hasReferenceImages
              ? referenceImages[0].id
              : null;

        const createOption = ({
            id,
            imageUrl,
            title,
            checked = false,
            textOnly = false,
        }) => {
            const option = document.createElement("label");
            option.className =
                "relative flex items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-white cursor-pointer group";

            const radio = document.createElement("input");
            radio.type = "radio";
            radio.name = "product_ai_image_reference";
            radio.value = id === null ? "" : String(id);
            radio.className = "sr-only";
            radio.checked = checked;
            radio.addEventListener("change", () => {
                activeAiImageReferenceId =
                    id === null || !Number.isInteger(id) ? null : id;
                aiImageReferenceOptions
                    .querySelectorAll("[data-ai-image-reference-state]")
                    .forEach((badge) => {
                        badge.textContent = "Référence";
                    });

                const state = option.querySelector("[data-ai-image-reference-state]");
                if (state instanceof HTMLElement) {
                    state.textContent = "Sélectionnée";
                }
            });

            const mediaWrapper = document.createElement("div");
            mediaWrapper.className = textOnly
                ? "flex h-20 w-full items-center justify-center bg-gray-50 px-2 text-center text-xxxs text-gray-600"
                : "h-20 w-full";

            if (textOnly) {
                mediaWrapper.textContent = title;
            } else {
                const image = document.createElement("img");
                image.src = imageUrl || "";
                image.alt = title;
                image.className = "h-20 w-full object-cover group-hover:opacity-95";
                mediaWrapper.appendChild(image);
            }

            const footer = document.createElement("div");
            footer.className =
                "absolute inset-x-0 bottom-0 flex items-center justify-between bg-black/60 px-1.5 py-0.5 text-xxxs text-white";

            const label = document.createElement("span");
            label.textContent = title;

            const state = document.createElement("span");
            state.dataset.aiImageReferenceState = "1";
            state.textContent = checked ? "Sélectionnée" : "Référence";

            footer.appendChild(label);
            footer.appendChild(state);

            option.appendChild(radio);
            option.appendChild(mediaWrapper);
            option.appendChild(footer);
            aiImageReferenceOptions.appendChild(option);
        };

        if (!hasReferenceImages) {
            createOption({
                id: null,
                imageUrl: "",
                title: "Aucune référence",
                checked: true,
                textOnly: true,
            });
        }

        referenceImages.forEach((reference, index) => {
            createOption({
                id: reference.id,
                imageUrl: reference.imageUrl,
                title: `Image ${index + 1}`,
                checked: defaultReferenceId === reference.id,
            });
        });

        if (aiImageReferenceEmpty instanceof HTMLElement) {
            if (referenceImages.length === 0) {
                aiImageReferenceEmpty.classList.remove("hidden");
            } else {
                aiImageReferenceEmpty.classList.add("hidden");
            }
        }

        aiImageRequireReference = hasReferenceImages;
        activeAiImageReferenceId = defaultReferenceId;
    };

    const getNextAiGeneratedIndex = () => {
        if (!(resolvedInputsContainer instanceof HTMLElement)) {
            return 0;
        }

        const indices = Array.from(
            resolvedInputsContainer.querySelectorAll("input[data-ai-generated-index]")
        )
            .map((input) => {
                const raw = input.dataset.aiGeneratedIndex || "";
                return /^\d+$/.test(raw) ? Number.parseInt(raw, 10) : null;
            })
            .filter((value) => Number.isInteger(value));

        if (indices.length === 0) {
            return 0;
        }

        return Math.max(...indices) + 1;
    };

    const appendGeneratedImagePreview = (dataUrl) => {
        if (
            !(resolvedInputsContainer instanceof HTMLElement) ||
            !(resolvedPreviewContainer instanceof HTMLElement)
        ) {
            return;
        }

        const index = getNextAiGeneratedIndex();
        const key = `ai-${index}`;

        const hiddenInput = document.createElement("input");
        hiddenInput.type = "hidden";
        hiddenInput.name = `ai_generated_images[${index}]`;
        hiddenInput.value = dataUrl;
        hiddenInput.dataset.aiGeneratedInput = "1";
        hiddenInput.dataset.aiGeneratedIndex = String(index);
        resolvedInputsContainer.appendChild(hiddenInput);

        const wrapper = document.createElement("label");
        wrapper.className =
            "relative border rounded-xl overflow-hidden cursor-pointer group";

        const image = document.createElement("img");
        image.src = dataUrl;
        image.alt = "Image IA générée";
        image.className = "w-full h-52 object-cover group-hover:opacity-95";

        const badge = document.createElement("span");
        badge.className =
            "absolute left-1 top-1 z-10 inline-flex items-center rounded-md bg-emerald-600/90 px-1.5 py-0.5 text-xxxs font-semibold uppercase tracking-wide text-white shadow-sm";
        badge.textContent = "IA";

        const bar = document.createElement("div");
        bar.className =
            "absolute bottom-1 left-1 right-1 flex items-center justify-center gap-1 bg-black/60 text-white text-xxxs px-1.5 py-0.5 rounded-full";

        const radio = document.createElement("input");
        radio.type = "radio";
        radio.name = "main_image";
        radio.value = key;
        radio.className = "h-2 w-2";
        radio.addEventListener("change", () => {
            if (mainImageInput instanceof HTMLInputElement) {
                mainImageInput.value = radio.value;
            }
        });

        const text = document.createElement("span");
        text.textContent = "Définir comme principale";

        bar.appendChild(radio);
        bar.appendChild(text);
        wrapper.appendChild(image);
        wrapper.appendChild(badge);
        wrapper.appendChild(bar);
        resolvedPreviewContainer.appendChild(wrapper);

        const hasMainSelected = Array.from(
            document.querySelectorAll('input[type="radio"][name="main_image"]')
        ).some((input) => input.checked);
        const currentMainValue =
            mainImageInput instanceof HTMLInputElement
                ? mainImageInput.value?.trim() ?? ""
                : "";
        const hasIndexedUploadSelection =
            /^\d+$/.test(currentMainValue) &&
            createInput instanceof HTMLInputElement &&
            createInput.files !== null &&
            Number.parseInt(currentMainValue, 10) < createInput.files.length;
        const hasExplicitMainSelection =
            currentMainValue !== "" &&
            (currentMainValue !== "0" || hasIndexedUploadSelection);

        if (
            !hasMainSelected &&
            !hasExplicitMainSelection &&
            mainImageInput instanceof HTMLInputElement
        ) {
            radio.checked = true;
            mainImageInput.value = key;
            text.textContent = "Image principale";
        }

        if (typeof syncVariantImageChoices === "function") {
            syncVariantImageChoices();
        }
    };

    const openAiImagePromptModal = ({ endpoint }) => {
        if (!ensureAiProviderConfigured()) {
            return;
        }

        setAiImageModalError("");
        activeAiImageEndpoint = endpoint;
        const references = collectReferenceImages();
        renderAiImageReferenceOptions(references);
        if (aiImagePromptInput instanceof HTMLTextAreaElement) {
            aiImagePromptInput.value = "";
        }
        setAiImageLoading(false);

        showAiImagePromptModal();

        window.setTimeout(() => {
            aiImagePromptInput?.focus();
            const inputLength = aiImagePromptInput?.value?.length ?? 0;
            if (aiImagePromptInput instanceof HTMLTextAreaElement && inputLength > 0) {
                aiImagePromptInput.setSelectionRange(inputLength, inputLength);
            }
        }, 40);
    };

    const openAiPromptModal = ({ targetField, targetLabel, endpoint }) => {
        if (!ensureAiProviderConfigured()) {
            return;
        }

        setAiModalError("");
        activeAiField = targetField;
        activeAiFieldLabel = targetLabel;
        activeAiEndpoint = endpoint;

        if (aiTargetLabel instanceof HTMLElement) {
            aiTargetLabel.textContent = targetLabel;
        }

        showAiPromptModal();

        window.setTimeout(() => {
            aiPromptInput?.focus();
            const inputLength = aiPromptInput?.value?.length ?? 0;
            if (aiPromptInput instanceof HTMLTextAreaElement && inputLength > 0) {
                aiPromptInput.setSelectionRange(inputLength, inputLength);
            }
        }, 40);
    };

    const runAiGeneration = async ({ prompt, targetField, targetLabel, endpoint }) => {
        if (!ensureAiProviderConfigured()) {
            return;
        }

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");

        if (!endpoint || !csrfToken) {
            setAiStatus("Configuration de génération IA incomplète.", "error");
            return;
        }

        syncWysiwygEditors();

        const nameField = document.querySelector('input[name="name"]');
        const shortDescriptionField = document.querySelector(
            'textarea[name="short_description"]'
        );
        const descriptionField = document.querySelector(
            'textarea[name="description"]'
        );
        const metaTitleField = document.querySelector('input[name="meta_title"]');
        const metaDescriptionField = document.querySelector(
            'textarea[name="meta_description"]'
        );

        const categories = Array.from(
            document.querySelectorAll('input[name="categories[]"]:checked')
        )
            .map((checkbox) => {
                const label = checkbox.closest("label");
                if (!label) {
                    return "";
                }

                return (label.textContent || "").trim();
            })
            .filter((value) => value.length > 0);

        const payload = {
            prompt,
            target_field: targetField,
            name: nameField ? nameField.value : "",
            short_description: shortDescriptionField ? shortDescriptionField.value : "",
            description: descriptionField ? descriptionField.value : "",
            meta_title: metaTitleField ? metaTitleField.value : "",
            meta_description: metaDescriptionField ? metaDescriptionField.value : "",
            categories,
        };

        toggleAiControls(true);
        setAiStatus("Génération IA en cours...", "info");

        try {
            const response = await fetch(endpoint, {
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
                    result.message ||
                        "La génération IA a échoué. Vérifie ta configuration et réessaie."
                );
            }

            const generated = result.data || {};
            const generatedValue = generated[targetField];
            if (typeof generatedValue === "string") {
                setFormFieldValue(targetField, generatedValue);
            }

            setAiStatus(
                `${targetLabel} généré. Vérifie le contenu avant d'enregistrer.`,
                "success"
            );
        } catch (error) {
            const message =
                error instanceof Error
                    ? error.message
                    : "Erreur inconnue pendant la génération IA.";
            setAiStatus(message, "error");
        } finally {
            toggleAiControls(false);
        }
    };

    const runAiImageGeneration = async ({ prompt, sourceImageIds, endpoint }) => {
        if (!ensureAiProviderConfigured()) {
            return false;
        }

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");

        if (!endpoint || !csrfToken) {
            setAiStatus("Configuration de génération d’image IA incomplète.", "error");
            return false;
        }

        const payload = {
            prompt,
            product_id: productId,
            source_image_ids: Array.isArray(sourceImageIds) ? sourceImageIds : [],
        };
        const usesReference =
            Array.isArray(payload.source_image_ids) &&
            payload.source_image_ids.length > 0;

        toggleAiControls(true);
        setAiImageLoading(true);
        setAiStatus(
            usesReference
                ? "Génération d’image IA en cours avec image de référence..."
                : "Génération d’image IA en cours...",
            "info"
        );

        try {
            const response = await fetch(endpoint, {
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
                    result.message ||
                        "La génération d’image IA a échoué. Vérifie ta configuration et réessaie."
                );
            }

            const dataUrl = result?.data?.image_data_url;
            if (typeof dataUrl !== "string" || !dataUrl.startsWith("data:image/")) {
                throw new Error("Réponse image IA invalide.");
            }

            appendGeneratedImagePreview(dataUrl);
            setAiStatus(
                "Image IA générée. Vérifie le rendu puis enregistre le produit.",
                "success"
            );
            return true;
        } catch (error) {
            const message =
                error instanceof Error
                    ? error.message
                    : "Erreur inconnue pendant la génération d’image IA.";
            setAiStatus(message, "error");
            return false;
        } finally {
            setAiImageLoading(false);
            toggleAiControls(false);
        }
    };

    if (
        aiOpenModalButtons.length > 0 &&
        aiModalSubmitButton instanceof HTMLButtonElement &&
        aiPromptInput instanceof HTMLTextAreaElement
    ) {
        aiOpenModalButtons.forEach((button) => {
            button.addEventListener("click", () => {
                openAiPromptModal({
                    targetField: button.dataset.aiTarget || "",
                    targetLabel: button.dataset.aiTargetLabel || "Champ",
                    endpoint: button.dataset.aiGenerateUrl || "",
                });
            });
        });

        aiModalSubmitButton.addEventListener("click", async () => {
            if (!ensureAiProviderConfigured()) {
                return;
            }

            const prompt = aiPromptInput.value.trim();
            if (prompt.length < 3) {
                setAiModalError("Saisis un prompt d'au moins 3 caractères.");
                return;
            }
            if (!activeAiField || activeAiField.trim() === "") {
                setAiModalError("Champ cible introuvable.");
                return;
            }
            if (!activeAiEndpoint || activeAiEndpoint.trim() === "") {
                setAiModalError("Endpoint IA introuvable.");
                return;
            }

            setAiModalError("");
            hideAiPromptModal();

            await runAiGeneration({
                prompt,
                targetField: activeAiField,
                targetLabel: activeAiFieldLabel || "Champ",
                endpoint: activeAiEndpoint,
            });
        });

        if (aiModalCloseButton instanceof HTMLButtonElement) {
            aiModalCloseButton.addEventListener("click", () => {
                hideAiPromptModal();
            });
        }

        if (aiModalCancelButton instanceof HTMLButtonElement) {
            aiModalCancelButton.addEventListener("click", () => {
                hideAiPromptModal();
            });
        }

        if (aiPromptModal instanceof HTMLElement) {
            aiPromptModal.addEventListener("click", (event) => {
                if (event.target === aiPromptModal) {
                    hideAiPromptModal();
                }
            });
        }

        document.addEventListener("keydown", (event) => {
            if (
                event.key === "Escape" &&
                aiPromptModal instanceof HTMLElement &&
                !aiPromptModal.classList.contains("hidden")
            ) {
                hideAiPromptModal();
            }
        });
    }

    if (aiImageGenerateButtons.length > 0) {
        aiImageGenerateButtons.forEach((button) => {
            button.addEventListener("click", () => {
                const endpoint = button.dataset.aiImageGenerateUrl || "";
                openAiImagePromptModal({
                    endpoint,
                });
            });
        });

        if (
            aiImageModalSubmitButton instanceof HTMLButtonElement &&
            aiImagePromptInput instanceof HTMLTextAreaElement
        ) {
            aiImageModalSubmitButton.addEventListener("click", async () => {
                if (!ensureAiProviderConfigured()) {
                    return;
                }

                const prompt = aiImagePromptInput.value.trim();
                if (prompt.length < 3) {
                    setAiImageModalError("Saisis un prompt d'au moins 3 caractères.");
                    return;
                }
                if (!activeAiImageEndpoint || activeAiImageEndpoint.trim() === "") {
                    setAiImageModalError("Endpoint image IA introuvable.");
                    return;
                }
                if (aiImageRequireReference && !Number.isInteger(activeAiImageReferenceId)) {
                    setAiImageModalError("Sélectionne une image de référence.");
                    return;
                }

                setAiImageModalError("");

                const sourceImageIds = Number.isInteger(activeAiImageReferenceId)
                    ? [activeAiImageReferenceId]
                    : [];

                const success = await runAiImageGeneration({
                    prompt,
                    sourceImageIds,
                    endpoint: activeAiImageEndpoint,
                });

                if (success) {
                    hideAiImagePromptModal();
                }
            });
        }

        if (aiImageModalCloseButton instanceof HTMLButtonElement) {
            aiImageModalCloseButton.addEventListener("click", () => {
                hideAiImagePromptModal();
            });
        }

        if (aiImageModalCancelButton instanceof HTMLButtonElement) {
            aiImageModalCancelButton.addEventListener("click", () => {
                hideAiImagePromptModal();
            });
        }

        if (aiImagePromptModal instanceof HTMLElement) {
            aiImagePromptModal.addEventListener("click", (event) => {
                if (event.target === aiImagePromptModal) {
                    hideAiImagePromptModal();
                }
            });
        }

        document.addEventListener("keydown", (event) => {
            if (
                event.key === "Escape" &&
                aiImagePromptModal instanceof HTMLElement &&
                !aiImagePromptModal.classList.contains("hidden")
            ) {
                hideAiImagePromptModal();
            }
        });
    }
};
