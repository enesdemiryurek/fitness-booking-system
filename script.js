
let currentSlide = 0;
let autoScrollInterval;
let cardWidth = 0;
let isDragging = false;
let dragStartX = 0;
let dragDistance = 0;

const carousel = document.querySelector('.carousel-wrapper');
const carouselContainer = document.querySelector('.carousel-container');

function initCarousel() {
    const cards = document.querySelectorAll('.carousel-card');
    if (cards.length === 0) return;
    
    calculateCardWidth();
    
    updateCarouselPosition();
    updateCarouselDots();
    startAutoScroll();
    
    const prevBtn = document.querySelector('.carousel-prev');
    const nextBtn = document.querySelector('.carousel-next');
    
    if (prevBtn) prevBtn.addEventListener('click', () => scrollCarousel(-1));
    if (nextBtn) nextBtn.addEventListener('click', () => scrollCarousel(1));
    
    const dots = document.querySelectorAll('.dot');
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => goToCarouselSlide(index));
    });
    
    carousel.addEventListener('touchstart', handleTouchStart, false);
    carousel.addEventListener('touchend', handleTouchEnd, false);
    
    carousel.addEventListener('mousedown', handleMouseDown);
    document.addEventListener('mousemove', handleMouseMove);
    document.addEventListener('mouseup', handleMouseUp);
    carousel.addEventListener('mouseleave', handleMouseLeave);
    
    carouselContainer.addEventListener('mouseenter', () => {
        clearInterval(autoScrollInterval);
    });
    
    carouselContainer.addEventListener('mouseleave', () => {
        resetAutoScroll();
    });
    
    window.addEventListener('resize', calculateCardWidth);
}

function calculateCardWidth() {
    const cards = document.querySelectorAll('.carousel-card');
    if (cards.length > 0) {
        cardWidth = cards[0].offsetWidth + 20;
    }
}

function scrollCarousel(direction) {
    const cards = document.querySelectorAll('.carousel-card');
    const maxSlide = cards.length - 1;
    const prevSlide = currentSlide;
    
    currentSlide += direction;
    
    if (currentSlide > maxSlide) {
        currentSlide = 0;
    } else if (currentSlide < 0) {
        currentSlide = maxSlide;
    }
    
    updateCarouselPosition();
    updateCarouselDots();
    resetAutoScroll();
    
    // Animasyon efekti
    const prevBtn = document.querySelector('.carousel-prev');
    const nextBtn = document.querySelector('.carousel-next');
    if (prevBtn && nextBtn) {
        prevBtn.style.transform = 'scale(0.95)';
        nextBtn.style.transform = 'scale(0.95)';
        setTimeout(() => {
            prevBtn.style.transform = 'scale(1)';
            nextBtn.style.transform = 'scale(1)';
        }, 100);
    }
}

function goToCarouselSlide(index) {
    currentSlide = index;
    updateCarouselPosition();
    updateCarouselDots();
    resetAutoScroll();
}

function updateCarouselPosition() {
    if (!carousel) return;
    carousel.style.transform = `translateX(-${currentSlide * cardWidth}px)`;
    carousel.style.transition = isDragging ? 'none' : 'transform 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
}

function updateCarouselDots() {
    const dots = document.querySelectorAll('.dot');
    dots.forEach((dot, index) => {
        if (index === currentSlide) {
            dot.classList.add('active');
        } else {
            dot.classList.remove('active');
        }
    });
}

function startAutoScroll() {
    autoScrollInterval = setInterval(() => {
        const cards = document.querySelectorAll('.carousel-card');
        currentSlide++;
        if (currentSlide >= cards.length) {
            currentSlide = 0;
        }
        carousel.style.transition = 'transform 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
        updateCarouselPosition();
        updateCarouselDots();
    }, 5000);
}

function resetAutoScroll() {
    clearInterval(autoScrollInterval);
    startAutoScroll();
}

// Touch işlemleri
let touchStartX = 0;
let touchEndX = 0;

function handleTouchStart(e) {
    touchStartX = e.changedTouches[0].screenX;
    isDragging = true;
}

function handleTouchEnd(e) {
    touchEndX = e.changedTouches[0].screenX;
    isDragging = false;
    handleSwipe();
}

function handleSwipe() {
    const diff = touchStartX - touchEndX;
    
    if (Math.abs(diff) > 50) {
        if (diff > 0) {
            scrollCarousel(1);
        } else {
            scrollCarousel(-1);
        }
    }
}

// Mouse drag işlemleri
function handleMouseDown(e) {
    isDragging = true;
    dragStartX = e.clientX;
    if (carousel) carousel.style.cursor = 'grabbing';
    clearInterval(autoScrollInterval);
}

function handleMouseMove(e) {
    if (!isDragging || !carousel) return;
    
    dragDistance = e.clientX - dragStartX;
    
    if (Math.abs(dragDistance) > 10) {
        carousel.style.transition = 'none';
        const offset = dragDistance - (currentSlide * cardWidth);
        carousel.style.transform = `translateX(${offset}px)`;
    }
}

function handleMouseUp(e) {
    if (!isDragging) return;
    isDragging = false;
    
    const dragEndX = e.clientX;
    const diff = dragStartX - dragEndX;
    
    if (carousel) carousel.style.cursor = 'grab';
    
    if (Math.abs(diff) > 50) {
        if (diff > 0) {
            scrollCarousel(1);
        } else {
            scrollCarousel(-1);
        }
    } else {
        updateCarouselPosition();
    }
    
    resetAutoScroll();
}

function handleMouseLeave() {
    if (isDragging) {
        isDragging = false;
        if (carousel) carousel.style.cursor = 'grab';
        updateCarouselPosition();
        resetAutoScroll();
    }
}

// Sayfa yüklendikten sonra
document.addEventListener('DOMContentLoaded', function() {
    initCarousel();
    
    // Mesaj kutularını otomatik gizle
    const messageBox = document.querySelector('p[style*="background:#d4edda"], p[style*="color:green"], .message');
    
    if (messageBox) {
        setTimeout(() => {
            messageBox.style.transition = "opacity 1s";
            messageBox.style.opacity = "0";
            setTimeout(() => messageBox.remove(), 1000);
        }, 4000);
    }

    // Kartlara hover efekti
    const cards = document.querySelectorAll('.class-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.borderColor = '#2a5298';
        });
        card.addEventListener('mouseleave', () => {
            card.style.borderColor = 'transparent';
        });
    });
});

// Yorum formu açıp kapatma
function toggleReviewForm(classId) {
    const form = document.getElementById('review-form-' + classId);
    if (form.style.display === 'none') {
        form.style.display = 'block';
    } else {
        form.style.display = 'none';
    }
}

// Yıldız seçim fonksiyonu
function setRating(element, rating, classId) {
    const stars = element.parentElement.querySelectorAll('.star-icon');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('active');
            star.textContent = '⭐';
        } else {
            star.classList.remove('active');
            star.textContent = '☆';
        }
    });
    document.getElementById('rating-' + classId).value = rating;
}