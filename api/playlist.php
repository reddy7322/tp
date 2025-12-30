<?php
// ===================== HEADERS =====================
header("Cache-Control: max-age=84000, public");
header("Content-Type: audio/x-mpegurl");
header('Content-Disposition: attachment; filename="playlist.m3u"');

// ===================== LOAD CHANNEL DATA =====================
function getAllChannelInfo(): array {
    $json = @file_get_contents('https://raw.githubusercontent.com/reddy7322/tp/refs/heads/main/public/tplay.json');
    if ($json === false) {
        http_response_code(500);
        exit;
    }

    $channels = json_decode($json, true);
    if ($channels === null) {
        http_response_code(500);
        exit;
    }

    return $channels;
}

$channels = getAllChannelInfo();

// ===================== M3U HEADER =====================
$m3u8PlaylistFile = "#EXTM3U x-tvg-url=\"https://www.tsepg.cf/epg.xml.gz\"\n\n";

// ===================== MAIN CHANNEL LOOP =====================
foreach ($channels as $channel) {

    $id = $channel['id'] ?? null;
    $dashUrl = $channel['streamData']['MPD='] ?? null;

    if (!$id || !$dashUrl) {
        continue;
    }

    $playlistUrl = "https://salman-bhai-ka.vercel.app/{$id}.mpd";

    $logo = $channel['channel_logo'] ?? '';
    $genre = $channel['channel_genre'][0] ?? 'Entertainment';
    $name  = $channel['channel_name'] ?? "Channel {$id}";

    $m3u8PlaylistFile .= "#EXTINF:-1 "
        . "tvg-id=\"{$id}\" "
        . "tvg-country=\"IN\" "
        . "catchup-days=\"7\" "
        . "tvg-logo=\"https://mediaready.videoready.tv/tatasky-epg/image/fetch/f_auto,fl_lossy,q_auto,h_250,w_250/{$logo}\" "
        . "group-title=\"{$genre}\",{$name}\n";

    // REQUIRED FOR KODI
    $m3u8PlaylistFile .= "#KODIPROP:inputstream=inputstream.adaptive\n";
    $m3u8PlaylistFile .= "#KODIPROP:inputstream.adaptive.manifest_type=mpd\n";
    $m3u8PlaylistFile .= "#KODIPROP:inputstream.adaptive.license_type=clearkey\n";

    // 🔥 DYNAMIC END ID (IMPORTANT)
    $m3u8PlaylistFile .= "#KODIPROP:inputstream.adaptive.license_key=https://kong-tatasky-clearkey-api.vercel.app/tkey/{$id}\n";

    // VLC / PLAYER HEADER
    $m3u8PlaylistFile .= "#EXTVLCOPT:http-user-agent=third-party\n";

    // STREAM URL
    $m3u8PlaylistFile .= "{$playlistUrl}\n\n";
}

// ===================== EXTRA STATIC CHANNELS =====================
$additionalEntries = <<<EOT
#EXTINF:-1 tvg-logo="https://upload.wikimedia.org/wikipedia/commons/1/12/%26flix_logo.png" group-title="Movies",&flix HD
#EXTVLCOPT:http-user-agent=Mozilla/5.0
https://la.drmlive.au/tp/zee.php?id=andflixhd

#EXTINF:-1 tvg-logo="http://jiotv.catchup.cdn.jio.com/dare_images/images/Sony_HD.png" group-title="Sony Liv",SONY HD
https://la.drmlive.au/tp/sliv.php?id=sony

#EXTINF:-1 tvg-id="144" group-title="Entertainment" tvg-language="Hindi" tvg-logo="https://v3img.voot.com/resizeMedium,w_1090,h_613/v3Storage/assets/colors-hindi--16x9-1714557869344.jpg",Colors HD
https://jc.drmlive-01.workers.dev/144.m3u8
EOT;

// ===================== OUTPUT =====================
echo $m3u8PlaylistFile . "\n" . $additionalEntries;
?>
