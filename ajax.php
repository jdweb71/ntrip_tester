
<?php
/*****
Cette oeuvre est mise à disposition selon les termes de la Licence Creative Commons Attribution 
- Pas d'Utilisation Commerciale 
- Pas de Modification 
4.0 International
http://creativecommons.org/licenses/by-nc-nd/4.0/
09/11/2021 Julian Desfetes
******/
include('class/ntrip_cli.php');
$ntrip_cli = new ntrip_cli();

if (
    isset($_GET['lon']) && !empty($_GET['lon']) &&
    isset($_GET['lat']) && !empty($_GET['lat']) &&
    isset($_GET['agent']) && !empty($_GET['agent']) &&
    isset($_GET['user']) && !empty($_GET['user']) &&
    isset($_GET['pwd']) && !empty($_GET['pwd']) &&
    isset($_GET['caster_url']) && !empty($_GET['caster_url']) &&
    isset($_GET['caster_port']) && !empty($_GET['caster_port']) &&
    isset($_GET['mp']) && !empty($_GET['mp']) &&
    isset($_GET['time']) && !empty($_GET['time']) &&
    isset($_GET['tk']) && !empty($_GET['tk'])
) {



    $var = 'http://' . $_GET['caster_url'] . ':' . $_GET['caster_port'] . '/' . $_GET['mp'];
    $user = $_GET['user'] . ':' . $_GET['pwd'];
    $agent = 'NTRIP ' . $_GET['agent'];


    $coord = $ntrip_cli->convert_DD_to_DDMM($_GET['lat'], $_GET['lon'], 'array');

    $nmea = '\\' . $ntrip_cli->nmea_checksum('GPGGA,' . $_GET['time'] . ',' . $coord['lat']['coord'] . ',' . $coord['lat']['dir'] . ',' . $coord['lon']['coord'] . ',' . $coord['lon']['dir'] . ',1,00,0.0,00.00,M,-25.669,M,1,');

    $tk = $_GET['tk'];

    $output = sha1(uniqid(rand(), true));

    $ntrip_cli->curl_request($var, $user, $agent, $nmea, $output, $tk);
}


if (isset($_GET['curl']) && !empty($_GET['curl']) && ($_GET['curl'] == 'progress') && isset($_GET['tk']) && !empty($_GET['tk'])) {
    echo  $ntrip_cli->get_curl_progress($_GET['tk']);
}

if (isset($_GET['curl']) && !empty($_GET['curl']) && ($_GET['curl'] == 'cancel') && isset($_GET['tk']) && !empty($_GET['tk'])) {
    $ntrip_cli->curl_cancel($_GET['tk']);
}

if (isset($_GET['file']) && !empty($_GET['file'])) {
    echo $ntrip_cli->check_output($_GET['file'], $_GET['dl']);
}

if (isset($_GET['action']) && !empty($_GET['action']) && ($_GET['action'] == 'init_session')  && isset($_GET['tk']) && !empty($_GET['tk'])) {
    $ntrip_cli->init_session($tk);
}


if (isset($_GET['action']) && !empty($_GET['action']) && ($_GET['action'] == 'gen_light_tk')) {
    $out['tk'] = substr(sha1(uniqid(rand(), true)), 0, 5);
    echo json_encode($out, true);
}

if (isset($_GET['curl']) && !empty($_GET['curl']) && ($_GET['curl'] == 'get_mp') && isset($_GET['caster']) && !empty($_GET['caster'])) {
    echo $ntrip_cli->curl_get_mp($_GET['caster']);
}


?>