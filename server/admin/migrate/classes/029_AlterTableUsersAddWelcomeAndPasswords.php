<?php

class AlterTableUsersAddWelcomeAndPasswords extends Doctrine_Migration_Base
{
    private $_tableName = 'users';
    protected $_columnName1 = 'welcome';
    protected $_columnName2 = '_login_auth';
    protected $_columnName3 = '_password_reminder_hash';
    protected $_columnName4 = '_password_reminder_expire';
    

    
    public function up()
    {
        $this->addColumn($this->_tableName, $this->_columnName1, 'Integer', null, array('notnull' => false ));
        $this->addColumn($this->_tableName, $this->_columnName2, 'Varchar(32)', null, array('notnull' => false ));
        $this->addColumn($this->_tableName, $this->_columnName3, 'Varchar(32)', null, array('notnull' => false ));
        $this->addColumn($this->_tableName, $this->_columnName4, 'Integer', null, array('notnull' => false ));
    
    }

    public function postUp()
    {
        $sql="UPDATE users SET welcome=1 WHERE id IN (SELECT user FROM events)";
        Doctrine_Manager::connection()->execute($sql);
    }
    
    public function down()
    {
        $this->removeColumn($this->_tableName, $this->_columnName4);
        $this->removeColumn($this->_tableName, $this->_columnName3);
        $this->removeColumn($this->_tableName, $this->_columnName2);
        $this->removeColumn($this->_tableName, $this->_columnName1);
    }
}
