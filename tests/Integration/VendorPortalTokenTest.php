<?php

namespace Tests\Integration;

use App\Core\Database;
use App\Models\Vendor;
use Tests\TestCase;

class VendorPortalTokenTest extends TestCase
{
    private int $vendorId;

    public function setUp(): void
    {
        $this->vendorId = Vendor::create('[TEST] Portal Vendor', '0899990077', null);
    }

    public function tearDown(): void
    {
        Database::connection()->prepare('DELETE FROM vendors WHERE id = ?')->execute([$this->vendorId]);
    }

    public function testAValidUnexpiredTokenResolvesToTheVendor(): void
    {
        $token = 'test-token-valid';
        Vendor::setPortalToken($this->vendorId, hash('sha256', $token), date('Y-m-d H:i:s', time() + 900));

        $found = Vendor::findByValidPortalTokenHash(hash('sha256', $token));

        $this->assertNotNull($found);
        $this->assertSame($this->vendorId, (int) $found['id']);
    }

    public function testAnExpiredTokenDoesNotResolve(): void
    {
        $token = 'test-token-expired';
        Vendor::setPortalToken($this->vendorId, hash('sha256', $token), date('Y-m-d H:i:s', time() - 60));

        $found = Vendor::findByValidPortalTokenHash(hash('sha256', $token));

        $this->assertNull($found);
    }

    public function testAWrongTokenDoesNotResolve(): void
    {
        Vendor::setPortalToken($this->vendorId, hash('sha256', 'test-token-real'), date('Y-m-d H:i:s', time() + 900));

        $found = Vendor::findByValidPortalTokenHash(hash('sha256', 'test-token-guessed'));

        $this->assertNull($found);
    }
}
