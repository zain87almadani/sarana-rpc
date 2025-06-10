# Sarana RPC

Library Laravel untuk interaksi dengan node blockchain (BSC, Ethereum, dll) via RPC. Memudahkan pengembangan aplikasi yang membutuhkan komunikasi dengan jaringan blockchain tanpa harus mengelola node sendiri secara langsung.

## Instalasi

 jalankan perintah berikut untuk menginstall package:

```bash
composer require sarana/rpc
```

## Konfigurasi

Setelah instalasi, publish konfigurasi package dengan perintah:

```bash
php artisan vendor:publish --provider="Sarana\Blockchainrpc\SaranaRpcServiceProvider" --tag="saranarpc-config"
```

Kemudian, tambahkan konfigurasi koneksi RPC di file `.env`:

```
TATUM_RPC_URL=
TATUM_RPC_KEY=
CHAIN_ID=
```

<!-- API key bisa didapatkan dengan mendaftar di https://tatum.io -->

Sesuaikan `TATUM_RPC_URL`, `TATUM_RPC_KEY`, `CHAIN_ID` sesuai kebutuhan aplikasi Anda.

## Fitur

- Cek saldo native coin (BNB, ETH, dll) dengan `cekBalance`
- Cek saldo token ERC20/BEP20 dengan `cekTokenBalance`
- Mengirim transaksi blockchain dengan `sendTransaction`
- Mendapatkan detail transaksi dan statusnya
- Mendukung berbagai jaringan blockchain yang kompatibel dengan RPC Ethereum

## Contoh Penggunaan

### Cek Saldo Native Coin

```php
use Sarana\Rpc\SaranaRpc;

$sarana = new SaranaRpc();

$address = '0xYourWalletAddress';
$balance = $sarana->cekBalance($address);

echo "Saldo native coin: " . $balance . " wei";
```

### Cek Saldo Token ERC20/BEP20

```php
$tokenAddress = '0xTokenContractAddress';
$address = '0xYourWalletAddress';

$tokenBalance = $sarana->cekTokenBalance($address, $tokenAddress);

echo "Saldo token: " . $tokenBalance;
```

### Kirim Transaksi

```php
$from = '0xYourWalletAddress';
$privateKey = 'your-private-key';
$to = '0xRecipientAddress';
$value = '1000000000000000000'; // 1 ETH atau BNB dalam wei

$txHash = $sarana->sendTransaction($from, $privateKey, $to, $value);

echo "Transaksi dikirim dengan hash: " . $txHash;
```

## Format Respon

- `cekBalance($address)`  
  Mengembalikan saldo dalam satuan wei sebagai string.

- `cekTokenBalance($address, $tokenAddress)`  
  Mengembalikan saldo token dalam satuan terkecil token (biasanya wei) sebagai string.

- `sendTransaction($from, $privateKey, $to, $value)`  
  Mengembalikan hash transaksi (`string`) jika berhasil mengirim transaksi, atau `null` jika gagal.

---

Untuk informasi lebih lanjut dan dokumentasi lengkap, silakan lihat kode sumber dan contoh penggunaan di repository.