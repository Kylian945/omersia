export const initProductImagePreviews = ({ onImagesChanged = () => {} } = {}) => {
    const createInput = document.querySelector('input[name="images[]"]');
    const createPreview = document.getElementById("image-preview-container");
    const newImagesInput = document.getElementById("new-images-input");
    const newImagesContainer = document.getElementById("new-images-preview");
    const mainImageInput = document.getElementById("main_image_input");
    const aiGeneratedPreviewContainer = document.getElementById(
        "ai-generated-images-preview"
    );
    const aiGeneratedInputsContainer = document.getElementById(
        "ai-generated-images-inputs"
    );

    if (
        createInput instanceof HTMLInputElement &&
        createPreview instanceof HTMLElement &&
        mainImageInput instanceof HTMLInputElement
    ) {
        createInput.addEventListener("change", () => {
            createPreview.innerHTML = "";
            Array.from(createInput.files ?? []).forEach((file, index) => {
                const url = URL.createObjectURL(file);

                const wrapper = document.createElement("button");
                wrapper.type = "button";
                wrapper.className =
                    "relative border rounded-xl overflow-hidden group focus:outline-none";
                wrapper.addEventListener("click", () => {
                    mainImageInput.value = String(index);
                    [...createPreview.querySelectorAll("[data-main]")].forEach((el) => {
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

            onImagesChanged();
        });
    }

    if (
        newImagesInput instanceof HTMLInputElement &&
        newImagesContainer instanceof HTMLElement &&
        mainImageInput instanceof HTMLInputElement
    ) {
        newImagesInput.addEventListener("change", () => {
            newImagesContainer.innerHTML = "";
            Array.from(newImagesInput.files ?? []).forEach((file, index) => {
                const url = URL.createObjectURL(file);

                const wrapper = document.createElement("label");
                wrapper.className =
                    "relative border rounded-xl overflow-hidden cursor-pointer group";

                const img = document.createElement("img");
                img.src = url;
                img.className = "w-full h-52 object-cover group-hover:opacity-95";

                const bar = document.createElement("div");
                bar.className =
                    "absolute bottom-1 left-1 right-1 flex items-center justify-center gap-1 bg-black/60 text-white text-xxxs px-1.5 py-0.5 rounded-full";

                const radio = document.createElement("input");
                radio.type = "radio";
                radio.name = "main_image";
                radio.value = "new-" + index;
                radio.className = "h-2 w-2";
                radio.addEventListener("change", () => {
                    mainImageInput.value = radio.value;
                });

                const text = document.createElement("span");
                text.textContent = "Définir comme principale";

                bar.appendChild(radio);
                bar.appendChild(text);
                wrapper.appendChild(img);
                wrapper.appendChild(bar);
                newImagesContainer.appendChild(wrapper);
            });

            onImagesChanged();
        });
    }

    document
        .querySelectorAll('input[type="radio"][name="main_image"]')
        .forEach((radio) => {
            radio.addEventListener("change", () => {
                if (mainImageInput instanceof HTMLInputElement) {
                    mainImageInput.value = radio.value;
                }
            });
        });

    return {
        createInput,
        createPreview,
        newImagesContainer,
        mainImageInput,
        aiGeneratedPreviewContainer,
        aiGeneratedInputsContainer,
    };
};
