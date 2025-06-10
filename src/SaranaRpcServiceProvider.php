<?php

namespace Sarana\Blockchainrpc;

use Illuminate\Support\ServiceProvider;

class SaranaRpcServiceProvider extends ServiceProvider
{
    public function register()
    {
       
        $this->app->singleton(RpcClient::class, function () {
            return new RpcClient();
        });
    }

    public function boot()
    {
        $dist = __DIR__.'/../config/rpcconfig.php';
        if (function_exists('config_path')) {
            // Publishes config File.
            $this->publishes([
                $dist => config_path('rpcconfig.php'),
            ]);
        }
        $this->mergeConfigFrom($dist, 'rpcconfig');
        
    }
}