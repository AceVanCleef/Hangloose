$(document).ready(function () {
    initChart();
    $(document.body).find('#io_wrapper').on('click', '#search_button', function (event) {
        event.preventDefault();
        var searchQuery = $('#search_input').val();
        // TODO: search for locations and autocomplete them

    });

    $(document.body).find('#ratings_section').hide();
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
    $(document.body).find('main').on('click', '#location_button', function () {
        alert("my location");
    });
    placenumber();

});

var marker, surfMarker, map, chart;


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
        setLocationMarker(pos = {
            lat: e.latLng.lat(),
            lng: e.latLng.lng()
        });
    });
    //infoWindow = new google.maps.InfoWindow({map: map});


    // Try HTML5 geolocation.
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (position) {
            pos = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };

            //infoWindow.setPosition(pos);
            //infoWindow.setContent('Location found.');

            setLocationMarker(pos);
        }, function () {
            //handleLocationError(true, infoWindow, map.getCenter());
            setLocationMarker(pos);
        });
    } else {
        // Browser doesn't support Geolocation
        //handleLocationError(false, infoWindow, map.getCenter());
        setLocationMarker(pos);
    }

    var geocoder = new google.maps.Geocoder();

    document.getElementById('search_button').addEventListener('click', function () {
        geocodeAddress(geocoder, map);
    });
}

/**
 * Set positions of the markers.
 * @param pos Position object of the requested Location
 */
function setLocationMarker(pos) {
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
    $('#coordinatesLocationLat').val(parseFloat(tideData.requestLat).toFixed(3));
    $('#coordinatesLocationLng').val(parseFloat(tideData.requestLon).toFixed(3));
    $('#coordinatesSurfspotLat').val(parseFloat(tideData.responseLat).toFixed(3));
    $('#coordinatesSurfspotLng').val(parseFloat(tideData.responseLon).toFixed(3));
    setChartData(tideData.heights);
}

/**
 * Show an error message due to Geolocation.
 * @param browserHasGeolocation
 * @param infoWindow
 * @param pos
 */
function handleLocationError(browserHasGeolocation, infoWindow, pos) {
    infoWindow.setPosition(pos);
    infoWindow.setContent(browserHasGeolocation ?
        'Error: The Geolocation service failed.' :
        'Error: Your browser doesn\'t support geolocation.');
}

function geocodeAddress(geocoder, resultsMap) {
    var address = document.getElementById('search_input').value;
    geocoder.geocode({'address': address}, function (results, status) {
        if (status === google.maps.GeocoderStatus.OK) {
            resultsMap.setCenter(results[0].geometry.location);
            setLocationMarker(results[0].geometry.location);
        } else {
            alert('Geocode was not successful for the following reason: ' + status);
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

function initChart() {
    chart = new Highcharts.Chart('chartContainer', {
        chart: {
            type: 'area'
        },
        xAxis: [
            {
                "type": "datetime",
                "labels": {
                    "format": "{value:%b %e}"
                }
            }
        ],/*{
            title: {
                text: 'Date/Time'
            },
            tickInterval: 3600 * 1000,
            type: 'datetime',
            labels: {
                formatter: function () {
                    return Highcharts.dateFormat('%b %e', this.value);
                }
            }
        },*/
        yAxis: {
            title: {
                text: 'Heights (in meters)'
            }
        },
        tooltip: {
            pointFormat: '<b>{point.y}</b>'
        }
    });
}

function setChartData(chartData) {
    var data = [];

    chartData.forEach(function(e) {
        var point = [Date.parse(e.date), e.height];
        data.push(point);
    });
    console.log(data);

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

/**
 * Places the captcha numbers.
 */
function placenumber() {
    var x = Math.floor((Math.random() * 10) + 1);
    var y = Math.floor((Math.random() * 10) + 1);
    var no1 = makenumber(x);
    var no2 = makenumber(y);
    var ans = x + y;
    document.getElementById('Antwort').pattern = ans;
    document.getElementById("no1").innerHTML = no1;
    document.getElementById("no2").innerHTML = no2;
}

$(function () {
    $("#search_input").autocomplete({
        minLength: 3,
        source: function (request, response) {
            geocoder = new google.maps.Geocoder();

            geocoder.geocode({
                'address': request.term
            }, function (results, status) {
                if (status == google.maps.GeocoderStatus.OK) {
                    /*var searchLoc = results[0].geometry.location;
                     var lat = results[0].geometry.location.lat();
                     var lng = results[0].geometry.location.lng();
                     var latlng = new google.maps.LatLng(lat, lng);
                     var bounds = results[0].geometry.bounds;*/

                    response($.map(results, function (loc) {
                        return {
                            label: loc.formatted_address,
                            value: loc.formatted_address,
                            pos: loc.geometry.location
                        }
                    }));
                }
            });
        },
        select: function (event, ui) {
            /*  var pos = ui.item.position;
             var lct = ui.item.locType;*/
            var pos = ui.item.pos;

            if (pos) {
                setLocationMarker(pos);
            }
        }
    });

    /* Highcharts.chart('chartContainer', {
     chart: {
     type: 'area'
     },
     xAxis: {
     title: {
     text: 'Date/Time'
     },
     allowDecimals: false,
     labels: {
     formatter: function () {
     return this.value; // clean, unformatted number for year
     }
     }
     },
     yAxis: {
     title: {
     text: 'Heights (in meters)'
     },
     labels: {
     formatter: function () {
     return this.value / 1000 + 'k';
     }
     }
     },
     tooltip: {
     pointFormat: '{series.name} produced <b>{point.y:,.0f}</b><br/>warheads in {point.x}'
     },
     plotOptions: {
     area: {
     pointStart: 1940,
     marker: {
     enabled: false,
     symbol: 'circle',
     radius: 2,
     states: {
     hover: {
     enabled: true
     }
     }
     }
     }
     },
     series: [{
     name: 'USA',
     data: [null, null, null, null, null, 6, 11, 32, 110, 235, 369, 640,
     1005, 1436, 2063, 3057, 4618, 6444, 9822, 15468, 20434, 24126,
     27387, 29459, 31056, 31982, 32040, 31233, 29224, 27342, 26662,
     26956, 27912, 28999, 28965, 27826, 25579, 25722, 24826, 24605,
     24304, 23464, 23708, 24099, 24357, 24237, 24401, 24344, 23586,
     22380, 21004, 17287, 14747, 13076, 12555, 12144, 11009, 10950,
     10871, 10824, 10577, 10527, 10475, 10421, 10358, 10295, 10104]
     }]
     });*/

});

