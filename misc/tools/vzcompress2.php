#!php -f
<?PHP

/**
 * Data compression tool
 *
 * This is a tool designed to decrease data resolution based on
 * it's age. Lower resolution results in less data and thus may
 * save a lot of storage space
 *
 * Script must be executed with misc/tools as current workdir
 * Script was only tested with MySQL-Storage
 * Script should only be executed if you've got a recent backup
 *
 * By default we assume the following resolution scheme:
 *   Newer than 7 Days      Keep Original
 *   Older than 7 Days      Datapoint per 1 Minute
 *   Older than 30 Days     Datapoint per 5 Minutes
 *   Older than 6 Month     Datapoint per 15 Minutes
 *   Older than 1 Year      Datapoint per 30 Minutes
 * You can set your own scheme for all or specific datapoints at the
 * bottom of this file
 *
 * By default this script saves its state in /tmp/vzcompress2/. You may want
 * to move this files to a location that is not cleaned on reboot. If the files
 * are not present or caching is turned off the script will scan the complete
 * database. If you often insert/import historic data you may want to turn
 * this off. Configuration can be found near the end of this file.
 *
 * Database parameters are read from ../../etc/volkszaehler.conf.php
 *
 * @copyright Copyright (c) 2013, The volkszaehler.org project
 * @author Florian Knodt <adlerweb@adlerweb.info>
 * @package tools
 * @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
 */
 
/**
 * This file is part of volkzaehler.org
 *
 * volkzaehler.org is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * volkzaehler.org is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with volkszaehler.org. If not, see <http://www.gnu.org/licenses/>.
 */

 /**
  * Path to Volkszaehler
  *
  * Absolute Path or relative to current workdir
  */
 define('VZCOMPRESS2_VZPATH', '../../');
 
 //Dummy VZ_DIR so volkszaehler.conf.php doesn't throw warnings
 if(!defined('VZ_DIR')) define('VZ_DIR', '');

 class vzcompress2 {
    private $config;
    private $sql_config;
    private $sql;
    private $channels;
    private $entities;
    private $purgecounter;
    private $timestr = '%x %X';
    
    static private $sensortypemap = array(
        'SensorInterpreter'  => 'AVG',
        'MeterInterpreter'   => 'SUM',
        'CounterInterpreter' => 'MAX',
        
        'AggregatorInterpreter' => false
    );
    
    public function __construct($config = array()) {
        if(!isset($config['compressscheme'])) {
            $config['compressscheme']['default'] = array( //Definition for all other channels
                (7*24*60*60)    => (1*60),      //Older than 7 Days      Datapoint per 1 Minute
                (30*24*60*60)   => (5*60),      //Older than 30 Days     Datapoint per 5 Minutes
                (6*30*24*60*60) => (15*60),     //Older than 6 Month     Datapoint per 15 Minutes
                (365*24*60*60)  => (30*60),     //Older than 1 Year      Datapoint per 30 Minutes 
            );
        }
        
        if(!isset($config['verbose'])) $config['verbose']=true;
        if(!isset($config['caching'])) $config['caching']=false;
        if(!isset($config['sleep'])) $config['sleep']=0;
        
        $this->config = $config;
        $this->sql_config_load();
        $this->sql_connect();
        $this->cache_init();
        $this->sql_getChannels();
        $this->json_getEntities();
        $this->compress();
    }
    
    private function sql_config_load() {
        require(VZCOMPRESS2_VZPATH.'etc/volkszaehler.conf.php');
        $this->sql_config = $config['db'];
    }
    
    private function sql_connect() {
        //Let's hack a hopefully valid DSN based on configuration
        $dsn = str_replace('pdo_', '', $this->sql_config['driver']).':dbname='.$this->sql_config['dbname'].';host='.$this->sql_config['host'];
        
        try {
            $this->sql = new PDO($dsn, $this->sql_config['user'], $this->sql_config['password']);
        } catch (PDOException $e) {
            trigger_error('Connection to database failed: ' . $e->getMessage(), E_USER_ERROR);
        }
    }
    
    private function sql_simplequery($qry) {
        usleep($this->config['sleep']);
        if(!$stmt = $this->sql->prepare ($qry)) return false;
        if(!$stmt->execute()) {
            var_dump($stmt->errorInfo());
            return false;
        }
        $out = $stmt->fetchAll();
        $stmt->closeCursor();
        return $out;
    }
    
    private function sql_getChannels() {
        $this->channels = $this->sql_simplequery("SELECT * FROM `entities` WHERE `class` = 'channel';");
    }
    
    private function json_getEntities() {
        $json = file_get_contents(VZCOMPRESS2_VZPATH.'lib/Definition/EntityDefinition.json');
        
        //The JSON-File contains comments which violates the spec and derps PHPs decoder
        //Remove the headers...
        $json = explode("\n[\n", $json);
        if(count($json) > 1) {
            $json = '['.$json[1];
        }else{
            $json = $json[0];
        }
        
        $this->entities = json_decode($json);
    }
    
    private function cache_init() {
        if($this->config['caching']) {
            if(substr($this->config['caching'], -1) != '/') $this->config['caching'].='/';
            
            if(file_exists($this->config['caching'])) {
                if(!is_dir($this->config['caching'])) {
                    trigger_error('Can not cache to '.$this->config['caching'].' - Not a directory', E_USER_WARNING);
                    $this->config['caching'] = false;
                }
                if(!is_writable($this->config['caching'])) {
                    trigger_error('Can not cache to'.$this->config['caching'].' - Not writable', E_USER_WARNING);
                    $this->config['caching'] = false;
                }
            }else{
                if(!mkdir($this->config['caching'], 0755, true)) {
                    trigger_error('Can not cache to'.$this->config['caching'].' - Could not create directory', E_USER_WARNING);
                    $this->config['caching'] = false;
                }
            }
        }
    }
    
    private function cache_write($chanid, $timebase, $last) {
        if(!$this->config['caching']) return false;
        if($timebase == 0 || $last == 0) return false;
        file_put_contents($this->config['caching'].$chanid.'.'.$timebase, $last);
    }
    
    private function cache_read($chanid, $timebase) {
        if(!$this->config['caching']) return false;
        if(!file_exists($this->config['caching'].$chanid.'.'.$timebase)) return false;
        return (float)file_get_contents($this->config['caching'].$chanid.'.'.$timebase);
    }
    
    private function compress() {
        $start = time();
        foreach($this->channels as $channel) {
            if(isset($this->config['channels']) && !in_array($channel['id'], $this->config['channels'])) continue;
            
            echo strftime($this->timestr).' - Processing Sensor ID '.$channel['id'].'...'."\n";
            $this->process($channel);
        }
        echo strftime($this->timestr).' - Done. Purged '.$this->purgecounter.' Datapoints from '.count($this->channels).' Channels in '.(time()-$start).' Seconds'."\n";
    }
    
    private function process($channel) {
        //What type of sensor?
        foreach($this->entities as $entity) {
            if($entity->name == $channel['type']) {
                $type = str_replace('Volkszaehler\\Interpreter\\', '', $entity->interpreter);
                break;
            }
        }
        if(!isset($type)) {
            trigger_error('Could not detect inperpreter for type '.$channel['type'], E_USER_WARNING);
            return false;
        }
        if(!isset(self::$sensortypemap[$type])) {
            trigger_error('Interpreter '.$type.' is currently not supported', E_USER_WARNING);
            return false;
        }
        $sqlfunc = self::$sensortypemap[$type];
        if($sqlfunc == false) return false;
        
        //Detect compressscheme
        if(isset($this->config['compressscheme'][$channel['id']])) {
            $cs = $this->config['compressscheme'][$channel['id']];
        }else{
            $cs = $this->config['compressscheme']['default'];
        }
        
        //Prepare compressscheme
        ksort($cs);
        $times = array_keys($cs);
        $times[] = 0;
        
        $timestamp = time(); //Local timestamp should be consistent during our transactions
        
        //Run compression passes
        for($i=0; $i<count($times)-1; $i++) {
            if($cs[$times[$i]] == 0) continue;
            
            //Step 1: Detect oldest and newest dataset
            $datatimes = $this->sql_simplequery("SELECT MIN(`timestamp`) as `min`, MAX(`timestamp`) as `max` FROM `data` WHERE `channel_id` =  '".$channel['id']."' AND `timestamp` <= '".(($timestamp-$times[$i])*1000)."' AND `timestamp` > '".(($times[$i+1] > 0) ? (($timestamp-$times[$i+1])*1000) : 0 )."'");
            
            if((float)$datatimes[0]['max'] == 0) {
                echo strftime($this->timestr).' -   Skipping compression pass for datapoints between '.strftime($this->timestr, ($timestamp-$times[$i+1])).' and '.strftime($this->timestr, ($timestamp-$times[$i])).' using a '.$cs[$times[$i]].' second timeframe: No Datapoints found'."\n";
                continue;
            }
            
            //Caching
            $lastrun = (float)$this->cache_read($channel['id'], $times[$i]);
            if($lastrun && (float)$lastrun >= (float)$datatimes[0]['min']) {
                echo strftime($this->timestr).' -   Skipping datapoints between '.strftime($this->timestr, ((float)$datatimes[0]['min']/1000)).' and '.strftime($this->timestr, ((float)$lastrun/1000)).' (Cached)'."\n";
                (float)$datatimes[0]['min'] = $lastrun;
            }
            
            echo strftime($this->timestr).' -   Compressing datapoints between '.strftime($this->timestr, ((float)$datatimes[0]['min']/1000)).' and '.strftime($this->timestr, ((float)$datatimes[0]['max']/1000)).' using a '.$cs[$times[$i]].' second timeframe'."\n";
            
            //Step 2: Loop new possible timeframes
            $curtime = (float)$datatimes[0]['min'];
            $lastpurgecount = $this->purgecounter;
            $steps = (((float)$datatimes[0]['max']/1000)-((float)$datatimes[0]['min']/1000))/$cs[$times[$i]];
            if($steps == 0) continue;
            $step = 0;
            $passstart = time();
            do {
                //Step 2.1: Increase timestamps
                $lastcurtime = $curtime;
                $step++;
                $curtime += $cs[$times[$i]]*1000;
                
                //Print status
                if($this->config['verbose']) echo "\r".strftime($this->timestr).' -     Processing: '.strftime($this->timestr, $lastcurtime/1000).' - '.strftime($this->timestr, $curtime/1000).' ('.round(100/$steps*$step).'%)...                    ';
                
                //Step 2.1: Get new Value for timeframe
                $newset = $this->sql_simplequery("SELECT ".$sqlfunc."(`value`) as `newval`, COUNT(`value`) as `datapoints`, MIN(`id`) as updateid FROM `data` WHERE `channel_id` = '".$channel['id']."' AND `timestamp` > '".$lastcurtime."' AND `timestamp` <= '".$curtime."';");
                
                //Step 2.2: Skip if current timeframe has no or already just one datapoint
                if(count($newset) == 0 || $newset[0]['datapoints'] < 2) continue;
                
                $this->sql->beginTransaction();
                
                //Step 2.3: Delete old Datapoints
                if($this->sql_simplequery("DELETE FROM `data` WHERE `channel_id` = '".$channel['id']."' AND `timestamp` > '".$lastcurtime."' AND `timestamp` <= '".$curtime."' AND `id` != '".$newset[0]['updateid']."';") === false) {
                    $this->sql->rollback();
                    trigger_error('SQL FAILURE', E_USER_ERROR);
                }
                $this->purgecounter+=($newset[0]['datapoints']-1);
                
                //Step 2.4: Update oldest Datapoint
                //          Note: Use UPDATE instead of INSERT to avoid filling up our id-pool
                if($this->sql_simplequery("UPDATE `data` SET `timestamp` = '".($curtime-1)."', `value` = '".$newset[0]['newval']."' WHERE `channel_id` = '".$channel['id']."' AND `id` = '".$newset[0]['updateid']."';") === false) {
                    $this->sql->rollback();
                    trigger_error('SQL FAILURE', E_USER_ERROR);
                }
                
                //Step 2.6 Commit to Database
                $this->sql->commit();
            
            }while($curtime <= (float)$datatimes[0]['max']);
            echo "\r".strftime($this->timestr).' -     Removed '.($this->purgecounter-$lastpurgecount).' Datapoints in '.(time()-$passstart).' Seconds.                                  '."\n";
            
            $this->cache_write($channel['id'], $times[$i], (float)$datatimes[0]['max']);
        }
    }
 }
 
 /**
  * Sample Configuration
  */
 setlocale(LC_ALL, 'de_DE.UTF-8', 'de_DE@euro', 'de_DE', 'de', 'ge');
 $config = array(
    'verbose' => true,      //Show times/percentage - should be disables on slow TTYs
    'caching' => '/tmp/vzcompress2/', //Path or false
    'sleep' => 500, //Microseconds to sleep between requests
    
    //'channels' => array(  //If defined only this channels are compressed
    //  '1', '2', '3'       //Note that IDs are strings
    //),
    
    'compressscheme' => array(
    //  '1' => array(       //Definition for Channel ID 1
    //      //...see below...
    //  ),
        'default' => array( //Definition for all other channels
            (7*24*60*60)    => (1*60),      //Older than 7 Days      Datapoint per 1 Minute
            (30*24*60*60)   => (5*60),      //Older than 30 Days     Datapoint per 5 Minutes
            (6*30*24*60*60) => (15*60),     //Older than 6 Month     Datapoint per 15 Minutes
            (365*24*60*60)  => (30*60),     //Older than 1 Year      Datapoint per 30 Minutes 
        )
    )
 );
 $test = new vzcompress2($config);
 
?>
