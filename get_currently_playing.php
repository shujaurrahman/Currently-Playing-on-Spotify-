<?php
session_start();

// Function to get the access token
function getAccessToken() {
    return $_SESSION['access_token'];
}

// Use the access token to fetch currently playing track details from Spotify API
$accessToken = getAccessToken();

$options = array(
    'http' => array(
        'header' => "Authorization: Bearer $accessToken",
    ),
);

$context = stream_context_create($options);
$response = file_get_contents('https://api.spotify.com/v1/me/player/currently-playing', false, $context);

if ($response === FALSE) {
    echo 'An error occurred while fetching currently playing track.';
} else {
    $trackData = json_decode($response);

    // Check if a track is currently playing
    if (isset($trackData->item)) {
        $trackName = $trackData->item->name;
        $artistName = $trackData->item->artists[0]->name;
        $albumName = $trackData->item->album->name;

        echo "Currently playing: $trackName by $artistName from the album $albumName";
    } else {
        echo 'No track is currently playing.';
    }
}
?>
