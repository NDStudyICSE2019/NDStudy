<?php // $Id: linker.cnr.php 14359 2013-01-24 12:47:40Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Resource Resolver for the Forum tool
 *
 * @version 1.9 $Revision: 14359 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author claroline Team <cvs@claroline.net>
 * @package CLFRM
 *
 */

FromKernel::uses('fileManage.lib', 'file.lib');

class CLFRM_Resolver implements ModuleResourceResolver
{
    public function resolve ( ResourceLocator $locator )
    {
        if ( $locator->hasResourceId() )
        {
            if ( $locator->inGroup() )
            {
                // $resourceElements = explode( '/', ltrim( $locator->getResourceId(), '/') );
                
                return get_module_url('CLFRM') . "/viewtopic.php?topic=".(int)ltrim( $locator->getResourceId(), '/');
            }
            else
            {
                $resourceElements = explode( '/', ltrim( $locator->getResourceId(), '/') );
                
                if ( count( $resourceElements ) == 1 )
                {
                    return get_module_url('CLFRM') . "/viewforum.php?forum=".(int)$resourceElements[0];
                }
                elseif ( count( $resourceElements ) == 2 )
                {
                    return get_module_url('CLFRM') . "/viewtopic.php?topic=".(int)$resourceElements[1];
                }
                else
                {
                    return get_module_entry_url('CLFRM');
                }
            }
        }
        else
        {
            return get_module_entry_url('CLFRM');
        }
    }
    
    private function getForumName( $forumId, $courseId )
    {
        $tbl = get_module_course_tbl( array('bb_forums'), $courseId );
        
        $sql = "SELECT `forum_name`\n"
            . "FROM `{$tbl['bb_forums']}`\n"
            . "WHERE `forum_id`=". Claroline::getDatabase()->escape( $forumId )
            ;
        
        $res = Claroline::getDatabase()->query($sql);
        $res->setFetchMode(Database_ResultSet::FETCH_VALUE);
        
        $title = $res->fetch();
        
        if ( $title )
        {
        
            $title = trim( $title );

            if ( empty( $title ) )
            {
                $title = get_lang('Untitled');
            }

            return $title;
        }
        else
        {
            Console::debug ("Cannot load forum " 
                . var_export( $forumId, true )
                . " in " . __CLASS__
                . " : query returned " 
                . var_export( $title, true ) );
            return null;
        }
    }
    
    private function getTopicName( $topicId, $courseId )
    {
        $tbl = get_module_course_tbl( array('bb_topics'), $courseId );
        
        $sql = "SELECT `topic_title`\n"
            . "FROM `{$tbl['bb_topics']}`\n"
            . "WHERE `topic_id`=". Claroline::getDatabase()->escape( $topicId )
            ;
        
        $res = Claroline::getDatabase()->query($sql);
        $res->setFetchMode(Database_ResultSet::FETCH_VALUE);
        
        $title = $res->fetch();
        
        if ( $title )
        {
            $title = trim( $title );

            if ( empty( $title ) )
            {
                $title = get_lang('Untitled');
            }

            return $title;
        }
        else
        {
            Console::debug ("Cannot load topic " 
                . var_export( $topicId, true )
                . " in " . __CLASS__
                . " : query returned " 
                . var_export( $title, true ) );
            return null;
        }
    }

    public function getResourceName( ResourceLocator $locator )
    {
        if ( ! $locator->hasResourceId() )
        {
            return false;
        }
        
        if ( $locator->inGroup() )
        {
            $name = $this->getTopicName( ltrim( $locator->getResourceId(), '/'), $locator->getCourseId() );
        }
        else
        {
            $resourceElements = explode( '/', ltrim( $locator->getResourceId(), '/') );
                
            $name = $this->getForumName($resourceElements[0], $locator->getCourseId());
            
            if ( count( $resourceElements ) == 2 )
            {
                $name .= ' > ' . $this->getTopicName($resourceElements[1], $locator->getCourseId());
            }
        }
        
        return $name;
    }
}

class CLFRM_Navigator implements ModuleResourceNavigator
{
    public function getResourceId( $params = array() )
    {
        $elems = array();
        
        if ( isset( $params['forum_id'] ) )
        {
            $elems['forum_id'] =  $params['forum_id'];
            
            if ( isset( $params['topic_id'] ) )
            {
                $elems['topic_id'] =  $params['topic_id'];
            }
        }
        else
        {
            if ( isset( $params['topic_id'] ) )
            {
                $tbl = get_module_course_tbl( array('bb_topics') ); //, $locator->getCourseId() );
        
                $sql = "SELECT `forum_id`\n"
                    . "FROM `{$tbl['bb_topics']}`\n"
                    . "WHERE `topic_id`=". Claroline::getDatabase()->escape( $params['topic_id'] )
                    ;
                
                $res = Claroline::getDatabase()->query($sql);
                $res->setFetchMode(Database_ResultSet::FETCH_VALUE);
                $forumId = trim( $res->fetch() );
                
                $elems['forum_id'] = (int) $forumId;
                $elems['topic_id'] =  $params['topic_id'];
            }
            else
            {
                return false;
            }
        }
        
        return implode( '/', $elems );
    }
    
    public function isNavigable( ResourceLocator $locator )
    {
        if ( $locator->inGroup() )
        {
            return (! $locator->hasResourceId());
        }
        elseif (  $locator->hasResourceId() )
        {
            $elems = explode( '/', ltrim( $locator->getResourceId(), '/') );
            
            return ( count( $elems ) == 1 );
        }
        else
        {
            return $locator->inModule() && $locator->getModuleLabel() == 'CLFRM';
        }
    }
    
    public function getParentResourceId( ResourceLocator $locator )
    {
        if (  $locator->hasResourceId() )
        {
            $elems = explode( '/', ltrim( $locator->getResourceId(), '/') );
            
            if( count( $elems ) == 1 )
            {
                return false;
            }
            else
            {
                return $elems[0];
            }
        }
        else
        {
            return false;
        }
    }
    
    public function getResourceList( ResourceLocator $locator )
    {
        $resourceList = new LinkerResourceIterator;
        
        $tbl = get_module_course_tbl( array('bb_topics','bb_forums'), $locator->getCourseId() );
        
        if ( !$locator->hasResourceId() )
        {
            if ( ! $locator->inGroup() )
            {
                $sql = "SELECT `forum_id`, `forum_name`, `group_id`\n"
                    . "FROM `{$tbl['bb_forums']}`\n"
                    // . "WHERE IS_NULL(`group_id`)"
                    ;
                
                $forumList = Claroline::getDatabase()->query( $sql );
                
                foreach ( $forumList as $forum )
                {
                    $forumLoc = new ClarolineResourceLocator(
                        $locator->getCourseId(),
                        'CLFRM',
                        ( empty($forum['group_id'])
                            ? (int) $forum['forum_id']
                            : null ),
                        ( empty($forum['group_id'] )
                            ? null
                            : $forum['group_id'] )
                    );
                    
                    $topicResource = new LinkerResource(
                        ( empty( $forum['forum_name'] )
                            ? get_lang('Untitled')
                            : $forum['forum_name'] ),
                        $forumLoc,
                        true,
                        true,
                        true
                    );
                    
                    $resourceList->addResource( $topicResource );
                }
            }
            else
            {
                $sql = "SELECT `forum_id` AS `id`, `forum_name` AS `name`\n"
                    . "FROM `{$tbl['bb_forums']}`\n"
                    . "WHERE `group_id` = " . Claroline::getDatabase()->escape( $locator->getGroupId() )
                    ;
                
                $res = Claroline::getDatabase()->query($sql);
                
                if ( count( $res ) )
                {
                    $groupForum = $res->fetch(Database_ResultSet::FETCH_OBJECT);
                    
                    $sql = "SELECT `topic_id`, `topic_title`, `forum_id`\n"
                        . "FROM `{$tbl['bb_topics']}`\n"
                        . "WHERE `forum_id` = " . Claroline::getDatabase()->escape( $groupForum->id )
                        ;
                        
                    $topicList = Claroline::getDatabase()->query( $sql );
                    
                    foreach ( $topicList as $topic )
                    {
                        $topicLoc = new ClarolineResourceLocator(
                            $locator->getCourseId(),
                            'CLFRM',
                            (int) $topic['topic_id'],
                            $locator->getGroupId()
                        );
                        
                        $topicResource = new LinkerResource(
                            ( empty( $topic['topic_title'] )
                                ? get_lang('Untitled')
                                : $topic['topic_title'] ),
                            $topicLoc,
                            true,
                            true,
                            false
                        );
                        
                        $resourceList->addResource( $topicResource );
                    }
                }
            }
        }
        else
        {
            if ( $locator->inGroup() )
            {
                
            }
            else
            {
                $elems = explode( '/', ltrim( $locator->getResourceId(), '/') );
                
                if ( count( $elems ) == 1 )
                {
                    
                    $sql = "SELECT `topic_id`, `topic_title`, `forum_id`\n"
                        . "FROM `{$tbl['bb_topics']}`\n"
                        . "WHERE `forum_id` = " . Claroline::getDatabase()->escape( $elems[0] )
                        ;
                        
                    $topicList = Claroline::getDatabase()->query( $sql );
                    
                    foreach ( $topicList as $topic )
                    {
                        $topicLoc = new ClarolineResourceLocator(
                            $locator->getCourseId(),
                            'CLFRM',
                            ((int) $topic['forum_id']) . '/' . ((int) $topic['topic_id'])
                        );
                        
                        $topicResource = new LinkerResource(
                            ( empty( $topic['topic_title'] )
                                ? get_lang('Untitled')
                                : $topic['topic_title'] ),
                            $topicLoc,
                            true,
                            true,
                            false
                        );
                        
                        $resourceList->addResource( $topicResource );
                    }
                }
                else
                {
                    // not navigable
                }
            }
        }

        return $resourceList;
    }
}
