<?php
	require_once('guid.class.php');
	
	/* make it short */
	use OZ\GUID as GUID;
	
	/* SCHEMA - data schema of your guid, see README or guid.class.php for more instructions */
	$GUID_schema = array(
		'type' => 2,
		'field1' => 4,
		'field2' => 4,
		'field3' => 4,
		'flags1' => 1,
		'flags2' => 1
	);
	
	/* KEY - your secret md5() hash */
	$GUID_key = '7116bfe60a4d7393b2151400ef3a67ea';
	
	if(GUID::init($GUID_schema, $GUID_key)) {
		/* some date */
		$data = array(
			'type' => 1,
			'field1' => 123,
			'field2' => 12,
			'field3' => 1,
			'flags1' => 0,	/* 0000 */
			'flags2' => 6		/* 0111 */
		);
		
		/* data > guid */
		echo 'Data to GUID: <br/>';
		$guid1 = GUID::code($data);
		print_r($data);
		echo ' > ' . $guid1;
		echo '<br/>';
		$data['flags2'] = 5; /* 0110, change data for 1 BIN digit */ 
		$guid2 = GUID::code($data);
		print_r($data);
		echo ' > ' . $guid2;
		echo '<br/>';
		
		echo '<br/>';
		echo 'GUID to data: <br/>';
		echo $guid1 . ' > ';
		print_r(GUID::decode($guid1));
		echo '<br/>';
		echo $guid2 . ' > ';
		print_r(GUID::decode($guid2));
		echo '<br/>';

		echo '<br/>';
		echo 'Fake GUID to data: <br/>';
		$guid3 = '7a368ea2-2eeb-851a-1258-f5dd806f7a08'; /* change last HEX digit */
		$data3 = GUID::decode($guid3);
		echo $guid3 . ' > ';
		print_r(empty($data3) ? 'false' : $data3);
		echo '<br/>';
		$guid3 = '7a368ea2-2eeb-851a-2258-f5dd806f7a09'; /* change one middle HEX digit */
		$data3 = GUID::decode($guid3);
		echo $guid3 . ' > ';
		print_r(empty($data3) ? 'false' : $data3);
		echo '<br/>';
		$guid3 = '7a368ea2-2eec-851a-1258-f5dd806f7a09'; /* change one middle HEX digit */
		$data3 = GUID::decode($guid3);
		echo $guid3 . ' > ';
		print_r(empty($data3) ? 'false' : $data3);
		echo '<br/>';
	}
	else {
		echo 'GUID class is not init';
	}
	