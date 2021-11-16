<?php

//--------------------------------------------------
// The library

	class db {

		/**
		 * @param literal-string $sql
		 * @param array<int, string|int> $parameters
		 * @param array<string, string> $identifiers
		 */
		function query($sql, $parameters = [], $identifiers = []): void {

			foreach ($identifiers as $name => $value) {
				if (!preg_match('/^[a-z0-9_]+$/', strval($name))) {
					throw new Exception('Invalid identifier name "' . $name . '"');
				} else if (!preg_match('/^[a-z0-9_]+$/', $value)) {
					throw new Exception('Invalid identifier value "' . $value . '"');
				} else {
					$sql = str_replace('{' . $name . '}', '`' . $value . '`', $sql);
				}
			}

			echo $sql . "\n\n";
			print_r($parameters);

		}

		/**
		 * @return literal-string
		 */
		function placeholders(int $count): string {
			$sql = '?';
			for ($k = 1; $k < $count; $k++) {
				$sql .= ',?';
			}
			return $sql;
		}

	}

	class unsafe_value {

		private string $value = '';

		function __construct(string $unsafe_value) {
			$this->value = $unsafe_value;
		}

		function __toString(): string {
			return $this->value;
		}
	}

//--------------------------------------------------
// Example 1


	$db = new db();

	$id = (string) ($_GET['id'] ?? '123');

	$id = strtolower($id); // Ensure it's not seen as a literal-string

	$db->query('SELECT name FROM user WHERE id = ?', [$id]);

	// $db->query('SELECT name FROM user WHERE id = ' . $id); // INSECURE


//--------------------------------------------------
// Example 2


	$parameters = [];

	$where_sql = 'u.deleted IS NULL';



	$name = (string) ($_GET['name'] ?? 'MyName');
	if ($name) {
		$where_sql .= ' AND u.name LIKE ?';
		$parameters[] = '%' . $name . '%';
	}



	$ids = [1, 2, 3];
	$where_sql .= ' AND u.id IN (' . $db->placeholders(count($ids)) . ')';
	$parameters = array_merge($parameters, $ids);



	$sql = '
		SELECT
			u.name,
			u.email
		FROM
			user AS u
		WHERE
			' . $where_sql;



	$order_by = (string) ($_GET['sort'] ?? 'email');
	$order_fields = ['name', 'email'];
	$order_id = array_search($order_by, $order_fields, true);
	$sql .= '
		ORDER BY
			' . $order_fields[$order_id]; // Limited to known-safe fields.



	$sql .= '
		LIMIT
			?, ?';
	$parameters[] = 0;
	$parameters[] = 3;



	$db->query($sql, $parameters);


//--------------------------------------------------
// Example 3, with identifiers.


	$parameters = [];

	$identifiers = [
			'with_1'  => 'w1',
			'table_1' => 'user',
			'field_1' => 'email',
			'field_2' => 'dob',
		];

	$identifiers = array_map('strtolower', $identifiers); // Ensure they are not literal-string

	$with_sql = '{with_1} AS (SELECT id, name, type, {field_1} as f1, deleted FROM {table_1})';

	$sql = "
		WITH
			$with_sql
		SELECT
			t.name,
			t.f1
		FROM
			{with_1} AS t
		WHERE
			t.type = ? AND
			t.deleted IS NULL";

	$parameters[] = (string) ($_GET['type'] ?? 'admin');

	$db->query($sql, $parameters, $identifiers);


?>