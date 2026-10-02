<?php

namespace Tests\Integration;

use App\Core\Database;
use App\Models\Vendor;
use Tests\TestCase;

class VendorDeletionRequestTest extends TestCase
{
    private int $vendorId;

    public function setUp(): void
    {
        $this->vendorId = Vendor::create('[TEST] Deletion Request', '0895550001', 'deletion-test@example.invalid');
    }

    public function tearDown(): void
    {
        Database::connection()->prepare('DELETE FROM vendors WHERE id = ?')->execute([$this->vendorId]);
    }

    public function testANewVendorHasNoDeletionRequest(): void
    {
        $this->assertNull(Vendor::find($this->vendorId)['deletion_requested_at']);
    }

    public function testRequestingDeletionFlagsTheVendorWithoutRemovingThem(): void
    {
        Vendor::requestDeletion($this->vendorId);

        $vendor = Vendor::find($this->vendorId);
        $this->assertNotNull($vendor, 'a deletion request must only flag the vendor, never delete them');
        $this->assertNotNull($vendor['deletion_requested_at']);
    }

    public function testDismissingARequestClearsTheFlagAndKeepsTheVendor(): void
    {
        Vendor::requestDeletion($this->vendorId);
        Vendor::clearDeletionRequest($this->vendorId);

        $vendor = Vendor::find($this->vendorId);
        $this->assertNotNull($vendor);
        $this->assertNull($vendor['deletion_requested_at']);
    }
}
