<?php

use Aharon\JsonDatabase;
use PHPUnit\Framework\TestCase;

class JsonDatabaseTest extends TestCase
{
    private JsonDatabase $db;

    private string $testTableName = 'test';

    private array $testColumns = array( 'username', 'email', 'city' );

    private array $testData = array(
        array( 'id' => 1, 'username' => 'aharon', 'email' => 'aharon@test.com', 'city' => 'Tel Aviv' ),
        array( 'id' => 2, 'username' => 'moshe', 'email' => 'moshe@test.com', 'city' => 'Haifa' ),
        array( 'id' => 3, 'username' => 'yosef', 'email' => 'yosef@test.com', 'city' => 'Tel Aviv' ),
    );

    private string $path = __DIR__;

    protected function setUp(): void
    {
        $this->db = new JsonDatabase( $this->path );
        $this->prepareTestTable();
    }

    protected function tearDown(): void
    {
        $this->db->destroy();
        @unlink( $this->path );
    }

    public function testCreateTable()
    {
        $testTableName = "$this->testTableName-1";
        // table creation
        $this->db->createTable( $testTableName, $this->testColumns );

        // Check schema and table columns
        $this->assertFileExists( $this->path . '/schema.json' );
        $this->assertEquals( $this->testColumns, $this->db->getTableColumns( $testTableName ) );

        // Check table exists
        $this->assertFileExists( $this->path . "/{$testTableName}.json" );
        @unlink( $this->path . "$testTableName.json" );
    }

    public function testInsert()
    {
        for ( $i = 0; $i < 3; ++$i ) {
            $fromDb = current( $this->db->get( $this->testTableName, array( 'username' => $this->testData[ $i ][ 'username' ] ) ) );
            $this->assertEquals( $i + 1, $fromDb[ 'id' ] );
            $this->assertEquals( $this->testData[ $i ], $fromDb );
        }
    }

    public function testGet()
    {
        // Get all rows
        $this->assertCount(3, $this->db->get( $this->testTableName ) );

        $firstRow = $this->testData[0];

        // Test single filter fetch
        $this->assertEquals(
            $firstRow,
            current( $this->db->get( $this->testTableName, array( 'username' => 'aharon' ) ) )
        );

        // Test multiple row results
        $this->assertCount( 2, $this->db->get( $this->testTableName, array( 'city' => 'Tel Aviv' ) ) );

        // Test limit parameter
        $fetchWithLimit = $this->db->get( $this->testTableName, array( 'city' => 'Tel Aviv' ), 1 );
        $this->assertEquals( $firstRow, current( $fetchWithLimit ) );
        $this->assertCount( 1, $fetchWithLimit );
    }

    public function testUpdate()
    {
        // Update single column
        $this->db->update( $this->testTableName, array( 'username' => 'aharon' ), array( 'username' => 'aharon-updated' ) );
        $fetchData = $this->db->get( $this->testTableName, array( 'username' => 'aharon-updated' ) );
        $this->assertCount( 1, $fetchData );

        // Update multiple columns
        $this->db->update( $this->testTableName, array( 'city' => 'Tel Aviv' ), array( 'city' => 'Jerusalem' ) );
        $fetchData = $this->db->get( $this->testTableName, array( 'city' => 'Jerusalem' ) );
        $this->assertCount( 2, $fetchData );
    }

    public function testDelete()
    {
        // Single row deletion
        $this->db->delete( $this->testTableName, array( 'username' => 'moshe' ) );
        $this->assertCount( 2, $this->db->get( $this->testTableName ) );

        // Multiple rows deletion
        $this->db->delete( $this->testTableName, array( 'city' => 'Tel Aviv' ) );
        $this->assertCount( 0, $this->db->get( $this->testTableName ) );
    }

    public function testDestroy() {
        $this->db->createTable( 'test1', array( 'test' ) );
        $this->db->createTable( 'test2', array( 'test' ) );

        $this->assertFileExists( $this->path . '/schema.json' );
        $this->assertFileExists( $this->path . '/test1.json' );
        $this->assertFileExists( $this->path . '/test2.json' );

        $this->db->destroy();

        $this->assertFileDoesNotExist( $this->path . '/schema.json' );
        $this->assertFileDoesNotExist( $this->path . '/test1.json' );
        $this->assertFileDoesNotExist( $this->path . '/test2.json' );
    }

    private function prepareTestTable() {
        $this->db->createTable( $this->testTableName, $this->testColumns );

        foreach( $this->testData as $row ) {
            $this->db->insert( $this->testTableName, $row );
        }
    }
}
