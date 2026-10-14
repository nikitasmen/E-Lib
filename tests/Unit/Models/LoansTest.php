<?php

namespace Tests\Unit\Models;

use App\Database\DatabaseInterface;
use App\Models\Loans;
use MongoDB\Driver\Exception\CommandException;
use Tests\Support\ModelTestCase;

class LoansTest extends ModelTestCase
{
    private const BOOK = 'bbbbbbbbbbbbbbbbbbbbbbbb';
    private const USER = 'aaaaaaaaaaaaaaaaaaaaaaaa';

    private function makeLoans(DatabaseInterface $db): Loans
    {
        /** @var Loans $loans */
        $loans = $this->makeModel(Loans::class, $db);
        return $loans;
    }

    public function testOtherServerErrorsAreNotSwallowed(): void
    {
        $db = $this->createMock(DatabaseInterface::class);
        $db->method('findOne')->willReturn(null);
        $db->method('findOneAndUpdate')->willThrowException(new CommandException('not primary', 10107));

        $this->expectException(CommandException::class);
        $this->makeLoans($db)->borrow(self::BOOK, self::USER, 2, 14);
    }
}
