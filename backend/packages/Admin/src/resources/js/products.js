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
    /**
     * Gestion des previews d'images pour la création
     */
    const createInput = document.querySelector('input[name="images[]"]');
    const createPreview = document.getElementById("image-preview-container");
    const mainInputCreate = document.getElementById("main_image_input");

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
                    mainInputCreate.value = index;
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
                    mainInputEdit.value = radio.value;
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
                if (mainInputEdit) {
                    mainInputEdit.value = radio.value;
                }
            });
        });

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
