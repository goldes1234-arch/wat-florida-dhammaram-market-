<?php

namespace Tests\Integration;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Models\AdminUser;
use Tests\TestCase;

class CheckinLinkTest extends TestCase
{
    private int $userId;

    public function setUp(): void
    {
        $this->userId = AdminUser::create([
            'name' => '[TEST] Checkin Link',
            'email' => 'test-checkin-link-' . bin2hex(random_bytes(4)) . '@example.invalid',
            'password_hash' => password_hash('irrelevant', PASSWORD_DEFAULT),
            'role' => 'checkin',
        ]);
        $this->resetAuthCache();
    }

    public function tearDown(): void
    {
        Session::forget('admin_id');
        $this->resetAuthCache();
        Database::connection()->prepare('DELETE FROM admin_users WHERE id = ?')->execute([$this->userId]);
    }

    public function testAGeneratedTokenResolvesBackToTheSameUser(): void
    {
        $token = bin2hex(random_bytes(32));
        AdminUser::setCheckinLinkToken($this->userId, hash('sha256', $token));

        $resolved = AdminUser::findByCheckinLinkTokenHash(hash('sha256', $token));

        $this->assertTrue($resolved !== null);
        $this->assertSame($this->userId, (int) $resolved['id']);
    }

    public function testRegeneratingInvalidatesThePreviousToken(): void
    {
        $first = bin2hex(random_bytes(32));
        AdminUser::setCheckinLinkToken($this->userId, hash('sha256', $first));

        $second = bin2hex(random_bytes(32));
        AdminUser::setCheckinLinkToken($this->userId, hash('sha256', $second));

        $this->assertTrue(AdminUser::findByCheckinLinkTokenHash(hash('sha256', $first)) === null);
        $this->assertTrue(AdminUser::findByCheckinLinkTokenHash(hash('sha256', $second)) !== null);
    }

    public function testRevokingClearsTheToken(): void
    {
        $token = bin2hex(random_bytes(32));
        AdminUser::setCheckinLinkToken($this->userId, hash('sha256', $token));
        AdminUser::clearCheckinLinkToken($this->userId);

        $this->assertTrue(AdminUser::findByCheckinLinkTokenHash(hash('sha256', $token)) === null);
    }

    public function testLoginAsEstablishesASessionLikeAPasswordLoginWould(): void
    {
        $user = AdminUser::find($this->userId);
        Auth::loginAs($user);
        $this->resetAuthCache();

        $this->assertSame($this->userId, Session::get('admin_id'));
        $this->assertTrue(Auth::isCheckinOnly());
    }

    private function resetAuthCache(): void
    {
        $ref = new \ReflectionClass(Auth::class);
        $cache = $ref->getProperty('userCache');
        $cache->setAccessible(true);
        $cache->setValue(null, null);
        $resolved = $ref->getProperty('resolved');
        $resolved->setAccessible(true);
        $resolved->setValue(null, false);
    }
}
