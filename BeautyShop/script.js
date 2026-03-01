/**
 * LUMINÉ BEAUTY SHOP - OPTIMIZED JAVASCRIPT
 * Performance-focused interactions with minimal overhead
 * No frameworks, vanilla JS using modern APIs
 */

// ==========================================
// MOBILE NAVIGATION
// ==========================================
function toggleMobileNav() {
    const button = document.querySelector('.nav-hamburger');
    const navLinks = document.querySelector('.nav-links');
    const isExpanded = button.getAttribute('aria-expanded') === 'true';
    
    button.setAttribute('aria-expanded', !isExpanded);
    navLinks.classList.toggle('active');
}

// Close mobile menu when clicking links
document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('click', () => {
        const button = document.querySelector('.nav-hamburger');
        const navLinks = document.querySelector('.nav-links');
        button.setAttribute('aria-expanded', 'false');
        navLinks.classList.remove('active');
    });
});

// Close mobile menu when clicking outside
document.addEventListener('click', (e) => {
    const nav = document.querySelector('.nav');
    const button = document.querySelector('.nav-hamburger');
    if (!nav.contains(e.target)) {
        button.setAttribute('aria-expanded', 'false');
        document.querySelector('.nav-links').classList.remove('active');
    }
});

// ==========================================
// SCROLL REVEAL ANIMATIONS
// ==========================================
const revealElements = document.querySelectorAll('.reveal');

if ('IntersectionObserver' in window) {
    const observerOptions = {
        root: null,
        rootMargin: '0px 0px -50px 0px',
        threshold: 0.1
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    revealElements.forEach(el => {
        observer.observe(el);
    });
} else {
    // Fallback for browsers without IntersectionObserver
    revealElements.forEach(el => {
        el.style.opacity = '1';
        el.style.transform = 'translateY(0)';
    });
}

// ==========================================
// SMOOTH SCROLL FALLBACK
// ==========================================
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        // Skip if href is just "#"
        if (href === '#') return;
        
        e.preventDefault();
        const target = document.querySelector(href);
        if (target) {
            const headerHeight = document.querySelector('header').offsetHeight;
            const targetPosition = target.offsetTop - headerHeight;
            
            // Use native smooth scroll if available
            if ('scrollTo' in window) {
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            } else {
                // Fallback
                window.scrollTop = targetPosition;
            }
        }
    });
});

// ==========================================
// PERFORMANCE MONITORING
// ==========================================
if ('PerformanceObserver' in window && 'LayoutShift' in window) {
    // Monitor Cumulative Layout Shift for Lighthouse
    let cls = 0;
    const observer = new PerformanceObserver((entryList) => {
        for (const entry of entryList.getEntries()) {
            if (!entry.hadRecentInput) {
                cls += entry.value;
            }
        }
    });
    
    try {
        observer.observe({ entryTypes: ['layout-shift'] });
    } catch (e) {
        // Silently fail if not supported
    }
}

// ==========================================
// LAZY LOAD IMAGES (FALLBACK)
// ==========================================
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                }
                if (img.dataset.srcset) {
                    img.srcset = img.dataset.srcset;
                    img.removeAttribute('data-srcset');
                }
                observer.unobserve(img);
            }
        });
    });
    
    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
}

// ==========================================
// PREFETCH/PRECONNECT LINKS
// ==========================================
// Prefetch external WhatsApp links on hover
document.querySelectorAll('a[target="_blank"][href*="wa.me"]').forEach(link => {
    link.addEventListener('mouseenter', () => {
        // Browser will handle prefetch automatically
    });
});

// ==========================================
// THEME DETECTION
// ==========================================
// Automatic dark mode support based on system preference
if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
    document.documentElement.setAttribute('data-theme', 'dark');
}

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
    if (e.matches) {
        document.documentElement.setAttribute('data-theme', 'dark');
    } else {
        document.documentElement.removeAttribute('data-theme');
    }
});

// ==========================================
// VIEWPORT OPTIMIZATION
// ==========================================
// Enable viewport-fit for notches on mobile
if (navigator.userAgent.includes('iPhone OS')) {
    const viewport = document.querySelector('meta[name="viewport"]');
    if (viewport) {
        viewport.setAttribute('content', 
            'width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover'
        );
    }
}

// ==========================================
// PERFORMANCE LOGGING (DEV ONLY)
// ==========================================
function logPerformanceMetrics() {
    if (!window.performance || !window.performance.measure) return;
    
    // Log only if in development
    if (new URLSearchParams(window.location.search).get('debug') === '1') {
        const perfData = window.performance.timing;
        const pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
        const connectTime = perfData.responseEnd - perfData.requestStart;
        const renderTime = perfData.domComplete - perfData.domLoading;
        
        console.log('=== Luminé Beauty Shop Performance ===');
        console.log(`Page Load Time: ${pageLoadTime}ms`);
        console.log(`Connect Time: ${connectTime}ms`);
        console.log(`Render Time: ${renderTime}ms`);
        console.log(`DOM Content Loaded: ${perfData.domContentLoadedEventEnd - perfData.navigationStart}ms`);
    }
}

// Run after page load
if (document.readyState === 'complete') {
    logPerformanceMetrics();
} else {
    window.addEventListener('load', logPerformanceMetrics, { once: true });
}

// ==========================================
// PAGE VISIBILITY API
// ==========================================
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        // Pause animations if page is hidden
        document.querySelectorAll('[style*="animation"]').forEach(el => {
            el.style.animationPlayState = 'paused';
        });
    } else {
        // Resume animations
        document.querySelectorAll('[style*="animation"]').forEach(el => {
            el.style.animationPlayState = 'running';
        });
    }
});

// ==========================================
// WCAG COMPLIANCE
// ==========================================
// Skip to main content link (for accessibility)
if (!document.querySelector('a[href="#main"]')) {
    const skipLink = document.createElement('a');
    skipLink.href = '#main';
    skipLink.textContent = 'Skip to main content';
    skipLink.style.position = 'absolute';
    skipLink.style.top = '-40px';
    skipLink.style.left = '0';
    skipLink.style.background = '#000';
    skipLink.style.color = '#fff';
    skipLink.style.padding = '8px';
    skipLink.style.zIndex = '100';
    skipLink.addEventListener('focus', () => {
        skipLink.style.top = '0';
    });
    skipLink.addEventListener('blur', () => {
        skipLink.style.top = '-40px';
    });
    document.body.insertBefore(skipLink, document.body.firstChild);
}

// ==========================================
// INITIALIZATION LOG
// ==========================================
console.log('✨ Luminé Beauty Shop loaded');

// ==========================================
// BOOKING & PAYMENT SYSTEM
// ==========================================

// Store current booking data
let currentBooking = {
    client_name: '',
    client_phone: '',
    client_email: '',
    service: '',
    preferred_date: '',
    preferred_time: '',
    special_requests: '',
    amount: 0
};

// Service prices mapping
const servicePrices = {
    makeup: { name: 'Makeup & Styling', price: 1500 },
    nails: { name: 'Nail Care', price: 800 },
    hair: { name: 'Hair Treatment', price: 1200 },
    facial: { name: 'Skincare & Facial', price: 2000 },
    spa: { name: 'Spa & Massage', price: 2500 },
    waxing: { name: 'Threading & Waxing', price: 300 }
};

// ==========================================
// MODAL MANAGEMENT
// ==========================================
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }
}

// Close modals on escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeModal('booking-modal');
        closeModal('payment-modal');
        closeModal('success-modal');
    }
});

// ==========================================
// BOOKING FORM HANDLING
// ==========================================
const bookingForm = document.getElementById('booking-form');

function handleBookingSubmit(e) {
    e.preventDefault();

    // Get form data
    const formData = new FormData(bookingForm);
    currentBooking = {
        client_name: formData.get('client_name'),
        client_phone: formData.get('client_phone'),
        client_email: formData.get('client_email'),
        service: formData.get('service'),
        preferred_date: formData.get('preferred_date'),
        preferred_time: formData.get('preferred_time'),
        special_requests: formData.get('special_requests'),
        amount: servicePrices[formData.get('service')].price
    };

    // Populate payment summary
    const serviceName = servicePrices[currentBooking.service].name;
    document.getElementById('summary-service').textContent = serviceName;
    document.getElementById('summary-date').textContent = currentBooking.preferred_date;
    document.getElementById('summary-time').textContent = currentBooking.preferred_time;
    document.getElementById('summary-amount').textContent = `KES ${currentBooking.amount.toLocaleString()}`;

    // Prefill M-Pesa number with booking phone
    document.getElementById('m-pesa-phone').value = currentBooking.client_phone;

    // Move to payment modal
    closeModal('booking-modal');
    openModal('payment-modal');
}

// ==========================================
// PAYMENT FORM HANDLING (M-Pesa STK Push)
// ==========================================
const paymentForm = document.getElementById('payment-form');

function handlePaymentSubmit(e) {
    e.preventDefault();

    const mpesaPhone = document.getElementById('m-pesa-phone').value;
    const password = document.getElementById('payment-password').value;

    // Show processing message
    document.getElementById('payment-form').style.display = 'none';
    document.getElementById('payment-processing').style.display = 'block';

    // Prepare data for server
    const paymentData = {
        client_name: currentBooking.client_name,
        client_phone: currentBooking.client_phone,
        client_email: currentBooking.client_email,
        service: currentBooking.service,
        service_name: servicePrices[currentBooking.service].name,
        preferred_date: currentBooking.preferred_date,
        preferred_time: currentBooking.preferred_time,
        special_requests: currentBooking.special_requests,
        amount: currentBooking.amount,
        mpesa_phone: mpesaPhone,
        password: password
    };

    // Send to server for M-Pesa STK push
    fetch('backend/api/bookings', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(paymentData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Payment initiated successfully
            setTimeout(() => {
                showSuccessMessage(data);
            }, 2000); // Show success after 2 seconds
        } else {
            alert('Payment failed: ' + data.message);
            resetPaymentForm();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        resetPaymentForm();
    });
}

function resetPaymentForm() {
    document.getElementById('payment-form').style.display = 'block';
    document.getElementById('payment-processing').style.display = 'none';
}

function goBackToBooking() {
    closeModal('payment-modal');
    openModal('booking-modal');
}

// ==========================================
// SUCCESS MESSAGE
// ==========================================
function showSuccessMessage(data) {
    closeModal('payment-modal');

    // Populate success details
    const successDetails = document.getElementById('success-details');
    successDetails.innerHTML = `
        <div class="success-row">
            <span>Booking ID:</span>
            <strong>${data.booking_id || 'N/A'}</strong>
        </div>
        <div class="success-row">
            <span>Service:</span>
            <strong>${servicePrices[currentBooking.service].name}</strong>
        </div>
        <div class="success-row">
            <span>Date & Time:</span>
            <strong>${currentBooking.preferred_date} at ${currentBooking.preferred_time}</strong>
        </div>
        <div class="success-row">
            <span>Amount Paid:</span>
            <strong>KES ${currentBooking.amount.toLocaleString()}</strong>
        </div>
        <div class="success-row">
            <span>Status:</span>
            <strong style="color: var(--color-accent);">Payment Confirmed</strong>
        </div>
    `;

    // Store booking for WhatsApp link
    window.bookingConfirmation = {
        bookingId: data.booking_id,
        service: servicePrices[currentBooking.service].name,
        date: currentBooking.preferred_date,
        time: currentBooking.preferred_time,
        amount: currentBooking.amount,
        phone: currentBooking.client_phone
    };

    openModal('success-modal');
}

// ==========================================
// WHATSAPP INTEGRATION
// ==========================================
function openWhatsAppChat() {
    const booking = window.bookingConfirmation;
    const message = `Hi Luminé! 👋\n\nThank you for confirming my booking!\n\n📋 Booking Details:\n• ID: ${booking.bookingId}\n• Service: ${booking.service}\n• Date: ${booking.date}\n• Time: ${booking.time}\n• Amount: KES ${booking.amount.toLocaleString()}\n\nLooking forward to my appointment! ✨`;

    const encodedMessage = encodeURIComponent(message);
    const whatsappUrl = `https://wa.me/254712000003?text=${encodedMessage}`;
    window.open(whatsappUrl, '_blank');
}

// ==========================================
// OPEN BOOKING MODAL FROM CTA BUTTONS
// ==========================================
// Buttons use onclick="openModal('booking-modal')" directly in HTML

// For any legacy WhatsApp CTA buttons - open booking form instead
document.addEventListener('DOMContentLoaded', () => {
    // Initialize date picker
    const dateInput = document.getElementById('preferred-date');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
    }
});
