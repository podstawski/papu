<?php

class AlterTableGuestsAddCancelCommit extends Doctrine_Migration_Base
{
    private $_tableName = 'guests';
    protected $_columnName1 = 'd_cancel_commit';

    
    public function up()
    {
        $this->addColumn($this->_tableName, $this->_columnName1, 'Integer', null, array('notnull' => false ));
        $this->addIndex($this->_tableName,$this->_tableName.'_payment_key',array('fields'=>array('d_payment','d_cancel','d_cancel_commit')));
    }

    public function down()
    {
        $this->removeIndex($this->_tableName,$this->_tableName.'_payment_key');
        $this->removeColumn($this->_tableName, $this->_columnName1);
    }
}
