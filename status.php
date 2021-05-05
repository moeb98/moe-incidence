<?php
include('./src/CoronaIncidence.php');

### Configure here ###

# Find your region here and get the OBJECTID: 
# https://npgeo-corona-npgeo-de.hub.arcgis.com/datasets/917fc37a709542548cc3be077a786c17_0
$default_id = 26; // Göttingen

### End of configs ###

$id = $default_id;
if (!empty($_POST['latitude']) && !empty($_POST['longitude']) ) { 

    $c = curl_init();
    $c_url = 'https://services7.arcgis.com/mOBPykOjAyBO2ZKk/arcgis/rest/services/RKI_Landkreisdaten/FeatureServer/0/query?where=1%3D1&outFields=OBJECTID&geometry='
    .trim($_POST['longitude']).'%2C'.trim($_POST['latitude']).'%2C'.substr(trim($_POST['longitude']), 0, 7).'%2C'.substr(trim($_POST['latitude']), 0, 7).'%2C'
    .'&geometryType=esriGeometryEnvelope&inSR=4326&spatialRel=esriSpatialRelIntersects&outSR=4326&f=json';

    curl_setopt(
        $c,
        CURLOPT_URL, $c_url
    );

    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($c);
    if (curl_errno($c)) {
        $id = $default_id;
    } else {
        $json = json_decode($result, true);

        if (!isset($json['features'][0]['attributes'])) {
            return;
        }
        $id = $json['features'][0]['attributes']['OBJECTID'];
    }
    curl_close($c);
} 

$threshold = 100;

$incidence = new CoronaIncidence($id);

$today = $incidence->getDaily(0);

echo "<div class='widget'>";

echo "<h2><div class=bold>" . $today['GEN'] . "</div></h2>";

drawStoplight($today, $threshold);

echo "<h3>Inzidenzwerte</h3>";
echo "<table id='tbl_incidence'>";
echo drawLine($today);
echo drawLine($incidence->getDaily(1));
echo drawLine($incidence->getDaily(2));
echo "</table>";

// echo "<h6>Fälle in 7 Tagen pro 100.000 Einwohner, Quelle: <a href='https://www.rki.de/DE/Home/homepage_node.html'>RKI</a></h6>";

echo "</div>";

function drawLine($data) {
    echo "<tr><td>";
    if ($data) {
        $inc = round($data['cases7_per_100k'], 2);
        if ($inc < 100) {
            $co = "value_ok";
        } else {
            $co = "value_stop";
        }

        echo germanDay($data['ts']) . ", " . date("d.m.Y", $data['ts']) . "</td>
                <td class='" . $co . "'>" . round($data['cases7_per_100k'], 2);
    } else {
        echo "<td>&nbsp;</td>";
    }
    echo "&nbsp;</td></tr>";
}

function drawStoplight($data, $threshold) {
    if ($data['cases7_per_100k'] > $threshold) {
        $color = "stoplight_stop";
        $text = "Geschlossen";
    } else {
        $color = "stoplight_ok";
        $text = "Geöffnet";
    }
    echo "<div id='div_stoplight' class='" . $color . "'>";
    echo $text;
    echo "</div>";
}

function germanDay($ts) {
    $d = [
        1 => "Montag",
        2 => "Dienstag",
        3 => "Mittwoch",
        4 => "Donnerstag",
        5 => "Freitag",
        6 => "Samstag",
        7 => "Sonntag"
    ];
    return $d[date("N", $ts)];
}

?>
