<?php

namespace Aharon;

class Export
{
    private Database $db;

    private array $columns = array();
    private array $data    = array();

    public function __construct( Database $db )
    {
        $this->db = $db;
    }

    /**
     * @throws TableNotFoundException
     */
    public function setData( string $tableName, array $columns = array(), array $filterData = array() )
    {
        $this->data    = $this->db->get( $tableName, $filterData );
        $this->columns = $columns;
        return $this;
    }

    public function toArray(): array
    {
        $flipColumns = array_flip( $this->columns );

        return array_merge(
            array( $this->columns ),
            array_map( function( $row ) use ( $flipColumns ) {
                $result = $flipColumns;

                foreach ( $this->columns as $column ) {
                    $result[ $column ] = $row[$column] ?? '';
                }

                return $result;
            }, $this->data )
        );
    }

    public function toString( string $delimiter = "\t", string $newLine = "\n" ): string
    {
        $exportedRows = $this->toArray();

        // Begin the output
        $output = $this->exportRow( $exportedRows[0], $delimiter, $newLine );

        // Unset headers
        unset( $exportedRows[0] );

        // Append rows
        foreach( $exportedRows as $row ) {
            $output .= $this->exportRow( $row, $delimiter, $newLine );
        }

        return rtrim( $output, $newLine );
    }

    private function exportRow( array $row, string $delimiter, string $newLine ): string
    {
        return implode( $delimiter, $row ) . $newLine;
    }
}