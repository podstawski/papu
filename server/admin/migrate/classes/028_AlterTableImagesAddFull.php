<?php

class AlterTableImagesAddFull extends Doctrine_Migration_Base
{
    private $_tableName = 'images';
    protected $_columnName1 = 'full';

    
    public function up()
    {
        $this->addColumn($this->_tableName, $this->_columnName1, 'Varchar(255)', null, array('notnull' => false ));
       
    }

    public function down()
    {

        $this->removeColumn($this->_tableName, $this->_columnName1);
    }
}
