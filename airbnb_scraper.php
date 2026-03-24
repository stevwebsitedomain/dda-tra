<?php
header("Content-Type: application/json");

// Query ya kutafuta listings
$query = "Kinondoni, Dar es Salaam"; 

$url = "https://www.airbnb.com/api/v2/explore_tabs".
       "?version=1.7.9".
       "&_format=for_explore_search_web".
       "&items_per_grid=10".
       "&key=d306zoyjsyarp7ifhu67rjxn52tv0t20".
       "&query=" . urlencode($query);

// cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64)");
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

// Decode JSON
$data = json_decode($response, true);

$listings = [];
if (isset($data['explore_tabs'][0]['sections'][0]['listings'])) {
    foreach ($data['explore_tabs'][0]['sections'][0]['listings'] as $item) {
        $info = $item['listing'];
        $pricing = $item['pricing_quote'] ?? [];
        $listings[] = [
            "title" => $info['name'] ?? "No title",
            "city" => $info['city'] ?? "",
            "price" => $pricing['rate']['amount_formatted'] ?? "N/A",
            "url" => "https://www.airbnb.com/rooms/" . $info['id']
        ];
    }
}

echo json_encode([
    "success" => true,
    "count" => count($listings),
    "listings" => $listings
]);
