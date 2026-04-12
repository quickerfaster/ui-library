


document.addEventListener('livewire:initialized', function () {


    // Listen for browser events to control Bootstrap modal
    // THIS IS SHARED BY THE form-modal-blade.php AND detail-modal-blade.php 
    Livewire.on('open-bs-modal', function (event) {

        const modalId = event[0].modalId;
        const modalEl = document.getElementById(modalId);
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    });

    Livewire.on('close-bs-modal', function (event) {
        const modalId = event[0].modalId;
        const modalEl = document.getElementById(modalId);
        if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        }
    });



});
