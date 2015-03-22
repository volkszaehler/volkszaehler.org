<?php
namespace JpGraph ;

class JpGraph {

    static  $loaded = false ;
    static  $modules = array();

    static function load(){
        if(self::$loaded !== true){
            include_once __DIR__.'/src/jpgraph.php';
            self::$loaded = true ;
        }
    }
    static function module($moduleName){
        self::load();
        if(!in_array($moduleName,self::$modules)){
            $path = __DIR__.'/src/jpgraph_'.$moduleName.'.php' ;
            if(file_exists($path)){
                include_once $path ;
            }else{
                throw new ModuleNotFoundException('The JpGraphs\'s module "'.$moduleName.'" does not exist');
            }
        }
    }
}

?>