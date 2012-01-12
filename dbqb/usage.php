<?php
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
			->sql();

	#output: UPDATE `profiles` SET `twitter` = 'rhrn', `updated` = '2012-01-12 19:42:48', `gender` = 'male' WHERE `user_id` = '10';


	$sql[] = DBQB::Delete('messages')
			->where('messages.id', '=', 10)
			->sql();

	#output: DELETE FROM `messages` WHERE `messages`.`id` = '10';

	echo implode("\n", $sql);
