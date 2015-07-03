<?php

class AlterTableEventsAddAfterDeadlineGuestPrice extends Doctrine_Migration_Base
{
    private $_tableName1 = 'events';
    protected $_columnName1 = 'bookafterdeadline';
    
    private $_tableName2 = 'guests';
    protected $_columnName21 = 'guest_price';
    protected $_columnName22 = 'host_price';

    
    public function up()
    {
        $this->addColumn($this->_tableName1, $this->_columnName1, 'Integer', null, array('notnull' => false ));
        $this->addColumn($this->_tableName2, $this->_columnName21, 'DECIMAL(6,1)', null, array('notnull' => false ));
        $this->addColumn($this->_tableName2, $this->_columnName22, 'DECIMAL(6,1)', null, array('notnull' => false ));

    }

    public function down()
    {
        $this->removeColumn($this->_tableName2, $this->_columnName22);
        $this->removeColumn($this->_tableName2, $this->_columnName21);
        $this->removeColumn($this->_tableName1, $this->_columnName1);
    }
}
