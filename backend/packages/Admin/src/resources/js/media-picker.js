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

        confirmSelection() {
            if (this.selectedImage && this.callback) {
                this.callback(this.selectedImage);
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
                const response = await fetch(window.mediaPickerConfig.storeRoute, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: formData,
                });

                if (response.ok) {
                    await this.loadMedia(this.currentFolderId);
                }
            } catch (error) {
                console.error('Error uploading files:', error);
            } finally {
                this.loading = false;
                event.target.value = '';
            }
        }
    }
}

// Export pour utilisation globale
window.mediaPicker = mediaPicker;
