<?php
/**
 * SMART CROP ASSISTANT - Weather API Helper
 * File: app/views/weather.php
 *
 * This file fetches REAL weather data from OpenWeatherMap.
 * We call this from result.php and pass it the location
 * the user typed in the form.
 *
 * API USED: OpenWeatherMap (free tier)
 * DOCS: https://openweathermap.org/current
 */


/**
 * getWeatherData()
 * -------------------------------------------------------
 * Fetches current weather for a given city/location.
 *
 * @param  string $location   City name e.g. "Bicol", "Manila"
 * @param  string $api_key    Your OpenWeatherMap API key
 * @return array              Weather data array
 */
function getWeatherData($location, $api_key) {

    /* ====================================================
       STEP 1 — BUILD THE API URL
       We use urlencode() on the location so spaces and
       special characters don't break the URL.
       Example: "Legazpi City" becomes "Legazpi+City"
       ==================================================== */
    $location_encoded = urlencode($location);

    // Build the full API endpoint URL
    // units=metric   → temperature in Celsius (not Fahrenheit)
    // appid=...      → your API key
    $api_url = "https://api.openweathermap.org/data/2.5/weather"
             . "?q={$location_encoded}"
             . "&units=metric"
             . "&appid={$api_key}";


    /* ====================================================
       STEP 2 — CALL THE API USING PHP CURL
       cURL is PHP's built-in tool for making HTTP requests.
       Think of it like a browser that PHP can control.
       ==================================================== */

    // Initialize a new cURL session
    $curl = curl_init();

    // Set cURL options
    curl_setopt_array($curl, [
        // The URL to fetch
        CURLOPT_URL            => $api_url,

        // Return the response as a string (don't print it)
        CURLOPT_RETURNTRANSFER => true,

        // Timeout after 10 seconds if no response
        CURLOPT_TIMEOUT        => 10,

        // Follow redirects if any
        CURLOPT_FOLLOWLOCATION => true,

        // SSL verification (keep true for security)
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    // Execute the request and get the response
    $response = curl_exec($curl);

    // Check if cURL had an error (e.g. no internet)
    $curl_error = curl_error($curl);

    // Always close the cURL session when done
    curl_close($curl);


    /* ====================================================
       STEP 3 — HANDLE ERRORS
       If the API call failed, return fallback mock data
       so the result page still displays something useful.
       ==================================================== */
    if ($curl_error || !$response) {
        return getFallbackWeather($location, 'Connection failed: ' . $curl_error);
    }


    /* ====================================================
       STEP 4 — PARSE THE JSON RESPONSE
       The API returns data in JSON format.
       json_decode() converts it to a PHP array.

       Example API response:
       {
         "main": { "temp": 28.5, "humidity": 75 },
         "weather": [{ "main": "Rain", "description": "light rain" }],
         "wind": { "speed": 3.2 },
         "name": "Legazpi"
       }
       ==================================================== */
    $data = json_decode($response, true); // true = return as array

    // Check if JSON parsing worked
    if (!$data) {
        return getFallbackWeather($location, 'Invalid API response');
    }

    // Check if API returned an error (e.g. city not found)
    // API returns cod=200 for success, cod=404 for not found
    if (isset($data['cod']) && $data['cod'] !== 200) {
        $error_msg = $data['message'] ?? 'Unknown API error';
        return getFallbackWeather($location, $error_msg);
    }


    /* ====================================================
       STEP 5 — EXTRACT THE WEATHER DATA
       Pull out the specific values we need from the
       nested JSON response array.
       ==================================================== */

    // Temperature (already in Celsius because we set units=metric)
    $temp     = round($data['main']['temp'] ?? 0, 1);

    // "Feels like" temperature
    $feels    = round($data['main']['feels_like'] ?? 0, 1);

    // Humidity percentage (0-100)
    $humidity = $data['main']['humidity'] ?? 0;

    // Weather condition name e.g. "Rain", "Clear", "Clouds"
    $condition = $data['weather'][0]['main'] ?? 'Unknown';

    // More detailed description e.g. "light rain", "clear sky"
    $description = ucfirst($data['weather'][0]['description'] ?? 'No data');

    // Wind speed in meters per second
    $wind_speed = round($data['wind']['speed'] ?? 0, 1);

    // City name as confirmed by the API
    $city_name = $data['name'] ?? $location;


    /* ====================================================
       STEP 6 — PICK AN EMOJI ICON FOR THE CONDITION
       We map the API's condition string to a friendly emoji
       ==================================================== */
    $icon = getWeatherIcon($condition);


    /* ====================================================
       STEP 7 — DETERMINE FARMING IMPACT
       This is our smart feature — we translate weather
       conditions into farming-relevant alerts
       ==================================================== */
    $farming_alert = getFarmingAlert($temp, $humidity, $condition);


    /* ====================================================
       STEP 8 — RETURN THE FORMATTED WEATHER ARRAY
       ==================================================== */
    return [
        'success'        => true,
        'city'           => $city_name,
        'temp'           => $temp,
        'feels_like'     => $feels,
        'humidity'       => $humidity,
        'condition'      => $condition,
        'description'    => $description,
        'wind_speed'     => $wind_speed,
        'icon'           => $icon,
        'farming_alert'  => $farming_alert,
        'error'          => null,
    ];
}


/**
 * getWeatherIcon()
 * -------------------------------------------------------
 * Maps an OpenWeatherMap condition string to an emoji.
 *
 * @param  string $condition  e.g. "Rain", "Clear", "Clouds"
 * @return string             Emoji icon
 */
function getWeatherIcon($condition) {
    $icons = [
        'Clear'        => '☀️',
        'Clouds'       => '☁️',
        'Rain'         => '🌧️',
        'Drizzle'      => '🌦️',
        'Thunderstorm' => '⛈️',
        'Snow'         => '❄️',
        'Mist'         => '🌫️',
        'Fog'          => '🌫️',
        'Haze'         => '🌫️',
        'Smoke'        => '🌫️',
        'Dust'         => '🌪️',
        'Sand'         => '🌪️',
        'Tornado'      => '🌪️',
    ];

    // Return the matching icon, or a default sun if not found
    return $icons[$condition] ?? '🌤️';
}


/**
 * getFarmingAlert()
 * -------------------------------------------------------
 * Analyzes weather values and returns a farming-specific
 * alert or tip. This is what makes our app "smart"!
 *
 * @param  float  $temp       Temperature in Celsius
 * @param  int    $humidity   Humidity percentage
 * @param  string $condition  Weather condition
 * @return array              Alert with message and type
 */

/**
 * getFarmingAlert()
 * Rice-specific weather alerts for Philippine farmers
 */
function getFarmingAlert($temp, $humidity, $condition) {

    // ---- Typhoon / Thunderstorm ----
    if ($condition === 'Thunderstorm') {
        return [
            'type'    => 'danger',
            'icon'    => '⛈️',
            'message' => 'Typhoon or thunderstorm warning! Drain your rice '
                       . 'fields to prevent lodging. Secure seedlings and '
                       . 'young tillers from strong winds and flooding.',
        ];
    }

    // ---- Extreme Heat ----
    if ($temp > 36) {
        return [
            'type'    => 'danger',
            'icon'    => '🔥',
            'message' => 'Extreme heat alert! Rice at flowering stage is '
                       . 'very vulnerable. Ensure fields have 3-5cm water '
                       . 'depth to cool the root zone and prevent spikelet '
                       . 'sterility.',
        ];
    }

    // ---- Heavy Rain + High Humidity (Blast risk) ----
    if ($condition === 'Rain' && $humidity > 85) {
        return [
            'type'    => 'danger',
            'icon'    => '🌧️',
            'message' => 'High risk of Rice Blast disease! Prolonged leaf '
                       . 'wetness and high humidity are ideal for Pyricularia '
                       . 'oryzae. Apply preventive fungicide (Tricyclazole) '
                       . 'before symptoms appear.',
        ];
    }

    // ---- High Humidity alone ----
    if ($humidity > 80) {
        return [
            'type'    => 'warning',
            'icon'    => '💧',
            'message' => 'High humidity increases risk of Sheath Blight and '
                       . 'Bacterial Leaf Blight. Monitor fields closely. '
                       . 'Ensure proper drainage between your rice rows.',
        ];
    }

    // ---- Dry and Low Humidity ----
    if ($humidity < 40) {
        return [
            'type'    => 'warning',
            'icon'    => '🏜️',
            'message' => 'Dry conditions detected. Maintain 2-5cm flood depth '
                       . 'in your rice paddies. Consider Alternate Wetting '
                       . 'and Drying (AWD) technique to conserve water.',
        ];
    }

    // ---- Cold Temperature ----
    if ($temp < 18) {
        return [
            'type'    => 'warning',
            'icon'    => '🥶',
            'message' => 'Cool temperatures may delay rice growth and tillering. '
                       . 'Increase water depth to 10cm to protect roots '
                       . 'from cold stress. Avoid top-dressing urea during '
                       . 'cold weather.',
        ];
    }

    // ---- Ideal Rice Growing Conditions ----
    if ($temp >= 24 && $temp <= 34 && $humidity >= 50 && $humidity <= 75) {
        return [
            'type'    => 'good',
            'icon'    => '✅',
            'message' => 'Excellent conditions for rice growth today! '
                       . 'Temperature and humidity are within the ideal range '
                       . 'for Philippine rice varieties. Great day for '
                       . 'field scouting and fertilizer application.',
        ];
    }

    // ---- Normal / Moderate ----
    return [
        'type'    => 'info',
        'icon'    => 'ℹ️',
        'message' => 'Weather conditions are moderate for rice farming. '
                   . 'Continue regular field monitoring, irrigation '
                   . 'management, and pest scouting as scheduled.',
    ];
}


/**
 * getFallbackWeather()
 * -------------------------------------------------------
 * Returns mock weather data when the API call fails.
 * This ensures the result page always shows something
 * even if there is no internet connection.
 *
 * @param  string $location  City name
 * @param  string $error     Error message for debugging
 * @return array             Mock weather data
 */
function getFallbackWeather($location, $error = '') {
    return [
        'success'       => false,
        'city'          => $location,
        'temp'          => 28,
        'feels_like'    => 30,
        'humidity'      => 75,
        'condition'     => 'Clouds',
        'description'   => 'Weather data unavailable',
        'wind_speed'    => 2.5,
        'icon'          => '🌤️',
        'farming_alert' => [
            'type'    => 'info',
            'icon'    => 'ℹ️',
            'message' => 'Could not fetch live weather. Showing default '
                       . 'values. Check your internet connection.',
        ],
        'error'         => $error,
    ];
}