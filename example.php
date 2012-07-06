<?php
/*

	Basic 'static' usage. Stores all three supported types, retrieves them.
*/



/* 

	Basic use, store a string to a key value 

*/

	print_r(noRedis::put('test_string','test'));

/* 

	Stores an 1 dimensional assoc. array (or object) as a hash; embedded arrays/objects are serialized 

*/


	$example_assoc_array = array('item1'=>'here is my data','item2'=>'here is items 2 data');

	print_r(noRedis::put($example_assoc_array,'test'));


/* 

	Stores 1 dimensional enumerated array as list; embedded array/objects are serialized

*/


	$example_list_data = array(0=>'item 0',1=>'item 1',2=>'item 2');

	print_r(noRedis::put($example_list_data,'test3'));

/*

	Getting back all data stored using the same method (get)

*/


	echo "\n A redis string \n";
	print_r(noRedis::get('test'));
	
	echo "\n A redis hash \n";
	print_r(noRedis::get('test2'));
	
	echo "\n A redis list \n";
	print_r(noRedis::get('test3'));
