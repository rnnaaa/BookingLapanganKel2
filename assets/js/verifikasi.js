document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('bukti_pembayaran');
    const fileNamePreview = document.getElementById('file-name-preview');

    if (fileInput) {
        // Update nama file saat dipilih
        fileInput.addEventListener('change', function() {
            if (fileInput.files.length > 0) {
                fileNamePreview.textContent = fileInput.files[0].name;
            } else {
                fileNamePreview.textContent = 'Klik untuk memilih file';
            }
        });
    }
});