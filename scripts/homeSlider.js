document.addEventListener('DOMContentLoaded', function () {
    const slides = document.querySelectorAll('#featureSlider .slide');
    const buttons = document.querySelectorAll('.slider-button');
    const prevButton = document.querySelector('.slider-prev');
    const nextButton = document.querySelector('.slider-next');
    let currentIndex = 0;
    let timer = null;

    function showSlide(index) {
        if (index < 0) {
            index = slides.length - 1;
        }

        if (index >= slides.length) {
            index = 0;
        }

        slides.forEach((slide, idx) => {
            slide.style.display = idx === index ? 'flex' : 'none';
            slide.classList.toggle('active', idx === index);
        });

        buttons.forEach((button, idx) => {
            button.classList.toggle('active', idx === index);
        });

        currentIndex = index;
    }

    function nextSlide() {
        showSlide(currentIndex + 1);
    }

    function resetTimer() {
        if (timer) {
            clearInterval(timer);
        }

        timer = setInterval(nextSlide, 7000);
    }

    buttons.forEach((button, index) => {
        button.addEventListener('click', () => {
            showSlide(index);
            resetTimer();
        });
    });

    if (prevButton) {
        prevButton.addEventListener('click', () => {
            showSlide(currentIndex - 1);
            resetTimer();
        });
    }

    if (nextButton) {
        nextButton.addEventListener('click', () => {
            showSlide(currentIndex + 1);
            resetTimer();
        });
    }

    if (slides.length) {
        showSlide(0);
        resetTimer();
    }
});
