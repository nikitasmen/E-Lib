<?php

namespace Tests\Integration;

use App\Models\Loans;
use MongoDB\BSON\UTCDateTime;
use Tests\Support\IntegrationTestCase;

class LoansTest extends IntegrationTestCase
{
    private const BOOK = 'bbbbbbbbbbbbbbbbbbbbbbbb';
    private const ALICE = 'aaaaaaaaaaaaaaaaaaaaaaa1';
    private const BOB = 'aaaaaaaaaaaaaaaaaaaaaaa2';
    private const CAROL = 'aaaaaaaaaaaaaaaaaaaaaaa3';

    private Loans $loans;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loans = new Loans($this->db);
    }

    /** Move a slot's free_at into the past, as if its loan or hold had run out. */
    private function expire(string $slotId): void
    {
        $this->mongo->selectCollection('Loans')->updateOne(
            ['_id' => $slotId],
            ['$set' => ['free_at' => new UTCDateTime((time() - 60) * 1000)]]
        );
    }

    public function testCopyLimitIsEnforcedBySlotIds(): void
    {
        $this->assertSame(self::BOOK . ':0', $this->loans->borrow(self::BOOK, self::ALICE, 2, 14)['_id']);
        $this->assertSame(self::BOOK . ':1', $this->loans->borrow(self::BOOK, self::BOB, 2, 14)['_id']);
        $this->assertNull($this->loans->borrow(self::BOOK, self::CAROL, 2, 14));
        $this->assertSame(2, $this->loans->activeCount(self::BOOK));
        $this->assertSame(2, $this->mongo->selectCollection('LoanHistory')->countDocuments());
    }

    public function testBorrowingAgainReturnsTheSameLoan(): void
    {
        $first = $this->loans->borrow(self::BOOK, self::ALICE, 2, 14);
        $again = $this->loans->borrow(self::BOOK, self::ALICE, 2, 14);

        $this->assertSame($first['_id'], $again['_id']);
        $this->assertSame(1, $this->loans->activeCount(self::BOOK));
    }

    public function testAnExpiredLoanFreesItsCopyForTheNextReader(): void
    {
        $this->loans->borrow(self::BOOK, self::ALICE, 1, 14);
        $this->expire(self::BOOK . ':0');

        $this->assertNull($this->loans->activeFor(self::BOOK, self::ALICE));
        $slot = $this->loans->borrow(self::BOOK, self::BOB, 1, 14);
        $this->assertSame(self::BOOK . ':0', $slot['_id']);
        $this->assertSame(self::BOB, $slot['user_id']);
        // The reused slot points at a new history record; Alice's stays.
        $this->assertSame(2, $this->mongo->selectCollection('LoanHistory')->countDocuments());
    }

    public function testReturnFreesTheCopyAndClosesTheHistory(): void
    {
        $slot = $this->loans->borrow(self::BOOK, self::ALICE, 1, 14);

        $history = $this->loans->returnBook(self::BOOK, self::ALICE);

        $this->assertNotNull($history['returned_at']);
        $this->assertNull($history['cancelled_at']);
        $this->assertSame($slot['history_id'], (string) $history['_id']);
        $this->assertNotNull($this->loans->borrow(self::BOOK, self::BOB, 1, 14));
    }

    public function testCancellingAReservationIsNotAReturn(): void
    {
        $this->loans->reserve(self::BOOK, self::ALICE, 1);

        $history = $this->loans->returnBook(self::BOOK, self::ALICE);

        $this->assertNotNull($history['cancelled_at']);
        $this->assertNull($history['returned_at']);
    }

    public function testBorrowStartsTheLoanOnACopyHeldForTheReader(): void
    {
        $hold = $this->loans->reserve(self::BOOK, self::ALICE, 1, 'digital');
        $this->assertNull($hold['due_at']);
        $this->assertNull($this->loans->borrow(self::BOOK, self::BOB, 1, 14), 'the held copy is not free');

        $loan = $this->loans->borrow(self::BOOK, self::ALICE, 1, 14);

        $this->assertSame($hold['_id'], $loan['_id']);
        $this->assertNotNull($loan['due_at']);
        $this->assertNotNull($this->loans->findHistory($hold['history_id'])['borrowed_at']);
    }

    public function testAnUncollectedReservationFreesItself(): void
    {
        $this->loans->reserve(self::BOOK, self::ALICE, 1);
        $this->expire(self::BOOK . ':0');

        $this->assertNotNull($this->loans->reserve(self::BOOK, self::BOB, 1));
    }

    public function testCheckOutTurnsAReservationIntoALoanThatNeverFreesItself(): void
    {
        $this->loans->reserve(self::BOOK, self::ALICE, 1);

        $history = $this->loans->checkOut(self::BOOK, self::ALICE, 1, 14);

        $this->assertNotNull($history['borrowed_at']);
        $this->assertNull($this->loans->checkOut(self::BOOK, self::ALICE, 1, 14), 'already out');
        // Past its due date a physical loan is overdue, not free.
        $this->mongo->selectCollection('Loans')->updateOne(
            ['_id' => self::BOOK . ':0'],
            ['$set' => ['due_at' => new UTCDateTime((time() - 86400) * 1000)]]
        );
        $this->assertNull($this->loans->checkOut(self::BOOK, self::BOB, 1, 14));
        $this->assertNotNull($this->loans->activeFor(self::BOOK, self::ALICE));
    }

    public function testWalkInCheckOutTakesAFreeCopy(): void
    {
        $history = $this->loans->checkOut(self::BOOK, self::ALICE, 1, 14);

        $this->assertSame('physical', $history['kind']);
        $this->assertNull($history['pickup_by']);
    }

    public function testRenewStopsAfterTheLimit(): void
    {
        $this->loans->borrow(self::BOOK, self::ALICE, 1, 14);

        for ($i = 1; $i <= Loans::MAX_RENEWALS; $i++) {
            $this->assertSame($i, $this->loans->renew(self::BOOK, self::ALICE, 14, false)['renewals']);
        }
        $this->assertIsString($this->loans->renew(self::BOOK, self::ALICE, 14, false));
    }

    public function testOverdueLoansRenewOnlyAtTheDesk(): void
    {
        $this->loans->checkOut(self::BOOK, self::ALICE, 1, 14);
        $this->mongo->selectCollection('Loans')->updateOne(
            ['_id' => self::BOOK . ':0'],
            ['$set' => ['due_at' => new UTCDateTime((time() - 86400) * 1000)]]
        );

        $this->assertIsString($this->loans->renew(self::BOOK, self::ALICE, 14, false));
        $renewed = $this->loans->renew(self::BOOK, self::ALICE, 14, true);
        $this->assertGreaterThan(time() * 1000, (int) (string) $renewed['due_at']);
    }

    public function testWaitlistIsFirstComeFirstServedAndJoiningTwiceIsANoop(): void
    {
        $this->loans->joinWaitlist(self::BOOK, self::BOB);
        usleep(2000); // joined_at has millisecond precision
        $this->loans->joinWaitlist(self::BOOK, self::ALICE);
        $this->loans->joinWaitlist(self::BOOK, self::BOB);

        $this->assertSame([self::BOB, self::ALICE], array_column($this->loans->waitlist(self::BOOK), 'user_id'));
        $this->assertSame([self::BOOK], $this->loans->waitedForBookIds());

        $this->loans->leaveWaitlist(self::BOOK, self::BOB);
        $this->assertSame([self::ALICE], array_column($this->loans->waitlist(self::BOOK), 'user_id'));
    }

    public function testDueSoonAndOverdueNoticesGoOutOnce(): void
    {
        $this->loans->checkOut(self::BOOK, self::ALICE, 2, 1);
        $late = $this->loans->checkOut(self::BOOK, self::BOB, 2, 14);
        $this->mongo->selectCollection('LoanHistory')->updateOne(
            ['_id' => $late['_id']],
            ['$set' => ['due_at' => new UTCDateTime((time() - 86400) * 1000)]]
        );

        $dueSoon = $this->loans->dueSoonUnnotified(2);
        $overdue = $this->loans->overdueUnnotified();
        $this->assertSame([self::ALICE], array_column($dueSoon, 'user_id'));
        $this->assertSame([self::BOB], array_column($overdue, 'user_id'));

        $this->loans->markNotified((string) $dueSoon[0]['_id'], 'due_soon_notified_at');
        $this->loans->markNotified((string) $overdue[0]['_id'], 'overdue_notified_at');
        $this->assertSame([], $this->loans->dueSoonUnnotified(2));
        $this->assertSame([], $this->loans->overdueUnnotified());
    }
}
