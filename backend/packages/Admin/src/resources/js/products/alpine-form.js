const mainTabs = [
    "general",
    "media",
    "offer",
    "variants",
    "organization",
    "seo",
];

const variantTabs = ["options", "combinations", "pricing", "images"];

const toTrimmedString = (value) => (typeof value === "string" ? value.trim() : "");

const toNullableId = (value) => {
    if (typeof value === "number" && Number.isInteger(value) && value > 0) {
        return value;
    }

    if (typeof value === "string" && /^\d+$/.test(value.trim())) {
        const parsed = Number.parseInt(value.trim(), 10);
        return parsed > 0 ? parsed : null;
    }

    return null;
};

const toBoolean = (value, fallback = true) => {
    if (typeof value === "boolean") {
        return value;
    }

    if (typeof value === "number") {
        return value !== 0;
    }

    if (typeof value === "string") {
        const normalized = value.trim().toLowerCase();
        if (["1", "true", "on", "yes"].includes(normalized)) {
            return true;
        }
        if (["0", "false", "off", "no"].includes(normalized)) {
            return false;
        }
    }

    return fallback;
};

const normalizeOptions = (options) => {
    if (!Array.isArray(options)) {
        return [];
    }

    return options
        .map((option) => {
            if (!option || typeof option !== "object") {
                return null;
            }

            const name = toTrimmedString(option.name);
            const valuesText = Array.isArray(option.values)
                ? option.values
                      .map((value) => toTrimmedString(value))
                      .filter((value) => value !== "")
                      .join(",")
                : toTrimmedString(option.valuesText);

            return {
                id: toNullableId(option.id),
                name,
                valuesText,
            };
        })
        .filter((option) => option !== null);
};

const normalizeVariants = (variants) => {
    if (!Array.isArray(variants)) {
        return [];
    }

    return variants
        .map((variant) => {
            if (!variant || typeof variant !== "object") {
                return null;
            }

            const values = Array.isArray(variant.values)
                ? variant.values
                      .map((value) => toTrimmedString(value))
                      .filter((value) => value !== "")
                : [];

            const fallbackLabel = values
                .map((value) => value.split(":")[1] || "")
                .filter((value) => value !== "")
                .join(" / ");

            return {
                id: toNullableId(variant.id),
                label: toTrimmedString(variant.label) || fallbackLabel,
                sku: toTrimmedString(variant.sku),
                is_active: toBoolean(variant.is_active, true),
                stock_qty:
                    variant.stock_qty === null ||
                    variant.stock_qty === undefined ||
                    variant.stock_qty === ""
                        ? 0
                        : variant.stock_qty,
                price:
                    variant.price === null || variant.price === undefined
                        ? ""
                        : variant.price,
                compare_at_price:
                    variant.compare_at_price === null ||
                    variant.compare_at_price === undefined
                        ? ""
                        : variant.compare_at_price,
                image_key: toTrimmedString(variant.image_key),
                values,
            };
        })
        .filter((variant) => variant !== null);
};

const normalizeVariantImageChoices = (choices) => {
    if (!Array.isArray(choices)) {
        return [];
    }

    const dedup = new Set();
    return choices
        .map((choice) => {
            if (!choice || typeof choice !== "object") {
                return null;
            }

            const key = toTrimmedString(choice.key);
            if (key === "" || dedup.has(key)) {
                return null;
            }

            dedup.add(key);

            return {
                key,
                label: toTrimmedString(choice.label) || key,
                url: toTrimmedString(choice.url),
            };
        })
        .filter((choice) => choice !== null);
};

const defaultBulkVariantUpdate = () => ({
    stock_qty: "",
    price: "",
    compare_at_price: "",
    active_mode: "keep",
    image_key: "",
});

const normalizeMainTab = (value, fallback = "general") => {
    const normalized = toTrimmedString(value);
    if (mainTabs.includes(normalized)) {
        return normalized;
    }

    return mainTabs.includes(fallback) ? fallback : "general";
};

const normalizeVariantTab = (value, fallback = "options") => {
    const normalized = toTrimmedString(value);
    if (variantTabs.includes(normalized)) {
        return normalized;
    }

    return variantTabs.includes(fallback) ? fallback : "options";
};

export const createProductCreateForm = (config = {}) => {
    const normalizedConfig =
        config && typeof config === "object" && !Array.isArray(config)
            ? config
            : {
                  type: typeof config === "string" ? config : "simple",
              };

    return {
        productName: toTrimmedString(normalizedConfig.productName),
        productIsActive: toBoolean(normalizedConfig.isActive, true),
        productType: toTrimmedString(normalizedConfig.type) || "simple",
        activeTab: normalizeMainTab(normalizedConfig.activeTab, "general"),
        activeVariantTab: normalizeVariantTab(
            normalizedConfig.activeVariantTab,
            "options"
        ),
        options: normalizeOptions(normalizedConfig.options),
        variants: normalizeVariants(normalizedConfig.variants),
        variantImageChoices: normalizeVariantImageChoices(
            normalizedConfig.variantImageChoices
        ),
        bulkVariantUpdate: defaultBulkVariantUpdate(),

        setActiveTab(tab) {
            const nextTab = normalizeMainTab(tab, this.activeTab);

            if (nextTab === "variants" && this.productType !== "variant") {
                this.activeTab = "offer";
                return;
            }
            if (nextTab === "offer" && this.productType === "variant") {
                this.activeTab = "variants";
                return;
            }

            this.activeTab = nextTab;
        },

        isTabActive(tab) {
            return this.activeTab === tab;
        },

        setActiveVariantTab(tab) {
            this.activeVariantTab = normalizeVariantTab(tab, this.activeVariantTab);
        },

        isVariantTabActive(tab) {
            return this.activeVariantTab === tab;
        },

        onTypeChange() {
            if (this.productType === "simple") {
                this.variants = [];
                if (this.activeTab === "variants") {
                    this.activeTab = "offer";
                }
                return;
            }

            if (this.activeTab === "offer") {
                this.activeTab = "variants";
            }

            if (!Array.isArray(this.options) || this.options.length === 0) {
                this.addOption();
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

        hasVariantImageChoice(key) {
            const normalizedKey = toTrimmedString(key);
            if (normalizedKey === "") {
                return false;
            }

            return this.variantImageChoices.some(
                (choice) => choice.key === normalizedKey
            );
        },

        variantImagePreview(key) {
            const normalizedKey = toTrimmedString(key);
            if (normalizedKey === "") {
                return "";
            }

            const choice = this.variantImageChoices.find(
                (item) => item.key === normalizedKey
            );

            return choice ? choice.url : "";
        },

        variantImageLabel(key) {
            const normalizedKey = toTrimmedString(key);
            if (normalizedKey === "") {
                return "Image principale / auto";
            }

            if (normalizedKey === "__clear__") {
                return "Aucune image";
            }

            const choice = this.variantImageChoices.find(
                (item) => item.key === normalizedKey
            );

            return choice ? choice.label : normalizedKey;
        },

        setVariantImageChoices(choices = []) {
            this.variantImageChoices = normalizeVariantImageChoices(choices);
            const allowedKeys = new Set(
                this.variantImageChoices.map((choice) => choice.key)
            );

            this.variants = normalizeVariants(this.variants).map((variant) => {
                if (variant.image_key !== "" && !allowedKeys.has(variant.image_key)) {
                    return {
                        ...variant,
                        image_key: "",
                    };
                }

                return variant;
            });

            if (
                this.bulkVariantUpdate.image_key !== "" &&
                this.bulkVariantUpdate.image_key !== "__clear__" &&
                !allowedKeys.has(this.bulkVariantUpdate.image_key)
            ) {
                this.bulkVariantUpdate.image_key = "";
            }
        },

        applyBulkToVariants() {
            if (!Array.isArray(this.variants) || this.variants.length === 0) {
                return;
            }

            const stockValue = this.bulkVariantUpdate.stock_qty;
            const priceValue = this.bulkVariantUpdate.price;
            const compareValue = this.bulkVariantUpdate.compare_at_price;
            const activeMode = toTrimmedString(this.bulkVariantUpdate.active_mode);
            const imageMode = toTrimmedString(this.bulkVariantUpdate.image_key);

            const shouldApplyStock =
                stockValue !== "" && stockValue !== null && stockValue !== undefined;
            const shouldApplyPrice =
                priceValue !== "" && priceValue !== null && priceValue !== undefined;
            const shouldApplyCompare =
                compareValue !== "" &&
                compareValue !== null &&
                compareValue !== undefined;
            const shouldClearImage = imageMode === "__clear__";
            const shouldApplyImage =
                shouldClearImage ||
                (imageMode !== "" && this.hasVariantImageChoice(imageMode));

            this.variants = normalizeVariants(this.variants).map((variant) => {
                const nextVariant = {
                    ...variant,
                };

                if (shouldApplyStock) {
                    nextVariant.stock_qty = stockValue;
                }
                if (shouldApplyPrice) {
                    nextVariant.price = priceValue;
                }
                if (shouldApplyCompare) {
                    nextVariant.compare_at_price = compareValue;
                }
                if (activeMode === "active") {
                    nextVariant.is_active = true;
                } else if (activeMode === "inactive") {
                    nextVariant.is_active = false;
                }
                if (shouldApplyImage) {
                    nextVariant.image_key = shouldClearImage ? "" : imageMode;
                }

                return nextVariant;
            });
        },

        generateVariants() {
            const normalizedOptions = this.options
                .map((o) => ({
                    name: toTrimmedString(o.name),
                    values: this.splitValues(o.valuesText),
                }))
                .filter((o) => o.name && o.values.length);

            if (!normalizedOptions.length) {
                this.variants = [];
                return;
            }

            const combos = this.cartesian(normalizedOptions.map((o) => o.values));
            const normalizedVariants = normalizeVariants(this.variants);

            this.variants = combos.map((combo) => {
                const values = combo.map((val, idx) => {
                    const optName = normalizedOptions[idx].name;
                    return optName + ":" + val;
                });

                const fallbackLabel = values.map((v) => v.split(":")[1]).join(" / ");

                const existing = normalizedVariants.find((variant) => {
                    if (
                        !Array.isArray(variant.values) ||
                        variant.values.length !== values.length
                    ) {
                        return false;
                    }

                    return values.every((value) => variant.values.includes(value));
                });

                if (existing) {
                    return {
                        ...existing,
                        label: toTrimmedString(existing.label) || fallbackLabel,
                        values,
                    };
                }

                return {
                    id: null,
                    label: fallbackLabel,
                    sku: "",
                    is_active: true,
                    stock_qty: 0,
                    price: "",
                    compare_at_price: "",
                    image_key: "",
                    values,
                };
            });
        },

        init() {
            this.activeTab = normalizeMainTab(this.activeTab, "general");
            this.activeVariantTab = normalizeVariantTab(
                this.activeVariantTab,
                "options"
            );
            this.options = normalizeOptions(this.options);
            this.variants = normalizeVariants(this.variants);
            this.variantImageChoices = normalizeVariantImageChoices(
                this.variantImageChoices
            );

            // Keep assignments until DOM-driven image choices are synced.
            if (this.variantImageChoices.length > 0) {
                this.setVariantImageChoices(this.variantImageChoices);
            }

            if (this.productType === "simple") {
                this.variants = [];
                if (this.activeTab === "variants") {
                    this.activeTab = "offer";
                }
                return;
            }

            if (this.activeTab === "offer") {
                this.activeTab = "variants";
            }

            if (!Array.isArray(this.options) || this.options.length === 0) {
                this.addOption();
            }
        },
    };
};

export const registerProductCreateForm = () => {
    window.productCreateForm = createProductCreateForm;
};
