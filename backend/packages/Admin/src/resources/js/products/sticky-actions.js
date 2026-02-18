export const initProductStickyActions = () => {
    const primaryActions = document.querySelector(
        "#product-primary-actions, #product-edit-primary-actions"
    );
    const stickyActions = document.querySelector(
        "#product-sticky-actions, #product-edit-sticky-actions"
    );

    if (
        !(primaryActions instanceof HTMLElement) ||
        !(stickyActions instanceof HTMLElement) ||
        !("IntersectionObserver" in window)
    ) {
        return;
    }

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
};
