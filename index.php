<?php
session_start();

$clientId = '';
$clientSecret = '';
$redirectUri = '';

function getAccessToken() {
    return $_SESSION['access_token'];
}

function getRefreshToken() {
    return $_SESSION['refresh_token'];
}

function refreshAccessToken() {
    global $clientId, $clientSecret;

    $refreshToken = getRefreshToken();

    if (!$refreshToken) {
        echo 'Refresh token missing.';
        return;
    }

    $auth = base64_encode("$clientId:$clientSecret");

    $data = array(
        'grant_type' => 'refresh_token',
        'refresh_token' => $refreshToken
    );

    $options = array(
        'http' => array(
            'header' => "Authorization: Basic $auth\r\nContent-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
        ),
    );

    $context = stream_context_create($options);
    $response = file_get_contents('https://accounts.spotify.com/api/token', false, $context);

    if ($response === FALSE) {
        echo 'An error occurred while refreshing the token.';
        return;
    }

    $tokenData = json_decode($response);
    $_SESSION['access_token'] = $tokenData->access_token;
    $_SESSION['token_expiration_time'] = time() + $tokenData->expires_in; // Set token expiration time

    echo 'Access token refreshed successfully.';
}

function tokenExpired() {
    if (isset($_SESSION['token_expiration_time'])) {
        $tokenExpirationTime = $_SESSION['token_expiration_time'];
        $currentTime = time();
        return $currentTime >= $tokenExpirationTime;
    }
    return true; // Token is considered expired if expiration time is not set
}

if (!getAccessToken()) {
    if (isset($_GET['code'])) {
        $code = $_GET['code'];

        $auth = base64_encode("$clientId:$clientSecret");

        $data = array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
        );

        $options = array(
            'http' => array(
                'header' => "Authorization: Basic $auth\r\nContent-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ),
        );

        $context = stream_context_create($options);
        $response = file_get_contents('https://accounts.spotify.com/api/token', false, $context);

        if ($response === FALSE) {
            echo 'An error occurred during authentication.';
        } else {
            $tokenData = json_decode($response);
            $_SESSION['access_token'] = $tokenData->access_token;
            $_SESSION['refresh_token'] = $tokenData->refresh_token;
            $_SESSION['token_expiration_time'] = time() + $tokenData->expires_in; // Set token expiration time

            // Redirect to your homepage or another relevant page
            header('Location: index.php');
            exit();
        }
    } else {
        $authUrl = 'https://accounts.spotify.com/authorize?' . http_build_query(array(
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'scope' => 'user-read-currently-playing',
        ));

        echo "<a href='$authUrl'>Authorize with Spotify</a>";
    }
} else {
    // Check if the access token has expired
    if (tokenExpired()) {
        refreshAccessToken();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Currently Playing Track</title>
    <!-- Include jQuery for AJAX requests -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        /* Reset default margin and padding */
        body, html {
            margin: 0;
            padding: 0;
        }

        /* Center-align the content vertically and horizontally */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4; /* Background color for the entire page */
        }

        /* Beautify the container */
        .container {
            text-align: center;
            background-color: #1DB954;
            color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
            width: 80%;
            max-width: 400px; /* Limit the maximum width */
        }

        /* Add circling animation for music note icon */
        @keyframes musicAnimation {
            0% { transform: scale(1) rotate(0deg); }
            25% { transform: scale(1.1) rotate(90deg); }
            50% { transform: scale(1) rotate(180deg); }
            75% { transform: scale(1.1) rotate(270deg); }
            100% { transform: scale(1) rotate(360deg); }
        }

        /* Apply animation to a music note icon */
        .music-icon {
            font-size: 24px;
            animation: musicAnimation 4s infinite linear;
        }

        /* Style the "Authorize with Spotify" link */
        a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #1DB954;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin-top: 20px;
            font-size: 16px;
        }

        a:hover {
            background-color: #168f3a;
        }

        /* Center align the loading message */
        .loading {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h3>Currently Playing Track</h3>
        <div id="currentlyPlaying">
            <span class="music-icon">&#9835;</span> <!-- Music note icon -->
            <div class="loading">Loading...</div> <!-- Loading message, replace with actual track information -->
        </div>
    </div>

    <script>
        // Function to fetch and update the currently playing track
        function fetchCurrentlyPlaying() {
            $.ajax({
                url: 'get_currently_playing.php',
                method: 'GET',
                success: function(data) {
                    // Update the content of the 'currentlyPlaying' div
                    $('#currentlyPlaying').html(data);
                }
            });
        }

        // Periodically fetch the currently playing track every 10 seconds
        setInterval(fetchCurrentlyPlaying, 10000);

        // Fetch the currently playing track on page load
        fetchCurrentlyPlaying();
    </script>
</body>
</html>
