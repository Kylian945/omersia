import "./quill-editor.js";

// Exposé global pour Alpine : x-data="productCreateForm('simple')"
window.productCreateForm = function (config = {}) {
    return {
        productType: config.type || "simple",
        options: Array.isArray(config.options) ? config.options : [],
        variants: Array.isArray(config.variants) ? config.variants : [],

        onTypeChange() {
            if (this.productType === "simple") {
                this.variants = [];
            }
        },

        addOption() {
            this.options.push({
                name: "",
                valuesText: "",
            });
        },

        removeOption(index) {
            this.options.splice(index, 1);
            this.generateVariants();
        },

        splitValues(text) {
            return (text || "")
                .split(",")
                .map((v) => v.trim())
                .filter((v) => v.length > 0);
        },

        cartesian(arrays) {
            return arrays.reduce(
                (a, b) => {
                    const res = [];
                    a.forEach((x) => {
                        b.forEach((y) => {
                            res.push([].concat(x, y));
                        });
                    });
                    return res;
                },
                [[]]
            );
        },

        generateVariants() {
            const normalizedOptions = this.options
                .map((o) => ({
                    name: (o.name || "").trim(),
                    values: this.splitValues(o.valuesText),
                }))
                .filter((o) => o.name && o.values.length);

            if (!normalizedOptions.length) {
                this.variants = [];
                return;
            }

            const combos = this.cartesian(
                normalizedOptions.map((o) => o.values)
            );

            this.variants = combos.map((combo) => {
                const values = combo.map((val, idx) => {
                    const optName = normalizedOptions[idx].name;
                    return optName + ":" + val;
                });

                const label = values
                    .map((v) => v.split(":")[1])
                    .join(" / ");

                const existing = this.variants.find((vExisting) => {
                    if (
                        !vExisting.values ||
                        vExisting.values.length !== values.length
                    ) {
                        return false;
                    }
                    return values.every((v) =>
                        vExisting.values.includes(v)
                    );
                });

                return (
                    existing || {
                        label,
                        sku: "",
                        is_active: true,
                        stock_qty: 0,
                        price: "",
                        compare_at_price: "",
                        values,
                    }
                );
            });
        },

        init() {
            // Si on est en variant et qu'il n'y a rien, on pré-ajoute une option
            if (
                this.productType === "variant" &&
                (!this.options || this.options.length === 0)
            ) {
                this.addOption();
            }
        },
    };
};


document.addEventListener("DOMContentLoaded", () => {
    const productWysiwygEditors = new Map();

    const isLikelyHtml = (value) =>
        /<[a-z][\s\S]*>/i.test(value || "");

    const normalizeWysiwygValue = (value) => {
        if (typeof value !== "string") {
            return "";
        }

        const normalized = value.trim();
        if (
            normalized === "" ||
            normalized === "<p><br></p>" ||
            normalized === "<div><br></div>"
        ) {
            return "";
        }

        return normalized;
    };

    const syncWysiwygField = (fieldName) => {
        const editorState = productWysiwygEditors.get(fieldName);
        if (!editorState) {
            return;
        }

        editorState.input.value = normalizeWysiwygValue(
            editorState.quill.root.innerHTML
        );
    };

    const syncWysiwygEditors = () => {
        productWysiwygEditors.forEach((_state, fieldName) => {
            syncWysiwygField(fieldName);
        });
    };

    const setFormFieldValue = (fieldName, nextValue) => {
        const safeValue = typeof nextValue === "string" ? nextValue : "";
        const editorState = productWysiwygEditors.get(fieldName);

        if (editorState) {
            if (safeValue.trim() === "") {
                editorState.quill.setText("");
            } else if (isLikelyHtml(safeValue)) {
                editorState.quill.clipboard.dangerouslyPasteHTML(safeValue);
            } else {
                editorState.quill.setText(safeValue);
            }

            syncWysiwygField(fieldName);
            return true;
        }

        const field = document.querySelector(`[name="${fieldName}"]`);
        if (!field || !("value" in field)) {
            return false;
        }

        field.value = safeValue;
        return true;
    };

    const initProductWysiwyg = () => {
        const editorContainers = Array.from(
            document.querySelectorAll("[data-product-wysiwyg]")
        );

        if (editorContainers.length === 0) {
            return;
        }

        if (typeof window.Quill !== "function") {
            editorContainers.forEach((container) => {
                container.classList.add("hidden");
                const fieldName = container.dataset.productWysiwyg || "";
                if (!fieldName) {
                    return;
                }

                const fallbackInput = document.querySelector(
                    `textarea[name="${fieldName}"]`
                );
                if (!fallbackInput) {
                    return;
                }

                fallbackInput.classList.remove("hidden");
                fallbackInput.style.width = "100%";
                fallbackInput.style.border = "0";
                fallbackInput.style.padding = "0.5rem 0.75rem";
                fallbackInput.style.fontSize = "0.75rem";
                fallbackInput.style.resize = "vertical";
                fallbackInput.style.outline = "none";

                const minHeight = Number.parseInt(
                    container.dataset.productWysiwygMinHeight || "120",
                    10
                );
                if (Number.isFinite(minHeight) && minHeight > 0) {
                    fallbackInput.style.minHeight = `${minHeight}px`;
                }
            });

            return;
        }

        const toolbar = [
            [{ header: [2, 3, false] }],
            ["bold", "italic", "underline"],
            [{ list: "ordered" }, { list: "bullet" }],
            ["link", "clean"],
        ];

        editorContainers.forEach((container) => {
            const fieldName = container.dataset.productWysiwyg || "";
            if (!fieldName) {
                return;
            }

            const input = document.querySelector(
                `textarea[name="${fieldName}"]`
            );
            if (!input) {
                return;
            }

            const quill = new window.Quill(container, {
                theme: "snow",
                modules: {
                    toolbar,
                },
                placeholder:
                    container.dataset.productWysiwygPlaceholder || "",
            });

            const initialValue = (input.value || "").trim();
            if (initialValue !== "") {
                if (isLikelyHtml(initialValue)) {
                    quill.clipboard.dangerouslyPasteHTML(initialValue);
                } else {
                    quill.setText(initialValue);
                }
            }

            const minHeight = Number.parseInt(
                container.dataset.productWysiwygMinHeight || "",
                10
            );
            const quillEditor = container.querySelector(".ql-editor");
            if (
                quillEditor &&
                Number.isFinite(minHeight) &&
                minHeight > 0
            ) {
                quillEditor.style.minHeight = `${minHeight}px`;
            }

            productWysiwygEditors.set(fieldName, {
                quill,
                input,
            });

            quill.on("text-change", () => {
                syncWysiwygField(fieldName);
            });

            syncWysiwygField(fieldName);
        });
    };

    initProductWysiwyg();

    document.querySelectorAll("form").forEach((form) => {
        if (form.querySelector("[data-product-wysiwyg]")) {
            form.addEventListener("submit", () => {
                syncWysiwygEditors();
            });
        }
    });

    /**
     * Gestion des previews d'images pour la création
     */
    const createInput = document.querySelector('input[name="images[]"]');
    const createPreview = document.getElementById("image-preview-container");
    const mainInputCreate = document.getElementById("main_image_input");
    const mainImageInput = document.getElementById("main_image_input");

    if (createInput && createPreview && mainInputCreate) {
        createInput.addEventListener("change", () => {
            createPreview.innerHTML = "";
            Array.from(createInput.files).forEach((file, index) => {
                const url = URL.createObjectURL(file);

                const wrapper = document.createElement("button");
                wrapper.type = "button";
                wrapper.className =
                    "relative border rounded-xl overflow-hidden group focus:outline-none";
                wrapper.addEventListener("click", () => {
                    if (mainImageInput) {
                        mainImageInput.value = String(index);
                    }
                    [
                        ...createPreview.querySelectorAll("[data-main]"),
                    ].forEach((el) => {
                        el.textContent = "";
                    });
                    label.textContent = "Image principale";
                });

                const img = document.createElement("img");
                img.src = url;
                img.className = "w-full h-52 object-cover";

                const label = document.createElement("div");
                label.className =
                    "absolute bottom-1 left-1 right-1 text-xxxs px-1 py-0.5 rounded bg-black/60 text-white text-center";
                label.dataset.main = "0";
                if (index === 0) {
                    label.textContent = "Image principale";
                }

                wrapper.appendChild(img);
                wrapper.appendChild(label);
                createPreview.appendChild(wrapper);
            });
        });
    }

    /**
     * Gestion des nouvelles images sur "edit"
     */
    const newImagesInput = document.getElementById("new-images-input");
    const newImagesContainer =
        document.getElementById("new-images-preview");
    const mainInputEdit = document.getElementById("main_image_input");

    if (newImagesInput && newImagesContainer && mainInputEdit) {
        newImagesInput.addEventListener("change", () => {
            newImagesContainer.innerHTML = "";
            Array.from(newImagesInput.files).forEach((file, index) => {
                const url = URL.createObjectURL(file);

                const wrapper = document.createElement("label");
                wrapper.className =
                    "relative border rounded-xl overflow-hidden cursor-pointer group";

                const img = document.createElement("img");
                img.src = url;
                img.className =
                    "w-full h-52 object-cover group-hover:opacity-95";

                const bar = document.createElement("div");
                bar.className =
                    "absolute bottom-1 left-1 right-1 flex items-center justify-center gap-1 bg-black/60 text-white text-xxxs px-1.5 py-0.5 rounded-full";

                const radio = document.createElement("input");
                radio.type = "radio";
                radio.name = "main_image";
                radio.value = "new-" + index;
                radio.className = "h-2 w-2";

                radio.addEventListener("change", () => {
                    if (mainImageInput) {
                        mainImageInput.value = radio.value;
                    }
                });

                const text = document.createElement("span");
                text.textContent = "Définir comme principale";

                bar.appendChild(radio);
                bar.appendChild(text);
                wrapper.appendChild(img);
                wrapper.appendChild(bar);
                newImagesContainer.appendChild(wrapper);
            });
        });
    }

    // Sync radio -> main_image sur edit
    document
        .querySelectorAll('input[type="radio"][name="main_image"]')
        .forEach((radio) => {
            radio.addEventListener("change", () => {
                if (mainImageInput) {
                    mainImageInput.value = radio.value;
                }
            });
        });

    /**
     * Génération IA manuelle pour les champs produit + SEO
     */
    const aiOpenModalButtons = Array.from(
        document.querySelectorAll("[data-ai-open-modal]")
    );
    const aiPromptModal = document.getElementById("product-ai-prompt-modal");
    const aiModalCloseButton = document.getElementById("product-ai-modal-close-button");
    const aiModalCancelButton = document.getElementById("product-ai-modal-cancel-button");
    const aiModalSubmitButton = document.getElementById("product-ai-modal-submit-button");
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
    const aiImageLoadingState = document.getElementById(
        "product-ai-image-loading"
    );
    const aiGeneratedPreviewContainer = document.getElementById(
        "ai-generated-images-preview"
    );
    const aiGeneratedInputsContainer = document.getElementById(
        "ai-generated-images-inputs"
    );
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

    const setAiStatus = (message, type = "info") => {
        if (typeof window.showToast === "function") {
            window.showToast(message, type);
            return;
        }
    };

    const setAiModalError = (message = "") => {
        if (!aiModalError) {
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

    const toggleAiControls = (isDisabled) => {
        aiOpenModalButtons.forEach((button) => {
            button.disabled = isDisabled;
        });
        aiImageGenerateButtons.forEach((button) => {
            button.disabled = isDisabled;
        });
        if (aiModalSubmitButton) {
            aiModalSubmitButton.disabled = isDisabled;
        }
        if (aiImageModalSubmitButton) {
            aiImageModalSubmitButton.disabled = isDisabled;
        }
    };

    const showAiPromptModal = () => {
        if (!aiPromptModal) {
            return;
        }

        aiPromptModal.classList.remove("hidden");
        aiPromptModal.classList.add("flex");
        document.body.classList.add("overflow-hidden");
    };

    const hideAiPromptModal = () => {
        if (!aiPromptModal) {
            return;
        }

        aiPromptModal.classList.add("hidden");
        aiPromptModal.classList.remove("flex");
        document.body.classList.remove("overflow-hidden");
    };

    const setAiImageModalError = (message = "") => {
        if (!aiImageModalError) {
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

    const setAiImageLoading = (isLoading) => {
        aiImageIsGenerating = isLoading;

        if (aiImageLoadingState) {
            if (isLoading) {
                aiImageLoadingState.classList.remove("hidden");
                aiImageLoadingState.classList.add("flex");
            } else {
                aiImageLoadingState.classList.add("hidden");
                aiImageLoadingState.classList.remove("flex");
            }
        }

        if (aiImagePromptInput) {
            aiImagePromptInput.disabled = isLoading;
        }

        if (aiImageReferenceOptions) {
            aiImageReferenceOptions
                .querySelectorAll('input[name="product_ai_image_reference"]')
                .forEach((input) => {
                    input.disabled = isLoading;
                });
        }

        if (aiImageModalCloseButton) {
            aiImageModalCloseButton.disabled = isLoading;
        }

        if (aiImageModalCancelButton) {
            aiImageModalCancelButton.disabled = isLoading;
        }

        if (aiImageModalSubmitButton) {
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
        if (!aiImagePromptModal) {
            return;
        }

        aiImagePromptModal.classList.remove("hidden");
        aiImagePromptModal.classList.add("flex");
        document.body.classList.add("overflow-hidden");
    };

    const hideAiImagePromptModal = () => {
        if (!aiImagePromptModal) {
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
        Array.from(
            document.querySelectorAll("[data-product-existing-image-id]")
        )
            .map((element) => {
                const rawId = element.dataset.productExistingImageId || "";
                const id = /^\d+$/.test(rawId)
                    ? Number.parseInt(rawId, 10)
                    : null;
                const imageElement = element.querySelector("img");
                const rawUrl =
                    element.dataset.productExistingImageUrl ||
                    (imageElement instanceof HTMLImageElement
                        ? imageElement.src
                        : "");
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
        if (!aiImageReferenceOptions) {
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

                const state = option.querySelector(
                    "[data-ai-image-reference-state]"
                );
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

        if (aiImageReferenceEmpty) {
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
        if (!aiGeneratedInputsContainer) {
            return 0;
        }

        const indices = Array.from(
            aiGeneratedInputsContainer.querySelectorAll(
                "input[data-ai-generated-index]"
            )
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
        if (!aiGeneratedInputsContainer || !aiGeneratedPreviewContainer) {
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
        aiGeneratedInputsContainer.appendChild(hiddenInput);

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
            if (mainImageInput) {
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
        aiGeneratedPreviewContainer.appendChild(wrapper);

        const hasMainSelected = Array.from(
            document.querySelectorAll('input[type="radio"][name="main_image"]')
        ).some((input) => input.checked);
        const currentMainValue = mainImageInput?.value?.trim() ?? "";
        const hasIndexedUploadSelection =
            /^\d+$/.test(currentMainValue) &&
            createInput instanceof HTMLInputElement &&
            createInput.files !== null &&
            Number.parseInt(currentMainValue, 10) < createInput.files.length;
        const hasExplicitMainSelection =
            currentMainValue !== "" &&
            (currentMainValue !== "0" || hasIndexedUploadSelection);

        if (!hasMainSelected && !hasExplicitMainSelection && mainImageInput) {
            radio.checked = true;
            mainImageInput.value = key;
            text.textContent = "Image principale";
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
        if (aiImagePromptInput) {
            aiImagePromptInput.value = "";
        }
        setAiImageLoading(false);

        showAiImagePromptModal();

        window.setTimeout(() => {
            aiImagePromptInput?.focus();
            const inputLength = aiImagePromptInput?.value?.length ?? 0;
            if (aiImagePromptInput && inputLength > 0) {
                aiImagePromptInput.setSelectionRange(inputLength, inputLength);
            }
        }, 40);
    };

    const openAiPromptModal = ({
        targetField,
        targetLabel,
        endpoint,
    }) => {
        if (!ensureAiProviderConfigured()) {
            return;
        }

        setAiModalError("");
        activeAiField = targetField;
        activeAiFieldLabel = targetLabel;
        activeAiEndpoint = endpoint;

        if (aiTargetLabel) {
            aiTargetLabel.textContent = targetLabel;
        }

        showAiPromptModal();

        window.setTimeout(() => {
            aiPromptInput?.focus();
            const inputLength = aiPromptInput?.value?.length ?? 0;
            if (aiPromptInput && inputLength > 0) {
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
        const shortDescriptionField = document.querySelector('textarea[name="short_description"]');
        const descriptionField = document.querySelector('textarea[name="description"]');
        const metaTitleField = document.querySelector('input[name="meta_title"]');
        const metaDescriptionField = document.querySelector('textarea[name="meta_description"]');

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
                    result.message || "La génération IA a échoué. Vérifie ta configuration et réessaie."
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
            const message = error instanceof Error ? error.message : "Erreur inconnue pendant la génération IA.";
            setAiStatus(message, "error");
        } finally {
            toggleAiControls(false);
        }
    };

    const runAiImageGeneration = async ({
        prompt,
        sourceImageIds,
        endpoint,
    }) => {
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
            const message = error instanceof Error
                ? error.message
                : "Erreur inconnue pendant la génération d’image IA.";
            setAiStatus(message, "error");
            return false;
        } finally {
            setAiImageLoading(false);
            toggleAiControls(false);
        }
    };

    if (aiOpenModalButtons.length > 0 && aiModalSubmitButton && aiPromptInput) {
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

        if (aiModalCloseButton) {
            aiModalCloseButton.addEventListener("click", () => {
                hideAiPromptModal();
            });
        }

        if (aiModalCancelButton) {
            aiModalCancelButton.addEventListener("click", () => {
                hideAiPromptModal();
            });
        }

        if (aiPromptModal) {
            aiPromptModal.addEventListener("click", (event) => {
                if (event.target === aiPromptModal) {
                    hideAiPromptModal();
                }
            });
        }

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape" && aiPromptModal && !aiPromptModal.classList.contains("hidden")) {
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

        if (aiImageModalSubmitButton && aiImagePromptInput) {
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
                if (
                    aiImageRequireReference &&
                    !Number.isInteger(activeAiImageReferenceId)
                ) {
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

        if (aiImageModalCloseButton) {
            aiImageModalCloseButton.addEventListener("click", () => {
                hideAiImagePromptModal();
            });
        }

        if (aiImageModalCancelButton) {
            aiImageModalCancelButton.addEventListener("click", () => {
                hideAiImagePromptModal();
            });
        }

        if (aiImagePromptModal) {
            aiImagePromptModal.addEventListener("click", (event) => {
                if (event.target === aiImagePromptModal) {
                    hideAiImagePromptModal();
                }
            });
        }

        document.addEventListener("keydown", (event) => {
            if (
                event.key === "Escape" &&
                aiImagePromptModal &&
                !aiImagePromptModal.classList.contains("hidden")
            ) {
                hideAiImagePromptModal();
            }
        });
    }

    /**
     * Barre sticky CTA visible seulement quand le bloc principal est hors écran
     */
    const primaryActions = document.querySelector(
        "#product-primary-actions, #product-edit-primary-actions"
    );
    const stickyActions = document.querySelector(
        "#product-sticky-actions, #product-edit-sticky-actions"
    );

    if (primaryActions && stickyActions && "IntersectionObserver" in window) {
        const observer = new IntersectionObserver(
            (entries) => {
                const entry = entries[0];
                if (entry.isIntersecting) {
                    stickyActions.classList.add("hidden");
                } else {
                    stickyActions.classList.remove("hidden");
                }
            },
            {
                threshold: 0.2,
            }
        );

        observer.observe(primaryActions);
    }
});
