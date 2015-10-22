<?php

class AlterTableUsersAddSite extends Doctrine_Migration_Base
{
    private $_tableName = 'users';
    protected $_columnName1 = 'site';

    

    
    public function up()
    {
        $this->addColumn($this->_tableName, $this->_columnName1, 'varchar(50)', null, array('notnull' => false ));
 
    }

    public function postUp()
    {
        $sql="UPDATE users SET site='jemyrazem'";
        Doctrine_Manager::connection()->execute($sql);
    }
    
    public function down()
    {
   
        $this->removeColumn($this->_tableName, $this->_columnName1);
    }
}
