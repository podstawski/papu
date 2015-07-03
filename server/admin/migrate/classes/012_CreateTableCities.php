<?php

class CreateTableCities extends Doctrine_Migration_Base
{
    private $_tableName = 'cities';
    private $_fkName1 = 'fk_cities_img';
    
    
    public function up()
    {
        $this->createTable($this->_tableName, array(
            'id' => array(
                'type' => 'integer',
                'notnull' => true,
                'primary' => true,
                'autoincrement' => true,
            ),
            'img' => array(
                'type' => 'integer',
                'notnull' => false,
            ),            
            'country' => array(
                'type' => 'varchar(3)',
                'notnull' => true,
            ),
            'name' => array(
                'type' => 'varchar(100)',
                'notnull' => true,
            ),
            'lat' => array(
                'type' => 'DECIMAL(18,9)',
                'notnull' => false,
            ),
            'lng' => array(
                'type' => 'DECIMAL(18,9)',
                'notnull' => false,
            ),
            'distance' => array(
                'type' => 'DECIMAL(4,1)',
                'notnull' => false,
            ),            

        ), array('charset'=>'utf8'));
        
        $this->addIndex($this->_tableName,$this->_tableName.'_geo_key',array('fields'=>array('lat','lng')));
        
        
        
        $this->createForeignKey($this->_tableName, $this->_fkName1, array(
             'local'         => 'img',
             'foreign'       => 'id',
             'foreignTable'  => 'images',
             'onDelete'      => 'SET NULL',
             'onUpdate'      => 'CASCADE'
        ));


          
    }

    public function down()
    {
        $this->dropForeignKey($this->_tableName, $this->_fkName1);
        $this->dropTable($this->_tableName);
    }
}
