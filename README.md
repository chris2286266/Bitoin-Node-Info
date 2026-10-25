# Bitcoin Node RPC Dashboard

Copyright (C) baumann.at 2026

A small PHP website for viewing Bitcoin Core node information and running selected read-only RPC methods.
The RPC request function is defined in `bitcoin_rpc.inc` and included by the dashboard.

## Requirements

- PHP 7.0 or newer
- PHP cURL extension
- Bitcoin Core with JSON-RPC enabled

## Configure and run

Copy `config-example.php` to `config.php`, then set your node RPC URL, username, and password in `config.php`. Keep real credentials private and do not commit `config.php`.

Then start the PHP development server:

```
php -S 127.0.0.1:8080
```

Open `http://127.0.0.1:8080`. The dashboard reads its RPC settings from `config.php`; do not share or commit real credentials.

Configure Bitcoin Core RPC credentials in `bitcoin.conf` using `rpcuser` and `rpcpassword`, then restart the node. Keep the RPC service bound to a trusted interface, and use HTTPS when exposing this dashboard beyond localhost. Do not expose the node's RPC port to the public internet.

If the Bitcoin RPC service is reachable from the public internet, place it behind a reverse proxy configured for HTTPS, and restrict direct access to the RPC port. HTTPS protects credentials and RPC traffic in transit; it does not replace access controls.

## Test the RPC library

Run the CLI smoke test to make a `getblockchaininfo` call using the settings in `config.php`:

```
php test_bitcoin_rpc.php
```

The test requires the configured node to be reachable and prints the chain and block height on success. It is restricted to CLI use.

## Available methods

- `getblockchaininfo`
- `getwalletinfo` (requires a wallet to be loaded)
- `getnetworkinfo`
- `getmininginfo`
- `getpeerinfo`
- `getmempoolinfo`
- `getblockcount`
- `getdifficulty`
- `getblockhash` (by height)
- `getblock` (by hash)

## License

MIT License

Copyright (C) baumann.at 2026

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
