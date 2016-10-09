# guid
GUID generator, which allow to store some data in GUID (code and decode).
Create GUID string and store upto 8 bytes of positive numeric data in hexadecimal representation in it.


CONTENTS
--------

1. CASES OF USAGE
2. PRINCIPLE OF GENERATION
	2.1. GUID code
	2.2. GUID decode
3. PUBLIC METHODS
	3.1. GUID::init()
	3.2. GUID::code()
	3.3. GUID::decode()


1. CASES OF USAGE
-----------------

You can use it fo generate unique identifiers, which store some data.
It is useful for shards of huge database. It can be used as key field for sharding logic also.

Some times it's good idea to store some relations in GUID. For example:
Schema of GUID (for more detail see section 3.1.):
<?php
	$schema = array(
		'type' => 2,
		'id_master' => 6,
		'id_second' => 6
	);
?>
GUID generation table:
Entity					type		id_master			id_second					Comment
----------------------------------------------------------------------------------------------------------------------------------
Comapny					1				<company_id>	0
User						2				<user_id>			<company_id>			If you decode user GUID, you'll get both userID and companyID.
Messages group	10			<user_id1>		<user_id2>				GUID of chat room. It's good idea to make user_id1 lower than user_id2.
Message					11			<message_id>	<user_id>					If you decode message GUID, you'll get both messageID and userID (author).


2. PRINCIPLE OF GENERATION
--------------------------

	2.1. GUID code
	
	- Get an array of data.
	- Convert all values from DEC to HEX and normolize to its lengths (add zeros to make proper length)
	- Create data string (implode all normolized values to one string)
	- Normolize data string to length of 16 digits (add zeros to make proper length)
	- Get cehck sum of data string - CRC32 based on custom polynomials (gets from your secret key)
	- Add check sum to data string
	- Get hash - md5() of check sum
	- XOR data string and hash
	- Replace last 8 digits to check sum
	- Format string to guid like string
	
	2.2. GUID decode
	- Get guid and format it to hexadecimal string
	- Get last 8 digits - check_sum_1
	- Get hash - md5() of check sum
	- XOR data string and hash
	- Get check sum (check_sum_2) and data string from XOR result
	- Check check_sum_1 and check_sum_2 and check sum of data string
	- Fill data array with values from data string
	- Conver all values from HEX to DEC


3. PUBLIC METHODS
-----------------

GUID class is abstract, it provides 3 public and static methods.

	3.1. GUID::init($schema, $key)

	Initialization of class. Should be called before other methods.

	$schema - associated array of data schema. Contains pairs 'filed => length':
		filed - any valid associated array key.
		lenght - int value, length of field in HEX digits:
			1 - field could contain one HEX digit (0-15 DEC values);
			2 - field could contain two HEX digit (0-255 DEC values);
			...
			Total length of all fields should be 16 digits (8 bytes).

	$key - your secret key.

	Examples of schemas:
	<?php
		$schema = array(
			'type' => 2,				/* type of GUID, contain values 0 - 255 */
			'id_master' => 6,		/* id field, contain values 0 - 16777215 */
			'id_second' => 6		/* id field, contain values 0 - 16777215 */
		);
		
		$schema = array(
			'type' => 1,				/* type of GUID, contain values 0 - 15 */
			'id_1' => 4,				/* id field, contain values 0 - 65535 */
			'id_2' => 4,				/* id field, contain values 0 - 65535 */
			'id_3' => 4,				/* id field, contain values 0 - 65535 */
			'param' => 2,				/* some param field, contain values 0 - 255 */
			'flags' => 1				/* flags field, contain 4 flags in dec 0 - 15 (0000, 0001, 0010, ..., 1111) */
		);
	?>

	
	3.2. GUID::code($data)

	Generates GUID using $data.

	$data - associated array. Keys of array equal to data schema. Values - exact value of the field.
		If you skip some keys, they will get 0 values.
		If you use values lager than described in schema, they will be trancated. For examle:
			<?php
				$schema = array(
					'type' => 1 /* values from 0 - 15*/
				);
			?>
			Type value 300 (HEX: 0x12C) will be trancated to 12 (HEX: 0xC).

	Returns GUID or false (if class wasn't initialized or something very strange happend :D).

	
	3.3. GUID::decode($guid)
	
	Gets data from GUID.
	
	$guid - GUID string or just hexadecimal string (length 32).

	Returns data array or false (if class wasn't initialized or GUID fails with check sum).

