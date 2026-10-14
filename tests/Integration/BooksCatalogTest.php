<?php

namespace Tests\Integration;

use App\Models\Books;
use Tests\Support\IntegrationTestCase;

class BooksCatalogTest extends IntegrationTestCase
{
    private Books $books;

    protected function setUp(): void
    {
        parent::setUp();
        $this->books = new Books($this->db);
        $this->mongo->selectCollection('Books')->insertMany([
            ['title' => 'Kurose Networking', 'author' => 'Kurose', 'categories' => ['Networking'],
                'year' => '2017', 'file_extension' => 'PDF', 'status' => 'public'],
            ['title' => 'tanenbaum networks', 'author' => 'Tanenbaum', 'categories' => ['Networking', 'OS'],
                'year' => 2011, 'file_type' => 'physical', 'status' => 'public'],
            // Uploaded before file_extension existed: counts as a PDF. Unparseable year: no year.
            ['title' => 'Modern OS', 'author' => 'Tanenbaum', 'categories' => ['OS'], 'year' => 'n/a', 'status' => 'public'],
            ['title' => 'Kur(ose draft', 'author' => 'Kurose', 'categories' => ['Networking'], 'year' => 2020, 'status' => 'draft'],
        ]);
    }

    /**
     * @param array{category?: list<string>, format?: list<string>, author?: list<string>} $filters
     * @param array{0: ?int, 1: ?int} $years
     * @return array<string, mixed>
     */
    private function catalog(string $text = '', array $filters = [], array $years = [null, null], bool $publicOnly = true, string $sort = 'title'): array
    {
        return $this->books->catalog($text, $filters + ['category' => [], 'format' => [], 'author' => []], $years, $publicOnly, $sort, 0, 24);
    }

    /**
     * @param list<array{value: string, count: int}> $facet
     * @return array<string, int>
     */
    private static function counts(array $facet): array
    {
        return array_column($facet, 'count', 'value');
    }

    public function testListsPublicBooksByCaseInsensitiveTitle(): void
    {
        $result = $this->catalog();

        $this->assertSame(3, $result['total']);
        $this->assertSame(['Kurose Networking', 'Modern OS', 'tanenbaum networks'], array_column($result['books'], 'title'));
        $this->assertSame(['pdf' => 2, 'physical' => 1], self::counts($result['facets']['format']));
        $this->assertSame(['min' => 2011, 'max' => 2017], $result['years']);
    }

    public function testDraftsOnlyForAdmins(): void
    {
        $this->assertSame(4, $this->catalog(publicOnly: false)['total']);
    }

    public function testSearchTextIsLiteralNotARegex(): void
    {
        $result = $this->catalog('kur(ose', publicOnly: false);

        $this->assertSame(['Kur(ose draft'], array_column($result['books'], 'title'));
    }

    public function testEachFacetIsCountedWithEveryFilterButItsOwn(): void
    {
        $result = $this->catalog(filters: ['category' => ['OS']], years: [2010, null]);

        $this->assertSame(['tanenbaum networks'], array_column($result['books'], 'title'));
        // Picking a subject shouldn't hide the other subjects: counted with only the year filter.
        $this->assertSame(['Networking' => 2, 'OS' => 1], self::counts($result['facets']['category']));
        $this->assertSame(['physical' => 1], self::counts($result['facets']['format']));
        // The year span ignores the year filter.
        $this->assertSame(['min' => 2011, 'max' => 2011], $result['years']);
    }

    public function testYearSortsNumericallyAcrossStringAndIntYears(): void
    {
        $result = $this->catalog(sort: 'year_desc', years: [2000, 2030]);

        $this->assertSame(['Kurose Networking', 'tanenbaum networks'], array_column($result['books'], 'title'));
    }
}
