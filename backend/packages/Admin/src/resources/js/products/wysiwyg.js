export const initProductWysiwyg = () => {
    const productWysiwygEditors = new Map();

    const isLikelyHtml = (value) => /<[a-z][\s\S]*>/i.test(value || "");

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

    const editorContainers = Array.from(
        document.querySelectorAll("[data-product-wysiwyg]")
    );

    if (editorContainers.length === 0) {
        return {
            syncWysiwygEditors,
            setFormFieldValue,
        };
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

        return {
            syncWysiwygEditors,
            setFormFieldValue,
        };
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

        const input = document.querySelector(`textarea[name="${fieldName}"]`);
        if (!input) {
            return;
        }

        const quill = new window.Quill(container, {
            theme: "snow",
            modules: {
                toolbar,
            },
            placeholder: container.dataset.productWysiwygPlaceholder || "",
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
        if (quillEditor && Number.isFinite(minHeight) && minHeight > 0) {
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

    document.querySelectorAll("form").forEach((form) => {
        if (form.querySelector("[data-product-wysiwyg]")) {
            form.addEventListener("submit", () => {
                syncWysiwygEditors();
            });
        }
    });

    return {
        syncWysiwygEditors,
        setFormFieldValue,
    };
};
