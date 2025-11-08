

document.addEventListener('DOMContentLoaded', () => {

    // --- Global State for Order ---
    // This object will hold order details between pages
    const currentOrder = {
        packageName: 'Premium', // Default package
        packagePrice: '₹999',
        features: ['All premium features', 'Priority support', 'Editable Extra mix revisions'],
        language: 'English',     // Default language
        description: ''
    };

    // --- Page Navigation ---
    const pageSections = document.querySelectorAll('.page-section');
    const navLinks = document.querySelectorAll('.nav-link');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileMenuButton = document.getElementById('mobile-menu-button');

    // Function to show a specific page section
    window.showSection = (sectionId) => {
        pageSections.forEach(section => {
            if (section.id === sectionId) {
                section.classList.add('active');
            } else {
                section.classList.remove('active');
            }
        });
        // Scroll to top of new section
        window.scrollTo(0, 0);
        // Close mobile menu if open
        if (!mobileMenu.classList.contains('hidden')) {
            mobileMenu.classList.add('hidden');
        }
    };

    // Add click listeners to all nav links
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const sectionId = link.getAttribute('data-section');
            if (sectionId) {
                showSection(sectionId);
            }
        });
    });

    // Mobile menu toggle
    mobileMenuButton.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');
    });

    // --- Review Carousel ---
    const reviewSlider = document.getElementById('review-slider');
    const reviewPrev = document.getElementById('review-prev');
    const reviewNext = document.getElementById('review-next');
    
    if (reviewSlider) {
        let currentReviewIndex = 0;
        const reviews = reviewSlider.children;
        const totalReviews = reviews.length;
        // Clone reviews for infinite loop effect
        for(let i=0; i<totalReviews; i++) {
            reviewSlider.appendChild(reviews[i].cloneNode(true));
        }
        
        const reviewSlideWidth = () => {
             // On mobile, show 1. On desktop, show 3.
            return window.innerWidth < 768 ? 100 : (100 / 3);
        }

        const updateReviewSlider = () => {
            const width = reviewSlideWidth();
            reviewSlider.style.transform = `translateX(-${currentReviewIndex * width}%)`;
            
            // Adjust review card widths based on screen size
            const cards = reviewSlider.querySelectorAll('.flex-shrink-0');
            if (window.innerWidth < 768) {
                cards.forEach(card => card.classList.remove('md:w-1/3'));
            } else {
                 cards.forEach(card => card.classList.add('md:w-1/3'));
            }
        };

        reviewNext.addEventListener('click', () => {
            currentReviewIndex++;
            reviewSlider.style.transition = 'transform 0.5s ease-in-out';
            
            let limit = totalReviews;
            if (window.innerWidth >= 768) {
                limit = totalReviews - 2; // Stop 3 from end on desktop
            }

            if (currentReviewIndex >= limit) {
                // At the end of the "real" slides
                setTimeout(() => {
                    // Jump back to start without transition
                    reviewSlider.style.transition = 'none';
                    currentReviewIndex = 0;
                    updateReviewSlider();
                }, 500); // Must match transition duration
            }
            updateReviewSlider();
        });

        reviewPrev.addEventListener('click', () => {
            if (currentReviewIndex === 0) {
                 // At the start, jump to the end (clones)
                reviewSlider.style.transition = 'none';
                let limit = totalReviews;
                if (window.innerWidth >= 768) {
                    limit = totalReviews - 2; 
                }
                currentReviewIndex = limit - 1;
                updateReviewSlider();
            }

            setTimeout(() => {
                // Slide back
                currentReviewIndex--;
                reviewSlider.style.transition = 'transform 0.5s ease-in-out';
                updateReviewSlider();
            }, 50); // Short delay to allow jump to apply
        });
        
        // Initial setup and resize handling
        updateReviewSlider();
        window.addEventListener('resize', updateReviewSlider);
    }


    // --- Package Selection (Home Page) ---
    const packageCards = document.querySelectorAll('.package-card');
    
    // Function to update the 'select-package' form
    const updateSelectPackageForm = () => {
        document.getElementById('package-name').textContent = currentOrder.packageName;
        document.getElementById('package-price').textContent = currentOrder.packagePrice;
        
        const featuresList = document.getElementById('package-features');
        featuresList.innerHTML = ''; // Clear old features
        currentOrder.features.forEach(feature => {
            const li = document.createElement('li');
            li.className = 'flex items-center space-x-2';
            li.innerHTML = `
                <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                <span>${feature}</span>
            `;
            featuresList.appendChild(li);
        });
    };

    // 'Choose Package' button click (on home/pricing)
    window.choosePackageAndShow = (button, name, price, features) => {
        // Update global state
        currentOrder.packageName = name;
        currentOrder.packagePrice = price;
        currentOrder.features = features;

        // Remove 'selected-card' from all
        packageCards.forEach(card => card.classList.remove('selected-card'));
        
        // Add 'selected-card' to the clicked one (if button exists)
        if (button) {
            button.closest('.package-card').classList.add('selected-card');
        }

        // Update the form on the next page
        updateSelectPackageForm();
        
        // Show the 'select-package' page
        showSection('select-package');
    };

    // --- 'select-package' Page Logic ---
    const langButtons = document.querySelectorAll('#lang-grid .btn-lang');
    const songDescriptionInput = document.getElementById('song-description');
    
    // Language button clicks
    langButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Update global state
            currentOrder.language = button.getAttribute('data-lang');
            
            // Update styles
            langButtons.forEach(btn => {
                btn.classList.remove('bg-black', 'text-white');
                btn.classList.add('bg-white', 'text-gray-700', 'border', 'border-gray-300', 'hover:bg-gray-50');
            });
            button.classList.add('bg-black', 'text-white');
            button.classList.remove('bg-white', 'text-gray-700', 'border', 'border-gray-300', 'hover:bg-gray-50');
        });
    });

    // 'Continue to Payment' button click
    document.getElementById('continue-to-payment-btn').addEventListener('click', () => {
        // Save description to global state
        currentOrder.description = songDescriptionInput.value;
        
        // Populate the hidden fields on the payment form
        document.getElementById('package_name').value = currentOrder.packageName;
        document.getElementById('package_price').value = currentOrder.packagePrice;
        document.getElementById('language').value = currentOrder.language;
        document.getElementById('description').value = currentOrder.description;
        
        // Show the payment page
        showSection('payment');
    });

    // --- Payment Page Logic (customerForm) ---
    const customerForm = document.getElementById('customerForm');
    const payButton = document.getElementById('pay-button');
    const payButtonText = payButton.querySelector('.pay-button-text');
    const payButtonLoader = payButton.querySelector('.loader-container');
    const paymentErrorMsg = document.getElementById('payment-error');

    customerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Show loader, hide text
        payButton.disabled = true;
        payButtonText.classList.add('hidden');
        payButtonLoader.classList.remove('hidden');
        paymentErrorMsg.classList.add('hidden');

        // 1. Get all data for the order
        const orderData = {
            name: document.getElementById('name').value,
            email: document.getElementById('email').value,
            mobile: document.getElementById('mobile').value,
            language: document.getElementById('language').value,
            description: document.getElementById('description').value,
            packageName: document.getElementById('package_name').value,
            packagePrice: document.getElementById('package_price').value
        };

        try {
            // 2. Create the order on the backend
            const createOrderResponse = await fetch('create_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(orderData)
            });

            const orderResult = await createOrderResponse.json();

            if (!orderResult.success) {
                throw new Error(orderResult.message || 'Failed to create order.');
            }

            // 3. If order is created, open Razorpay checkout
            const options = {
                "key": orderResult.razorpay_key_id, // From create_order.php
                "amount": orderResult.amount, // Amount in paise
                "currency": "INR",
                "name": "ForeverTunes",
                "description": `Payment for ${orderResult.customer_name}`,
                "image": "https://placehold.co/100x100/000000/FFFFFF?text=FT", // Your Logo
                "order_id": "", // We will use this in the handler
                "handler": async (response) => {
                    // 4. Payment is successful, now verify it
                    const verificationData = {
                        razorpay_payment_id: response.razorpay_payment_id,
                        order_id: orderResult.order_id, // The ID from our database
                        amount: orderResult.amount // Pass amount to verify
                    };

                    const verifyResponse = await fetch('verify_payment.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(verificationData)
                    });
                    
                    const verifyResult = await verifyResponse.json();

                    if (verifyResult.success) {
                        // 5. All good! Show success message
                        document.getElementById('customer-details-section').classList.add('hidden');
                        document.getElementById('payment-success-message').classList.remove('hidden');
                    } else {
                        // Verification failed
                        throw new Error(verifyResult.message || 'Payment verification failed.');
                    }
                },
                "prefill": {
                    "name": orderResult.customer_name,
                    "email": orderResult.customer_email,
                    "contact": orderResult.customer_mobile
                },
                "notes": {
                    "address": "ForeverTunes Corporate",
                    "database_order_id": orderResult.order_id // Store our DB order_id
                },
                "theme": {
                    "color": "#3399cc"
                }
            };

            const rzp1 = new Razorpay(options);
            
            // Add listener for payment failure
            rzp1.on('payment.failed', (response) => {
                paymentErrorMsg.textContent = `Payment failed: ${response.error.description}`;
                paymentErrorMsg.classList.remove('hidden');
                // Hide loader, show text
                payButton.disabled = false;
                payButtonText.classList.remove('hidden');
                payButtonLoader.classList.add('hidden');
            });
            
            // Open the checkout
            rzp1.open();

        } catch (error) {
            // Handle errors from create_order.php or network
            paymentErrorMsg.textContent = error.message;
            paymentErrorMsg.classList.remove('hidden');
            // Hide loader, show text
            payButton.disabled = false;
            payButtonText.classList.remove('hidden');
            payButtonLoader.classList.add('hidden');
        }
    });

    // --- Contact Page Logic ---
    const contactForm = document.getElementById('contact-form');
    const contactSubmitBtn = document.getElementById('contact-submit-btn');
    const contactSuccessMsg = document.getElementById('contact-success-message');
    const contactErrorMsg = document.getElementById('contact-error-message');

    contactForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        contactSubmitBtn.disabled = true;
        contactSubmitBtn.textContent = 'Sending...';
        contactSuccessMsg.classList.add('hidden');
        contactErrorMsg.classList.add('hidden');

        const formData = {
            name: document.getElementById('contact-name').value,
            email: document.getElementById('contact-email').value,
            subject: document.getElementById('contact-subject').value,
            message: document.getElementById('contact-message').value,
        };

        try {
            const response = await fetch('submit_contact.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            
            const result = await response.json();

            if (result.success) {
                contactForm.reset();
                contactSuccessMsg.textContent = result.message;
                contactSuccessMsg.classList.remove('hidden');
            } else {
                throw new Error(result.message || 'Failed to send message.');
            }

        } catch (error) {
            contactErrorMsg.textContent = error.message;
            contactErrorMsg.classList.remove('hidden');
        } finally {
            contactSubmitBtn.disabled = false;
            contactSubmitBtn.textContent = 'Send Message';
        }
    });

});