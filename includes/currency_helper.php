<?php
/**
 * Currency Helper for GPT33
 * Fetches real-time exchange rates from CoinGecko with caching.
 */

function get_dynamic_rate($target_currency)
{
    global $pdo;

    $target_currency = strtoupper($target_currency);

    // USDT is our base (Tether)
    if ($target_currency === 'USDT' || $target_currency === 'USD') {
        return 1.0;
    }

    // Check cache in settings or a temporary file
    // We'll use a simple file-based cache in GPT33/scratch/rates_cache.json
    $cache_file = __DIR__ . '/../scratch/rates_cache.json';
    $cache_expiry = 3600; // 1 hour

    $rates = [];
    if (file_exists($cache_file)) {
        $cache_data = json_decode(file_get_contents($cache_file), true);
        if ($cache_data && (time() - $cache_data['timestamp'] < $cache_expiry)) {
            $rates = $cache_data['rates'];
        }
    }

    if (empty($rates)) {
        // Fetch from CoinGecko
        // USDT to PKR and TRX
        $url = "https://api.coingecko.com/api/v3/simple/price?ids=tether&vs_currencies=pkr,trx";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'GPT33-Currency-Bot/1.0');
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['tether'])) {
                $rates = [
                    'PKR' => $data['tether']['pkr'],
                    'TRX' => $data['tether']['trx']
                ];
                // Save to cache
                file_put_contents($cache_file, json_encode([
                    'timestamp' => time(),
                    'rates' => $rates
                ]));
            }
        }
    }

    // Fallback if API fails and no cache
    if (empty($rates)) {
        $rates = [
            'PKR' => 280.00,
            'TRX' => 8.50
        ];
    }

    return $rates[$target_currency] ?? 1.0;
}
?>