// Media library - Alpine.js component
window.mediaLibrary = function() {
    return {
        showCreateFolderModal: false,
        uploading: false,

        openCreateFolderModal() {
            this.showCreateFolderModal = true;
        },

        submitUpload() {
            this.uploading = true;
            const form = this.$refs.uploadForm;
            if (form) {
                form.submit();
            }
        }
    }
};
