/**
 * Simple logic for putting a nice Google map with custom icons and stuff into any large enough block level element (a.k.a. DIV) on your site.
 * Prerequisites:
 * - Google Maps API key -> this is necessary for any domain you wish to use this script on.
 * - Custom icons: -> Fill the array gicons below. One key per icon type you need
 * - geocoded points to display -> use the separate queryGoogleGeoCoder.php file to encode your points accordingly
 * - Include the following line of JavaScript BEFORE this one in your source
 *   <script src="http://maps.google.com/?file=api&amp;v=2.x&amp;key=<YOUR_GOOGLE_API_KEY>" type="text/javascript"></script>
 * @copyright 2007-2008 phlyLabs Berlin, http;//phlylabs.de
 * @author Matthias Sommerfeld <mso@phlylabs.de>
 * @version 0.2.5 2008-04-07
 */
markers = [];
GMicons = [];
GMsetup =
    {'autoCenter' : true
    ,'mapId' : 'google_map'
    ,'zoomTo' : {'lat' : 0, 'lng' : 0, 'lvl' : 25}
    ,'typeControl' : true
    ,'zoomControl' : true
    };
/**
 * Use this function to add markers to the map
 * @param  string  icontype of the marker
 * @param  float  latitude of the marker
 * @param  float  longitude of the marker
 * @param  string  Labelling text
 * @param  string  additional HTML snippet to show inside the bubble popup
 * @param  string  optional url this marker is linked to
 */
function GMaddMarker(icontype, latitude, longitude, label, html, href)
{
    markers.push({"lat":latitude,"lng":longitude,"html": html,"label":label,"icontype":icontype,"href":href});
}

/**
 * Add all your icon types here
 * Important: To prevent script errors, make sure you fill in ALL the icon types actually appearing in
 * any of your markers. Clean data is the key!
 * @param string  type name
 * @param string  URL to the icon; size: 20 x 20 px; GIF recommended for maximum browser compatibility
 */
function GMaddIcon(typ, url)
{
    GMicons[typ] = url
}
/**
 * Call this function after adjusting the GMsetup, adding custom icons and all your markers
 * In case the browser is not capable of drawing the map, no map will be dran
 * @param void
 * @return bool  FALSE, if not capable of drawing, TRUE if drawing has been done
 */
function GMdraw()
{
    if (!GBrowserIsCompatible()) return false; // If browser cannot draw Google map, don't even try to.
    gmarkers = [];
    htmls = [];
    gicons = []
    farbicon = new GIcon();
    farbicon.iconSize = new GSize(20, 20);
    farbicon.shadowSize = new GSize(22, 20);
    farbicon.iconAnchor = new GPoint(10, 10);
    farbicon.infoWindowAnchor = new GPoint(5, 1);
    for (var j in GMicons) {
        gicons[j] = new GIcon(farbicon, GMicons[j], null, null);
    }
    var map = new GMap2(document.getElementById(GMsetup['mapId']));
    if(GMsetup['zoomControl']) map.addControl(new GLargeMapControl());
    if(GMsetup['typeControl']) map.addControl(new GMapTypeControl());
    map.setCenter(new GLatLng(0,0),0);
    map.setMapType(G_NORMAL_MAP);
    bounds = new GLatLngBounds();
    i = 0;
    for (var j = 0; j < markers.length; j++) {
        point = new GLatLng(parseFloat(markers[j].lat), parseFloat(markers[j].lng));
        marker = GMcreateMarker(point, markers[j].label, markers[j].html, gicons[markers[j].icontype], markers[j].href);
        marker2 = GMcreateMarker2(point, markers[j].icontype);
        map.addOverlay(marker);
        bounds.extend(point);
    }
    if (GMsetup['autoCenter']) {
        map.setZoom(map.getBoundsZoomLevel(bounds));
        map.setCenter(bounds.getCenter());
    } else if (GMsetup['zoomTo']['lat'] != 0 || GMsetup['zoomTo']['lng'] != 0) {
        map.setZoom(GMsetup['zoomTo']['lvl']);
        point = new GLatLng(parseFloat(GMsetup['zoomTo']['lat']), parseFloat(GMsetup['zoomTo']['lng']));
        map.setCenter(point);
    }
    return true;
}

function GMcreateMarker(point, name, html, icontype, href)
{
    var marker = new GMarker(point, icontype);
    if (html) {
        GEvent.addListener(marker, 'mouseover', function() {
            marker.openInfoWindowHtml('<div id="window"><div class="name"> ' + name + '</div>' + html + '</div>');
        });
        GEvent.addListener(marker, 'mouseout', function() { marker.closeInfoWindow(); });
    }
    if (href) GEvent.addListener(marker, 'click', function() { self.location.href = href; });
    gmarkers[i] = marker;
    if (html) htmls[i] = '<div id="window"><div class="name"> ' + name + '</div>' + html + '</div>';
    i++;
    return marker;
}

function GMcreateMarker2(point,icontype)
{
    var marker2 = new GMarker(point,gicons[icontype]);
    gmarkers[i] = marker2;
    return marker2;
}