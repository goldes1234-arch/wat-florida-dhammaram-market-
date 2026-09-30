<?php

namespace Tests\Integration;

use App\Core\Database;
use App\Models\Vendor;
use Tests\TestCase;

class VendorSearchTest extends TestCase
{
    private int $vendorId;

    public function setUp(): void
    {
        $this->vendorId = Vendor::create('[TEST] Somchai Search', '0891234567', 'searchtest@example.invalid');
    }

    public function tearDown(): void
    {
        Database::connection()->prepare('DELETE FROM vendors WHERE id = ?')->execute([$this->vendorId]);
    }

    public function testSearchingByNamePhoneOrEmailEachFindTheVendor(): void
    {
        $this->assertTrue($this->anyResultHasId(Vendor::allWithBookingCounts('Somchai'), $this->vendorId));
        $this->assertTrue($this->anyResultHasId(Vendor::allWithBookingCounts('0891234567'), $this->vendorId));
        $this->assertTrue($this->anyResultHasId(Vendor::allWithBookingCounts('searchtest@example.invalid'), $this->vendorId));
    }

    public function testASearchWithNoMatchesReturnsAnEmptyArrayInsteadOfThrowing(): void
    {
        $results = Vendor::allWithBookingCounts('no-such-vendor-xyz');
        $this->assertFalse($this->anyResultHasId($results, $this->vendorId));
    }

    private function anyResultHasId(array $rows, int $id): bool
    {
        foreach ($rows as $row) {
            if ((int) $row['id'] === $id) {
                return true;
            }
        }
        return false;
    }
}
