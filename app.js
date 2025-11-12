// app.js — Razorpay integration + routing + payment flow (Final Version)

document.addEventListener('DOMContentLoaded', () => {
    // 🌍 Global order state
    const currentOrder = {
        packageName: '',
        packagePrice: '',
        features: [],
        language: 'English',
        description: '',
        name: '',
        email: '',
        mobile: ''
    };

    // 🧭 Routing / Navigation
    const pageSections = document.querySelectorAll('.page-section');
    const navLinks = document.querySelectorAll('.nav-link');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileMenuButton = document.getElementById('mobile-menu-button');

    window.showSection = (sectionId) => {
        pageSections.forEach(section => {
            section.classList.toggle('active', section.id === sectionId);
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
        if (mobileMenu && !mobileMenu.classList.contains('hidden')) {
            mobileMenu.classList.add('hidden');
        }
    };

    navLinks.forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            const sectionId = link.dataset.section;
            if (sectionId) showSection(sectionId);
        });
    });

    mobileMenuButton?.addEventListener('click', () => {
        mobileMenu?.classList.toggle('hidden');
    });

    // 💎 Package Selection
    const planButtons = document.querySelectorAll('.select-plan');
    const packageCards = document.querySelectorAll('.package-card');

    planButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const planName = btn.dataset.name;
            const planPrice = btn.dataset.price;

            currentOrder.packageName = planName;
            currentOrder.packagePrice = planPrice;
            currentOrder.features = [
                'High-quality mix and master',
                'Fast delivery',
                'Unlimited revisions'
            ];

            // Highlight selected plan visually
            packageCards.forEach(card =>
                card.classList.remove('selected-card', 'border-blue-600', 'ring', 'ring-blue-300')
            );
            btn.closest('.package-card')?.classList.add('selected-card', 'border-blue-600', 'ring', 'ring-blue-300');

            console.log(`💎 Selected Plan: ${planName} (${planPrice})`);
            showSection('select-package');
        });
    });

    // 🌐 Language Selection
    const langButtons = document.querySelectorAll('#lang-grid .btn-lang');
    langButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            currentOrder.language = btn.dataset.lang;
            langButtons.forEach(b => b.classList.remove('bg-black', 'text-white'));
            btn.classList.add('bg-black', 'text-white');
        });
    });

    // 📝 Continue to Payment Section
    const songDescriptionInput = document.getElementById('song-description');
    const continueBtn = document.getElementById('continue-to-payment-btn');
    continueBtn?.addEventListener('click', () => {
        currentOrder.description = songDescriptionInput?.value.trim() || '';
        showSection('payment');
    });

    // 💳 Payment Form Logic
    const customerForm = document.getElementById('customerForm');
    const payButton = document.getElementById('pay-button');
    const paymentErrorMsg = document.getElementById('payment-error');
    const successMessage = document.getElementById('payment-success-message');

    const toggleLoader = (btn, state) => {
        if (!btn) return;
        if (state) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
        } else {
            btn.disabled = false;
            btn.textContent = 'Pay Now';
        }
    };

    const safeParseJSON = async (res) => {
        try {
            return await res.json();
        } catch {
            const text = await res.text();
            throw new Error(`Invalid JSON: ${text}`);
        }
    };

    const log = (...args) => console.log('[💳]', ...args);

    // Prevent multiple event bindings
    if (!window.__razorpayFormBound) {
        window.__razorpayFormBound = true;

        customerForm?.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (customerForm.dataset.processing === 'true') return;
            customerForm.dataset.processing = 'true';

            currentOrder.name = document.getElementById('name').value.trim();
            currentOrder.email = document.getElementById('email').value.trim();
            currentOrder.mobile = document.getElementById('mobile').value.trim();

            toggleLoader(payButton, true);
            paymentErrorMsg?.classList.add('hidden');

            const orderData = {
                name: currentOrder.name,
                email: currentOrder.email,
                mobile: currentOrder.mobile,
                language: currentOrder.language,
                description: currentOrder.description,
                packageName: currentOrder.packageName,
                packagePrice: currentOrder.packagePrice
            };

            log('📦 Sending order to backend:', orderData);

            try {
                // Step 1️⃣: Create order
                const res = await fetch('create_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(orderData)
                });

                const order = await safeParseJSON(res);
                log('✅ Order created:', order);

                if (!order.success) throw new Error(order.message || 'Order creation failed.');

                // Step 2️⃣: Razorpay Checkout Setup
                const options = {
                    key: order.razorpay_key_id,
                    amount: order.amount,
                    currency: 'INR',
                    name: 'ForeverTunes',
                    description: `Payment for ${order.packageName}`,
                    image: 'https://placehold.co/100x100/000000/FFFFFF?text=FT',
                    order_id: order.razorpay_order_id,
                    handler: async (response) => {
                        log('💳 Payment successful:', response);
                        try {
                            // Step 3️⃣: Verify payment
                            const verifyRes = await fetch('verify_payment.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    razorpay_payment_id: response.razorpay_payment_id,
                                    razorpay_order_id: response.razorpay_order_id,
                                    razorpay_signature: response.razorpay_signature,
                                    order_id: order.order_id
                                })
                            });

                            const verifyResult = await safeParseJSON(verifyRes);
                            log('✅ Payment verified:', verifyResult);

                            if (verifyResult.success) {
                                // Step 4️⃣: Redirect to confirmation page
                                if (verifyResult.redirect_url) {
                                    window.location.href = verifyResult.redirect_url;
                                } else {
                                    showSection('success');
                                    successMessage?.classList.remove('hidden');
                                }
                            } else {
                                throw new Error(verifyResult.message || 'Verification failed.');
                            }
                        } catch (error) {
                            log('❌ Verification error:', error);
                            paymentErrorMsg.textContent = error.message;
                            paymentErrorMsg.classList.remove('hidden');
                        } finally {
                            toggleLoader(payButton, false);
                            customerForm.dataset.processing = 'false';
                        }
                    },
                    prefill: {
                        name: order.customer_name,
                        email: order.customer_email,
                        contact: order.customer_mobile
                    },
                    notes: { order_id: order.order_id },
                    theme: { color: '#3399cc' }
                };

                const rzp = new Razorpay(options);

                // Handle Payment Failure
                rzp.on('payment.failed', (res) => {
                    log('❌ Payment failed:', res.error);
                    paymentErrorMsg.textContent = `Payment failed: ${res.error.description}`;
                    paymentErrorMsg.classList.remove('hidden');
                    toggleLoader(payButton, false);
                    customerForm.dataset.processing = 'false';
                });

                // Step 4️⃣: Open Razorpay Checkout
                rzp.open();

            } catch (err) {
                log('❌ Error during order creation or payment:', err);
                paymentErrorMsg.textContent = err.message;
                paymentErrorMsg.classList.remove('hidden');
                toggleLoader(payButton, false);
                customerForm.dataset.processing = 'false';
            }
        });
    }
});
