<?php

namespace Sarana\Rpc;

use Illuminate\Support\Facades\Http;
use kornrunner\Ethereum\Transaction;
use kornrunner\Keccak;
use Web3p\EthereumUtil\Util;
use kornrunner\Ethereum\Address as Account;

class SaranaRpc
{
    protected string $rpcUrl;
    protected string $rpcKey;
    protected int $chainId;
    protected string $smartcontract;

    /**
     * SaranaRpc constructor.
     *
     * Initializes the RPC URL, API key, and chain ID from configuration.
     */
    public function __construct()
    {
        $this->rpcUrl = config('rpcconfig.rpc_url');
        $this->rpcKey = config('rpcconfig.rpc_key');
        $this->chainId = config('rpcconfig.chain_id');
    }

    /**
     * Generate a new Ethereum address.
     *
     * @return array Result message and address details (address, privateKey, publicKey)
     */
    public function createAddress()
      {


        try {

          $account = new Account();
          $address = '0x'.$account->get();
          $privateKey = $account->getPrivateKey();
          $publicKey = $account->getPublicKey();

            return array(
                'message' => 'success',
                'data' => array(
                        'address' => $address,
                        'privateKey' => $privateKey,
                        'publicKey' => $publicKey,
                    ),
            );


        } catch (\Exception $e) {

          return array(
            'message' => 'failed',
            'data' => 'failed create address',
          );

        }

      }

    /**
     * Import a wallet using a given private key.
     *
     * @param string $privateKey The private key to import
     * @return array Result message and wallet details (address, privateKey, publicKey)
     */
    public function ImportWallet($privateKey)
    {

            try {
              $account  = new Account($privateKey);
              $address  = $account->get();

              $data = array(
                'address' => '0x'.$address,
                'privateKey' => $privateKey,
                'publicKey' => 'null',
              );

              return array(
                'message' => 'success',
                'data' => $data,
              );


            } catch (\Exception $e) {
              return array(
                'message' => 'failed',
                'data' => 'invalid Private Key',
              );
            }

    }

    /**
     * Make a JSON-RPC request to the configured Ethereum node.
     *
     * @param string $method The RPC method name
     * @param array $params Parameters for the RPC method
     * @return array The decoded JSON response
     */
    protected function request(string $method, array $params = [])
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->rpcKey,
            'Content-Type' => 'application/json',
        ])->post($this->rpcUrl, [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => 1
        ]);

        return $response->json();
    }

    /**
     * Check the ETH balance of an address.
     *
     * @param string $address The Ethereum address to check
     * @return array Result message and balance in ETH
     */
    public function cekBalance(string $address)
    {
        $result = $this->request('eth_getBalance', [$address, 'latest']);
        
        if (isset($result['result'])) {
            $balance = hexdec($result['result']) / pow(10, 18);
            return [
                'message' => 'success',
                'data' => $balance,
            ];
        } else {
            $errorMessage = $result['error']['message'] ?? $result;
            return [
                'message' => 'failed',
                'data' => $errorMessage,
            ];
        }
    }

    /**
     * Check the token balance of an address for a given smart contract.
     *
     * @param string $address The Ethereum address to check
     * @param string $contract The token contract address
     * @return array Result message and token balance
     */
    public function cekTokenBalance(string $address, string $contract)
    {
        $data = '0x70a08231000000000000000000000000' . substr($address, 2);

        $result = $this->request('eth_call', [[
            'to' => $contract,
            'data' => $data,
        ], 'latest']);

        if (isset($result['result'])) {
            // Convert hex to decimal and then to ETH (divide by 1e18)
            $balance = hexdec($result['result']) / pow(10, 18);
            return [
                'message' => 'success',
                'data' => $balance,
            ];
        } else {
            $errorMessage = $result['error']['message'] ?? $result;
            return [
                'message' => 'failed',
                'data' => $errorMessage,
            ];
        }
    }

    /**
     * Send an ETH transaction from one address to another.
     *
     * @param string $sendFrom Sender address
     * @param string $sendTo Recipient address
     * @param string $amount Amount in ETH (as string)
     * @param string $privateKey Sender's private key
     * @param int $gasLimit Gas limit for the transaction (default 21000)
     * @param int $gasPriceGwei Gas price in Gwei (default 5)
     * @return array Result message and transaction hash or error
     */
    public function sendTransaction(string $sendFrom, string $sendTo, string $amount, string $privateKey , int $gasLimit = 21000, int $gasPriceGwei = 5)
    {
        if (!is_numeric($amount) || bccomp($amount, '0', 18) <= 0) {
            return [
                'message' => 'failed',
                'data' => 'Invalid token amount. Must be a positive number.',
            ];
        }

        // Get nonce
        $nonceResponse = $this->request('eth_getTransactionCount', [$sendFrom, 'pending']);
        $nonceHex = $nonceResponse['result'] ?? '0x0';
        $nonce = hexdec($nonceHex);

        // Gas parameters
        $gasPrice = bcmul((string)$gasPriceGwei, bcpow('10', '9')); // 5 Gwei in wei

        // Value to send: 0.00001 ETH in wei
        $valueEth = $amount;

        $tx = new Transaction(
            '0x' . dechex($nonce),
            '0x' . dechex($gasPrice),
            '0x' . dechex($gasLimit),
            $sendTo,
            '0x' . dechex((int)(bcmul($valueEth, bcpow('10', '18')))), // value
            '',
             $this->chainId
        );
        $signedTx = '0x' . $tx->getRaw($privateKey,  $this->chainId);
        
        $result = $this->request('eth_sendRawTransaction', [$signedTx]);

        if (isset($result['result'])) {
            return [
                'message' => 'success',
                'data' => $result['result'],
            ];
        } else {
            $errorMessage = $result['error']['message'] ?? $result;
            return [
                'message' => 'failed',
                'data' => $errorMessage,
            ];
        }
    }

    /**
     * Send an ERC20 token transaction.
     *
     * @param string $sendFrom Sender address
     * @param string $sendTo Recipient address
     * @param string $amount Amount of tokens (as string)
     * @param string $privateKey Sender's private key
     * @param string $smartcontract Token contract address
     * @param int $gasLimit Gas limit for the transaction (default 100000)
     * @param int $gasPriceGwei Gas price in Gwei (default 5)
     * @return array Result message and transaction hash or error
     */
    public function sendTokenTransaction(string $sendFrom, string $sendTo,string $amount, string $privateKey, string $smartcontract, int $gasLimit = 100000, int $gasPriceGwei = 5)
    {
        if (!is_numeric($amount) || bccomp($amount, '0', 18) <= 0) {
            return [
                'message' => 'failed',
                'data' => 'Invalid token amount. Must be a positive number.',
            ];
        }

        // Get nonce
        $nonceResponse = $this->request('eth_getTransactionCount', [$sendFrom, 'pending']);
        $nonceHex = $nonceResponse['result'] ?? '0x0';
        $nonce = hexdec($nonceHex);
    
        $gasPrice = bcmul((string)$gasPriceGwei, bcpow('10', '9')); // 5 Gwei in wei

        // Encode transfer function call data
        $methodSignature = 'transfer(address,uint256)';
        $methodId = substr(Keccak::hash($methodSignature, 256), 0, 8);

        $recipient = str_pad(substr($sendTo, 2), 64, '0', STR_PAD_LEFT);

        // Assuming amount is in token's smallest unit (uint256)
        $valueHex = bcmul($amount, bcpow('10', (string) 18));
        $amountHex = str_pad($this->bcdechex($valueHex), 64, '0', STR_PAD_LEFT);
        $data = '0x' . $methodId . $recipient . $amountHex;
        // Create and sign transaction
        $tx = new Transaction(
            '0x' . dechex($nonce),
            '0x' . dechex($gasPrice),
            '0x' . dechex($gasLimit),
             $smartcontract,
            '0x0', // token transfer doesn't send native ETH
            $data,
             $this->chainId
        );
        $signedTx = '0x' . $tx->getRaw($privateKey,  $this->chainId);

        $result = $this->request('eth_sendRawTransaction', [$signedTx]);

        if (isset($result['result'])) {
            return [
                'message' => 'success',
                'data' => $result['result'],
            ];
        } else {
            $errorMessage = $result['error']['message'] ?? $result;
            return [
                'message' => 'failed',
                'data' => $errorMessage,
            ];
        }
    }

    /**
     * Get all transactions in a block by block number (hex).
     *
     * @param string $blockNumberHex Hexadecimal string of the block number
     * @return array Result message and block data or error
     */
    public function getBlockTransaction(string $blockNumberHex)
    {
        $result = $this->request('eth_getBlockByNumber', [$blockNumberHex, true]);

        if (isset($result['result'])) {
            return [
                'message' => 'success',
                'data' => $result['result'],
            ];
        } else {
            $errorMessage = $result['error']['message'] ?? $result;
            return [
                'message' => 'failed',
                'data' => $errorMessage,
            ];
        }
    }

    /**
     * Get smart contract transactions in a block, parse and filter token transfers.
     *
     * @param string $blockNumberHex Hex string of the block number (e.g., '0x1a4b')
     * @return array
     */
    /**
     * Get smart contract transactions in a block, parse and filter token transfers.
     *
     * @param string $blockNumberHex Hex string of the block number (e.g., '0x1a4b')
     * @param string $smartcontract Smart contract address to filter transactions
     * @return array Result message and filtered transactions or error
     */
    public function getSmartContractTransaction(string $blockNumberHex, string $smartcontract)
    {
        $transactions = [];
        $totalSmartContractTx = 0;
        $block = $this->getBlockTransaction($blockNumberHex);
        if (!isset($block['message']) || $block['message'] !== 'success' || !isset($block['data']['transactions'])) {
            return [
                'message' => 'failed',
                'data' => 'Block not found or invalid response',
            ];
        }
        foreach ($block['data']['transactions'] as $tx) {
            // Only process tx to the smart contract address
            if (strtolower($tx['to'] ?? '') !== strtolower($smartcontract)) {
                continue;
            }
            // Parse input data for transfer method
            $input = $tx['input'] ?? '';
            // ERC20 transfer method id: a9059cbb
            if (strpos($input, '0xa9059cbb') === 0 && strlen($input) === (10 + 64 + 64)) {
                $recipientHex = '0x' . substr($input, 10, 64);
                $recipient = '0x' . ltrim(substr($input, 34, 40), '0'); // last 40 chars of 64
                $amountHex = '0x' . substr($input, 74, 64);
                $amount = hexdec($amountHex);
                $transactions[] = [
                    'hash' => $tx['hash'],
                    'from' => $tx['from'],
                    'to' => $tx['to'],
                    'recipient' => $recipient,
                    'amount' => $amount,
                    'blockNumber' => $tx['blockNumber'],
                ];
                $totalSmartContractTx++;
            }
        }
        if ($totalSmartContractTx > 0) {
            return [
                'message' => 'success',
                'data' => [
                    'totalSmartContractTx' => $totalSmartContractTx,
                    'transactions' => $transactions,
                ],
            ];
        } else {
            return [
                'message' => 'failed',
                'data' => 'Smart contract transactions not found',
            ];
        }
    }

    /**
     * Convert a decimal number (as string) to hexadecimal (as string).
     *
     * @param string $dec Decimal number as string
     * @return string Hexadecimal representation
     */
    public function bcdechex(string $dec): string
    {
        $hex = '';
        do {
            $last = bcmod($dec, '16');
            $dec = bcdiv(bcsub($dec, $last), '16');
            $hex = dechex((int)$last) . $hex;
        } while ($dec > 0);

        return $hex;
    }
    /**
     * Estimate gas for a transaction.
     *
     * @param array $txParams Transaction parameters (from, to, value, data, etc.)
     * @return int Estimated gas as integer
     */
    public function estimateGas(array $txParams): int
    {
        $response = $this->request('eth_estimateGas', [$txParams]);
        $resultHex = $response['result'] ?? '0x0';
        return hexdec($resultHex);
    }

    /**
     * Get the latest block number.
     *
     * @return array Result message and block number or error
     */
    public function getBlock(): array
    {
        try {
            $result = $this->request('eth_blockNumber');
            if (isset($result['result'])) {
                $blockNumber = hexdec($result['result']);
                return [
                    'message' => 'success',
                    'data' => $blockNumber,
                ];
            } else {
                $errorMessage = $result['error']['message'] ?? $result;
                return [
                    'message' => 'failed',
                    'data' => $errorMessage,
                ];
            }
        } catch (\Exception $e) {
            return [
                'message' => 'failed',
                'data' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get transaction details by transaction hash.
     *
     * @param string $txHash Transaction hash
     * @return array Result message and transaction details or error
     */
    public function getTransactionByHash(string $txHash)
    {
        $result = $this->request('eth_getTransactionByHash', [$txHash]);
        if (!isset($result['result'])) {
            $errorMessage = $result['error']['message'] ?? $result;
            return [
                'message' => 'failed',
                'data' => $errorMessage,
            ];
        }

        $tx = $result['result'];
        // Get current block number
        $blockResp = $this->getBlock();
        $currentBlock = null;
        if (isset($blockResp['message']) && $blockResp['message'] === 'success') {
            $currentBlock = $blockResp['data'];
        }

        $hash = $tx['hash'] ?? null;
        $from = $tx['from'] ?? null;
        $to = $tx['to'] ?? null;
        $value = $tx['value'] ?? '0x0';
        $blockNumberHex = $tx['blockNumber'] ?? null;
        $blockNumber = $blockNumberHex ? hexdec($blockNumberHex) : null;
        $confirmation = null;
        if ($currentBlock !== null && $blockNumber !== null) {
            $confirmation = $currentBlock - $blockNumber;
        }
        $input = $tx['input'] ?? '';
        $transaction_type = 'coin';
        $token_amount = null;
        $smartcontract = null;
        // If 'to' is a contract address, and input starts with ERC20 transfer methodId
        if (!empty($input) && strlen($input) >= 10 && isset($to)) {
            // ERC20 transfer methodId: 0xa9059cbb
            if (strpos($input, '0xa9059cbb') === 0 && strlen($input) >= (10 + 64 + 64)) {
                $transaction_type = 'token';
                // Parse token amount from input (last 64 hex chars after methodId and address)
                $amountHex = substr($input, 74, 64);
                // Remove leading zeros
                $amountHex = ltrim($amountHex, '0');
                $token_amount = $amountHex !== '' ? (string)hexdec($amountHex) : '0';
                $smartcontract = $to;
            }
        }
        // ETH value in decimal
        $amount = is_numeric($value) ? $value : (string)hexdec($value);
        // For ETH, convert wei to ETH
        if ($transaction_type === 'coin') {
            $amount = bcdiv($amount, bcpow('10', '18'), 18);
        } else {
            // For token, amount is 0, token_amount is actual
            $amount = '0';
        }
        return [
            'message' => 'success',
            'data' => [
                'hash' => $hash,
                'from' => $from,
                'to' => $to,
                'amount' => $amount,
                'confirmation' => $confirmation,
                'transaction_type' => $transaction_type,
                'token_amount' => $token_amount,
                'smartcontract' => $smartcontract,
            ],
        ];
    }

     /**
     * Get the transaction receipt by transaction hash.
     *
     * @param string $txHash
     * @return array
     */
    public function getTransactionReceipt(string $txHash)
    {
        $result = $this->request('eth_getTransactionReceipt', [$txHash]);
        if (isset($result['result'])) {
            return [
                'message' => 'success',
                'data' => $result['result'],
            ];
        } else {
            $errorMessage = $result['error']['message'] ?? $result;
            return [
                'message' => 'failed',
                'data' => $errorMessage,
            ];
        }
    }


    /**
     * Encrypt data using a password and AES-256-GCM.
     *
     * @param string $data Data to encrypt
     * @param string $password Password for encryption
     * @return string Encrypted data (base64 encoded)
     */
    public function encryptkey(string $data, string $password)
    {
        $method = 'aes-256-gcm';
        $salt = random_bytes(16);
        $nonce = random_bytes(openssl_cipher_iv_length($method));
        $key = sodium_crypto_pwhash(
            32,
            $password,
            $salt,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );
        $ciphertext = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $nonce, $tag);
        $encryptedData = base64_encode($salt . $nonce . $tag . $ciphertext);
        return $encryptedData;
    }

    /**
     * Decrypt data encrypted with encryptkey().
     *
     * @param string $encryptedData Encrypted data (base64 encoded)
     * @param string $password Password for decryption
     * @return string|false Decrypted data or false on failure
     */
    public function decryptkey(string $encryptedData, string $password)
    {
        $method = 'aes-256-gcm';
        $decodedData = base64_decode($encryptedData);
        $salt = substr($decodedData, 0, 16);
        $nonce = substr($decodedData, 16, openssl_cipher_iv_length($method));
        $tag = substr($decodedData, 16 + openssl_cipher_iv_length($method), 16);
        $ciphertext = substr($decodedData, 16 + openssl_cipher_iv_length($method) + 16);
        $key = sodium_crypto_pwhash(
            32,
            $password,
            $salt,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );
        $decryptedData = openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $nonce, $tag);
        return $decryptedData;
    }

    /**
     * Format a balance value with given precision, using comma as decimal separator and dot as thousands separator.
     *
     * @param float|int|string $balance The balance value
     * @param int $precission Number of decimal places
     * @return string Formatted balance string
     */
    public function formatBalance($balance,int $precission)
    {
        return number_format($balance, $precission, ',', '.');
    }


}
   