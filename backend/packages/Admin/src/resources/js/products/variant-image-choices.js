const toVariantImageChoice = ({ key, label, url = "" }) => {
    if (typeof key !== "string") {
        return null;
    }

    const normalizedKey = key.trim();
    if (normalizedKey === "") {
        return null;
    }

    return {
        key: normalizedKey,
        label:
            typeof label === "string" && label.trim() !== ""
                ? label.trim()
                : normalizedKey,
        url: typeof url === "string" ? url.trim() : "",
    };
};

export const createVariantImageChoicesSync = ({
    createInput,
    createPreview,
    newImagesContainer,
    aiGeneratedPreviewContainer,
    aiGeneratedInputsContainer,
}) => {
    const productForm = document.querySelector("form[data-product-id]");

    const getProductFormState = () => {
        if (!(productForm instanceof HTMLElement)) {
            return null;
        }

        if (window.Alpine && typeof window.Alpine.$data === "function") {
            try {
                return window.Alpine.$data(productForm);
            } catch (_error) {
                // Alpine internals can differ by runtime version.
            }
        }

        const legacyData = productForm.__x?.$data;
        return legacyData && typeof legacyData === "object" ? legacyData : null;
    };

    const collectVariantImageChoices = () => {
        const choices = [];
        const dedup = new Set();

        const pushChoice = (choice) => {
            if (!choice || dedup.has(choice.key)) {
                return;
            }

            dedup.add(choice.key);
            choices.push(choice);
        };

        Array.from(
            document.querySelectorAll("[data-product-existing-image-id]")
        ).forEach((element, index) => {
            const rawId = (element.dataset.productExistingImageId || "").trim();
            if (!/^\d+$/.test(rawId)) {
                return;
            }

            const image = element.querySelector("img");
            pushChoice(
                toVariantImageChoice({
                    key: `existing-${rawId}`,
                    label: `Existante ${index + 1}`,
                    url:
                        element.dataset.productExistingImageUrl ||
                        (image instanceof HTMLImageElement ? image.src : ""),
                })
            );
        });

        if (
            createInput instanceof HTMLInputElement &&
            createPreview instanceof HTMLElement &&
            createInput.files !== null
        ) {
            Array.from(createInput.files).forEach((file, index) => {
                const wrapper = createPreview.children.item(index);
                const image = wrapper?.querySelector("img");

                pushChoice(
                    toVariantImageChoice({
                        key: String(index),
                        label:
                            typeof file.name === "string" && file.name.trim() !== ""
                                ? `Upload ${index + 1} - ${file.name}`
                                : `Upload ${index + 1}`,
                        url: image instanceof HTMLImageElement ? image.src : "",
                    })
                );
            });
        }

        if (newImagesContainer instanceof HTMLElement) {
            Array.from(
                newImagesContainer.querySelectorAll(
                    'input[type="radio"][name="main_image"][value^="new-"]'
                )
            ).forEach((radio, index) => {
                const image = radio.closest("label")?.querySelector("img");
                pushChoice(
                    toVariantImageChoice({
                        key: radio.value,
                        label: `Nouvelle ${index + 1}`,
                        url: image instanceof HTMLImageElement ? image.src : "",
                    })
                );
            });
        }

        if (aiGeneratedPreviewContainer instanceof HTMLElement) {
            Array.from(
                aiGeneratedPreviewContainer.querySelectorAll(
                    'input[type="radio"][name="main_image"][value^="ai-"]'
                )
            ).forEach((radio, index) => {
                const image = radio.closest("label")?.querySelector("img");
                pushChoice(
                    toVariantImageChoice({
                        key: radio.value,
                        label: `IA ${index + 1}`,
                        url: image instanceof HTMLImageElement ? image.src : "",
                    })
                );
            });
        }

        if (
            aiGeneratedInputsContainer instanceof HTMLElement &&
            aiGeneratedPreviewContainer instanceof HTMLElement &&
            aiGeneratedPreviewContainer.querySelector("img") === null
        ) {
            Array.from(
                aiGeneratedInputsContainer.querySelectorAll("input[data-ai-generated-index]")
            ).forEach((input, index) => {
                const aiKey = `ai-${input.dataset.aiGeneratedIndex || index}`;
                pushChoice(
                    toVariantImageChoice({
                        key: aiKey,
                        label: `IA ${index + 1}`,
                    })
                );
            });
        }

        return choices;
    };

    const syncVariantImageChoices = () => {
        const formState = getProductFormState();
        if (!formState || typeof formState.setVariantImageChoices !== "function") {
            return;
        }

        formState.setVariantImageChoices(collectVariantImageChoices());
    };

    return {
        syncVariantImageChoices,
    };
};
