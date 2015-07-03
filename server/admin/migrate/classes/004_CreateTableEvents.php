<?php

class CreateTableEvents extends Doctrine_Migration_Base
{
    private $_tableName = 'events';
    private $_fkName1 = 'fk_events_user';
    private $_fkName2 = 'fk_events_parent';
    private $_fkName3 = 'fk_image_labels_event';
    
    
    public function up()
    {
        $this->createTable($this->_tableName, array(
            'id' => array(
                'type' => 'integer',
                'notnull' => true,
                'primary' => true,
                'autoincrement' => true,
            ),
            'user' => array(
                'type' => 'integer',
                'notnull' => true,
            ),
            'parent' => array(
                'type' => 'integer',
                'notnull' => false,
            ),
            'd_create' => array(
                'type' => 'integer',
                'notnull' => true,
            ),
            'd_event_start' => array(
                'type' => 'integer',
                'notnull' => false,
            ),            
            'd_event_end' => array(
                'type' => 'integer',
                'notnull' => false,
            ),            
            'd_deadline' => array(
                'type' => 'integer',
                'notnull' => false,
            ),
            'min_guests' => array(
                'type' => 'integer',
                'notnull' => false,
            ),
            'max_guests' => array(
                'type' => 'integer',
                'notnull' => false,
            ),
            'name' => array(
                'type' => 'character varying(255)',
                'notnull' => true,
            ),
            'url' => array(
                'type' => 'character varying(255)',
                'notnull' => true,
            ),
            'country' => array(
                'type' => 'varchar(3)',
                'notnull' => true,
            ),
            'city' => array(
                'type' => 'varchar(200)',
                'notnull' => false,
            ),
            'address' => array(
                'type' => 'varchar(200)',
                'notnull' => false,
            ),
            'postal' => array(
                'type' => 'varchar(10)',
                'notnull' => false,
            ),
            'hints' => array(
                'type' => 'text',
                'notnull' => false,
            ),
            'lat' => array(
                'type' => 'DECIMAL(18,9)',
                'notnull' => false,
            ),
            'lng' => array(
                'type' => 'DECIMAL(18,9)',
                'notnull' => false,
            ),
            'about' => array(
                'type' => 'text',
                'notnull' => false,
            ),

            'price' => array(
                'type' => 'DECIMAL(6,1)',
                'notnull' => false,
            ),
            'currency' => array(
                'type' => 'varchar(3)',
                'notnull' => false,
            ),           
            'active' => array(
                'type' => 'integer',
                'notnull' => true,
            ), 

        ), array('charset'=>'utf8'));
        
        $this->addIndex($this->_tableName,$this->_tableName.'_user_key',array('fields'=>array('user')));
        $this->addIndex($this->_tableName,$this->_tableName.'_parent_key',array('fields'=>array('parent')));
        $this->addIndex($this->_tableName,$this->_tableName.'_geo_key',array('fields'=>array('country','lat','lng')));
        
        
        
        $this->createForeignKey($this->_tableName, $this->_fkName1, array(
             'local'         => 'user',
             'foreign'       => 'id',
             'foreignTable'  => 'users',
             'onDelete'      => 'CASCADE',
             'onUpdate'      => 'CASCADE'
        ));

        $this->createForeignKey($this->_tableName, $this->_fkName2, array(
             'local'         => 'parent',
             'foreign'       => 'id',
             'foreignTable'  => 'events',
             'onDelete'      => 'CASCADE',
             'onUpdate'      => 'CASCADE'
        ));

        $this->createForeignKey('image_labels', $this->_fkName3, array(
             'local'         => 'event',
             'foreign'       => 'id',
             'foreignTable'  => 'events',
             'onDelete'      => 'CASCADE',
             'onUpdate'      => 'CASCADE'
        ));

          
    }

    public function down()
    {
        $this->dropForeignKey('image_labels', $this->_fkName3);
        $this->dropForeignKey($this->_tableName, $this->_fkName2);
        $this->dropForeignKey($this->_tableName, $this->_fkName1);
        $this->dropTable($this->_tableName);
    }
}
