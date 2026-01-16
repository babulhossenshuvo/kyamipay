<?php

namespace KPay\LaravelKPayment\Commands;

use Illuminate\Console\Command;
use KPay\LaravelKPayment\Services\KPayService;

class TestConnectionCommand extends Command
{
    protected $signature = 'kpay:test';
    protected $description = 'Test KPay API connection';

    public function handle(KPayService $service)
    {
        $this->info('Testing KPay API connection...');

        try {
            $config = config('kpay');

            // Validate required configuration
            if (!$config['token']) {
                $this->error('❌ KPAY_TOKEN not configured');
                return 1;
            }

            if (!$config['hash']) {
                $this->error('❌ KPAY_HASH not configured');
                return 1;
            }

            if (!$config['entity']) {
                $this->error('❌ KPAY_ENTITY not configured');
                return 1;
            }

            $this->info('✓ Configuration validated');
            $this->line('');
            $this->table(['Setting', 'Value'], [
                ['Sandbox Mode', $config['sandbox_mode'] ? 'Yes' : 'No'],
                ['Base URL', $config['base_url']],
                ['Entity', $config['entity']],
                ['Currency', $config['currency']],
                ['Reference Expiry', $config['reference_expiry_hours'] . ' hours'],
            ]);

            $this->info('✓ KPay API connection is properly configured!');
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}
