// Sayfa tamamen yüklendiğinde çalışır
document.addEventListener("DOMContentLoaded", function() {
    
    // 1. Mobil Menü İşlemleri (İleride lazım olursa diye hazır yapı)
    console.log("Fitness App Hazır!");

    // 2. Mesaj Kutularını Otomatik Gizle
    // Eğer ekranda yeşil veya kırmızı bir mesaj varsa 4 saniye sonra yavaşça yok et.
    const messageBox = document.querySelector('p[style*="background:#d4edda"], p[style*="color:green"], .message');
    
    if (messageBox) {
        setTimeout(() => {
            messageBox.style.transition = "opacity 1s";
            messageBox.style.opacity = "0";
            setTimeout(() => messageBox.remove(), 1000); // Tamamen sil
        }, 4000); // 4000 milisaniye = 4 saniye
    }

    // 3. Kartlara Hover Efekti (JS ile Ekstra Süs)
    const cards = document.querySelectorAll('.class-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.borderColor = '#2a5298';
        });
        card.addEventListener('mouseleave', () => {
            card.style.borderColor = 'transparent'; // Veya eski rengi
        });
    });
});