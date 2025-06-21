document.addEventListener('DOMContentLoaded', function() {
    const menuButtonFeatures = document.querySelector('.button-features'); // Ganti ini dengan selector yang sesuai
    const menuButtonServices = document.querySelector('.button-services'); // Ganti ini dengan selector yang sesuai
    const menuButtonReview = document.querySelector('.button-review'); 
    const menuButtonLokasi = document.querySelector('.button-lokasi'); // Ganti ini dengan selector yang sesuai
    const scrollTargetFeatures = document.getElementById('features');
    const scrollTargetServices = document.getElementById('services');
    const scrollTargetReview = document.getElementById('review');
    const scrollTargetLokasi = document.getElementById('lokasi'); // Ganti ini dengan ID elemen yang sesuai

    // Pastikan semua elemen ada sebelum menambahkan event listener
   


    menuButtonFeatures.addEventListener('click', function(event) {
        event.preventDefault(); // Mencegah perilaku default tautan
        scrollTargetFeatures.scrollIntoView({ behavior: 'smooth' }); // Gulir dengan animasi halus
    });

    menuButtonServices.addEventListener('click', function(event) {
        event.preventDefault(); // Mencegah perilaku default tautan
        scrollTargetServices.scrollIntoView({ behavior: 'smooth'}); // Gulir dengan animasi halus
    });
    menuButtonReview.addEventListener('click', function(event){
        event.preventDefault();
        scrollTargetReview.scrollIntoView({ behavior: 'smooth'});
    });
    menuButtonLokasi.addEventListener('click', function(event){
        event.preventDefault();
        scrollTargetLokasi.scrollIntoView({ behavior: 'smooth'});
    });
});
