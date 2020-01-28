<?php // $Id: profile.class.php 14314 2012-11-07 09:09:19Z zefredz $

/**
 * CLAROLINE
 *
 * Library to manage profile
 *
 * @version     1.11 $Revision: 14314 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     RIGHT
 * @author      Claro Team <cvs@claroline.net>
 */

require_once dirname(__FILE__) . '/constants.inc.php';

/**
 * Class right profile
 */

class RightProfile
{
    /**
     * @var $id id ofthe profile, -1 if profile doesn't exists already
     */

    var $id;

    /**
     * @var $label
     */

    var $label;

    /**
     * @var $name name of the profile
     */

    var $name;

    /**
     * @var $type type of the profile
     */

    var $type;

    /**
     * @var $description description of the profile
     */

    var $description;

    /**
     * @var $isLocked is profile locked
     */

    var $isLocked;

    /**
     * @var $isRequired is profile locked
     */

    var $isRequired;

    /**
     * @var $isCourseManager user can edit course settings
     */

    var $isCourseManager;

    /**
     * @var $isTutor user is a tutor
     */

    var $isTutor;

    /**
     * @var $isUserPublic user is a displayed in user list
     */

    var $isUserPublic;

    /**
     * @var $isEmailNotify user receive announcement, ... from course
     */

    var $isEmailNotify;

    /**
     * @var $tbl array with all tables name;
     */

    var $tbl = array() ;

    /**
     * Constructor
     */

    public function __construct()
    {
        $this->id = 0 ;
        $this->name = '';
        $this->label = '';
        $this->type = PROFILE_TYPE_COURSE ;
        $this->description = '';

        $this->isLocked = false;
        $this->isRequired = false;

        $this->isCourseManager = false;
        $this->isTutor = false;
        $this->isUserPublic = true;
        $this->isEmailNotify = true;

        $tbl_mdb_names = claro_sql_get_main_tbl();
        $this->tbl['profile'] = $tbl_mdb_names['right_profile'];
    }

    /**
     * Load a profile from DB
     *
     * @param integer $id identifier of profile
     * @return boolean load successfull
     */

    public function load($id)
    {
        $sql = " SELECT profile_id,
                        name,
                        label,
                        type,
                        description,
                        locked,
                        required,
                        courseManager,
                        groupTutor,
                        userListPublic,
                        mailingList
                 FROM `" . $this->tbl['profile'] . "`
                 WHERE profile_id = " . (int) $id ;

        $data = claro_sql_query_get_single_row($sql);

        if ( !empty($data) )
        {
            $this->id = (int) $data['profile_id'];
            $this->name = $data['name'];
            $this->label = $data['label'];
            $this->type = $data['type'];
            $this->description = $data['description'];

            $this->isLocked = $data['locked'];
            $this->isRequired = $data['required'];

            $this->isCourseManager = $data['courseManager'];
            $this->isTutor = $data['groupTutor'];
            $this->isUserPublic = $data['userListPublic'];
            $this->isEmailNotify = $data['mailingList'];

            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * save profile to DB
     *
     * @return mixed false or id of the profile
     */

    public function save()
    {
        if ( $this->id == 0 )
        {
            // insert
            $sql = "INSERT INTO `" . $this->tbl['profile'] . "`
                    SET `name` = '" . claro_sql_escape($this->name) . "',
                        `label` = '" . claro_sql_escape($this->label) . "',
                        `description` = '" . claro_sql_escape($this->description) . "',
                        `type` = '" . claro_sql_escape($this->type) . "',
                        `courseManager` = " . (int) $this->isCourseManager . ",
                        `mailingList` = " . (int) $this->isEmailNotify . ",
                        `userlistPublic` = " . (int) $this->isUserPublic . ",
                        `groupTutor` = " . (int) $this->isTutor . ",
                        `locked` = " . (int) $this->isLocked . ",
                        `required` = " . (int) $this->isRequired . "";

            // execute the creation query and get id of inserted assignment
            $insertedId = claro_sql_query_insert_id($sql);

            if( $insertedId )
            {
                $this->id = (int) $insertedId;
                return $this->id;
            }
            else
            {
                return false;
            }

        }
        else
        {
            // update, main query
            $sql = "UPDATE `". $this->tbl['profile'] ."`
                    SET `name` = '" . claro_sql_escape($this->name) . "',
                        `label` = '" . claro_sql_escape($this->label) . "',
                        `description` = '" . claro_sql_escape($this->description) . "',
                        `type` = '" . claro_sql_escape($this->type) . "',
                        `courseManager` = " . (int) $this->isCourseManager . ",
                        `mailingList` = " . (int) $this->isEmailNotify . ",
                        `userlistPublic` = " . (int) $this->isUserPublic . ",
                        `groupTutor` = " . (int) $this->isTutor . ",
                        `locked` = " . (int) $this->isLocked . ",
                        `required` = " . (int) $this->isRequired . "
                    WHERE `profile_id` = '". (int) $this->id."'";

            // execute and return main query
            if( claro_sql_query($sql) )
            {
                return $this->id;
            }
            else
            {
                return false;
            }
        }

    }

    /**
     * delete profile from DB
     *
     * @todo TODO Possibility to delete used profile ?
     * @return boolean
     */

    public function delete()
    {
        if ( ! $this->isRequired )
        {
            $sql = "DELETE FROM `". $this->tbl['profile'] ."`
                    WHERE `profile_id` = '". (int) $this->id."'";
            claro_sql_query($sql);

            // is it required to empty the fields of the object ?
            $this->id = -1;
            return true;
        }
        else
        {
            // the profile is required
            return false;
        }
    }

    /**
     * Check if data are valide
     *
     * @return boolean
     */

    public function validate()
    {
        // use validator library
        require_once dirname(__FILE__) . '/../datavalidator.lib.php';

        // new validator
        $validator = new DataValidator();

        $dataList = array('name'  => $this->name,
                          'description' => $this->description);

        $validator->setDataList($dataList);

        $validator->addRule('name',get_lang('Name is missing'),'required');
        // $validator->addRule('description',get_lang('Description is missing'),'required');

        if ( $validator->validate(DATAVALIDATOR_STRICT_MODE) )
        {
            return true;
        }
        else
        {
            $this->errorList = $validator->getErrorList();
            return false;
        }
    }

    /**
     * Get profile identifier
     *
     * @return integer
     */

    public function getId()
    {
        return $this->id;
    }

    /**
     * Get profile label
     *
     * @return string
     */

    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Get profile name
     *
     * @return string
     */

    public function getName()
    {
        return get_lang($this->name);
    }

    /**
     * Get profile type ()
     *
     * @return string
     */

    public function getType()
    {
        return $this->type;
    }

    /**
     * Get profile description
     *
     * @return string
     */

    public function getDescription()
    {
        return get_lang($this->description);
    }

    /**
     * Get required status
     *
     * @return boolean
     */

    public function isRequired()
    {
        return (bool) $this->isRequired;
    }

    /**
     * Get locked status
     *
     * @return boolean
     */

    public function isLocked()
    {
        return (bool) $this->isLocked;
    }

    /**
     * Get course manager status
     *
     * @return boolean
     */

    public function isCourseManager()
    {
        return (bool) $this->iscourseManager;
    }

    /**
     * Get user public status
     *
     * @return boolean
     */

    public function isUserPublic()
    {
        return (bool) $this->isUserPublic;
    }

    /**
     * Get mail notify status
     *
     * @return boolean
     */

    public function isMailNotify()
    {
        return (bool) $this->isMailNotify;
    }

    /**
     * Set profile label
     *
     * @param string $value
     */

    public function setLabel($value)
    {
        $this->label = trim($value);
    }

    /**
     * Set profile name
     * @param string $value
     */

    public function setName($value)
    {
        $this->name = trim($value);
    }

    /**
     * Set description
     *
     * @param string $description
     */

    public function setDescription($value)
    {
        $this->description = trim($value);
    }

    /**
     * Set type
     *
     * @param string $value
     */

    public function setType($value)
    {
        $this->type = $value;
    }

    /**
     * Set locked status
     * @param boolean $value
     */

    public function setIsLocked($value)
    {
        $this->isLocked = (bool) $value;
    }

    /**
     * Set required status
     * @param boolean $value
     */

    public function setIsRequired($value)
    {
        $this->isRequired = (bool) $value;
    }

    /**
     * Set course manager status
     * @param boolean $value
     */

    public function setIsCourseManager($value)
    {
        $this->isCourseManager = (bool) $value;
    }

    /**
     * Set is tutor status
     * @param boolean $value
     */

    public function setIsTutor($value)
    {
        $this->isTutor = (bool) $value;
    }

    /**
     * Set user public status
     * @param boolean $value
     */

    public function setIsUserPublic($value)
    {
        $this->isUserPublic = (bool) $value;
    }

    /**
     * Set email notification status
     * @param boolean $value
     */

    public function setIsEmailNotify($value)
    {
        $this->isEmailNotify = (bool) $value;
    }

    /**
     * Display form profile
     * @return string
     */

    public function displayProfileForm()
    {
        $form = '<form action="' . $_SERVER['PHP_SELF'] . '" method="post" >'
        .       claro_form_relay_context()
        .       '<input type="hidden" name="profile_id" value="' . $this->id . '" />' . "\n"
        .       '<input type="hidden" name="claroFormId" value="' . uniqid('') . '" />' . "\n"
        .       '<input type="hidden" name="cmd" value="exSave" />' . "\n"
        .       '<table>'
        ;

        // Display name
        $form .= '<tr valign="top">' . "\n"
        .       '<td align="right">' . "\n"
        .       '<label for="name">' . "\n"
        .       get_lang('Name') . "\n"
        .       ' :' . "\n"
        .       '</label>' . "\n"
        .       '</td>' ;

        if ( $this->isRequired() )
        {
            $form .= '<td>' . claro_htmlspecialchars($this->getName()) . '</td>';
        }
        else
        {
            $form .= '<td>' . "\n"
            .        '<input type="text" id="name" name="name" value="' . $this->getName() . '"/>' . "\n"
            .        '</td>'
            ;
        }

        $form .= '</tr>';

        // Display description
        $form .= '<tr valign="top">' . "\n"
        .        '<td align="right">' . "\n"
        .        '<label for="description">'
        .        get_lang('Description') . ' :</label>' . "\n"
        .        '</td>' . "\n"
        .        '<td >' . "\n"
        .        '<textarea cols="60" rows="3" id="description" name="description">' . $this->getDescription() . '</textarea>' . "\n"
        .        '</td>' . "\n"
        .        '</tr>'
        ;

        // Display type

        // TODO after 1.8

        /*
        $form .= '
            <tr valign="top">
            <td align="right">' . get_lang('Type') . ' :</td>
            <td><input type="text" name="type" value="' . $this->type . '"/></td>
            </tr>';

        $form .= '<input type="hidden" name="type" value="' . PROFILE_TYPE_COURSE . '" />' ;

        // Display 'is course manager'
        $form .= '
            <tr valign="top">
            <td align="right"><label for="isCourseManager">' . get_lang('Course manager') . ' :</label></td>
            <td><input type="checkbox" id="isCourseManager" name="isCourseManager" value="1" ' . ($this->isCourseManager?'checked="checked"':'') . '/></td>
            </tr>';

        // Display 'is tutor'
        $form .= '
            <tr valign="top">
            <td align="right"><label for="isTutor">' . get_lang('Tutor') . ' :</label></td>
            <td><input type="checkbox" id="isTutor" name="isTutor" value="1" ' . ($this->isTutor?'checked="checked"':'') . '/></td>
            </tr>';

        */

        // Display 'is locked'
        $form .= '
            <tr valign="top">
            <td align="right"><label for="isLocked">' . get_lang('Locked') . ' :</label></td>
            <td><input type="checkbox" id="isLocked" name="isLocked" value="1" ' . ($this->isLocked?'checked="checked"':'') . '/></td>
            </tr>';

        /*

        // Display is 'user public'
        $form .= '
            <tr valign="top">
            <td align="right"><label for="isUserPublic">' . get_lang('Display in user list') . ' :</label></td>
            <td><input type="checkbox" id="isUserPublic" name="isUserPublic" value="1" ' . ($this->isUserPublic?'checked="checked"':'') . '/></td>
            </tr>';

        // Display is 'mailing list'
        $form .= '
            <tr valign="top">
            <td align="right"><label for="isEmailNotify">' . get_lang('Email notification') . ' :</label></td>
            <td><input type="checkbox" id="isEmailNotify" name="isEmailNotify" value="1" ' . ($this->isEmailNotify?'checked="checked"':'') . '/></td>
            </tr>';

        */

        // Display submit button
        $form .= '
            <tr>
            <td align="right">&nbsp;</td>
            <td><input type="submit" name="submitProfile" value="' . get_lang('Ok') . '" />&nbsp;'
            . claro_html_button((isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'.'), get_lang('Cancel'))
            . '</td></tr>
            </table>
            </form>' ;

        return $form;
    }

    /**
     * Validate profile form data
     * @return boolean
     */

    public function validateForm()
    {
        if ( isset($_REQUEST['id']) ) $this->id = (int)$_REQUEST['profile_id'];
        if ( isset($_REQUEST['name']) ) $this->name = $_REQUEST['name'];
        if ( isset($_REQUEST['description']) ) $this->description = $_REQUEST['description'];

        $this->isCourseManager = isset($_REQUEST['isCourseManager'])?true:false;
        $this->isTutor = isset($_REQUEST['isTutor'])?true:false;
        $this->isLocked = isset($_REQUEST['isLocked'])?true:false;
        $this->isUserPublic = isset($_REQUEST['isUserPublic'])?true:false;
        $this->isEmailNotify = isset($_REQUEST['isEmailNotify'])?true:false;

        return $this->validate();

    }

}
