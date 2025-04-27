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
                    // Reinitialize slick carousel
                    $('.category-carousel').slick('refresh');
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
                // Reinitialize slick carousel
                $('.category-carousel').slick('refresh');
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
                    // Reinitialize slick carousel
                    $('.category-carousel').slick('refresh');
                },
                error: function(xhr, status, error) {
                    console.error('Erreur AJAX (Pagination):', status, error, xhr.responseText);
                    alert('Erreur lors du chargement de la page.');
                }
            });
        }
    });
});
$(document).ready(function() {
    // Compter le nombre de catégories
    var categoryCount = $('.category-item').length; // +1 pour "Toutes les catégories"

    // Configuration du carrousel Slick
    $('.category-carousel').slick({
        slidesToShow: 5,
        slidesToScroll: 1,
        arrows: categoryCount > 4, // Affiche les flèches si plus de 4 catégories
        dots: false,
        prevArrow: '<button type="button" class="slick-prev"><i class="fas fa-chevron-left"></i></button>',
        nextArrow: '<button type="button" class="slick-next"><i class="fas fa-chevron-right"></i></button>',
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

    // Handle category click
    $('.category-item').on('click', function() {
        $('.category-item').removeClass('active');
        $(this).addClass('active');
        
        var categoryId = $(this).data('category-id');
        $('#category-input').val(categoryId);
        
        // Trigger form submission
        $('#eventFilterForm').submit();
    });
});