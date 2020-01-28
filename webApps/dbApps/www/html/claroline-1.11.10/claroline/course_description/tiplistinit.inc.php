<?php // $Id: tiplistinit.inc.php 13708 2011-10-19 10:46:34Z abourguignon $
if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * @version     $Revision: 13708 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 */

unset($tipList);


$tipList[] = array(
    'title'         => get_block('blockCourseDescriptionDescription'),
    'isEditable'    => false,
    'question'      => get_block('blockCourseDescriptionDescriptionComment1'),
    'information'   => get_block('blockCourseDescriptionDescriptionComment2')
);

$tipList[] = array(
    'title'         => get_block('blockCourseDescriptionQualificationsAndGoals'),
    'isEditable'    => false,
    'question'      => get_block('blockCourseDescriptionQualificationsAndGoalsComment1'),
    'information'   => get_block('blockCourseDescriptionQualificationsAndGoalsComment2')
);

$tipList[] = array(
    'title'         => get_block('blockCourseDescriptionCourseContent'),
    'isEditable'    => false,
    'question'      => get_block('blockCourseDescriptionCourseContentComment1'),
    'information'   => get_block('blockCourseDescriptionCourseContentComment2')
);

$tipList[] = array(
    'title'         => get_block('blockCourseDescriptionTeachingTrainingActivities'),
    'isEditable'    => false,
    'question'      => get_block('blockCourseDescriptionTeachingTrainingActivitiesComment1'),
    'information'   => get_block('blockCourseDescriptionTeachingTrainingActivitiesComment2')
);

$tipList[] = array(
    'title'         => get_block('blockCourseDescriptionSupports'),
    'isEditable'    => false,
    'question'      => get_block('blockCourseDescriptionSupportsComment1'),
    'information'   => get_block('blockCourseDescriptionSupportsComment2')
);

$tipList[] = array(
    'title'         => get_block('blockCourseDescriptionHumanAndPhysicalRessources'),
    'isEditable'    => false,
    'question'      => get_block('blockCourseDescriptionHumanAndPhysicalResourcesComment1'),
    'information'   => get_block('blockCourseDescriptionHumanAndPhysicalResourcesComment2')
);

$tipList[] = array(
    'title'         => get_block('blockCourseDescriptionMethodsOfEvaluation'),
    'isEditable'    => false,
    'question'      => '',
    'information'   => get_block('blockCourseDescriptionMethodsOfEvaluationComment1')
);
