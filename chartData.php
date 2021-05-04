<?php
include('./src/CoronaIncidence.php');

header('Content-Type: application/json');

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

$incidence = new CoronaIncidence($id);

$data = array();
for ($i = 0; $i < 7; $i++ ) {
    $dataset = $incidence->getDaily($i);
    if (isset($dataset)) {
       $data[$i] = $dataset;
    } else {
        break;
    }
}

print json_encode($data);
?>