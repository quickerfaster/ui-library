


document.addEventListener('livewire:initialized', function () {




    ///************ PRINT EVENT  *************///
    Livewire.on('print-table-event', (event) => {
        printTable();
    });




    ///************ MODALS FOR ADD-EDIT   *************///
    Livewire.on('open-modal-event', function (event) {
        let modalId = event[0].modalId;

        let modalWrapper = null;
        modalWrapper = document.getElementById(modalId);
        modalWrapper.style.zIndex = 1040 + (parseInt(modalId)); // Adjust starting value if needed
        modalWrapper.classList.add('is-open');
        modalWrapper.style.visibility = "visible !important";
    });

    Livewire.on('close-modal-event', function (event) {
        componentIds = event[0].componentIds;
        const modalWrapper = document.getElementById(event[0].modalId);

        if (!modalWrapper) return;

        modalWrapper.classList.remove('is-open'); // Start closing animation
        // Wait for animation to finish before removing the modal
        /*setTimeout(() => {
            if (event[0].modalId != "addEditModal") // Dont remove the parent modal [addEditModal]
                modalWrapper.remove();
        }, 800); // Adjust timing to match your CSS transition duration*/
    });





    ///************ CHILD MODAL HANDLING   *************///
    // Function to set the child modal content dynamically
    function setChildModalContent(modalHtml) {
        let container = document.createElement('div');
        document.body.insertBefore(container, document.body.firstChild);

        let temp = container.innerHTML;
        container.innerHTML = " ";
        container.innerHTML = modalHtml + temp;

        // Use outerHTML to properly add wire:ignore
        //container.outerHTML = container.outerHTML.replace('<div', '<div wire:ignore.self');
    }


    ///************ CHILD MODAL OPEN EVENT  *************///
    Livewire.on('open-child-modal-event', (event) => {
        // Set the child modal content
        const modalHtml = event[0].modalHtml; // Or event[0].modalHtml if it's an array
        const modalId = event[0].modalId; // Or event[0].modalId if it's an array

        setChildModalContent(modalHtml);

        Livewire.dispatch('open-modal-event', [{
            "modalId": modalId
        }]); // To show modal
    });



    ///************ SHOW IMAGE CROP MODAL EVENT  *************///
    if (!window.__cropModalListenerRegistered) {
        window.__cropModalListenerRegistered = true;

        let cropper;
        Livewire.on('show-crop-image-modal-event', (event) => {

            // Data from the Backend
            const field = event[0].field;
            const imgSrc = event[0].imgUrl;
            const modalHtml = event[0].modalHtml;
            const modalId = event[0].modalId;
            const componentId = event[0].id;

            // Get the reference of the LIVEWIRE COMPONENT THAT TRIGER THIS EVENT
            const component = Livewire.find(componentId);

            // Set the child modal content
            setChildModalContent(modalHtml);

            // Get the modal element by ID
            const modalElement = document.getElementById(modalId);

            // Create a new Bootstrap Modal instance
            const myModal = new bootstrap.Modal(modalElement, {
                keyboard: false // Optional: Disable closing the modal with keyboard
            });

            // Show the modal
            myModal.show();

            // Set up the Cropper.js instance once the modal is fully shown
            modalElement.addEventListener('shown.bs.modal', function () {
                const cropperContainer = document.getElementById('cropper-image-container' +
                    modalId);

                // Ensure the container has 100% width and appropriate height
                cropperContainer.style.width = '100%';
                cropperContainer.style.height = '70vh'; // Adjust height as needed

                // Get the image element (img with empty src must exist for the JCroper.js to work)
                const image = document.getElementById('image-to-crop' +
                    modalId); // Ensure you have an image element with this ID

                if (image) {
                    image.src = imgSrc;
                    // Destroy the old cropper instance if it exists
                    if (cropper) {
                        cropper.destroy();
                    }

                    // Create the JCripper instance and display the image
                    cropper = new Cropper(image, {
                        aspectRatio: 0, // Set aspect ratio if needed
                        viewMode: 2, // Adjust the view mode as needed
                        autoCropArea: 1, // Optional: Set the initial crop area to cover the entire image
                        responsive: true // Optional: Enable responsive mode
                    });

                    // Track button click for saving the croped image
                    document.getElementById('save-croped-image' + modalId).addEventListener(
                        'click',
                        function () {
                            // Get the cropped image data URL
                            const croppedImage = cropper.getCroppedCanvas().toDataURL(
                                'image/jpeg');

                            // Emit the event to Livewire with the Base64 image data
                            if (component) {
                                // Call the method on the correct component instance
                                component.call('saveCroppedImage', field, croppedImage,
                                    component.id);
                            }

                            // Close the modal
                            myModal.hide();
                        });
                }
            });

        })

    }

    ///****************** SWEET ALEART DIALOGS  *******************///

    Livewire.on('confirm-delete', (event) => {

        // Use the theme's showSwal function for 'warning-message-and-confirmation'
        //soft.showSwal('warning-message-and-confirmation'); // Adjust if needed based on your theme configuration

        ///************ CONFIRM DELETE  *************///
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this operation!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'No, cancel!',
            customClass: {
                confirmButton: 'btn bg-gradient-success me-3',
                cancelButton: 'btn bg-gradient-danger'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                //form.submit();
                Livewire.dispatch('deleteSelectedEvent');
            }
        });

    });


    Livewire.on('confirm-page-refresh', (event) => {
        ///************ CONFIRM PAGE REFRESH  *************///
        Swal.fire({
            title: 'Refreshing!',
            text: "To improve the performance, the page will be refreshed.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Refresh',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn bg-gradient-success me-3',
                cancelButton: 'btn bg-gradient-danger'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                location.reload(); // Refresh web browser page
            }
        });

    });

        // We define a global function or attach it to the window
        window.confirmAndDispatch = (eventClass, params) => {
            Swal.fire({
                title: 'Confirm Operation',
                text: "Do you want to proceed with this action?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Confirm',
            }).then((result) => {
                if (result.isConfirmed) {
                    // Using the Livewire JS dispatch method
                    Livewire.dispatch('dispatchStandardEvent', { 
                        eventClass: eventClass, 
                        params: params 
                    });
                }
            });
        }




    ///************ SUCCESS DIALOG *************///
    window.addEventListener('swal:success', function (event) {
        Swal.fire({
            title: event.detail[0].title,
            text: event.detail[0].text,
            icon: event.detail[0].icon,
            showConfirmButton: false,
            timer: 2000
        });
    });

    ///************ ERROR DIALOG  *************///
    window.addEventListener('swal:error', function (event) {

        Swal.fire({
            title: event.detail[0].title,
            text: event.detail[0].text,
            icon: 'error', // Use 'error' icon
            showConfirmButton: true, // Show the OK button
            confirmButtonText: 'OK', // Text for the OK button
            customClass: {
                confirmButton: 'btn bg-gradient-success me-3',
                cancelButton: 'btn bg-gradient-danger'
            },
        });
    });














});





function printTable() {
    printJS({
        printable: 'dataTable',
        type: 'html',
        showModal: true,
        style: `
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
                    .no-print { display: none; } /* Hide elements with the 'no-print' class */
                `
    });
}





document.addEventListener('livewire:init', () => {


    function initFlatpickr() {
        if (typeof flatpickr === 'undefined') return;

        document.querySelectorAll('.datepicker').forEach(el => {
            if (el._flatpickr) el._flatpickr.destroy();
        });

        flatpickr('.datepicker', {
            dateFormat: "Y-m-d"
        });
        flatpickr('.datetimepicker', {
            enableTime: true,
            dateFormat: "Y-m-d H:i"
        });
        flatpickr('.timepicker', {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true
        });
    }

    // Initialize on first load
    initFlatpickr();

    // ✅ CORRECT LIVEMIRE 3 HOOK
    Livewire.hook('morphed', ({
        el,
        component
    }) => {

        initFlatpickr();
    });
});



