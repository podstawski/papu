<?php

class CreateFunctionDistance extends Doctrine_Migration_Base
{
   
    
    
    public function up()
    {
        Doctrine_Manager::connection()->exec("
            CREATE FUNCTION geo_distance(p1 DECIMAL(18,9),p2 DECIMAL(18,9),p3 DECIMAL(18,9),p4 DECIMAL(18,9)) RETURNS DECIMAL(18,9)
            BEGIN
                RETURN sqrt(
                            pow( (p2-p4) * cos(p1 * pi() / 180),2)
                            +
                            pow(p3-p1,2)
                        ) * pi() * 12756.274 / 360 ;
            
            END
        ");

    }

    public function down()
    {

        Doctrine_Manager::connection()->exec("DROP FUNCTION IF EXISTS geo_distance;");

    }
}
