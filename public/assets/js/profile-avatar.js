/**
 * Profile Avatar Cropper
 * Permite recortar imagem de avatar antes do upload
 */
(function() {
    'use strict';

    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');
    const avatarCropped = document.getElementById('avatarCropped');
    const cropperModalEl = document.getElementById('avatarCropperModal');
    const cropperImage = document.getElementById('avatarCropperImage');
    const applyCropButton = document.getElementById('applyAvatarCrop');
    let cropperInstance = null;

    if (!avatarInput || !cropperModalEl) {
        return;
    }

    const cropperModal = new bootstrap.Modal(cropperModalEl);

    avatarInput.addEventListener('change', (event) => {
        const [file] = event.target.files || [];

        if (!file) {
            return;
        }

        const url = URL.createObjectURL(file);
        cropperImage.src = url;
        cropperImage.classList.remove('d-none');
        cropperModal.show();
    });

    cropperModalEl.addEventListener('shown.bs.modal', () => {
        if (cropperInstance) {
            cropperInstance.destroy();
        }

        cropperInstance = new Cropper(cropperImage, {
            aspectRatio: 1,
            viewMode: 1,
            autoCropArea: 1,
            guides: false,
            movable: false,
            zoomable: true,
        });
    });

    cropperModalEl.addEventListener('hidden.bs.modal', () => {
        if (cropperInstance) {
            cropperInstance.destroy();
            cropperInstance = null;
        }
    });

    applyCropButton.addEventListener('click', () => {
        if (!cropperInstance) {
            return;
        }

        const canvas = cropperInstance.getCroppedCanvas({ width: 512, height: 512 });

        if (!canvas) {
            return;
        }

        const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
        avatarCropped.value = dataUrl;
        avatarPreview.src = dataUrl;
        cropperModal.hide();
    });
})();
