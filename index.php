<?php

use Aharon\Export;
use Aharon\JsonDatabase;
use Aharon\TableNotFoundException;

require __DIR__ . '/vendor/autoload.php';

try {
    $db = new JsonDatabase( __DIR__ . '/tables/' );
    $db->createTable( 'users', array( 'username', 'email', 'city' ) );

    $db->insert( 'users', array( 'username' => 'aharon', 'email' => 'aharon@test.com', 'city' => 'Tel Aviv' ) );
    $db->insert( 'users', array( 'username' => 'moshe', 'email' => 'moshe@test.com', 'city' => 'Haifa' ) );
    $db->insert( 'users', array( 'username' => 'yosef', 'email' => 'yosef@test.com', 'city' => 'Tel Aviv' ) );

    $export = new Export( $db );
    $export->setData( 'users', array( 'id', 'username' ) );

    echo $export->toString( ', ', "<br />" );

    $db->destroy();
} catch( TableNotFoundException $e ) {
    echo $e->getMessage();
}

