<?php
/*

	noPhpredis
	Ronaldo Barbacahno
	
	Built around the short-lived 'redisExec', uses phpredis (php redis module) to
	interact with predis databases.
	
	Automatically stores arrays/objects as hashes, stores enumerated arrays as lists;
	
	automatically serializes embedded arrays/functions.
	
	noPhpredis::get() works the same for getting all three types.
	
	Update method needs work...
*/

// class extension merely over-rides callstatic to provide an interface to redis via
// phpredis (php module) or predis (library)

/*
	Needs an interface. If you are using noSql you don't have to define one.. and
	you may uncomment the line below
*/

require_once('interfaces.php');

/* keep global incase developer needs direct access,
	also makes it easy to have redis and mongo running
	at the same time; just include this file and begin using
	noPhpredis::
 */

if(class_exists('Redis')){
	// load from phpredis php module
	$redis = new Redis();
	// move connection values to interface.php
	if(!$redis->connect('127.0.0.1')){
		die('could not connect to redis');
		}
	
	// eventually set up another server construct to poll
	// available servers and connect to the 'fastest' one?
}else{
	// look for the predis library auto loader
	die('php-redis Module not Found!');
}


abstract class noPhpredis implements noSqlCRUD{
	// very basic command constructor for redis CLI
	// should implement an interface to better /more easily fit in with noClass...
	// stores very simple json structures from arrays .. stores EVERYTHING as json 
	// it will be unlikely that within noClass that users are storing single string values anyhow...

		function get($id,$database=NULL){
			global $redis;
			// phpredis module  returns an integer value ...  only supporting string/list and hash
			//array(1=>'string',2=>'set',3=>'list',4=>'zset',5=>'hash',6=>'other')
			$phpredis_types = array(1=>'string',3=>'list',5=>'hash');
			$key_type = $redis->type($id);
			
			$key_type = $phpredis_types[$key_type];
			
			$output = '';
			if($key_type != false){
			// FIRST DETERMINE THE TYPE OF ID using a COMMAND before using GET or sMembers etc..
			// since GET is the same name as the function we have to override _callStatic to avoid infininte loop
				if($key_type == 'string'){
					$output = $redis->get($id);
				}
				 elseif($key_type == 'set'){
				 	$output = self::sMembers($id);
				 }elseif($key_type == 'list'){
				 // get returns all elements of a list
					$output = $redis->lRange($id,0,-1);
				 }elseif($key_type == 'hash'){
				 	$output = self::hGetAll($id);
				 }
			 }
			return $output;
			
			// decode json ? 
		}
		
		public function key_check($id){
			$key_type = self::type($id);
			return ($key_type?$key_type:false);
		}
		
		public function put($data,$id,$database=NULL){
			global $redis;
			// if is array store serialized version? just serilaze every value to avoid bs?
			// do array type checking ?? PERSIST by defaulT?
			// if its an associtative array .. only
			if(is_array($data) || is_object($data)){
				// currently only supports up to two dimensional arrays.. this needs testing..				
					$hset = '';
					// us multi for this ??
					foreach($data as $key=>$value){					
						if($hset != false && !is_numeric($key)){
							if(is_array($value) || is_object($value))
								$hset [$key]= serialize($value);
							else{
								$hset [$key]= $value;
								}
							$redis->hMset($id,$key,$hset);	
						}else{
							$hset = false;
							// need to exit foreach loop better...
						}		
					}
					if($hset == false){
						// store data/id as list
						foreach($data as $the_id=>$the_data){
							if(is_array($the_data) || is_object($the_data)){
								$list = serialize($the_data);
							}else{
								$list = $the_data;
							}
							$redis->lPush($id,$list);	
						}	
					}
				
			}else{
			// redis in to only be used for sessions  just store values ... 
				return self::set($id,$data);
			}
		}
		
		public function view($name,$key=NULL,$database=NULL,$opt=NULL){
		/*
		
		probably needs to be rewritten for phpredis
		
		*/
		// NAME - of hash field to retrieve
		// KEY - null - provide array with list of documents to match - or regular string to get in on some commandline action search methods
		// basically a view should search for a field name $key, against a value $name inside of a database
		// maybe the database could be the redis key ?
		
		// but we want to work with hashes here... 
		// get a general list of all keys ... then figure out their types, remove any that are not hashes...
		
			// could pass exact term in as key ?? 
			if($key == NULL)
				$records = self::keys(NULL,'*');
			elseif(is_array($key)){
				// allow user to define an array with keys (documents) to look for the names for avoid the 'look everything up'
				$records = $key;
			}elseif(!is_object($key)){
				$records = self::keys(NULL,$key);
				
			}
			
			// would be cooler to match on  key value .. i.e. view ('this field' , 'in these documents', 'where this value is present')
			
			foreach($records as $loc=>$k){
				if(redisExec::key_check($k) != 'hash'){
				// learn array functions (reduce etc)
					unset($records[$loc]);
				}elseif(self::hExists($k,$name) == 1){
					$r [$k]= self::hGet($k,$name);
					}
				}
			return $r;
			

		}
		public function update($id,$data,$database=NULL){
			$key_type = self::key_check($id);
			if($key_type == 'hash'){
				return self::put($data,$id);
			
			}elseif($key_type == 'set'){
				return self::sMembers($id,$data);
			}
		// pretty much the same as 'get'
			return self::get($id);

		}
		public function delete($id,$opt=NULL,$database=NULL){
			// just delete whatever $ids are passed ...
			return self::del(trim($id));

		}

		public static function __callStatic($names,$arguments){
		global $redis;
			if(is_object($redis)){
				$function_call = $names;
				$arg_count = count($arguments);
				
				switch($arg_count){
					case 1:
						return $redis->$function_call($arguments[0]);
					break;
					case 2:
						$redis->$function_call($arguments[0],$arguments[1]);
					break;
					case 3:
						$redis->$function_call($arguments[0],$arguments[1],$arguments[2]);
					break;
					case 4:
						$redis->$function_call($arguments[0],$arguments[1],$arguments[2],$arguments[4]);
					break;
				}
			}else{
				die("Redis object error");
			}
		
		}
	}