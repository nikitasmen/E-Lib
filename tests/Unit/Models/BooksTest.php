<?php

namespace Tests\Unit\Models;

use App\Database\DatabaseInterface;
use App\Models\Books;
use InvalidArgumentException;
use Tests\Support\ModelTestCase;

class BooksTest extends ModelTestCase
{
    private function makeBooks(?DatabaseInterface $db = null): Books
    {
        /** @var Books $books */
        $books = $this->makeModel(Books::class, $db ?? $this->createMock(DatabaseInterface::class));
        return $books;
    }

    public function testValidateRequiresTitleButNotAuthor(): void
    {
        $errors = $this->makeBooks()->validate([]);

        $this->assertArrayHasKey('title', $errors);
        $this->assertArrayNotHasKey('author', $errors);
    }

    public function testValidatePassesWithTitleOnly(): void
    {
        $errors = $this->makeBooks()->validate(['title' => 'Meditations']);

        $this->assertSame([], $errors);
    }

    public function testValidatePassesWithTitleAndAuthor(): void
    {
        $errors = $this->makeBooks()->validate(['title' => 'Meditations', 'author' => 'Marcus Aurelius']);

        $this->assertSame([], $errors);
    }

    public function testValidateRejectsYearOutOfRange(): void
    {
        $errors = $this->makeBooks()->validate([
            'title' => 'Title',
            'author' => 'Author',
            'year' => 500,
        ]);

        $this->assertArrayHasKey('year', $errors);
    }

    public function testValidateAcceptsCurrentYear(): void
    {
        $errors = $this->makeBooks()->validate([
            'title' => 'Title',
            'author' => 'Author',
            'year' => (int) date('Y'),
        ]);

        $this->assertArrayNotHasKey('year', $errors);
    }

    public function testValidateRejectsMalformedIsbn(): void
    {
        $errors = $this->makeBooks()->validate([
            'title' => 'Title',
            'author' => 'Author',
            'isbn' => 'not-an-isbn',
        ]);

        $this->assertArrayHasKey('isbn', $errors);
    }

    public function testValidateAccepts13DigitIsbn(): void
    {
        $errors = $this->makeBooks()->validate([
            'title' => 'Title',
            'author' => 'Author',
            'isbn' => '978-3-16-148410-0',
        ]);

        $this->assertArrayNotHasKey('isbn', $errors);
    }

    public function testGetBookDetailsRejectsMalformedId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeBooks()->getBookDetails('not-a-valid-object-id');
    }

    public function testAddBookThrowsWhenValidationFails(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeBooks()->addBook(['author' => 'Missing a title']);
    }

    public function testAddBookInsertsValidatedData(): void
    {
        $db = $this->createMock(DatabaseInterface::class);
        $db->expects($this->once())
            ->method('insert')
            ->with('Books', $this->callback(fn ($data) => $data['title'] === 'Title' && $data['author'] === 'Author'))
            ->willReturn(['insertedId' => 'abc123']);

        $result = $this->makeBooks($db)->addBook(['title' => 'Title', 'author' => 'Author']);

        $this->assertSame(['insertedId' => 'abc123'], $result);
    }

    public function testAddBookInsertsWithoutAuthor(): void
    {
        $db = $this->createMock(DatabaseInterface::class);
        $db->expects($this->once())
            ->method('insert')
            ->with('Books', $this->callback(fn ($data) => $data['title'] === 'Title' && !isset($data['author'])))
            ->willReturn(['insertedId' => 'abc123']);

        $result = $this->makeBooks($db)->addBook(['title' => 'Title']);

        $this->assertSame(['insertedId' => 'abc123'], $result);
    }

    public function testGetAllBooksDelegatesToDatabase(): void
    {
        $expected = [['title' => 'A'], ['title' => 'B']];
        $db = $this->createMock(DatabaseInterface::class);
        $db->expects($this->once())
            ->method('find')
            ->with('Books', [], [])
            ->willReturn($expected);

        $this->assertSame($expected, $this->makeBooks($db)->getAllBooks());
    }

    public function testGetPublicBooksFiltersByStatus(): void
    {
        $db = $this->createMock(DatabaseInterface::class);
        $db->expects($this->once())
            ->method('find')
            ->with('Books', ['status' => 'public'], [])
            ->willReturn([]);

        $this->makeBooks($db)->getPublicBooks();
    }

    public function testAddReviewReturnsFalseOnDatabaseException(): void
    {
        $db = $this->createMock(DatabaseInterface::class);
        $db->method('update')->willThrowException(new \RuntimeException('mongo down'));

        $result = $this->makeBooks($db)->addReview('507f1f77bcf86cd799439011', ['rating' => 5]);

        $this->assertFalse($result);
    }

    public function testUpdateBookReturnsFalseWhenNoFieldsToUpdate(): void
    {
        $db = $this->createMock(DatabaseInterface::class);
        $db->expects($this->never())->method('update');

        $result = $this->makeBooks($db)->updateBook('507f1f77bcf86cd799439011', ['description' => '']);

        $this->assertFalse($result);
    }
}
