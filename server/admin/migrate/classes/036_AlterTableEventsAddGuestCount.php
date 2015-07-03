<?php

class AlterTableEventsAddGuestCount extends Doctrine_Migration_Base
{
    private $_tableName = 'events';
    protected $_columnName1 = '_guest_count';

    
    public function up()
    {
        $this->addColumn($this->_tableName, $this->_columnName1, 'Integer', null, array('notnull' => false ));
    }

    public function postUp()
    {
        $sql="UPDATE events SET ".$this->_columnName1."= (SELECT count(*) FROM guests WHERE event=events.id AND d_payment IS NOT NULL AND d_cancel IS NULL) ";
        Doctrine_Manager::connection()->execute($sql);
    }

    public function down()
    {
        $this->removeColumn($this->_tableName, $this->_columnName1);
    }
}
