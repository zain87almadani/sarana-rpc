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
$gasLimit = "default (2100)";
$gasPriceGwei = "default (5)";

$txHash = $sarana->sendTransaction($sendFrom,$sendTo,$privateKey,$amount,$gasLimit,$gasPriceGwei);

echo "Transaksi dikirim dengan hash: " . $txHash;
```

### Kirim Token ERC20/BEP20

```php
$smartcontract = '0xYourWalletAddress';
$from = '0xYourWalletAddress';
$privateKey = 'your-private-key';
$to = '0xRecipientAddress';
$amount = '10'; // jumlah token dalam satuan normal (misalnya 10 token)
$gasLimit = "default (2100)";
$gasPriceGwei = "default (5)";

$txHash = $sarana->sendTokenTransaction($sendFrom,$sendTo,$privateKey,$smartcontract,$amount,$gasLimit,$gasPriceGwei);

echo "Token berhasil dikirim dengan hash: " . $txHash;
```

## Format Respon

- `cekBalance($address)`  
  Mengembalikan saldo dalam satuan wei sebagai string.

- `cekTokenBalance($address, $tokenAddress)`  
  Mengembalikan saldo token dalam satuan terkecil token (biasanya wei) sebagai string.

- `sendTransaction($from, $privateKey, $to, $value)`  
  Mengembalikan hash transaksi (`string`) jika berhasil mengirim transaksi, atau `null` jika gagal.

- `sendTokenTransaction($from, $privateKey, $to, $amount)`  
  Mengembalikan hash transaksi token sebagai `string` jika berhasil, atau pesan kesalahan jika gagal.

---

Untuk informasi lebih lanjut dan dokumentasi lengkap, silakan lihat kode sumber dan contoh penggunaan di repository.
## Daftar Fungsi Tersedia

Berikut adalah fungsi-fungsi publik yang tersedia pada kelas `SaranaRpc` beserta deskripsi dan parameternya:

### cekBalance
Mengecek saldo native coin (seperti ETH, BNB) dari sebuah alamat wallet.

**Signature:**
```php
public function cekBalance(string $address): string
```
**Parameter:**
- `address` (`string`): Alamat wallet yang akan dicek saldonya.

**Return:**  
Saldo dalam satuan wei sebagai string.

---

### cekTokenBalance
Mengecek saldo token ERC20/BEP20 dari sebuah alamat wallet.

**Signature:**
```php
public function cekTokenBalance(string $address, string $tokenAddress): string
```
**Parameter:**
- `address` (`string`): Alamat wallet yang akan dicek saldonya.
- `tokenAddress` (`string`): Alamat kontrak token ERC20/BEP20.

**Return:**  
Saldo token dalam satuan terkecil token (biasanya wei) sebagai string.

---

### sendTransaction
Mengirim transaksi native coin dari satu alamat ke alamat lain.

**Signature:**
```php
public function sendTransaction(string $from, string $to, string $privateKey, string $value, $gasLimit = null, $gasPriceGwei = null): ?string
```
**Parameter:**
- `from` (`string`): Alamat wallet pengirim.
- `to` (`string`): Alamat tujuan.
- `privateKey` (`string`): Private key dari wallet pengirim.
- `value` (`string`): Jumlah native coin yang akan dikirim (dalam satuan wei).
- `gasLimit` (`int|string|null`, opsional): Batas gas untuk transaksi (default: 21000 jika tidak diisi).
- `gasPriceGwei` (`int|string|null`, opsional): Harga gas dalam satuan Gwei (default: 5 jika tidak diisi).

**Return:**  
Hash transaksi (`string`) jika berhasil, atau `null` jika gagal.

---

### sendTokenTransaction
Mengirim token ERC20/BEP20 dari satu alamat ke alamat lain.

**Signature:**
```php
public function sendTokenTransaction(string $from, string $to, string $privateKey, string $contractAddress, string $amount, $gasLimit = null, $gasPriceGwei = null): string
```
**Parameter:**
- `from` (`string`): Alamat wallet pengirim.
- `to` (`string`): Alamat tujuan.
- `privateKey` (`string`): Private key dari wallet pengirim.
- `contractAddress` (`string`): Alamat kontrak token ERC20/BEP20.
- `amount` (`string`): Jumlah token yang akan dikirim dalam satuan normal (bukan wei).
- `gasLimit` (`int|string|null`, opsional): Batas gas untuk transaksi (default: 21000 jika tidak diisi).
- `gasPriceGwei` (`int|string|null`, opsional): Harga gas dalam satuan Gwei (default: 5 jika tidak diisi).

**Return:**  
Hash transaksi token sebagai `string` jika berhasil, atau pesan kesalahan jika gagal.

---

### getBlock
Mendapatkan block height (ketinggian blok) terbaru dari blockchain.

**Signature:**
```php
public function getBlock(): array
```
**Return:**  
Array dengan format `['message' => 'success', 'data' => (int) blockNumber]` atau `['message' => 'failed', 'data' => '...']`

---

### getTransactionByHash
Mengambil informasi detail transaksi berdasarkan hash.

**Signature:**
```php
public function getTransactionByHash(string $txHash): array
```
**Parameter:**
- `txHash` (`string`): Hash transaksi yang ingin dicek.

**Return:**  
Array terstruktur dengan informasi seperti:
- `hash`
- `from`
- `to`
- `amount`
- `confirmation`
- `transaction_type` (`coin` atau `token`)
- `token_amount`
- `smartcontract`

---

### getTransactionReceipt
Mengambil receipt dari transaksi untuk mengecek status berhasil/gagal.

**Signature:**
```php
public function getTransactionReceipt(string $txHash): array
```
**Parameter:**
- `txHash` (`string`): Hash transaksi yang ingin dicek.

**Return:**  
Detail receipt dalam format array Laravel, atau error jika gagal.

---

### createAddress
Membuat wallet Ethereum/BSC baru lengkap dengan public address dan private key.

**Signature:**
```php
public function createAddress(): array
```

**Return:**  
Array dengan struktur:
- `address` (`string`): Alamat publik wallet
- `privateKey` (`string`): Private key dalam format heksadesimal

---

### importAccount
Mengimpor akun wallet dari private key untuk mendapatkan public address-nya.

**Signature:**
```php
public function importAccount(string $privateKey): array
```

**Parameter:**
- `privateKey` (`string`): Private key dari wallet yang ingin diimpor

**Return:**  
Array dengan struktur:
- `address` (`string`): Alamat publik wallet
- `privateKey` (`string`): Private key yang sama