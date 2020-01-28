<?php // $Id: eventlistener.cnr.php 13708 2011-10-19 10:46:34Z abourguignon $
if ( count( get_included_files() ) == 1 ) die( '---' );

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * @version     $Revision: 13708 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claro Team <cvs@claroline.net>
 */

$claroline->notification->addListener( 'agenda_event_visible',      'modificationDefault' );
$claroline->notification->addListener( 'agenda_event_added',        'modificationDefault' );
$claroline->notification->addListener( 'agenda_event_modified',     'modificationDefault' );
$claroline->notification->addListener( 'agenda_event_deleted',      'modificationDelete' );
$claroline->notification->addListener( 'agenda_event_invisible',    'modificationDelete' );

$claroline->notification->addListener( 'agenda_event_deleted',      'deleteEventResource' );
$claroline->notification->addListener( 'agenda_event_list_deleted', 'deleteEventResourceList' );
