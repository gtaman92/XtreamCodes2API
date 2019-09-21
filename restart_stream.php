<?php
set_time_limit(0);

define("MAIN_DIR", "/home/xtreamcodes/");
define("IPTV_PANEL_DIR", MAIN_DIR . "iptv_xtream_codes/");
define("STREAMS_PATH", IPTV_PANEL_DIR . "streams/");

define("TIMEOUT", 60);          // TIMEOUT BEFORE CANCELLING
define("AUTH", "98671942");     // API AUTHENTICATION

if ((!isset($_GET["auth"])) OR (!$_GET["auth"] == AUTH)) {
    echo "Access denied.";
    exit;
}

// MySQL details
define("HOST", "127.0.0.1:7999");
define("USER", "root");
define("PASS", "");
define("DB", "xtream_iptvpro");

$db = new mysqli(HOST, USER, PASS, DB);

function delete_existing($stream_id) {
    $pid = STREAMS_PATH . $stream_id . "_.pid";
    $monitor = STREAMS_PATH . $stream_id . ".monitor";
    unlink($pid); unlink($monitor);
}

function get_pid($stream_id) {
    $pid = STREAMS_PATH . $stream_id . "_.pid";
    if (file_exists($pid)) {
        return intval(file_get_contents($pid));
    } else {
        return false;
    }
}

function get_stream_info($server_stream_id) {
    global $db;
    $result = $db->query("SELECT `stream_info` FROM `streams_sys` WHERE `server_stream_id` = ".$server_stream_id.";");
    if ($result->num_rows == 1) {
        return json_decode($result->fetch_assoc()["stream_info"], True);
    } else {
        return Array();
    }
}

function restart_stream($stream_id, $server_id) {
    global $db;
    $result = $db->query("SELECT `server_stream_id` FROM `streams_sys` WHERE `stream_id` = ".$db->real_escape_string($stream_id)." AND `server_id` = ".$db->real_escape_string($server_id).";");
    if ($result->num_rows == 1) {
        $server_stream_id = intval($result->fetch_assoc()["server_stream_id"]);
        $db->query("UPDATE `streams_sys` SET `parent_id` = NULL, `pid` = -1, `to_analyze` = 1, `stream_status` = 1, `stream_started` = NULL, `stream_info` = NULL, `monitor_pid` = NULL, `current_source` = NULL, `bitrate` = NULL, `progress_info` = '', `on_demand` = '', `delay_pid` = NULL, `delay_available_at` = NULL WHERE `server_stream_id` = ".$server_stream_id.";");
        delete_existing($stream_id);
        $i = 0;
        while (!$pid = get_pid($stream_id)) {
            sleep(1);
            $i ++;
            if ($i == TIMEOUT) {
                $pid = null;
            }
        }
        if ($pid) {
            $db->query("UPDATE `streams_sys` SET `pid` = ".intval($pid).", `to_analyze` = 0, `stream_status` = 0 WHERE `server_stream_id` = ".$server_stream_id.";");
            echo "Stream restarted. New PID: ".$pid."<br/><br/>";
            print_r(get_stream_info($server_stream_id));
        } else {
            echo "Timeout before receiving new PID. Stream must have failed.";
        }
    } else {
        $result = $db->query("SELECT `id` FROM `streams` WHERE `id` = ".$db->real_escape_string($stream_id).";");
        if ($result->num_rows == 1) {
            $db->query("INSERT INTO `streams_sys`(`stream_id`, `server_id`, `pid`, `to_analyze`, `stream_status`) VALUES(".$db->real_escape_string($stream_id).", ".$db->real_escape_string($server_id).", -1, 1, 1);");
            $server_stream_id = $db->insert_id;
            delete_existing($stream_id);
            $i = 0;
            while (!$pid = get_pid($stream_id)) {
                sleep(1);
                $i ++;
                if ($i == TIMEOUT) {
                    $pid = null;
                }
            }
            if ($pid) {
                $db->query("UPDATE `streams_sys` SET `pid` = ".intval($pid).", `to_analyze` = 0, `stream_status` = 0 WHERE `server_stream_id` = ".$server_stream_id.";");
                echo "Stream created. PID: ".$pid."<br/><br/>";
                print_r(get_stream_info($server_stream_id));
            } else {
                echo "Timeout before receiving new PID. Stream must have failed.";
            }
        } else {
            echo "Source doesn't exist.";
        }
    }
}

if (isset($_GET["id"])) {
    if (!isset($_GET["server"])) {
        $_GET["server"] = 1;
    }
    restart_stream($_GET["id"], $_GET["server"]);
} else {
    echo "No Stream ID.";
}
?>