<?php
/*

	Config files should function as interfaces. Create a new interface
	and let whatever class/interface that needs access to it inherit it/extend it.

	Defines are really slow (compared to interfaces)
	
	If you are using noPhpredis this inteface is provided as a skelton
	to begin storing specific connection variables/etc as they are required
	
	You may run noPhpredis alongside other databases without any serious
	issue (just keep an eye on name spacing)
	
*/

interface noSqlCRUD extends noSqlConfig
{
    public function get($id,$database);
    public function put($data,$id,$database);
// reorder and put opt before database ...
    public function view($name,$key,$database,$opt);
    public function update($id,$data,$database);
    public function delete($id,$opt,$database);
}

interface noSqlConfig{
	/* 
	
	 store sql config related stuff here depending on your 
	 db type (couch,mongo) becomes accessisble everywhere within object scope as self::-const-
	 database name, url if using couchCurl self::mongodb
	
	*/


	// application prefix (for cookies mostly)
	const apf = 'noC';
	// this was originally for moniCookie to authenticate sessions
	const user_db = 'noC_users';

	// for a formula to modulate time values for creating unique keys
	// that can be used to help prevent similar records from being inserted
	const key_time_out = 5;
}