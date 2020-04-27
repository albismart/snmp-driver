<?php

namespace Albismart;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class SnmpServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/snmp.php' => config_path('snmp.php')
        ], 'snmp-assets');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/snmp.php', 'snmp'
        );

        Connection::useAliases(Config::get('snmp.aliases'));
    }
}
