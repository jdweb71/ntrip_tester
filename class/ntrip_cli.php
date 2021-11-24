<?php
/*****
Cette oeuvre est mise à disposition selon les termes de la Licence Creative Commons Attribution 
- Pas d'Utilisation Commerciale 
- Pas de Modification 
4.0 International
http://creativecommons.org/licenses/by-nc-nd/4.0/
09/11/2021 Julian Desfetes
******/

class ntrip_cli
{

    function nmea_checksum($nmea)
    {
        $parts = str_split($nmea);
        $checksum = 0;
        for ($i = 0; $i < strlen($nmea); $i++) {
            $part = $parts[$i];
            $nr = ord($part);
            $checksum ^= $nr;
        }
        return '$' . $nmea . '*' . strtoupper(base_convert($checksum, 10, 16));
    }


    function convert_DD_to_DDMM($lat, $lon, $output = 'json')
    {

        $fullcoord = substr(shell_exec('echo ' . $lat . ' ' . $lon . ' | GeoConvert -p 3 -:'), 0, -1);

        $coord = explode(' ', $fullcoord);

        foreach ($coord as $i => $value) {
            $coord = explode(':', $value);
            $min = $coord[1] + (substr($coord[2], 0, -1) / 60);

            switch ($i) {
                case 0:

                    $out['lat']['coord'] = $coord[0] . $min;
                    $out['lat']['dir'] = substr($coord[2], -1);
                    break;
                case 1:
                    $out['lon']['coord'] = $coord[0] . $min;
                    $out['lon']['dir'] = substr($coord[2], -1);
                    break;
            }
        }
        if ($output !== 'json') {
            return $out;
        } else {
            return json_encode($out, true);
        }
    }

    function init_session($tk)
    {
        session_start();
        $out['status'] = 'start';
        $_SESSION['curl_progress_' . $tk] = json_encode($out, true);
        session_write_close();
    }


    function progressCallback($resource, $download_size, $downloaded, $upload_size, $uploaded, $startime, $output, $tk)
    {

        if (($downloaded >= 0) && ((time() - $startime) > 0)) {
            $currentSpeed = ($downloaded / (time() - $startime));
            $out['downloaded'] = $downloaded;
            $out['currentSpeed'] = round($currentSpeed);
            $out['time'] = date("i:s", (time() - $startime));
            $out['outputFile'] = $output;
            $out['status'] = 'progress';
        } else {
            $out['downloaded'] = '0';
            $out['currentSpeed'] = '0';
            $out['time'] = '0';
            $out['outputFile'] = $output;
            $out['status'] = 'finish';
        }

        $_SESSION['curl_progress_' . $tk] = json_encode($out, true);
    }

    function curl_request($var, $user, $agent, $nmea, $output, $tk)
    {

        session_start();
        if (isset($_SESSION['cancel_curl_' . $tk])) {
            unset($_SESSION['cancel_curl_' . $tk]);
        }
        session_write_close();

        $startime =  time();
        $head = ['Ntrip-Version: Ntrip/2.0', 'Ntrip-GGA:' . $nmea];
        $targetFile = fopen(sys_get_temp_dir().'/' . $output, 'w+');
        $ch = curl_init($var);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $user);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $head);
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION,  function ($resource, $download_size, $downloaded, $upload_size, $uploaded) use ($startime, $output, $tk) {
            session_start();
            if (isset($_SESSION['cancel_curl_' . $tk])) {
                unset($_SESSION['cancel_curl_' . $tk]);
                $out['downloaded'] = '0';
                $out['currentSpeed'] = '0';
                $out['time'] = '0';
                $out['outputFile'] = $output;
                $out['status'] = 'finish';

                $_SESSION['curl_progress_' . $tk] = json_encode($out, true);
                session_write_close();
                return 1;
            }
            $this->progressCallback($resource, $download_size, $downloaded, $upload_size, $uploaded, $startime, $output, $tk);
            session_write_close();
        });

        curl_setopt($ch, CURLOPT_BUFFERSIZE, 1024);
        curl_setopt($ch, CURLOPT_FILE, $targetFile);
        curl_exec($ch);
        fclose($targetFile);
    }

    function check_output($file, $dl, $output = 'json')
    {
        $filename = sys_get_temp_dir().'/' . $file;

        if (file_exists($filename)) {

            $fifo = substr(finfo_file(finfo_open(FILEINFO_MIME), $filename), 0, 4);


            if ($fifo !== 'text') {
                if ($dl !== '0') {
                    $out['txt'] = 'Successfully connected,receiving NTRIP datas';
                    $out['color'] = 'green';
                } else if (((time() - filemtime($filename)) >= 5) && ($dl == '0')) {
                    $out['txt'] = 'A error was occured, check caster url and port';
                    $out['color'] = 'red';
                } else {
                    $out['txt'] = 'Connected, pending datas...';
                    $out['color'] = 'grey';
                }
            } else {
                $out['txt'] = file_get_contents($filename);
                $out['color'] = 'red';
            }
        } else {
            $out['txt'] = 'Not connected';
            $out['color'] = 'red';
        }
        if ($output == 'json') {
            return json_encode($out, true);
        } else {
            return $out;
        }
    }

    function get_curl_progress($tk)
    {
        session_start();
        if (isset($_SESSION['curl_progress_' . $tk])) {
            return $_SESSION['curl_progress_' . $tk];
            unset($_SESSION['curl_progress_' . $tk]);
        }

        session_write_close();
    }

    function curl_cancel($tk)
    {
        session_start();
        $_SESSION['cancel_curl_' . $tk] = '';
        session_write_close();
    }



    function curl_get_mp($caster)
    {

        $ch = curl_init();
        try {
            curl_setopt($ch, CURLOPT_URL, "http://" . $caster);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Ntrip-Version: Ntrip/2.0']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);



            $sourcetable = curl_exec($ch);

            if (curl_errno($ch)) {
                return curl_error($ch);
                die();
            }

            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($http_code == intval(200)) {



                $exp = explode('STR;', $sourcetable);
                unset($exp[0]);

                foreach ($exp as $key => $out) {

                    $output[$key]['name'] = explode(';', $out)[0];
                    $output[$key]['identifier'] = explode(';', $out)[1];
                    $output[$key]['format'] = explode(';', $out)[2];
                }
                return json_encode($output, true);
            } else {
                $output['error'] = $http_code;
                return json_encode($output, true);
            }
        } catch (\Throwable $th) {
            throw $th;
        } finally {
            curl_close($ch);
        }
    }
}
