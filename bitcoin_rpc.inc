<?php
declare(strict_types=1);

function callBitcoinRpc(string $url, string $username, string $password, string $method, array $params)
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('The PHP cURL extension is required.');
    }

    $payload = json_encode([
        'jsonrpc' => '1.0',
        'id' => 'bitcoin-dashboard',
        'method' => $method,
        'params' => $params,
    ]);
    if ($payload === false) {
        throw new RuntimeException('Could not encode RPC request: ' . json_last_error_msg());
    }

    $curl = curl_init($url);
    if ($curl === false) {
        throw new RuntimeException('Could not initialize the cURL client.');
    }

    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => $username . ':' . $password,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $response = curl_exec($curl);
    $curlError = curl_error($curl);
    $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($response === false) {
        throw new RuntimeException('Could not reach the Bitcoin node: ' . $curlError);
    }

    if ($statusCode === 401) {
        throw new RuntimeException(
            'RPC authentication failed (HTTP 401). Check the username and password in config.php and confirm the URL points to the node RPC service.'
        );
    }
    if ($statusCode === 403) {
        throw new RuntimeException(
            'The node or proxy denied RPC access (HTTP 403). Check its access-control and proxy settings.'
        );
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('The RPC endpoint returned a non-JSON response (HTTP ' . $statusCode . ').');
    }
    if (!empty($decoded['error'])) {
        $rpcError = $decoded['error'];
        $message = is_array($rpcError) ? ($rpcError['message'] ?? 'Unknown RPC error') : 'Unknown RPC error';
        throw new RuntimeException((string) $message);
    }
    if ($statusCode < 200 || $statusCode >= 300) {
        throw new RuntimeException('The Bitcoin node returned HTTP ' . $statusCode . '.');
    }

    return $decoded['result'] ?? null;
}
