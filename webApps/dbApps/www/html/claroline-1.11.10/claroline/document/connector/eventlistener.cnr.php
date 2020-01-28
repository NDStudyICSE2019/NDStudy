<?php // $Id: eventlistener.cnr.php 13708 2011-10-19 10:46:34Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

if ( count( get_included_files() ) == 1 ) die( '---' );

$claroline->notification->addListener( 'document_visible',          'modificationDefault' );
$claroline->notification->addListener( 'document_file_added',       'modificationDefault');
$claroline->notification->addListener( 'document_file_modified',    'modificationUpdate' );
$claroline->notification->addListener( 'document_moved',            'modificationUpdate' );
$claroline->notification->addListener( 'document_htmlfile_created', 'modificationDefault' );
$claroline->notification->addListener( 'document_htmlfile_edited',  'modificationDefault' );
$claroline->notification->addListener( 'document_file_deleted',     'modificationDelete' );
$claroline->notification->addListener( 'document_invisible',        'modificationDelete' );
