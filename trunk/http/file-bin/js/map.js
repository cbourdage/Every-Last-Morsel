
var map;
var initialLocation;
var initialMarker;
var zoomLevel = 14;
//var browserSupportFlag = new Boolean();

!function($) {

	// Init map height
	var resizeMap = null;
	(resizeMap = function() {
		$('#mapCanvas').height($(window).height() - $('.header-container').outerHeight(true));
	})();
	window.onresize = function(e) {
		resizeMap();
	}

	$('a[href="#how-it-works"]').on('click', function(e) {
		$(this).parent().toggleClass('active');
	});

	// Init location
	_initRecurse();

	var locationTimeout;
	function _initRecurse() {
		if (typeof(Elm.myPosition) == 'undefined') {
			initLocation();
			locationTimeout = window.setTimeout(function() {
				_initRecurse()
			}, 300);
		} else {
			// init location
			initialLocation = new google.maps.LatLng(Elm.myPosition.lat, Elm.myPosition.long);
			_initMap();
		}
	}

	/**
	 * Initializes the map options and events.
	 */
	function _initMap() {

		var options = {
			center: initialLocation,
			zoom: zoomLevel,
			mapTypeId: google.maps.MapTypeId.ROADMAP,
			panControl: true,
			zoomControl: true,
			zoomControlOptions: {
				style: google.maps.ZoomControlStyle.SMALL
		  	},
			streetViewControl: true,
			mapTypeControl: false,
			scaleControl: false,
			overviewMapControl: false
		};

		// Create map
		map = new google.maps.Map(document.getElementById("mapCanvas"), options);

		// Init marker
		initialMarker = new google.maps.Marker({
			map: map,
			position: initialLocation,
			icon: '/file-bin/images/marker-orange.png',
			draggable: true,
			animation: google.maps.Animation.DROP
		});

		var initContentString = '<div class="content mapMarker">' +
			'<div>' +
				'<h3 class="heading"><span>Howdy!</span> From the pitchfork to salad fork we are an <span>urban gardeners dream</span> come true...</h3>'+
				'<div class="content clearfix">' +
					//'<p>Move marker on the map to plot location or type in the address:</p>' +
					'<p>Move the marker on the map to plot location</p>' +
					//'<p><input type="text" name="location" id="location" />' +
					//'<button type="button" name="location_lookup"></button></p>' +
				'</div>' +
			'</div>' +
		'</div>';

		var changeContentString = '<div class="content mapMarker">' +
			'<div>' +
				'<h3 class="heading"><span>Fantastic!</span> This location looks great, is there a garden here?</h3>'+
				'<div class="content clearfix">' +
					'<p>Move the marker on the map to plot location or select this location!</p>' +
					'<p class="buttons-set"><button type="button" class="btn btn-blue authenticate" id="is_a_garden"><span>Absolutely! Lets plot it.</span></button></p>' +
					//'<p><input type="text" name="location" id="location" />' +
					//'<button type="button" name="location_lookup" class="btn search"></button></p>' +
				'</div>' +
			'</div>' +
		'</div>';

        var options = {
			boxStyle: { },
			closeBoxURL: "",
			//isHidden: false,
			pane: "floatPane",
			enableEventPropagation: true,
			maxWidth: 575,
			pixelOffset: new google.maps.Size(-65, -320),
			infoBoxClearance: new google.maps.Size(25, 25),
			zIndex: 10,
			content: initContentString
        };

		// Init infobox display - Trigger infowindow when map is loaded
        var infoWindow = new InfoBox(options);
		google.maps.event.addDomListener(window, 'load', function() {
			infoWindow.open(map, initialMarker);
		});

		// Marker drag functionality
		var coords = { };
		google.maps.event.addListener(initialMarker, 'dragend', function(event) {
			placeMarker(event.latLng);
			infoWindow.setContent(changeContentString);
			infoWindow.panBox_(false);
			coords = {
				lat : event.latLng.lat(),
				long : event.latLng.lng()
			};
		});

		jQuery(function() {
			jQuery('#mapCanvas').on('click', 'button.authenticate', function(e) {
				var $modal = jQuery('#mapModal'),
					$content = $modal.find('.modal-body'),
					$loader = jQuery('<span class="loader green">Loading...</span>');

				jQuery.ajax({
					url: '/index/plot-point/',
					data: jQuery.serializeJSON(coords) + '&type=garden',
					dataType: 'json',
					success: function(response) {
						if (response.success) {
							window.location = response.location;
						}  else {
							$modal.find('h3').html(response.title);
							$content.html(response.html);
							$modal.modal('show');

							// @TODO abstract out - create a login modal object
							$('#mapModal form').on('submit', function(e) {
								e.preventDefault();
								$content.find('.alert').slideUp('fast', function() {
									$(this).remove();
								});
								$content.find('button').attr('disable', 'disable').after($loader);

								jQuery.ajax({
									url: '/profile/login-ajax/?after_auth=/plot/create/',
									data: $(this).serialize(),
									type: 'post',
									dataType: 'json',
									complete: function(response) {
										$loader.remove();
										$content.find('button').attr('disable', '');
									},
									success: function(response) {
										if (response.success) {
											window.location = response.location;
										}  else {
											Elm.error(response.message, $content, 'prepend');
											$modal.modal('show');
										}
									},
									error: function() {
										Elm.error("Oops! We've encountered some troubles. Try again shortly!", $content, 'prepend');
										$modal.modal('show');
									}
								});
								return false;
							});
						}
					},
					error: function() {
						Elm.error("Oops! We've encountered some troubles. Try again shortly!", $content, 'prepend');
						$modal.modal('show');
					}
				});
			});
		});

		/**
		 * Places a marker at the provided latLng
		 *
		 * @param latLng
		 */
		function placeMarker(latLng) {
			console.log(latLng);
			initialMarker.setPosition(latLng);
			if (initialMarker.getAnimation() != null) {
				initialMarker.setAnimation(null);
			} else {
				initialMarker.setAnimation(google.maps.Animation.BOUNCE);
			}
		}
	}
}(window.jQuery);
