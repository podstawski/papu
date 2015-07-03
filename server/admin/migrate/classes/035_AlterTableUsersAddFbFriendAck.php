<?php

class AlterTableUsersAddFbFriendAck extends Doctrine_Migration_Base
{
    private $_tableName = 'users';
    protected $_columnName1 = 'fb_friend';

    
    public function up()
    {
        $this->addColumn($this->_tableName, $this->_columnName1, 'Text', null, array('notnull' => false ));
    }

    public function down()
    {
        $this->removeColumn($this->_tableName, $this->_columnName1);
    }
}
