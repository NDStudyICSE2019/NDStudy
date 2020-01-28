<?php

class Claro_MyGroupList
{
    private 
        $userId, 
        $courseId, 
        $database,
        $tbl;
    
    public function __construct( $userId = null, $courseId = null, $database = null )
    {
        $this->userId = $userId ? $userId : claro_get_current_user_id();
        $this->courseId = $courseId ? $courseId : claro_get_current_course_id ();
        $this->database = $database ? $database : Claroline::getDatabase();
        
        $this->tbl = array_merge( 
            get_module_main_tbl( array('rel_course_user') ), 
            get_module_course_tbl( array( 'group_team','group_rel_team_user' ) ) );
    }
    
    public function getMyGroupList()
    {
        return $this->database->query( "
            SELECT 
                `g`.`id` AS id,
                `g`.`name` AS name,
                `g`.`tutor`  AS id_tutor,
                `g`.`description`  AS description
            FROM 
                `{$this->tbl['group_team']}` AS `g`
            WHERE 
                `g`.`tutor` = " . (int) claro_get_current_user_id() . "
            
            UNION
            

            SELECT 
                `g`.`id` AS id,
                `g`.`name` AS name,
                `g`.`tutor`  AS id_tutor,
                `g`.`description`  AS description
            FROM 
                `{$this->tbl['group_rel_team_user']}` AS `ug`
            LEFT JOIN 
                `{$this->tbl['group_team']}` AS `g`
            ON  
                `g`.`id` = `ug`.`team` 
            WHERE 
                `ug`.`user` = " . (int) claro_get_current_user_id()
        )->setFetchMode(Database_Resultset::FETCH_CLASS, 'Claro_MyGroup');
    }
}

class Claro_MyGroup implements Database_Object
{
    public
        $id,
        $name,
        $description,
        $tutorId;
    
    public function hasTutor()
    {
        return $this->tutorId ? true : false;
    }
    
    public function getTutor()
    {
        $tutor = null;
        
        if ( $this->tutorId )
        {
            $tutor = new Claro_User( $this->tutorId );
            $tutor->load();
        }
        
        return $tutor;
    }
    
    public static function getInstance ( $data )
    {
        $group = new self;
        $group->id = (int) $data['id'];
        $group->name = $data['name'];
        $group->description = (string) $data['description'];
        $group->tutorId = (int) $data['id_tutor'];
        
        return $group;
    }
}