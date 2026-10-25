<?php
declare(strict_types=1);

$secureCookie = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $secureCookie,
        'samesite' => 'Strict',
    ]);
} else {
    session_set_cookie_params(0, '/', '', $secureCookie, true);
}
session_start();

require_once __DIR__ . '/bitcoin_rpc.inc';

$rpcConfig = require __DIR__ . '/config.php';
$rpcUrl = $rpcConfig['url'];
$rpcUsername = $rpcConfig['username'];
$rpcPassword = $rpcConfig['password'];
$methods = [
    'getblockchaininfo' => ['label' => 'Blockchain info', 'description' => 'Chain, block height, and sync status', 'params' => []],
    'getwalletinfo' => ['label' => 'Wallet info', 'description' => 'Wallet balance, transaction count, and wallet status', 'params' => []],
    'getnetworkinfo' => ['label' => 'Network info', 'description' => 'Version, connections, and network settings', 'params' => []],
    'getmininginfo' => ['label' => 'Mining info', 'description' => 'Mining and block statistics', 'params' => []],
    'getpeerinfo' => ['label' => 'Peer info', 'description' => 'Connected peer details', 'params' => []],
    'getmempoolinfo' => ['label' => 'Mempool info', 'description' => 'Current memory pool statistics', 'params' => []],
    'getblockcount' => ['label' => 'Block count', 'description' => 'Current best block height', 'params' => []],
    'getdifficulty' => ['label' => 'Difficulty', 'description' => 'Current proof-of-work difficulty', 'params' => []],
    'getblockhash' => ['label' => 'Block hash by height', 'description' => 'Look up a block hash by its height', 'params' => ['height']],
    'getblock' => ['label' => 'Block by hash', 'description' => 'Look up block details by its hash', 'params' => ['hash']],
];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$result = null;
$error = null;
$activeMethod = 'getblockchaininfo';
$inputValue = '';

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $error = 'Your session token expired. Refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'call') {
            $requestedMethod = $_POST['method'] ?? '';
            if (is_string($requestedMethod) && isset($methods[$requestedMethod])) {
                $activeMethod = $requestedMethod;
                $inputValue = trim((string) ($_POST['argument'] ?? ''));
                $params = [];
                $validArgument = true;

                if ($requestedMethod === 'getblockhash') {
                    if ($inputValue === '' || !ctype_digit($inputValue)) {
                        $error = 'Enter a non-negative whole-number block height.';
                        $validArgument = false;
                    } else {
                        $params[] = (int) $inputValue;
                    }
                } elseif ($requestedMethod === 'getblock') {
                    if (!preg_match('/^[a-fA-F0-9]{64}$/', $inputValue)) {
                        $error = 'Enter a valid 64-character block hash.';
                        $validArgument = false;
                    } else {
                        $params = [strtolower($inputValue), 1];
                    }
                }

                if ($validArgument) {
                    try {
                        $result = callBitcoinRpc(
                            $rpcUrl,
                            $rpcUsername,
                            $rpcPassword,
                            $requestedMethod,
                            $params
                        );
                    } catch (RuntimeException $exception) {
                        $error = $exception->getMessage();
                    }
                }
            } else {
                $error = 'Choose a supported RPC method.';
            }
        } else {
            $error = 'Choose a supported action.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title>Bitcoin Node · RPC Dashboard</title>
    <style>
        :root {
            color-scheme: dark;
            --background: #111318;
            --surface: #191c23;
            --surface-raised: #20242d;
            --border: #2d323d;
            --text: #f0f2f5;
            --muted: #a0a7b4;
            --accent: #f2a900;
            --accent-dark: #d89600;
            --danger: #ff8d8d;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--background); color: var(--text); min-height: 100vh; }
        .shell { width: min(1080px, calc(100% - 40px)); margin: 0 auto; }
        header { border-bottom: 1px solid var(--border); }
        .topbar { min-height: 76px; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .brand { display: flex; align-items: center; gap: 12px; font-weight: 700; letter-spacing: -.02em; }
        .coin { width: 35px; height: 35px; display: grid; place-items: center; border-radius: 50%; color: #151515; background: var(--accent); font-weight: 800; font-size: 21px; }
        .brand small { display: block; margin-top: 2px; color: var(--muted); font-size: 12px; font-weight: 500; letter-spacing: 0; }
        .endpoint { color: var(--muted); font-size: 13px; overflow-wrap: anywhere; text-align: right; }
        main { padding: 42px 0 72px; }
        h1 { margin: 0; font-size: clamp(27px, 4vw, 37px); letter-spacing: -.04em; }
        .intro { margin: 9px 0 28px; color: var(--muted); line-height: 1.6; }
        .layout { display: grid; grid-template-columns: 310px minmax(0, 1fr); gap: 20px; align-items: start; }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 13px; }
        .card-inner { padding: 21px; }
        h2 { margin: 0 0 17px; font-size: 16px; letter-spacing: -.01em; }
        label { display: block; margin: 15px 0 7px; color: #d5d9e0; font-size: 13px; font-weight: 600; }
        input, select { width: 100%; min-height: 42px; border: 1px solid var(--border); border-radius: 7px; padding: 0 11px; background: #12151a; color: var(--text); font: inherit; }
        input:focus, select:focus { outline: 2px solid #f2a90088; border-color: var(--accent); }
        button { min-height: 42px; border: 0; border-radius: 7px; padding: 0 15px; background: var(--accent); color: #18140b; font: inherit; font-weight: 700; cursor: pointer; }
        button:hover { background: #ffc233; }
        .primary { width: 100%; margin-top: 19px; }
        .quiet { min-height: 34px; padding: 0 11px; color: var(--muted); background: transparent; border: 1px solid var(--border); font-size: 12px; }
        .quiet:hover { background: var(--surface-raised); color: var(--text); }
        .note, .hint { color: var(--muted); font-size: 12px; line-height: 1.5; }
        .note { margin: 13px 0 0; }
        .status { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 18px; }
        .status span { color: var(--muted); font-size: 12px; }
        .error { margin: 0 0 18px; padding: 12px 14px; border: 1px solid #6d3838; border-radius: 8px; background: #321d20; color: var(--danger); font-size: 13px; line-height: 1.5; overflow-wrap: anywhere; }
        .result-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 13px; }
        .result-head h2 { margin: 0; }
        pre { max-height: 580px; overflow: auto; margin: 0; padding: 17px; border: 1px solid var(--border); border-radius: 8px; background: #111318; color: #d6deea; font: 12px/1.65 ui-monospace, SFMono-Regular, Consolas, monospace; white-space: pre-wrap; overflow-wrap: anywhere; }
        .placeholder { min-height: 310px; display: grid; place-content: center; text-align: center; color: var(--muted); line-height: 1.6; }
        .placeholder strong { display: block; margin-bottom: 4px; color: #dfe3e9; font-size: 14px; }
        .method-description { min-height: 18px; margin-top: 7px; }
        footer { margin-top: 23px; color: #777f8c; font-size: 12px; line-height: 1.6; }
        @media (max-width: 720px) {
            .shell { width: min(100% - 28px, 560px); }
            .topbar { min-height: 68px; }
            .endpoint { max-width: 48%; font-size: 11px; }
            main { padding-top: 30px; }
            .layout { grid-template-columns: 1fr; }
            .placeholder { min-height: 190px; }
        }
    </style>
</head>
<body>
<header>
    <div class="shell topbar">
        <div class="brand"><span class="coin">₿</span><span>Bitcoin Node<small>RPC dashboard</small></span></div>
        <div class="endpoint">Node endpoint&nbsp; <strong><?= escape($rpcUrl) ?></strong></div>
    </div>
</header>
<main class="shell">
    <h1>Node overview</h1>
    <p class="intro">Inspect your Bitcoin Core node using JSON-RPC.</p>

    <?php if ($error !== null): ?>
        <div class="error" role="alert"><?= escape($error) ?></div>
    <?php endif; ?>

    <div class="layout">
        <aside class="card">
            <div class="card-inner">
                <div class="status">
                    <h2 style="margin:0">RPC actions</h2>
                    <span>Configured in config.php</span>
                </div>
                <form method="post" id="rpc-form">
                    <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="call">
                    <label for="method">Function</label>
                    <select id="method" name="method">
                        <?php foreach ($methods as $method => $details): ?>
                            <option value="<?= escape($method) ?>" <?= $activeMethod === $method ? 'selected' : '' ?>><?= escape($details['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="hint method-description" id="method-description"></p>
                    <div id="argument-field" hidden>
                        <label id="argument-label" for="argument">Argument</label>
                        <input id="argument" name="argument" value="<?= escape($inputValue) ?>" autocomplete="off">
                    </div>
                    <button class="primary" type="submit">Run RPC call</button>
                </form>
                <p class="note">Only read-only RPC methods are available. Use HTTPS if this dashboard is accessible over a network.</p>
            </div>
        </aside>

        <section class="card" aria-live="polite">
            <div class="card-inner">
                <?php if ($result !== null): ?>
                    <div class="result-head">
                        <h2><?= escape($methods[$activeMethod]['label'] ?? 'Blockchain info') ?></h2>
                        <span class="hint">JSON response</span>
                    </div>
                    <pre><?= escape(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: 'null') ?></pre>
                <?php else: ?>
                    <div class="placeholder">
                        <div><strong>No RPC response yet</strong>Select a function to request data from your node.</div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
    <footer>RPC access is sent from this PHP server to the configured node endpoint. Keep RPC credentials private and do not expose the node RPC port to the public internet.<br>(C) baumann.at 2026 · MIT License</footer>
</main>
<script>
    const methodDetails = <?= json_encode($methods, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const methodSelect = document.getElementById('method');
    const argumentField = document.getElementById('argument-field');
    const argumentInput = document.getElementById('argument');
    const argumentLabel = document.getElementById('argument-label');
    const description = document.getElementById('method-description');

    function updateMethod() {
        const details = methodDetails[methodSelect.value];
        const requiresArgument = details.params.length > 0;
        argumentField.hidden = !requiresArgument;
        argumentInput.required = requiresArgument;
        argumentLabel.textContent = details.params[0] === 'height' ? 'Block height' : 'Block hash';
        argumentInput.placeholder = details.params[0] === 'height' ? 'e.g. 800000' : '64-character block hash';
        argumentInput.inputMode = details.params[0] === 'height' ? 'numeric' : 'text';
        description.textContent = details.description;
    }

    methodSelect.addEventListener('change', updateMethod);
    updateMethod();
</script>
</body>
</html>
