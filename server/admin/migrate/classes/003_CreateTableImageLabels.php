<?php

class CreateTableImageLabels extends Doctrine_Migration_Base
{
    private $_tableName = 'image_labels';
    private $_fkName1 = 'fk_image_labels_image';

    public function up()
    {
        $this->createTable($this->_tableName, array(
            'id' => array(
                'type' => 'integer',
                'notnull' => true,
                'primary' => true,
                'autoincrement' => true,
            ),
            'image' => array(
                'type' => 'integer',
                'notnull' => true,
            ),
            'name' => array(
                'type' => 'varchar(255)',
                'notnull' => true,
            ),            
            'event' => array(
                'type' => 'integer',
                'notnull' => false,
            ),
            
        ), array('charset'=>'utf8'));
        
        $this->addIndex($this->_tableName,$this->_tableName.'_image_key',array('fields'=>array('image')));
        
        
        $this->createForeignKey($this->_tableName, $this->_fkName1, array(
             'local'         => 'image',
             'foreign'       => 'id',
             'foreignTable'  => 'images',
             'onDelete'      => 'CASCADE',
             'onUpdate'      => 'CASCADE'
        ));
          
    }

    public function down()
    {
        $this->dropForeignKey($this->_tableName, $this->_fkName1);
        $this->dropTable($this->_tableName);
    }
}
