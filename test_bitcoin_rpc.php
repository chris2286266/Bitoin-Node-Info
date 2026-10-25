<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/bitcoin_rpc.inc';

$config = require __DIR__ . '/config.php';
foreach (['url', 'username', 'password'] as $key) {
    if (!isset($config[$key]) || !is_string($config[$key]) || $config[$key] === '') {
        fwrite(STDERR, "Invalid or missing '{$key}' setting in config.php.\n");
        exit(1);
    }
}

try {
    $result = callBitcoinRpc(
        $config['url'],
        $config['username'],
        $config['password'],
        'getblockchaininfo',
        []
    );
} catch (Exception $exception) {
    fwrite(STDERR, 'RPC library test failed: ' . $exception->getMessage() . "\n");
    exit(1);
}

if (
    !is_array($result)
    || !isset($result['chain'], $result['blocks'])
    || !is_string($result['chain'])
    || !is_int($result['blocks'])
) {
    fwrite(STDERR, "RPC library test failed: getblockchaininfo returned an unexpected result.\n");
    exit(1);
}

fwrite(STDOUT, "RPC library test passed.\n");
fwrite(STDOUT, 'Chain: ' . $result['chain'] . "\n");
fwrite(STDOUT, 'Block height: ' . $result['blocks'] . "\n");
