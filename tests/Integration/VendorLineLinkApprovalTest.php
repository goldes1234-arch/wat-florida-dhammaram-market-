<?php

namespace Tests\Integration;

use App\Core\Database;
use App\Models\Vendor;
use Tests\TestCase;

class VendorLineLinkApprovalTest extends TestCase
{
    private int $vendorId;
    private int $otherId;

    public function setUp(): void
    {
        $this->vendorId = Vendor::create('[TEST] Line Link', '0895550002', null);
        $this->otherId = Vendor::create('[TEST] Line Link Other', '0895550003', null);
    }

    public function tearDown(): void
    {
        Database::connection()->prepare('DELETE FROM vendors WHERE id IN (?, ?)')->execute([$this->vendorId, $this->otherId]);
    }

    public function testRequestingALinkDoesNotLinkUntilApproved(): void
    {
        Vendor::requestLineLink($this->vendorId, 'U_test_pending_1');

        $vendor = Vendor::find($this->vendorId);
        $this->assertNull($vendor['line_user_id'], 'knowing a phone number must not be enough to link');
        $this->assertSame('U_test_pending_1', $vendor['line_pending_user_id']);
        $this->assertNull(Vendor::findByLineUserId('U_test_pending_1'));
    }

    public function testApprovingPromotesThePendingAccount(): void
    {
        Vendor::requestLineLink($this->vendorId, 'U_test_pending_2');

        $this->assertSame('U_test_pending_2', Vendor::approveLineLink($this->vendorId));

        $vendor = Vendor::find($this->vendorId);
        $this->assertSame('U_test_pending_2', $vendor['line_user_id']);
        $this->assertNull($vendor['line_pending_user_id']);
        $this->assertNull($vendor['line_link_requested_at']);
    }

    public function testApprovingWithNothingPendingDoesNothing(): void
    {
        $this->assertNull(Vendor::approveLineLink($this->vendorId));
        $this->assertNull(Vendor::find($this->vendorId)['line_user_id']);
    }

    public function testRejectingClearsTheRequestWithoutLinking(): void
    {
        Vendor::requestLineLink($this->vendorId, 'U_test_pending_3');
        Vendor::clearLineLinkRequest($this->vendorId);

        $vendor = Vendor::find($this->vendorId);
        $this->assertNull($vendor['line_user_id']);
        $this->assertNull($vendor['line_pending_user_id']);
    }

    public function testALinkedVendorKeepsTheirAccountWhileAReplacementIsPending(): void
    {
        Vendor::linkLine($this->vendorId, 'U_test_owner');
        Vendor::requestLineLink($this->vendorId, 'U_test_intruder');

        $vendor = Vendor::find($this->vendorId);
        $this->assertSame('U_test_owner', $vendor['line_user_id'], 'a stranger asking must not displace the real owner');
        $this->assertSame('U_test_intruder', $vendor['line_pending_user_id']);
    }

    public function testALineAccountHasOnlyOneOpenRequest(): void
    {
        Vendor::requestLineLink($this->vendorId, 'U_test_pending_4');
        Vendor::requestLineLink($this->otherId, 'U_test_pending_4');

        $this->assertNull(Vendor::find($this->vendorId)['line_pending_user_id']);
        $this->assertSame('U_test_pending_4', Vendor::find($this->otherId)['line_pending_user_id']);
    }

    public function testCannotApproveAnAccountAlreadyLinkedToAnotherVendor(): void
    {
        Vendor::linkLine($this->otherId, 'U_test_taken');
        Vendor::requestLineLink($this->vendorId, 'U_test_taken');

        $this->assertNull(Vendor::approveLineLink($this->vendorId));
        $this->assertNull(Vendor::find($this->vendorId)['line_user_id']);
        $this->assertSame('U_test_taken', Vendor::find($this->otherId)['line_user_id']);
    }
}
