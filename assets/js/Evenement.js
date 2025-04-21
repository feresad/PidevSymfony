// assets/js/Evenement.js
$(document).ready(function() {
    var isShowingEvents = true;
    var evenementUrl = $('#dynamic-content').data('evenement-url');
    var categorieUrl = $('#dynamic-content').data('categorie-url'); // Correction ici

    // Vérification des URLs pour débogage
    console.log('Evenement URL:', evenementUrl);
    console.log('Categorie URL:', categorieUrl);

    // Fonction pour mettre à jour le formulaire de recherche
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

    // Initialiser le formulaire pour les événements
    updateSearchForm(true);

    // Gérer le clic sur le bouton de bascule (Catégorie/Événement)
    $('.toggle-content').on('click', function() {
        var button = $(this);

        if (isShowingEvents) {
            // Charger les catégories
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
            // Charger les événements
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
                },
                error: function(xhr, status, error) {
                    console.error('Erreur AJAX (Événements):', status, error, xhr.responseText);
                    alert('Erreur lors du chargement des événements.');
                }
            });
        }
    });

    // Gérer la soumission du formulaire de recherche
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
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX (Recherche):', status, error, xhr.responseText);
                alert('Erreur lors de la recherche.');
            }
        });
    });

    // Soumettre le formulaire lorsque le tri change
    $('.nk-sort-select').on('change', function() {
        if (isShowingEvents) {
            $('#eventFilterForm').submit();
        }
    });

    // Gérer les clics sur les liens de pagination
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
                },
                error: function(xhr, status, error) {
                    console.error('Erreur AJAX (Pagination):', status, error, xhr.responseText);
                    alert('Erreur lors du chargement de la page.');
                }
            });
        }
    });
});