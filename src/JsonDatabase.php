<?php

namespace Aharon;

class JsonDatabase implements Database
{
    private string $path;

    private string $schemaTableName = 'schema';

    public function __construct( $path )
    {
        // Add trailing slash
        $this->path = rtrim( $path,'/' ) . '/';

        // Creating the subdirectory when specified
        if ( $this->path !== __DIR__ && ! file_exists( $this->path ) ) {
            mkdir( $this->path );
        }

        $this->createTable( $this->schemaTableName, array() );
    }

    public function createTable( string $tableName, array $columns )
    {
        if ( $this->tableExists( $tableName ) ) {
            return;
        }

        if ( $tableName !== 'schema' ) {
            $schema = $this->getContents( $this->schemaTableName );
            $schema[ $tableName ] = $columns;
            $this->putTableContent( $this->schemaTableName, $schema );
        }

        $this->putTableContent( $tableName, array() );
    }

    public function getTableColumns( string $tableName ): array
    {
        return $this->getContents( $this->schemaTableName )[ $tableName ];
    }

    /**
     * @throws TableNotFoundException
     */
    public function insert( string $tableName, array $rowData )
    {
        $tableContent = $this->getTableContent( $tableName );
        $tableColumns = $this->getTableColumns( $tableName );
        $lastRowId    = intval( end( $tableContent )[ 'id' ] ?? 0 ) + 1;

        // Create the row
        $tableContent[] = array_merge(
            $this->extractColumns( $rowData, $tableColumns ),
            array( 'id' => $lastRowId )
        );

        $this->putTableContent( $tableName, $tableContent );
    }

    /**
     * @throws TableNotFoundException
     */
    public function get( string $tableName, array $filterData = array(), int $limit = -1 ): array
    {
        $tableContent = $this->getTableContent( $tableName );
        $tableContent = array_filter( $tableContent, function( $row ) use ( $filterData ) {
            return $this->filterTableRow( $row, $filterData );
        } );

        if ( $limit >= 0 ) {
            $tableContent = array_slice( $tableContent, 0, $limit );
        }

        return $tableContent;
    }

    /**
     * @throws TableNotFoundException
     */
    public function update( string $tableName, array $rowData, array $updateData )
    {
        $tableContent = $this->getTableContent( $tableName );
        $tableColumns = $this->getTableColumns( $tableName );

        foreach( $tableContent as &$row ) {
            if ( $this->filterTableRow( $row, $rowData ) ) {
                unset( $updateData['id'] ); // in case there is an attempt to update the row ID.
                $row = array_merge( $row, $this->extractColumns( $updateData, $tableColumns ) );
            }
        }

        $this->putTableContent( $tableName, $tableContent );
    }

    /**
     * @throws TableNotFoundException
     */
    public function delete( string $tableName, array $rowData )
    {
        $tableContent = $this->getTableContent( $tableName );
        $tableContent = array_filter( $tableContent, function( $row ) use ( $rowData ) {
            return ! $this->filterTableRow( $row, $rowData );
        });

        $this->putTableContent( $tableName, $tableContent );
    }

    private function validateTable( string $tableName ): bool
    {
        return $tableName !== $this->schemaTableName && $this->tableExists( $tableName );
    }

    private function tableExists( string $tableName ): bool
    {
        return file_exists( $this->jsonFileName( $tableName ) );
    }

    /**
     * @throws TableNotFoundException
     */
    private function getTableContent( string $tableName ): array
    {
        if ( ! $this->validateTable( $tableName ) ) {
            throw new TableNotFoundException( $tableName );
        }

        return $this->getContents( $tableName );
    }

    private function putTableContent( string $tableName, array|object $content ): void
    {
        file_put_contents( $this->jsonFileName( $tableName ), json_encode( $content ) );
    }

    private function jsonFileName( string $fileName ): string
    {
        return $this->path . $fileName . '.json';
    }

    private function filterTableRow( array $row, array $filterData ): bool
    {
        $result = true;

        foreach( $filterData as $key => $value ) {
            if ( ! ( isset( $row[ $key ] ) && str_contains( $row[ $key ], $value ) ) ) {
                $result = false;
            }
        }

        return $result;
    }

    private function extractColumns( array $rowData, array $columns ): array
    {
        return array_intersect_key( $rowData, array_flip( $columns ) );
    }

    private function getContents( string $fileName )
    {
        return json_decode( file_get_contents( $this->jsonFileName( $fileName ) ), true );
    }

    public function destroy(): void
    {
        $files = glob( $this->path . '*.json' ); // get all file names

        foreach ( $files as $file ) { // iterate files
            if ( is_file( $file ) )
                @unlink($file); // delete file
        }
    }
}