// assets/js/Evenement.js
$(document).ready(function() {
    var isShowingEvents = true;
    var evenementUrl = $('#dynamic-content').data('evenement-url');
    var categorieUrl = $('#dynamic-content').data('categorie-url');

    console.log('Evenement URL:', evenementUrl);
    console.log('Categorie URL:', categorieUrl);

    function updateSearchForm(isEvents) {
        var $form = $('#eventFilterForm');
        var $input = $form.find('input[name="search"]');
        var $sortDropdown = $('#sort-dropdown');

        if (isEvents) {
            $form.attr('action', evenementUrl);
            $input.attr('placeholder', 'Rechercher un événement ou catégorie...');
            $sortDropdown.show();
        } else {
            $form.attr('action', categorieUrl);
            $input.attr('placeholder', 'Rechercher une catégorie...');
            $sortDropdown.hide();
        }
    }

    updateSearchForm(true);

    $('.toggle-content').on('click', function() {
        var button = $(this);

        if (isShowingEvents) {
            $.ajax({
                url: categorieUrl,
                method: 'GET',
                success: function(data) {
                    var $data = $(data);
                    var content = $data.find('#dynamic-content').length 
                        ? $data.find('#dynamic-content').html() 
                        : $data.html();
                    $('#dynamic-content').html(content);
                    $('#content-title').text('Liste des Catégories d\'Événements');
                    button.find('span').text('Evenement');
                    isShowingEvents = false;
                    updateSearchForm(false);
                    afterAjaxUpdate();
                },
                error: function(xhr, status, error) {
                    console.error('Erreur AJAX (Catégories):', status, error, xhr.responseText);
                    alert('Erreur lors du chargement des catégories.');
                }
            });
        } else {
            $.ajax({
                url: evenementUrl,
                method: 'GET',
                success: function(data) {
                    var $data = $(data);
                    var content = $data.find('#dynamic-content').length 
                        ? $data.find('#dynamic-content').html() 
                        : $data.html();
                    $('#dynamic-content').html(content);
                    $('#content-title').text('Liste des Événements');
                    button.find('span').text('Categorie');
                    isShowingEvents = true;
                    updateSearchForm(true);
                    afterAjaxUpdate();
                },
                error: function(xhr, status, error) {
                    console.error('Erreur AJAX (Événements):', status, error, xhr.responseText);
                    alert('Erreur lors du chargement des événements.');
                }
            });
        }
    });

    $('#eventFilterForm').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var url = $form.attr('action');
        var data = $form.serialize();

        $.ajax({
            url: url,
            method: 'GET',
            data: data,
            success: function(response) {
                var $data = $(response);
                var content = $data.find('#dynamic-content').length 
                    ? $data.find('#dynamic-content').html() 
                    : $data.html();
                $('#dynamic-content').html(content);
                afterAjaxUpdate();
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX (Recherche):', status, error, xhr.responseText);
                alert('Erreur lors de la recherche.');
            }
        });
    });

    $('.nk-sort-select').on('change', function() {
        if (isShowingEvents) {
            $('#eventFilterForm').submit();
        }
    });

    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        if (url) {
            $.ajax({
                url: url,
                method: 'GET',
                success: function(data) {
                    var $data = $(data);
                    var content = $data.find('#dynamic-content').length 
                        ? $data.find('#dynamic-content').html() 
                        : $data.html();
                    $('#dynamic-content').html(content);
                    afterAjaxUpdate();
                },
                error: function(xhr, status, error) {
                    console.error('Erreur AJAX (Pagination):', status, error, xhr.responseText);
                    alert('Erreur lors du chargement de la page.');
                }
            });
        }
    });
});

function initCategoryCarousel() {
    var $carousel = $('.category-carousel');
    if ($carousel.hasClass('slick-initialized')) {
        $carousel.slick('unslick');
    }
    var categoryCount = $carousel.find('.category-item').length;
    $carousel.slick({
        slidesToShow: 5,
        slidesToScroll: 1,
        arrows: categoryCount > 4,
        dots: false,
        prevArrow: $('.carousel-arrow-left'),
        nextArrow: $('.carousel-arrow-right'),
        responsive: [
            {
                breakpoint: 992,
                settings: {
                    slidesToShow: 3,
                    arrows: categoryCount > 3
                }
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 2,
                    arrows: categoryCount > 2
                }
            },
            {
                breakpoint: 576,
                settings: {
                    slidesToShow: 1,
                    arrows: categoryCount > 1
                }
            }
        ]
    });
}

// Initialisation au chargement
$(document).ready(function() {
    initCategoryCarousel();

    // Clic sur catégorie
    $(document).on('click', '.category-item', function() {
        $('.category-item').removeClass('active');
        $(this).addClass('active');
        var categoryId = $(this).data('category-id');
        $('#category-input').val(categoryId);
        $('#eventFilterForm').submit();
    });
});

// Après chaque chargement AJAX, réinitialise le carousel
function afterAjaxUpdate() {
    initCategoryCarousel();
}