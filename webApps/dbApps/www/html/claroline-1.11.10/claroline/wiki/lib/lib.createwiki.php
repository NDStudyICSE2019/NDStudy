<?php // $Id: lib.createwiki.php 14094 2012-03-22 13:34:16Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * @version 1.11 $Revision: 14094 $
 *
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * This program is under the terms of the GENERAL PUBLIC LICENSE (GPL)
 * as published by the FREE SOFTWARE FOUNDATION. The GPL is available
 * through the world-wide-web at http://www.gnu.org/copyleft/gpl.html
 *
 * @author Frederic Minne <zefredz@gmail.com>
 *
 * @package Wiki
 */

require_once dirname(__FILE__) . "/class.wikiaccesscontrol.php";
require_once dirname(__FILE__) . "/class.wikistore.php";
require_once dirname(__FILE__) . "/class.wikipage.php";
require_once dirname(__FILE__) . "/class.wiki.php";
require_once dirname(__FILE__) . "/lib.wikisql.php";

function create_wiki($gid = false, $wikiName = 'New wiki')
{
    $creatorId = claro_get_current_user_id();

    $tblList = claro_sql_get_course_tbl();

    $config = array ();
    $config["tbl_wiki_properties"] = $tblList["wiki_properties"];
    $config["tbl_wiki_pages"] = $tblList["wiki_pages"];
    $config["tbl_wiki_pages_content"] = $tblList["wiki_pages_content"];
    $config["tbl_wiki_acls"] = $tblList["wiki_acls"];

    $con = Claroline::getDatabase();

    $acl = array ();

    if ($gid)
    {
        $acl = WikiAccessControl::defaultGroupWikiACL();
    }
    else
    {
        $acl = WikiAccessControl::defaultCourseWikiACL();
    }

    $wiki = new Wiki($con, $config);
    $wiki->setTitle($wikiName);
    $wiki->setDescription('This is a sample wiki');
    $wiki->setACL($acl);
    $wiki->setGroupId($gid);
    $wikiId = $wiki->save();
    $wikiTitle = $wiki->getTitle();

    $mainPageContent = sprintf("This is the main page of the Wiki %s. Click on edit to modify the content.", $wikiTitle);

    $wikiPage = new WikiPage($con, $config, $wikiId);
    $wikiPage->create($creatorId
        , '__MainPage__'
        , $mainPageContent
        , date("Y-m-d H:i:s")
        , true);
}

function delete_wiki($groupId)
{
    $tblList = claro_sql_get_course_tbl();

    $config = array ();
    $config["tbl_wiki_properties"] = $tblList["wiki_properties"];
    $config["tbl_wiki_pages"] = $tblList["wiki_pages"];
    $config["tbl_wiki_pages_content"] = $tblList["wiki_pages_content"];
    $config["tbl_wiki_acls"] = $tblList["wiki_acls"];

    $con = Claroline::getDatabase();

    $store = new WikiStore($con, $config);

    if (strtoupper($groupId) == 'ALL')
    {
        $wikiList = $store->getGroupWikiList();
    }
    else
    {
        $wikiList = $store->getWikiListByGroup($groupId);
    }

    if (count($wikiList) > 0)
    {
        foreach ($wikiList as $wiki)
        {
            $store->deleteWiki($wiki['id']);
        }
    }
}

function delete_group_wikis($groupIdList = 'ALL')
{
    // echo "passed here";
    if (strtoupper($groupIdList) == 'ALL')
    {
        delete_wiki('ALL');
    }
    elseif (is_array($groupIdList) && count($groupIdList) > 0)
    {
        foreach ($groupIdList as $groupId)
        {
            // echo "passed here";
            delete_wiki($groupId);
        }
    }
    else
    {
        delete_wiki($groupIdList);
    }
}
