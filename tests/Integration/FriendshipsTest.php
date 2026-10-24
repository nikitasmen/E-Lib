<?php

namespace Tests\Integration;

use App\Models\Friendships;
use Tests\Support\IntegrationTestCase;

class FriendshipsTest extends IntegrationTestCase
{
    private const A = 'aaaaaaaaaaaaaaaaaaaaaaaa';
    private const B = 'bbbbbbbbbbbbbbbbbbbbbbbb';

    private Friendships $friendships;

    protected function setUp(): void
    {
        parent::setUp();
        $this->friendships = new Friendships($this->db);
    }

    public function testOnlyTheAskedUserCanAccept(): void
    {
        $this->assertSame('requested', $this->friendships->request(self::A, self::B));

        $this->assertFalse($this->friendships->accept(self::A, self::B));
        $this->assertFalse($this->friendships->areFriends(self::A, self::B));
        $this->assertTrue($this->friendships->accept(self::B, self::A));
        $this->assertTrue($this->friendships->areFriends(self::B, self::A));
    }

    public function testCrossedRequestAcceptsTheirs(): void
    {
        $this->friendships->request(self::A, self::B);

        $this->assertSame('accepted', $this->friendships->request(self::B, self::A));
        $this->assertTrue($this->friendships->areFriends(self::A, self::B));
        $this->assertCount(1, $this->friendships->forUser(self::A));
    }

    public function testRepeatedOwnRequestChangesNothing(): void
    {
        $this->friendships->request(self::A, self::B);

        $this->assertSame('exists', $this->friendships->request(self::A, self::B));
        $this->assertFalse($this->friendships->areFriends(self::A, self::B));
    }

    public function testRemoveDeletesThePairFromEitherSide(): void
    {
        $this->friendships->request(self::A, self::B);
        $this->friendships->accept(self::B, self::A);

        $this->assertTrue($this->friendships->remove(self::B, self::A));
        $this->assertSame([], $this->friendships->forUser(self::A));
        $this->assertSame('requested', $this->friendships->request(self::B, self::A));
    }
}
