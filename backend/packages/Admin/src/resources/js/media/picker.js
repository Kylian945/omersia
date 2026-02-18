function mediaPicker(name) {
    return {
        name: name,
        isOpen: false,
        loading: false,
        currentFolderId: null,
        folders: [],
        items: [],
        breadcrumbs: [],
        selectedImage: null,
        callback: null,

        handleOpen(event) {
            if (event.detail.name === this.name) {
                this.callback = event.detail.callback;
                this.open();
            }
        },

        async open() {
            this.isOpen = true;
            this.selectedImage = null;
            await this.loadMedia();
        },

        close() {
            this.isOpen = false;
            this.selectedImage = null;
            this.callback = null;
        },

        async loadMedia(folderId = null) {
            this.loading = true;
            this.currentFolderId = folderId;

            try {
                const url = new URL(window.mediaPickerConfig.mediaRoute);
                if (folderId) {
                    url.searchParams.append('folder_id', folderId);
                }

                const response = await fetch(url);
                const data = await response.json();

                this.folders = data.folders;
                this.items = data.items;
                this.breadcrumbs = data.breadcrumbs;
            } catch (error) {
                console.error('Error loading media:', error);
            } finally {
                this.loading = false;
            }
        },

        async navigateToFolder(folderId) {
            await this.loadMedia(folderId);
        },

        selectImage(item) {
            this.selectedImage = item;
        },

        getPreferredMediaUrl(item) {
            if (!item || typeof item !== "object") {
                return "";
            }

            return item.optimized_url || item.avif_url || item.webp_url || item.url || "";
        },

        getSizeLevelClass(level) {
            switch (level) {
                case "ok":
                    return "border-emerald-200 bg-emerald-50 text-emerald-700";
                case "warning":
                    return "border-amber-200 bg-amber-50 text-amber-700";
                default:
                    return "border-red-200 bg-red-50 text-red-700";
            }
        },

        confirmSelection() {
            if (this.selectedImage && this.callback) {
                const preferredUrl = this.getPreferredMediaUrl(this.selectedImage);
                this.callback({
                    ...this.selectedImage,
                    original_url: this.selectedImage.url || "",
                    url: preferredUrl,
                    selected_url: preferredUrl,
                });
            }
            this.close();
        },

        async uploadFiles(event) {
            const files = event.target.files;
            if (!files.length) return;

            this.loading = true;

            const formData = new FormData();
            for (let i = 0; i < files.length; i++) {
                formData.append('images[]', files[i]);
            }
            if (this.currentFolderId) {
                formData.append('folder_id', this.currentFolderId);
            }

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                    || window.mediaPickerConfig?.csrfToken
                    || '';

                const response = await fetch(window.mediaPickerConfig.storeRoute, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                    },
                    body: formData,
                });

                if (response.ok) {
                    await this.loadMedia(this.currentFolderId);
                } else {
                    const errorData = await response.json().catch(() => ({}));
                    console.error('Upload failed:', errorData);
                    const message = errorData.message || 'Erreur lors de l\'upload de l\'image';
                    if (typeof window.showToast === 'function') {
                        window.showToast(message, 'error');
                    } else {
                        alert(message);
                    }
                }
            } catch (error) {
                console.error('Error uploading files:', error);
                const message = 'Erreur lors de l\'upload de l\'image';
                if (typeof window.showToast === 'function') {
                    window.showToast(message, 'error');
                } else {
                    alert(message);
                }
            } finally {
                this.loading = false;
                event.target.value = '';
            }
        }
    }
}

// Export pour utilisation globale
window.mediaPicker = mediaPicker;
