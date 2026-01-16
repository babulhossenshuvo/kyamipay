<?php

namespace KPay\LaravelKPayment\Tests;

use PHPUnit\Framework\TestCase;
use KPay\LaravelKPayment\Services\KPayService;

class KPayServiceTest extends TestCase
{
    protected KPayService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new KPayService();
    }

    /**
     * Test that we can instantiate the service.
     */
    public function testServiceInstantiation(): void
    {
        $this->assertInstanceOf(KPayService::class, $this->service);
    }

    // Add more test cases based on your requirements
}
