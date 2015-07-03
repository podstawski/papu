<?php

class AlterTableCitiesAddImg2 extends Doctrine_Migration_Base
{
    private $_tableName = 'cities';
    protected $_columnName1 = 'img2';

    
    public function up()
    {
        $this->addColumn($this->_tableName, $this->_columnName1, 'Integer', null, array('notnull' => false ));
       
    }

    public function down()
    {

        $this->removeColumn($this->_tableName, $this->_columnName1);
    }
}
