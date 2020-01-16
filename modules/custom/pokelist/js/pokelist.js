$ = jQuery.noConflict();

(function ($, Drupal) {
    'use strict';

    Drupal.behaviors.myComparisonTable = {
        attach: function(context, settings) {

            if( $("#comparison-table" ).hasClass( "speciesContainer" ) ) {
                var comparison_table =  new ComparisonTable(Drupal);
                comparison_table.readyComparisonTable();

                $(document, context).once('myComparisonTable').on("click","#mark-favorite-1 input, #mark-favorite-2 input", function (e) {
                    e.preventDefault();

                    //var clickedBtnID = $(this).attr('id');
                    var $this = $(this);
                    $this.button('loading');
                    setTimeout(function() {
                        $this.button('reset');
                    }, 10000);                    

                    var poke_id = $(this).data().id;
                    var poke_name = $(this).data().name;

                    comparison_table.markAsFavorite(poke_id, poke_name);
                });   
            }        
        }
    };

})(jQuery, Drupal);

function ComparisonTable () {

    this.readyComparisonTable = function () {
        var localInstance = this;

        var click_pokemon = $("#poke-grid li a");
        var clicks = 0;

        click_pokemon.click(function(e) {
			e.preventDefault();

            var poke_id = $(this).data().id;
            var base_url = drupalSettings.pokelist.pokeapi_url;

			if (poke_id) {

                data = {
                    offset: 0,
                    limit: 1
                };   

                var success = function( result ) {

                    if( result ) {

                        if (clicks % 2 === 0){
                            // first click
                            var template1 = localInstance.specieTemplate(result, 1);
                            var pokemon1 = localInstance.nano(template1, result);
                            $(".speciesContainer").html(pokemon1);
                        } else{
                            // second click
                            var template2 = localInstance.specieTemplate(result, 2);
                            var pokemon2 = localInstance.nano(template2, result);
                            
                            $(".speciesContainer").append(pokemon2);                        
                        }
                        ++clicks;

                        //$('#comparison-table').removeClass('show').addClass('hide');

                    } else {
                        error(result);
                    }                                                                              
                };

                var error = function( message ) {
                    console.log('error ' + message);

                    return false;
                };

                // Hacemos la petición para obtener los datos del pokémon
                $.ajax({
                    url: base_url + 'pokemon/' + poke_id,
                    method: 'GET',
                    dataType: 'json',
                    data: data,
                    beforeSend: function() {
                        $('#comparison-table').removeClass('hide').addClass('show');
                    }                    
                })
                .done( success )
                .fail( error );                

			} else {
                console.log('Pokémon Id not selected');

                return false;
            }
        });
    };    

    // Nano Templates - https://github.com/trix/nano 
    this.nano = function (template, data) {
        return template.replace(/\{([\w\.]*)\}/g, function(str, key) {
            var keys = key.split("."), v = data[keys.shift()];
            for (var i = 0, l = keys.length; i < l; i++) v = v[keys[i]];
            return (typeof v !== "undefined" && v !== null) ? v : "";
        });
    }; 

    this.templateTypes = function (data) {
        var template_types = '';

        $.each( data.types, function( i, type ) {
            template_types += `<div class="type` + ((data.types.length > 1) ? 50 : 100 ) + ` type-{types.`+i+`.type.name}">{types.`+i+`.type.name}</div>`;
        });

        return template_types;
    };

    this.templateStats = function (data) {
        var template_stats = ``;

        $.each( data.stats, function( i, stat ) {
            template_stats += `
                    <div class="col-xs-6">{stats.`+i+`.stat.name}</div>
                        <div class="col-xs-6">
                            <div class="progress" title="{stats.`+i+`.base_stat}">
                                <div class="progress-bar type-{types.0.type.name}" role="progressbar" aria-valuenow="{stats.`+i+`.base_stat}" aria-valuemin="0" aria-valuemax="100" style="width: {stats.`+i+`.base_stat}%;" title="{stats.`+i+`.base_stat}"></div>
                                <span class="progressBarLabel">{stats.`+i+`.base_stat}</span>
                            </div>
                        </div>
                    <div class="clearfix"></div>`;
        });

        return template_stats;
    }; 

    this.templateImage = function (data) {
        var templateImage = ``;
        var image = data.sprites.front_default;

        templateImage = ( image ) ? '<img src="'+image+'" class="img-responsive center-block" />' : '<img src="'+drupalSettings.pokelist.no_image_available+'" width="128" height="128" class="img-responsive center-block" />'; 

        return templateImage;
    };   

    this.specieTemplate = function (data, element) {

        var template = ``;
        var template_image = this.templateImage(data);
        var template_types = this.templateTypes(data);
        var template_stats = this.templateStats(data);

        var template = `
            <form id="mark-favorite-`+element+`">
                <div class="speciesWrap borderDashed pok-mark-favorite-`+element+` col-md-6 col-xs-12 col-sm-6">
                    <div class="speciesNumber">#{id}</div>
                    <div class="type-{types.0.type.name}">
                        `+template_image+`
                    </div>  
                    <div class="speciesInfo">
                        <ul>
                            <li>{name}</li>
                            <li>Height: {height}m</li>
                            <li>Weight: {weight}kg</li>
                        </ul>
                    </div>
                    <div class="speciesTypes">`+template_types+`</div>
                    <div class="speciesAbilities">
                        `+template_stats+`                                                                                                                    
                    </div>
                    <div class="center">
                        <input id="calltoaction-`+element+`" data-id="{id}" data-name="{name}" data-loading-text="Processing ..." class="btn btn-danger" type="button" value="Mark as favorite">
                    </div>
                    <div class="clearfix"></div>
                </div>
            </form>`; 

        return template;       
    };

    this.showModalResponse = function (message) {

            var $resultDialog = $('<div>' + message + '</div>').appendTo('body');
            Drupal.dialog($resultDialog, {
                title: Drupal.t('Mark as favorite result:'),
                buttons: [{
                        text: Drupal.t('Close'),
                        class: 'btn-secondary',
                        click: function click() {
                            $(this).dialog('close');
                        }
                    }, 
                    {
                        text: Drupal.t('Go to favorites list'),
                        class: 'btn-warning',
                        click: function click() {
                            window.top.location.href = '/pokemon/favorites'; //event.target.href;
                        }
                    }
                ]
            }).showModal();

    }; 

    this.markAsFavorite = function (poke_id, poke_name) {
        var localInstance = this;
        var base_url = Drupal.url('pokelist/ajax/markasfavorite');

        if (poke_id) {

            data = {
                poke_id: poke_id,
                poke_name: poke_name
            };   

            var success = function( result ) {
                
                if( !result[0].error ) {
                    localInstance.showModalResponse('Success: ' + result[0].response);
                } else {
                    error(result[0].response);
                }                                                                              
            };

            var error = function( message ) {
                localInstance.showModalResponse('Error: ' + message);

                return false;
            };

            // Hacemos la petición para obtener los datos del pokémon
            $.ajax({
                url: base_url,
                method: 'POST',
                dataType: 'json',
                data: data,
                beforeSend: function() {
                    //$('#comparison-table').removeClass('hide').addClass('show');
                }                    
            })
            .done( success )
            .fail( error );                

        } else {
            localInstance.showModalResponse('Error: Pokémon Id wasn\'t selected');

            return false;
        }
    };     
}