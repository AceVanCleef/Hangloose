/**
 * Global variables
 */
var marker, surfMarker, map, chart, geocoder, captchaX, captchaY;

/** holds the basic REST request URLs */
var restUrls = {
    getRatings: 'http://localhost:8080/hangloose/src/api/ratings/',
    postRating: 'http://localhost:8080/hangloose/src/api/rating'
};


$(document).ready(function () {

    initChart();
    initAutocomplete();
    initCaptcha();

    $('#readRatings').show();


    $('#rating_points').barrating({
        theme: 'fontawesome-stars'
    });

    /** Eventhandlers */

    // My Location
    $(document.body).find('main').on('click', '#location_button', function () {
        event.preventDefault();
        getGeolocation();
    });

    // search destination
    $(document.body).find('#destinationsuchen').on('click', '#search_button', function (event) {
        event.preventDefault();
        var location = $('#actualLocation').val();
        geocodeAddress(location, geocoder);
    });

    // Listen for changes in coordinates latitude
    $('#coordinatesLocationLng').change(function () {
        var lng = $(this).val();
        if (!isNaN(lng) && lng >= -180 && lng <= 180) {
            setLocation(pos = {
                lat: parseFloat($('#coordinatesLocationLat').val()),
                lng: parseFloat(lng)
            });
        }
        else {
            alert("Error: Your input isn't a correct coordinate for latitude!");
        }
    });

    // Listen for changes in coordinates longitude
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


    // submit button
    $('#submit_rating').click(function () {
        if (validateRatingForm()) {
            createRating();
        }
    });
});


/**
 * Validates rating form inputs.
 * @returns {boolean} true if all inputs correct
 */
function validateRatingForm() {
    var errorMsg = '';

    var toVerify = $('#answer').val();

    if (captchaX + captchaY != toVerify) {
        errorMsg += "Captcha has not the correct value!\n";
    }
    if ($('#rating_title').val().length == 0) {
        errorMsg += "Title can't be empty!\n";
    }

    if ($('#rating_text').val().length == 0) {
        errorMsg += "Rating comment can't be empty!\n";
    }

    if ($('#img-upload').prop('files').length != 0) {
        var imgExtension = $('#img-upload').val().substring(
            $('#img-upload').val().lastIndexOf('.') + 1).toLowerCase();

        if (!(imgExtension == "gif" || imgExtension == "png"
            || imgExtension == "jpeg" || imgExtension == "jpg")) {
            errorMsg += "File is not an image!\n";
        }
        else if ($('#img-upload').prop('files')[0].size / 1024 / 1024 > 20) {
            errorMsg += "Image is bigger than 20MB!\n";
        }
    }

    if (errorMsg != '') {
        alert("Form error:\n" + errorMsg);
        return false;
    } else {
        return true;
    }
}

/**
 * transfers rating input data and location coordinates to DB.
 */
function createRating() {
    var jsonDataObj = {
        lat: $('#coordinatesSurfspotLat').val(),
        lng: $('#coordinatesSurfspotLng').val(),
        ratPoints: $('#rating_points').val(),
        ratTitle: $('#rating_title').val(),
        ratText: $('#rating_text').val(),
        imgPath: null
    };

    var imageFile = $('#img-upload').prop('files')[0];

    var formData = new FormData();
    formData.append("jsonDataObj", JSON.stringify(jsonDataObj));
    formData.append("image", imageFile);

    $.ajax({
        url: restUrls.postRating,
        data: formData,
        processData: false,
        contentType: false,
        type: 'POST',
        crossDomain: true,
        error: function (msg) {
            alert('transmition failed:' + msg);
        },
        success: function (data) {
            showRatings({lat: data.lat, lng: data.lng});
            openRating('readRatings');
            $('#readRatingsTab').addClass("active");
            emptyForm();
        }
    });
}

/**
 * Empty the rating form.
 */
function emptyForm() {
    $('#rating_points').barrating('clear');
    $('#rating_title').val("");
    $('#rating_text').val("");
    $('#img-upload').val("");
    $('#Antwort').val("");
    initCaptcha();
}

/**
 * Initialize a Google Maps into the section with Geolocation. Called by script initialisation.
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
    captchaX = Math.floor((Math.random() * 10) + 1);
    captchaY = Math.floor((Math.random() * 10) + 1);
    var no1 = makeNumber(captchaX);
    var no2 = makeNumber(captchaY);
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
        var surfPos = {
            lat: tideData.responseLat,
            lng: tideData.responseLon
        };
        surfMarker.setPosition(surfPos);
        marker.setPosition(pos);
        map.setCenter(surfPos);
        showTideData(tideData);
        //showRatings({lat: 4.444, lng: 5.555});
        showRatings(surfPos);
    });
}

/**
 * Presents surf data in the frontend.
 * @param tideData response object of WorldTides API
 */
function showTideData(tideData) {
    myPos = {lat: parseFloat(tideData.requestLat), lng: parseFloat(tideData.requestLon)};
    surfPos = {lat: parseFloat(tideData.responseLat), lng: parseFloat(tideData.responseLon)};
    $('#coordinatesLocationLat').val(myPos.lat.toFixed(6));
    $('#coordinatesLocationLng').val(myPos.lng.toFixed(6));
    $('#coordinatesSurfspotLat').val(surfPos.lat.toFixed(6));
    $('#coordinatesSurfspotLng').val(surfPos.lng.toFixed(6));
    geocodeLatLng(geocoder, myPos, $('#actualLocation'));
    geocodeLatLng(geocoder, surfPos, $('#surfspotLocation'));
    setChartData(tideData.heights);
}

/**
 * Displays the ratings into the frontend.
 * @param pos coordinates
 */
function showRatings(pos) {
    var table = $(document.body).find('#readRatings');
    console.log(pos);
    $.ajax({
        url: restUrls.getRatings + pos.lat + '/' + pos.lng,
        dataType: 'json',
        type: 'GET',
        crossDomain: true,
        error: function (msg) {
            table.empty();
            table.append("There are no ratings yet. You're welcome to add a new one.")
            return 'error: ' + msg;
        },
        success: function (data) {
            table.empty();
            data.forEach(function (e) {
                table.append(createEntry(e));
                barrating();
            });
        }
    });
}

/**
 * prepares the html representation of a rating.
 * @param data a rating
 * @returns {string} DOM representation of a rating.
 */
function createEntry(data) {
    var imgHidden = '', imgPath = data.RAT_PICTURE_PATH;

    if (imgPath == null) {
        imgHidden = 'hidden';
        imgPath = '';
    }
    else {
        imgPath = 'img/ratings/' + imgPath;
    }

    var article = '<article class="row"><div class="col-m-6"><b>' +
        data.RAT_TITLE + '</b><p>' + data.RAT_COMMENT + '</p></div><div class="col-m-2"><p><select data-current-rating="' + data.RAT_POINTS + '"  class="starrating">' +
        '<option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4' +
        '</option><option value="5">5</option></select></p></div><div class="col-m-4"><img ' + imgHidden + 'src="' + imgPath +
        '" class="responsive-img"/></div></article>';

    return article;
}

/**
 * initializes barrating plugin for each <selection> element of ratings.
 */
function barrating() {
    $('#readRatings select').each(function (index, select) {
        var currentRating = $(select).data('current-rating');
        $(select).barrating({
            theme: 'fontawesome-stars',
            readonly: true,
            initialRating: currentRating,
        });
    });
}

/**
 * Gets the actual Location and set the location on the map.
 */
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

/**
 * Find coordinates with a search query and set the location on the map.
 * @param address Search query
 * @param geocoder Maps Geocoder object
 */
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

/**
 * Find address with coordinates and show in resultElement.
 * @param geocoder Maps Geocoder object
 * @param pos Map location object (coordinates)
 * @param resultElement Text input where the address is written
 */
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
 * @param pos requested location object
 * @param errorFunction function called by failure
 * @param successFunction function called by success
 */
function getTideData(pos, errorFunction, successFunction) {
    $.ajax({
        url: 'https://www.worldtides.info/api?heights&key=a9b7587f-73a5-4b34-b643-18a493c7c3e3&lat=' + pos.lat + '&lon=' + pos.lng,
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
 * returns String name of this number
 * @param numb number between 1-10
 * @returns {string} text of number
 */
function makeNumber(numb) {
    switch (numb) {
        case 1:     return "one"; break;
        case 2:     return "two"; break;
        case 3:     return "three"; break;
        case 4:     return "four"; break;
        case 5:     return "five"; break;
        case 6:     return "six"; break;
        case 7:     return "seven"; break;
        case 8:     return "eight"; break;
        case 9:     return "nine"; break;
        case 10:     return "ten"; break;
        default:    return "NR generation failed.";
    }
}

/**
 * Change Rating tabs.
 * @param ratingTab id der Zieldivs im DOM - Tree
 */
function openRating(ratingTab) {
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
    //evt.currentTarget.className += " active";
}

