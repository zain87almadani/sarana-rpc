<?php

namespace Sarana\Rpc;

use Illuminate\Support\Facades\Http;
use kornrunner\Ethereum\Transaction;
use kornrunner\Keccak;

class Saranarpc
{
    protected string $rpcUrl;
    protected string $rpcKey;
    protected int $chainId;
    protected string $smartcontract;

    public function __construct()
    {
        $this->rpcUrl = config('saranarpc.rpc_url');
        $this->rpcKey = config('saranarpc.rpc_key');
        $this->chainId = config('saranarpc.chain_id');
    }

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

    public function cekBalance(string $address)
    {
        $result = $this->request('eth_getBalance', [$address, 'latest']);
        
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

    public function cekTokenBalance(string $address, string $contract)
    {
        $data = '0x70a08231000000000000000000000000' . substr($address, 2);

        $result = $this->request('eth_call', [[
            'to' => $contract,
            'data' => $data,
        ], 'latest']);

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

    public function sendTransaction(string $sendFrom, string $sendTo, string $privateKey , int $gasLimit = 21000, int $gasPriceGwei = 5)
    {
        // Get nonce
        $nonceResponse = $this->request('eth_getTransactionCount', [$sendFrom, 'latest']);
        $nonceHex = $nonceResponse['result'] ?? '0x0';
        $nonce = hexdec($nonceHex);

        // Gas parameters
        $gasPrice = bcmul((string)$gasPriceGwei, bcpow('10', '9')); // 5 Gwei in wei

        // Value to send: 0.00001 ETH in wei
        $valueEth = '0.00001';
        $value = bcmul($valueEth, bcpow('10', '18'));

        // Create transaction array
        $txParams = [
            'nonce' => '0x' . dechex($nonce),
            'gasPrice' => '0x' . dechex($gasPrice),
            'gasLimit' => '0x' . dechex($gasLimit),
            'to' => $sendTo,
            'value' => '0x' . dechex($value),
            'data' => '0x',
            'chainId' => $this->chainId,
        ];

        // Create and sign transaction
        $transaction = new Transaction($txParams);
        $signedTx = '0x' . $transaction->sign($privateKey);

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

    public function sendTokenTransaction(string $sendFrom, string $sendTo, string $privateKey, string $smartcontract, string $amount, int $gasLimit = 21000, int $gasPriceGwei = 5)
    {
        // Get nonce
        $nonceResponse = $this->request('eth_getTransactionCount', [$sendFrom, 'latest']);
        $nonceHex = $nonceResponse['result'] ?? '0x0';
        $nonce = hexdec($nonceHex);
    
        $gasPrice = bcmul((string)$gasPriceGwei, bcpow('10', '9')); // 5 Gwei in wei

        // Encode transfer function call data
        $methodSignature = 'transfer(address,uint256)';
        $methodId = substr(Keccak::hash($methodSignature, 256), 0, 8);

        $recipient = str_pad(substr($sendTo, 2), 64, '0', STR_PAD_LEFT);

        // Assuming amount is in token's smallest unit (uint256)
        $amountHex = str_pad(gmp_strval($amount, 16), 64, '0', STR_PAD_LEFT);

        $data = '0x' . $methodId . $recipient . $amountHex;

        // Create transaction array
        $txParams = [
            'nonce' => '0x' . dechex($nonce),
            'gasPrice' => '0x' . dechex($gasPrice),
            'gasLimit' => '0x' . dechex($gasLimit),
            'to' => $smartcontract,
            'value' => '0x0',
            'data' => $data,
            'chainId' => $this->chainId,
        ];

        // Create and sign transaction
        $transaction = new Transaction($txParams);
        $signedTx = '0x' . $transaction->sign($privateKey);

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
}