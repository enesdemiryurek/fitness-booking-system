
// Sayfa yüklendikten sonra
document.addEventListener('DOMContentLoaded', function() {
    
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