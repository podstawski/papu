<?php

class AlterTableGuestsAddCancelReason extends Doctrine_Migration_Base
{
    private $_tableName = 'guests';
    protected $_columnName1 = 'cancel_reason';

    
    public function up()
    {
        $this->addColumn($this->_tableName, $this->_columnName1, 'Text', null, array('notnull' => false ));
    }

    public function down()
    {
        $this->removeColumn($this->_tableName, $this->_columnName1);
    }
}
