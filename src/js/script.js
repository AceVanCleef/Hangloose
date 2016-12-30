$(document).ready(function () {
    $(document.body).find('#io_wrapper').on('click', 'button', function (event) {
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
    $(document.body).find('main').on('click', '#location_button', function() {
        alert("my location");
    });
    placenumber();

});

var marker, surfMarker, map;

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
    surfMarker= new google.maps.Marker({
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

function showTideData() {
    alert("test");
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

function getTideData(pos, errorFunction, successFunction) {
    $.ajax({
        url: 'https://www.worldtides.info/api?heights&key=9ce9447c-6193-48a6-acf4-16d43c8b0915&lat=' + pos.lat + '&lon=' + pos.lng,
        dataType: 'json',
        type: 'GET',
        crossDomain: true,
        error: function (msg) {
            errorFunction(msg)
        },
        success: function (data) {
            successFunction(data);
        }
    });
}

//Support fuer Safari und andere iOs Browsers
function hasHtml5Validation() {
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
}
//math to text
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
}//end makenumber function
function placenumber() {
    var x = Math.floor((Math.random() * 10) + 1);
    var y = Math.floor((Math.random() * 10) + 1);
    var no1 = makenumber(x);
    var no2 = makenumber(y);
    var ans = x + y;
    document.getElementById('Antwort').pattern = ans;
    document.getElementById("no1").innerHTML = no1;
    document.getElementById("no2").innerHTML = no2;
}//end placenumber function

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

    Highcharts.chart('chartContainer', {
        chart: {
            type: 'area'
        },
        /*title: {
         text: 'US and USSR nuclear stockpiles'
         },*/
        /*subtitle: {
         text: 'Source: <a href="http://thebulletin.metapress.com/content/c4120650912x74k7/fulltext.pdf">' +
         'thebulletin.metapress.com</a>'
         },*/
        xAxis: {
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
        }, {
            name: 'USSR/Russia',
            data: [null, null, null, null, null, null, null, null, null, null,
                5, 25, 50, 120, 150, 200, 426, 660, 869, 1060, 1605, 2471, 3322,
                4238, 5221, 6129, 7089, 8339, 9399, 10538, 11643, 13092, 14478,
                15915, 17385, 19055, 21205, 23044, 25393, 27935, 30062, 32049,
                33952, 35804, 37431, 39197, 45000, 43000, 41000, 39000, 37000,
                35000, 33000, 31000, 29000, 27000, 25000, 24000, 23000, 22000,
                21000, 20000, 19000, 18000, 18000, 17000, 16000]
        }]
    });

});

