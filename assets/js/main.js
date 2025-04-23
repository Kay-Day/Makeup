// assets/js/main.js - Main JavaScript file for BeautyClick

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Password strength meter
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordStrength = document.getElementById('password-strength');
    
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = passwordInput.value;
            let strength = 0;
            let feedback = '';
            
            if (password.length >= 8) {
                strength += 1;
            }
            
            if (password.match(/[a-z]+/)) {
                strength += 1;
            }
            
            if (password.match(/[A-Z]+/)) {
                strength += 1;
            }
            
            if (password.match(/[0-9]+/)) {
                strength += 1;
            }
            
            if (password.match(/[\W]+/)) {
                strength += 1;
            }
            
            switch (strength) {
                case 0:
                case 1:
                    feedback = '<div class="progress-bar bg-danger" role="progressbar" style="width: 20%" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>';
                    break;
                case 2:
                    feedback = '<div class="progress-bar bg-warning" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>';
                    break;
                case 3:
                    feedback = '<div class="progress-bar bg-info" role="progressbar" style="width: 60%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>';
                    break;
                case 4:
                    feedback = '<div class="progress-bar bg-primary" role="progressbar" style="width: 80%" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"></div>';
                    break;
                case 5:
                    feedback = '<div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>';
                    break;
            }
            
            if (passwordStrength) {
                passwordStrength.innerHTML = feedback;
            }
        });
    }
    
    // Password matching check
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            if (passwordInput.value !== confirmPasswordInput.value) {
                confirmPasswordInput.setCustomValidity("Passwords don't match");
            } else {
                confirmPasswordInput.setCustomValidity('');
            }
        });
    }

    // File input preview
    const fileInput = document.querySelector('.custom-file-input');
    const filePreview = document.querySelector('.file-preview');
    
    if (fileInput && filePreview) {
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    filePreview.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" alt="Preview">`;
                    filePreview.style.display = 'block';
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    // Service search filter
    const searchInput = document.getElementById('service-search');
    const serviceCards = document.querySelectorAll('.service-card');
    
    if (searchInput && serviceCards.length > 0) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            serviceCards.forEach(function(card) {
                const title = card.querySelector('.service-title').textContent.toLowerCase();
                const description = card.querySelector('.service-description').textContent.toLowerCase();
                const artist = card.querySelector('.service-artist-name').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || description.includes(searchTerm) || artist.includes(searchTerm)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    // City selection
    const citySelect = document.getElementById('city-select');
    const locationWarning = document.getElementById('location-warning');
    
    if (citySelect && locationWarning) {
        citySelect.addEventListener('change', function() {
            const selectedCity = this.value;
            
            if (selectedCity !== 'Da Nang') {
                locationWarning.style.display = 'block';
            } else {
                locationWarning.style.display = 'none';
            }
        });
    }

    // Booking form validation
    const bookingForm = document.getElementById('booking-form');
    
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(event) {
            const selectedDate = document.getElementById('booking_date').value;
            const selectedTime = document.getElementById('booking_time').value;
            const address = document.getElementById('address').value;
            
            // Check if date is in the past
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const bookingDate = new Date(selectedDate);
            
            if (bookingDate < today) {
                event.preventDefault();
                alert('Please select a future date for booking.');
                return false;
            }
            
            // Check if address contains "Da Nang" or "Đà Nẵng"
            if (!address.toLowerCase().includes('da nang') && !address.toLowerCase().includes('đà nẵng')) {
                event.preventDefault();
                alert('We currently only provide services in Da Nang. Please enter a Da Nang address.');
                return false;
            }
            
            return true;
        });
    }

    // Price calculation in booking form
    const serviceSelect = document.getElementById('service_id');
    const discountCodeInput = document.getElementById('discount_code');
    const applyDiscountBtn = document.getElementById('apply-discount');
    const pointsCheckbox = document.getElementById('use_points');
    const originalPriceEl = document.getElementById('original-price');
    const discountAmountEl = document.getElementById('discount-amount');
    const pointsValueEl = document.getElementById('points-value');
    const finalPriceEl = document.getElementById('final-price');
    
    let originalPrice = 0;
    let discountAmount = 0;
    let pointsValue = 0;
    
    function updatePriceDisplay() {
        if (originalPriceEl) originalPriceEl.textContent = originalPrice.toLocaleString('vi-VN') + ' VND';
        if (discountAmountEl) discountAmountEl.textContent = discountAmount.toLocaleString('vi-VN') + ' VND';
        if (pointsValueEl) pointsValueEl.textContent = pointsValue.toLocaleString('vi-VN') + ' VND';
        if (finalPriceEl) finalPriceEl.textContent = (originalPrice - discountAmount - pointsValue).toLocaleString('vi-VN') + ' VND';
    }
    
    if (serviceSelect) {
        serviceSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            originalPrice = parseInt(selectedOption.dataset.price || 0);
            discountAmount = 0;
            pointsValue = 0;
            
            if (discountCodeInput) discountCodeInput.value = '';
            if (pointsCheckbox) pointsCheckbox.checked = false;
            
            updatePriceDisplay();
        });
    }
    
    if (applyDiscountBtn && discountCodeInput) {
        applyDiscountBtn.addEventListener('click', function(event) {
            event.preventDefault();
            
            const discountCode = discountCodeInput.value.trim();
            if (!discountCode) return;
            
            // AJAX call to validate discount code
            fetch('/beautyclick/services/validate_discount.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `code=${encodeURIComponent(discountCode)}&amount=${originalPrice}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.valid) {
                    discountAmount = parseInt(data.discount_amount);
                    updatePriceDisplay();
                    alert('Discount code applied successfully!');
                } else {
                    discountAmount = 0;
                    updatePriceDisplay();
                    alert('Invalid discount code or minimum purchase amount not met.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while validating the discount code.');
            });
        });
    }
    
    if (pointsCheckbox) {
        pointsCheckbox.addEventListener('change', function() {
            if (this.checked) {
                pointsValue = parseInt(this.dataset.pointsValue || 0);
            } else {
                pointsValue = 0;
            }
            
            updatePriceDisplay();
        });
    }

    // Time slots selection
    const dateInput = document.getElementById('booking_date');
    const timeSlotsContainer = document.getElementById('time-slots');
    
    if (dateInput && timeSlotsContainer) {
        dateInput.addEventListener('change', function() {
            const selectedDate = this.value;
            const artistId = timeSlotsContainer.dataset.artistId;
            
            if (!selectedDate || !artistId) return;
            
            // AJAX call to get available time slots
            fetch(`/beautyclick/services/get_time_slots.php?artist_id=${artistId}&date=${selectedDate}`)
            .then(response => response.json())
            .then(data => {
                timeSlotsContainer.innerHTML = '';
                
                if (data.slots && data.slots.length > 0) {
                    const row = document.createElement('div');
                    row.className = 'row g-2 mt-2';
                    
                    data.slots.forEach(slot => {
                        const col = document.createElement('div');
                        col.className = 'col-md-3 col-6';
                        
                        const label = document.createElement('label');
                        label.className = 'time-slot-label';
                        
                        const input = document.createElement('input');
                        input.type = 'radio';
                        input.name = 'booking_time';
                        input.value = slot.time;
                        input.id = `time-${slot.time.replace(':', '')}`;
                        input.className = 'btn-check';
                        input.required = true;
                        
                        const btn = document.createElement('span');
                        btn.className = 'btn btn-outline-primary w-100';
                        btn.textContent = slot.formatted_time;
                        
                        if (!slot.available) {
                            input.disabled = true;
                            btn.className = 'btn btn-outline-secondary w-100';
                            btn.innerHTML = `${slot.formatted_time} <span class="badge bg-danger">Booked</span>`;
                        }
                        
                        label.appendChild(input);
                        label.appendChild(btn);
                        col.appendChild(label);
                        row.appendChild(col);
                    });
                    
                    timeSlotsContainer.appendChild(row);
                } else {
                    timeSlotsContainer.innerHTML = '<div class="alert alert-warning">No available time slots for this date. Please select another date.</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                timeSlotsContainer.innerHTML = '<div class="alert alert-danger">An error occurred while fetching time slots.</div>';
            });
        });
    }

    // Post like functionality
    const likeButtons = document.querySelectorAll('.post-like-btn');
    
    if (likeButtons.length > 0) {
        likeButtons.forEach(function(button) {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                
                const postId = this.dataset.postId;
                const likeCountEl = document.getElementById(`like-count-${postId}`);
                
                fetch('/beautyclick/posts/like.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `post_id=${postId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.liked) {
                            this.innerHTML = '<i class="fas fa-heart text-danger"></i>';
                        } else {
                            this.innerHTML = '<i class="far fa-heart"></i>';
                        }
                        
                        if (likeCountEl) {
                            likeCountEl.textContent = data.likes_count;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });
    }

    // Artist availability management
    const availabilityForm = document.getElementById('availability-form');
    
    if (availabilityForm) {
        const dayCheckboxes = document.querySelectorAll('.day-checkbox');
        
        dayCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const dayId = this.dataset.day;
                const timeInputs = document.querySelectorAll(`.time-input-${dayId}`);
                
                timeInputs.forEach(function(input) {
                    input.disabled = !checkbox.checked;
                });
            });
        });
    }

    // Image gallery modal
    const galleryImages = document.querySelectorAll('.gallery-image');
    const galleryModal = document.getElementById('gallery-modal');
    
    if (galleryImages.length > 0 && galleryModal) {
        const modalImage = galleryModal.querySelector('.modal-image');
        
        galleryImages.forEach(function(image) {
            image.addEventListener('click', function() {
                modalImage.src = this.dataset.fullImage || this.src;
                const galleryModal = new bootstrap.Modal(document.getElementById('gallery-modal'));
                galleryModal.show();
            });
        });
    }
});