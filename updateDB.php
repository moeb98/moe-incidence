<?php

ignore_user_abort(true);
set_time_limit(0);

include('./src/CoronaIncidence.php');

$c = curl_init();
$c_url = 'https://services7.arcgis.com/mOBPykOjAyBO2ZKk/arcgis/rest/services/RKI_Landkreisdaten/FeatureServer/0/query?where=1%3D1&outFields=*&geometry=-30.805%2C46.211%2C52.823%2C55.839&geometryType=esriGeometryEnvelope&inSR=4326&spatialRel=esriSpatialRelIntersects&returnIdsOnly=true&outSR=4326&f=json';

curl_setopt(
    $c,
    CURLOPT_URL, $c_url
);

curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);

$result = curl_exec($c);
if (!curl_errno($c)) {
    $json = json_decode($result, true);
    if (!isset($json['objectIds'])) {
        return;
    }
    $ids = $json['objectIds'];
}
curl_close($c);

foreach ($ids as $id) {
    $incidence = new CoronaIncidence($id);

    $today = $incidence->getDaily(0);
    echo $today['ts']." ".$today['OBJECTID']."<br />";  
}

?>
