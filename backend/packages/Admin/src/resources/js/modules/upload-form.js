// Module upload form - Alpine.js component
window.uploadForm = function() {
    return {
        dragging: false,
        fileName: '',
        submitting: false,
        updateFileName(e) {
            const f = e.target.files?.[0];
            this.fileName = f ? f.name : '';
        },
        handleDrop(ev) {
            this.dragging = false;
            const file = ev.dataTransfer.files?.[0];
            if (file && file.name.toLowerCase().endsWith('.zip')) {
                this.$refs.file.files = ev.dataTransfer.files;
                this.fileName = file.name;
            }
        }
    }
};
