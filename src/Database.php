<?php

namespace Aharon;

interface Database
{
    public function createTable( string $tableName, array $columns );

    public function getTableColumns( string $tableName ): array;

    /**
     * @throws TableNotFoundException
     */
    public function insert( string $tableName, array $rowData );

    /**
     * @throws TableNotFoundException
     */
    public function get( string $tableName, array $filterData, int $limit ): array;

    /**
     * @throws TableNotFoundException
     */
    public function update( string $tableName, array $rowData, array $updateData );

    /**
     * @throws TableNotFoundException
     */
    public function delete( string $tableName, array $rowData );

    public function destroy(): void;
}