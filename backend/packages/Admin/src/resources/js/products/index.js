import "../core/quill-editor.js";
import { registerProductCreateForm } from "./alpine-form";
import { initProductWysiwyg } from "./wysiwyg";
import { initProductImagePreviews } from "./image-previews";
import { createVariantImageChoicesSync } from "./variant-image-choices";
import { initProductAiAssistant } from "./ai-assistant";
import { initProductStickyActions } from "./sticky-actions";

registerProductCreateForm();

document.addEventListener("DOMContentLoaded", () => {
    const { syncWysiwygEditors, setFormFieldValue } = initProductWysiwyg();

    let syncVariantImageChoices = () => {};

    const imageContext = initProductImagePreviews({
        onImagesChanged: () => {
            syncVariantImageChoices();
        },
    });

    const variantImageChoices = createVariantImageChoicesSync(imageContext);
    syncVariantImageChoices = variantImageChoices.syncVariantImageChoices;

    window.setTimeout(() => {
        syncVariantImageChoices();
    }, 0);

    initProductAiAssistant({
        syncWysiwygEditors,
        setFormFieldValue,
        mainImageInput: imageContext.mainImageInput,
        createInput: imageContext.createInput,
        syncVariantImageChoices,
        aiGeneratedPreviewContainer: imageContext.aiGeneratedPreviewContainer,
        aiGeneratedInputsContainer: imageContext.aiGeneratedInputsContainer,
    });

    initProductStickyActions();
});
