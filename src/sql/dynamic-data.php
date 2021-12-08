<?php

//--------------------------------------------------
// Allow list

	//--------------------------------------------------
	// Unsafe input

		$field_unsafe = (string) ($_GET['field'] ?? NULL);

	//--------------------------------------------------
	// Library

		/**
		 * @param literal-string $input
		 */
		function require_literal_string(string $input): void {
			echo $input . "\n";
		}

	//--------------------------------------------------
	// Example 1

		$fields = [
				'name'    => 'u.full_name',
				'email'   => 'u.email_address',
				'address' => 'u.postal_address',
			];

		$sql = '... ORDER BY ' . ($fields[$field_unsafe] ?? 'u.full_name');

		require_literal_string($sql);

	//--------------------------------------------------
	// Example 2

		$fields = ['name', 'address', 'email'];

		$field_id = array_search($field_unsafe, $fields);

		$sql = '... ORDER BY ' . $fields[$field_id];

		require_literal_string($sql);

//--------------------------------------------------
// Allow escaped

	//--------------------------------------------------
	// Library

			// The only important bit is that $identifiers is
			// applied after $sql is checked to be a `literal-string`

		class db_identifiers {

			/**
			 * @param literal-string $sql
			 * @param array<int, string|int> $parameters
			 * @param array<string, string> $identifiers
			 */
			public function query($sql, $parameters = [], $identifiers = []): void {

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
			public function placeholders(int $count): string {
				$sql = '?';
				for ($k = 1; $k < $count; $k++) {
					$sql .= ',?';
				}
				return $sql;
			}

		}

		$db = new db_identifiers();

	//--------------------------------------------------
	// Using

		$parameters = [];

		$where_sql = 't.deleted IS NULL';


		$ids = [1, 2, 3]; // Imagine these are from a set of <input type="checkbox" name="ids[]" value="1" />
		$parameters = array_merge($parameters, $ids);
		$where_sql .= ' AND
				t.id IN (' . $db->placeholders(count($ids)) . ')';


		$sql = '
			SELECT
				t.id,
				t.{select_field}
			FROM
				{from_table} AS t
			WHERE
				' . $where_sql . '
			ORDER BY
				{order_field}';

		$identifiers = [
				'select_field' => (string) ($_GET['select_field'] ?? 'name'),
				'from_table'   => (string) ($_GET['from_table']   ?? 'user'),
				'order_field'  => (string) ($_GET['order_field']  ?? 'email'),
			];


		$db->query($sql, $parameters, $identifiers);

			// This query is still dangerous, as it probably allows the
			// attacker to read details they shouldn't be allowed to.
			// But purely on a technical point of view, it's not an
			// Injection Vulnerability.

 ?>