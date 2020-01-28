<?php

class CLUSR_ProfileSelectorForm extends ModuleTemplate
{
    protected 
        $profileList, 
        $baseUrl, 
        $command, 
        $userId = null,
        $ignoreNonMemberProfiles = false;
    
    public function __construct( $baseUrl = null, $profileList = null, $command = 'registration' )
    {
        parent::__construct('CLUSR','profileselector.tpl.php');
        
        if ( !$profileList )
        {
            $this->profileList = new CLUSR_ProfileList;
        }
        else
        {
            $this->profileList = $profileList;
        }
        
        if ( !$baseUrl )
        {
            $this->baseUrl = new Url();
        }
        else
        {
            if ( $baseUrl instanceof  Url )
            {
                $this->baseUrl = $baseUrl;
            }
            else
            {
                $this->baseUrl = new Url( $baseUrl );
            }
        }
        
        $this->command = $command;
    }
    
    public function ignoreNonMemberProfiles()
    {
        $this->ignoreNonMemberProfiles = true;
    }
    
    public function setUserId( $userId )
    {
        $this->userId = $userId;
    }
}

class CLUSR_ProfileSelector extends ModuleTemplate
{
    protected 
        $profileList, 
        $baseUrl, 
        $command, 
        $userId = null, 
        $ignoreNonMemberProfiles = false;
    
    
    public function __construct( $profileList = null )
    {
        parent::__construct('CLUSR','profileselector_select.tpl.php');
        
        if ( !$profileList )
        {
            $this->profileList = new CLUSR_ProfileList;
        }
        else
        {
            $this->profileList = $profileList;
        }
    }
    
    public function ignoreNonMemberProfiles()
    {
        $this->ignoreNonMemberProfiles = true;
    }
}

class CLUSR_ProfileList
{
    protected 
        $type = null, 
        $database = null, 
        $profileList = null;
    
    public function __construct( $type = 'COURSE', $database = null )
    {
        if ( !$database )
        {
            $this->database = Claroline::getDatabase();
            $this->type = $type;
        }
        else
        {
            $this->database = $database;
            $this->type = $type;
        }
    }
    
    private function load()
    {
        if ( !empty( $this->type ) )
        {
            if ( $this->type == 'PLATFORM' )
            {
                $where = "
                    WHERE
                        `type` = 'PLATFORM'
                ";
            }
            else
            {
                $where = "
                    WHERE
                        `type` = 'COURSE'
                ";
            }
        }
        else
        {
            $where = '';
        }
        
        $this->profileList = $this->database->query("
            SELECT 
                `profile_id` AS `id`,
                `name`
            FROM
                `__CL_MAIN__right_profile`
            {$where};
        ")->setFetchMode(Database_ResultSet::FETCH_OBJECT);
    }
    
    public function getProfileList()
    {
        if ( is_null( $this->profileList ) )
        {
            $this->load();
        }
        
        return $this->profileList;
    }
}
