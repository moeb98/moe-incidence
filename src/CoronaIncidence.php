<?php

class CoronaIncidence
{
    private $cache_file;
    private $region_id;

    private $fields = [
        'OBJECTID',
        'GEN',
        'BEZ',
        'BL',
        'cases',
        'deaths',
        'cases_per_population',

        'cases7_per_100k',
        'cases7_lk',
        'death7_lk',

        'cases7_bl_per_100k',
        'cases7_bl',
        'death7_bl',

        'last_update'
    ];

    public function __construct(int $ri)
    {
        $this->region_id = $ri;
        $this->cache_file = './db/'.$ri.'.json';
    }

    public function getDaily($offset = 0)
    {
        $d = new DateTime("today -" . $offset . " day");
        $dt = $d->format('Ymd');

        $c = $this->getCache($dt);
        if (is_array($c)) {
            $c['cached'] = true;
            return $c;
        }
        $c = $this->fetchData($dt);
        if (is_array($c)) {
            $c['cached'] = false;
            return $c;
        }
    }

    private function getCache(string $dt) {
        $f = @file_get_contents($this->cache_file);

        if ($f === false) {
            return;
        }

        $data = json_decode($f, true);
        if (isset($data[$dt]) && ($data[$dt]['OBJECTID'] == $this->region_id)) {
            return $data[$dt];
        } else {
            return;
        }
    }

    private function fetchData(string $dt) {
        $fieldstr = implode(",", $this->fields);

        $c = curl_init();
        curl_setopt(
            $c,
            CURLOPT_URL,
            'https://services7.arcgis.com/mOBPykOjAyBO2ZKk/arcgis/rest/services/RKI_Landkreisdaten/FeatureServer/0/query?where=OBJECTID='
                . $this->region_id . '&outFields=' . $fieldstr . '&outSR=4326&f=json'
        );

        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($c);
        if (curl_errno($c)) {
            throw new Exception("could not contact arcgis server");
        }
        curl_close($c);

        $json = json_decode($result, true);

        if (!isset($json['features'][0]['attributes'])) {
            return;
        }

        $data = $json['features'][0]['attributes'];
        $date = DateTime::createFromFormat("d.m.Y, H:i", str_replace(" Uhr", "", $data['last_update']));
        $data['ts'] = $date->format("U");
        $set = $this->setCache($data);
        if ($dt >= $set) {
            return $data;
        } else {
            return;
        }
    }

    private function setCache($data) {
        $startFromScratch = false;
        $f = @file_get_contents($this->cache_file);
        if ($f === false) {
            $f = @file_get_contents($this->cache_file.".bak");
            if ($f === false) {
                $startFromScratch = true;
                $old = [];
            } else {
                $old = json_decode($f, true);
            }
        } else {
            $old = json_decode($f, true);
        }
        $date = DateTime::createFromFormat("d.m.Y, H:i", str_replace(" Uhr", "", $data['last_update']));
        $key = $date->format("Ymd");
        $old[$key] = $data;

        $parts = explode('/', $this->cache_file);
        array_pop($parts);
        $dir = implode('/', $parts);

        if(!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($this->cache_file, json_encode($old));
        if (!$startFromScratch) {
            file_put_contents($this->cache_file.".bak", json_encode($old));
        }
        return $key;
    }
}

