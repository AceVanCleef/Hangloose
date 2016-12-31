$(document).ready(function () {
    var marker, surfMarker, map, chart, geocoder;

    initNavigation();
    initChart();
    initAutocomplete();
    initCaptcha();
    // addEventHandlers();
    $(document.body).find('main').on('click', '#location_button', function () {
        event.preventDefault();
        getGeolocation();
    });

    $(document.body).find('#destinationsuchen').on('click', '#search_button', function (event) {
        event.preventDefault();
        var location = $('#actualLocation').val();
        geocodeAddress(location, geocoder);
    });

    $('#coordinatesLocationLng').change(function () {
        var lng = $(this).val();
        if (!isNaN(lng) && lng >= -180 && lat <= 180) {
            setLocation(pos = {
                lat: parseFloat($('#coordinatesLocationLat').val()),
                lng: parseFloat(lng)
            });
        }
        else {
            alert("Error: Yout input isn't a correct coordinate for latitude!");
        }
    });

    $('#coordinatesLocationLat').change(function () {
        var lat = $(this).val();
        if (!isNaN(lat) && lat >= -85.05115 && lat <= 85) {
            setLocation(pos = {
                lat: parseFloat(lat),
                lng: parseFloat($('#coordinatesLocationLng').val())
            });
        }
        else {
            alert("Error: Yout input isn't a correct coordinate for longitude!");
        }
    });

    //Tab "Bewertungen" bei Seitenaufruf sichtbar
    $('#readRatings').show();
});


/**
 * Initialize a Google Maps into the section with Geolocation.
 */
function initMap() {
    var pos;
    pos = {lat: -34.397, lng: 150.644};
    map = new google.maps.Map(document.getElementById('map_wrapper'), {
        zoom: 8
    });

    marker = new google.maps.Marker({
        map: map,
        position: pos
    });
    var image = 'https://developers.google.com/maps/documentation/javascript/examples/full/images/beachflag.png';
    surfMarker = new google.maps.Marker({
        map: map,
        icon: image
    });

    map.addListener('click', function (e) {
        setLocation(pos = {
            lat: e.latLng.lat(),
            lng: e.latLng.lng()
        });
    });

    getGeolocation();

    geocoder = new google.maps.Geocoder();

}

/**
 * Initialize the autocomplete of the search input with Geolocation.
 */
function initAutocomplete() {
    $("#actualLocation").autocomplete({
        minLength: 3,
        source: function (request, response) {
            geocoder = new google.maps.Geocoder();

            geocoder.geocode({
                'address': request.term
            }, function (results, status) {
                if (status == google.maps.GeocoderStatus.OK) {

                    response($.map(results, function (loc) {
                        return {
                            label: loc.formatted_address,
                            value: loc.formatted_address,
                            lat: loc.geometry.location.lat(),
                            lng: loc.geometry.location.lng()
                        }
                    }));
                }
            });
        },
        select: function (event, ui) {
            pos = {lat: ui.item.lat, lng: ui.item.lng};

            if (pos)
                setLocation(pos);
        }
    });
}

/**
 * Initialize the tab links for navigation.
 */
function initNavigation() {
    /*$(document.body).find('#ratings_section').hide();
     $(document.body).find('#rate_section').hide();


     $(document.body).find('nav').on('click', '#destinationsuchenLink', function (event) {
     $(document.body).find('#ratings_section').hide();
     $(document.body).find('#rate_section').hide();
     $(document.body).find('#destinationsuchen').show();
     });

     $(document.body).find('nav').on('click', '#ratingsLink', function (event) {
     $(document.body).find('#ratings_section').show();
     $(document.body).find('#rate_section').hide();
     $(document.body).find('#destinationsuchen').hide();
     });

     $(document.body).find('nav').on('click', '#ratingsabgebenLink', function (event) {
     $(document.body).find('#ratings_section').hide();
     $(document.body).find('#rate_section').show();
     $(document.body).find('#destinationsuchen').hide();
     });

     */
}

/**
 * Initialise tide chart with required options.
 */
function initChart() {
    chart = new Highcharts.Chart('chartContainer', {
        title: {
            text: null
        },
        chart: {
            type: 'area'
        },
        xAxis: [
            {
                "type": "datetime",
                "labels": {
                    "format": "{value:%b %e}"
                },
                tickInterval: 24 * 3600 * 1000
            }
        ],
        yAxis: {
            title: {
                text: 'Heights (in meters)'
            }
        },
        tooltip: {
            pointFormat: '<b>{point.y}m</b>'
        },
        legend: {
            enabled: false
        }
    });
}

/**
 * Places the captcha numbers.
 */
function initCaptcha() {
    var x = Math.floor((Math.random() * 10) + 1);
    var y = Math.floor((Math.random() * 10) + 1);
    var no1 = makenumber(x);
    var no2 = makenumber(y);
    var ans = x + y;
    document.getElementById('Antwort').pattern = ans;
    document.getElementById("no1").innerHTML = no1;
    document.getElementById("no2").innerHTML = no2;
}

/**
 * Set the new location and update data.
 * @param pos Position object of the requested Location
 */
function setLocation(pos) {
    getTideData(pos, function (msg) {
        alert("error: " + JSON.stringify(msg));
    }, function (tideData) {
        surfPos = {
            lat: tideData.responseLat,
            lng: tideData.responseLon
        };
        surfMarker.setPosition(surfPos);
        marker.setPosition(pos);
        map.setCenter(surfPos);
        showTideData(tideData);
    });
}

/**
 * Presents surf data in the frontend.
 * @param tideData response object of WorldTides API
 */
function showTideData(tideData) {
    myPos = {lat: parseFloat(tideData.requestLat), lng: parseFloat(tideData.requestLon)};
    surfPos = {lat: parseFloat(tideData.responseLat), lng: parseFloat(tideData.responseLon)};
    $('#coordinatesLocationLat').val(myPos.lat.toFixed(3));
    $('#coordinatesLocationLng').val(myPos.lng.toFixed(3));
    $('#coordinatesSurfspotLat').val(surfPos.lat.toFixed(3));
    $('#coordinatesSurfspotLng').val(surfPos.lng.toFixed(3));
    //alert(geocodeLatLng(geocoder, myPos));
    geocodeLatLng(geocoder, myPos, $('#actualLocation'));
    geocodeLatLng(geocoder, surfPos, $('#surfspotLocation'));
    setChartData(tideData.heights);
}

function getGeolocation() {
    // Try HTML5 geolocation.
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (position) {
            pos = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };
            setLocation(pos);
        }, function () {
            alert("Error: The Geolocation service failed");
        });
    } else {
        alert("Error: Your Browser don't support Geolocation");
    }
}

function geocodeAddress(address, geocoder) {
    geocoder = new google.maps.Geocoder();
    geocoder.geocode({'address': address}, function (results, status) {
        if (status === google.maps.GeocoderStatus.OK) {
            pos = {lat: results[0].geometry.location.lat(), lng: results[0].geometry.location.lng()};
            setLocation(pos);
        } else {
            alert("Error: Geocode couldn't find any results.");
        }
    });
}

function geocodeLatLng(geocoder, pos, resultElement) {
    geocoder = new google.maps.Geocoder();
    geocoder.geocode({'location': pos}, function (results, status) {
        if (status === google.maps.GeocoderStatus.OK) {
            if (results[1]) {
                resultElement.val(results[1].formatted_address);
            }
        } else {
            alert("Error: Geocode couldn't find any results.");
        }
    });
}

/**
 * Call data of WorldTides API.
 * @param pos requested location pbject
 * @param errorFunction function called by failure
 * @param successFunction function called by success
 */
function getTideData(pos, errorFunction, successFunction) {
    $.ajax({
        url: 'https://www.worldtides.info/api?heights&key=9ce9447c-6193-48a6-acf4-16d43c8b0915&lat=' + pos.lat + '&lon=' + pos.lng,
        dataType: 'json',
        type: 'GET',
        crossDomain: true,
        error: function (msg) {
            errorFunction(msg);
        },
        success: function (data) {
            successFunction(data);
        }
    });
}

/**
 * Parse data and display in chart.
 * @param chartData Json 'heights' data of WorldTides API
 */
function setChartData(chartData) {
    var data = [];

    if (chart.series.length != 0)
        chart.series[0].remove();
    chartData.forEach(function (e) {
        var point = [Date.parse(e.date), e.height];
        data.push(point);
    });

    chart.addSeries({
        name: 'USA',
        data: data,
    });
}

/**
 * Check if the browser supports html5
 * @returns {boolean} true if supported
 */
/*function hasHtml5Validation() {
 return typeof document.createElement('input').checkValidity === 'function';
 }

 if (hasHtml5Validation()) {
 $('.validate-form').submit(function (e) {
 if (!this.checkValidity()) {
 e.preventDefault();
 $(this).addClass('invalid');
 $('#status').html('invalid');
 } else {
 $(this).removeClass('invalid');
 $('#status').html('submitted');
 }
 });
 }*/

/**
 * Generate text out of a numbers.
 * @param numb number between 1-10
 * @returns {string} text of number
 */
function makenumber(numb) {
    if (numb == 1)return "Eins";
    if (numb == 2)return "Zwei";
    if (numb == 3)return "Drei";
    if (numb == 4)return "Vier";
    if (numb == 5)return "Fünf";
    if (numb == 6)return "Sechs";
    if (numb == 7)return "Sieben";
    if (numb == 8)return "Acht";
    if (numb == 9)return "Neun";
    if (numb == 10)return "Zehn";
}

/* Ermöglicht Wechsel zwischen den Tabs "Bewertungen" und "Gib deine Bewertung ab".
 *
 * @param evt   OnClick Event der Tabs.
 * @param ratingTab id der Zieldivs im DOM - Tree.
 */
function openRating(evt, ratingTab) {
    // Declare all variables
    var i, tabcontent, tablinks;

    // Get all elements with class="tabcontent" and hide them
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    // Get all elements with class="tablinks" and remove the class "active"
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    // Show the current tab, and add an "active" class to the link that opened the tab
    document.getElementById(ratingTab).style.display = "block";
    evt.currentTarget.className += " active";
}