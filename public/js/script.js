$(document).ready(function () {
     // Check if language is set in localStorage, otherwise set default to 'id' (Indonesian)
    var savedLang = localStorage.getItem('language');
    if (!savedLang) {
        savedLang = 'id';
        localStorage.setItem('language', 'id');
    } else if (savedLang === 'en') {
        savedLang = 'id';
        localStorage.setItem('language', 'id');
    }
    setLanguage(savedLang);
    $('#languageSwitcher').on('click', function () {
        var currentLang = localStorage.getItem('language') || 'id';
        var newLang = (currentLang === 'id') ? 'en' : 'id';
        localStorage.setItem('language', newLang);
        console.log("Language after click: " + newLang);
        setLanguage(newLang);
    });


    let startX = 0;
    let isDragging = false;
    const $carousel = $("#heroCarousel");

    $carousel.find(".carousel-inner").on("mousedown", function (e) {
        isDragging = true;
        startX = e.pageX;
        $(this).css("cursor", "grabbing");
    });

    $(document).on("mouseup", function (e) {
        if (!isDragging) return;
        isDragging = false;
        const endX = e.pageX;
        const diff = endX - startX;

        if (diff > 50) {
            $carousel.carousel("prev");
        } else if (diff < -50) {
            $carousel.carousel("next");
        }

        $carousel.find(".carousel-inner").css("cursor", "grab");
    });

    $carousel.find(".carousel-inner").on("mouseleave", function () {
        if (isDragging) {
            isDragging = false;
            $(this).css("cursor", "grab");
        }
    });

    $carousel.find(".carousel-inner").on("dragstart", function (e) {
        e.preventDefault(); // prevent image drag
    });


    $('#contact-form').on('submit', function (e) {
        e.preventDefault();

        $('#thankYouModal').modal('show');
        this.reset();
    })

    setTimeout(function () {
        $('#splashScreen').fadeOut('slow')
    }, 1000);

});

function setLanguage(lang) {
    $('.text').each(function () {
        var textContent = (lang === 'id') ? $(this).data('id') : $(this).data('en');
        $(this).html(textContent);
    });

    var flagIcon = (lang === 'id') ? "images/uk-flag copy.png" : "images/indonesia-flag copy.png"; 
    var languageLabel = (lang === 'id') ? "English" : "Bahasa"; 
    
    $('#languageIcon').attr('src', flagIcon);
    $('#languageLabel').text(languageLabel);

    $('input, textarea').each(function () {
        var placeholderText = lang === 'id' ? $(this).data('id') : $(this).data('en');
        $(this).attr('placeholder', placeholderText);
    });
}