<?php
	error_reporting(E_ALL | E_STRICT);

	require_once 'dbqb.php';
	
	$insert['firstname']	= 'Roman';
	$insert['middlename']	= 'rhrn';
	$insert['lastname']	= 'Nesterov';
	$insert['created']	= date('Y-m-d H:i:s');

	$sql[] = DBQB::Insert('users')
			->data($insert)
			->sql();

	#output: INSERT INTO `users` (`firstname`, `middlename`, `lastname`, `created`) VALUES('Roman', 'rhrn', 'Nesterov', '2012-01-12 19:21:49'); 

	$sql[] = DBQB::Select('users')
			->fields('users.*', 'profiles.*')
			->fields(array('users.name' => 'user_name'))
			->where('id', '=', 10)
			->join('profiles', 'LEFT')
				->on('profiles.user_id', '=', 'users.id')
			->sql();

	#output: SELECT `users`.*, `profiles`.* FROM `users` LEFT JOIN `profiles` ON (`profiles`.`user_id` = `users`.`id`) WHERE `id` = '10';


	$update['twitter']	= 'rhrn';
	$update['updated']	= date('Y-m-d H:i:s');

	$sql[] = DBQB::Update('profiles')
			->set($update)
			->set('gender', 'male')
			->where('user_id', '=', 10)
			->limit(1)
			->sql();

	#output: UPDATE `profiles` SET `twitter` = 'rhrn', `updated` = '2012-01-13 17:25:13', `gender` = 'male' WHERE `user_id` = '10' LIMIT 1;


	$sql[] = DBQB::Delete('messages')
			->where('messages.id', '=', 10)
			->order('id', 'ASC')
			->limit(1)
			->sql();

	#output: DELETE FROM `messages` WHERE `messages`.`id` = '10' ORDER BY `id` ASC LIMIT 1;

	$sql[] = DBQB::Select('users')
		->fields('users.firstname')
		->fields(array('some' => 'thing', 'ololo' => 'realni'))
		->fields(DBQB::Pure('NOW() AS `now`'), DBQB::Pure('COUNT(*) AS `count`'))
		->sql();

	#output: SELECT `users`.`firstname`, `qwe` AS `sum`, `qweq` AS `ccc`, NOW() AS `now`, COUNT(*) AS `count` FROM `users`;

	$message1 = array('id' => 1, 'message' => 'hello');
	$message2 = array('id' => 2, 'message' => 'world');

	$sql[] = DBQB::Insert('messages')
			->data($message1)
			->data($message2)
			->sql();

	#output: INSERT INTO `messages` (`id`,`message`) VALUES ('1', 'hello'), ('2', 'world');

	echo implode("\n", $sql);
