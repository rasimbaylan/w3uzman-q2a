<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/event-logger/qa-event-logger.php
	Description: Event module class for event logger plugin


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

class qa_event_logger
{
	public function init_queries($table_list)
	{
		if (qa_opt('event_logger_to_database')) {
			$tablename = qa_db_add_table_prefix('eventlog');

			if (!in_array($tablename, $table_list)) {
				// table does not exist, so create it
				require_once QA_INCLUDE_DIR . 'app/users.php';
				require_once QA_INCLUDE_DIR . 'db/maxima.php';

				return 'CREATE TABLE ^eventlog (' .
					'datetime DATETIME NOT NULL,' .
					'ipaddress VARCHAR (45) CHARACTER SET ascii,' .
					'userid ' . qa_get_mysql_user_column_type() . ',' .
					'handle VARCHAR(' . QA_DB_MAX_HANDLE_LENGTH . '),' .
					'cookieid BIGINT UNSIGNED,' .
					'event VARCHAR (20) CHARACTER SET ascii NOT NULL,' .
					'params VARCHAR (800) NOT NULL,' .
					'KEY datetime (datetime),' .
					'KEY ipaddress (ipaddress),' .
					'KEY userid (userid),' .
					'KEY event (event)' .
					') ENGINE=MyISAM DEFAULT CHARSET=utf8';
			} else {
				// table exists: check it has the correct schema
				$column = qa_db_read_one_assoc(qa_db_query_sub('SHOW COLUMNS FROM ^eventlog WHERE Field="ipaddress"'));
				if (strtolower($column['Type']) !== 'varchar(45)') {
					// upgrade to handle IPv6
					return 'ALTER TABLE ^eventlog MODIFY ipaddress VARCHAR(45) CHARACTER SET ascii';
				}
			}
		}

		return array();
	}


	public function admin_form(&$qa_content)
	{
		$saved = false;
		$directory = (string)qa_opt('event_logger_directory');
		$tofiles = (bool)qa_opt('event_logger_to_files');
		$todatabase = (bool)qa_opt('event_logger_to_database');
		$includeheader = !qa_opt('event_logger_hide_header');
		$error = null;

		if (qa_clicked('event_logger_save_button')) {
			$directory = trim((string)qa_post_text('event_logger_directory_field'));
			$tofiles = (bool)qa_post_text('event_logger_to_files_field');
			$todatabase = (bool)qa_post_text('event_logger_to_database_field');
			$includeheader = (bool)qa_post_text('event_logger_hide_header_field');
			$error = $tofiles ? $this->directory_error($directory) : null;

			// Validate the complete form before persisting any of its options.
			if ($error === null) {
				qa_opt('event_logger_to_database', (int)$todatabase);
				qa_opt('event_logger_to_files', (int)$tofiles);
				qa_opt('event_logger_directory', $directory);
				qa_opt('event_logger_hide_header', !$includeheader);
				$saved = true;
			}
		}

		if ($tofiles && $error === null)
			$error = $this->directory_error($directory);
		$note = 'Web sunucusunun yazabildiği, herkese açık web dizininin dışında bir klasör kullanın. Günlükler IP adresi ve kullanıcı bilgisi içerebilir.';

		// Create the form for display

		qa_set_display_rules($qa_content, array(
			'event_logger_directory_display' => 'event_logger_to_files_field',
			'event_logger_hide_header_display' => 'event_logger_to_files_field',
		));

		return array(
			'ok' => $saved ? 'Olay günlüğü ayarları kaydedildi.' : null,

			'fields' => array(
				array(
					'label' => 'Olayları <code>' . qa_html(QA_MYSQL_TABLE_PREFIX) . 'eventlog</code> veritabanı tablosuna kaydet',
					'tags' => 'name="event_logger_to_database_field"',
					'value' => $todatabase,
					'type' => 'checkbox',
				),

				array(
					'label' => 'Olayları günlük dosyalarına kaydet',
					'tags' => 'name="event_logger_to_files_field" id="event_logger_to_files_field"',
					'value' => $tofiles,
					'type' => 'checkbox',
				),

				array(
					'id' => 'event_logger_directory_display',
					'label' => 'Günlük klasörünün tam yolu:',
					'value' => qa_html($directory),
					'tags' => 'name="event_logger_directory_field"',
					'note' => $note,
					'error' => $error === null ? null : qa_html($error),
				),

				array(
					'id' => 'event_logger_hide_header_display',
					'label' => 'Her günlük dosyasının başına sütun başlıklarını ekle',
					'type' => 'checkbox',
					'tags' => 'name="event_logger_hide_header_field"',
					'value' => $includeheader,
				),
			),

			'buttons' => array(
				array(
					'label' => 'Değişiklikleri kaydet',
					'tags' => 'name="event_logger_save_button"',
				),
			),
		);
	}

	private function directory_error($directory)
	{
		if ($directory === '' || strpos($directory, "\0") !== false)
			return 'Dosyaya kayıt için geçerli bir günlük klasörünün tam yolunu girin.';
		if (!file_exists($directory))
			return 'Bu klasör bulunamadı. Var olan bir klasörün tam yolunu girin.';
		if (!is_dir($directory))
			return 'Bu yol bir dosyaya ait. Bir klasörün tam yolunu girin.';
		if (!is_writable($directory))
			return 'Web sunucusu bu klasöre yazamıyor. Yazılabilir bir klasör seçin veya sunucu yöneticinizle görüşün.';
		return null;
	}

	private function line_value($value)
	{
		return strtr((string)$value, "\t\n\r", '   ');
	}

	public function value_to_text($value)
	{
		require_once QA_INCLUDE_DIR . 'util/string.php';

		if (is_array($value))
			$text = 'array(' . count($value) . ')';
		elseif (is_object($value))
			$text = 'object(' . get_class($value) . ')';
		else
			$text = (string)$value;

		if (qa_strlen($text) > 40)
			$text = qa_substr($text, 0, 38) . '...';
		return $this->line_value($text);
	}


	public function process_event($event, $userid, $handle, $cookieid, $params)
	{
		if (qa_opt('event_logger_to_database')) {
			$paramstring = '';

			foreach ($params as $key => $value) {
				$paramstring .= (strlen($paramstring) ? "\t" : '') . $this->line_value($key) . '=' . $this->value_to_text($value);
			}

			qa_db_query_sub(
				'INSERT INTO ^eventlog (datetime, ipaddress, userid, handle, cookieid, event, params) ' .
				'VALUES (NOW(), $, $, $, #, $, $)',
				qa_remote_ip_address(), $userid, $handle, $cookieid, $event, $paramstring
			);
		}

		if (qa_opt('event_logger_to_files')) {
			// Substitute some placeholders if certain information is missing
			if ((string)$userid === '')
				$userid = 'no_userid';

			if ((string)$handle === '')
				$handle = 'no_handle';

			if ((string)$cookieid === '')
				$cookieid = 'no_cookieid';

			$ip = qa_remote_ip_address();
			if ((string)$ip === '')
				$ip = 'no_ipaddress';

			// Build the log file line to be written

			$fixedfields = array(
				'Date' => date('Y\-m\-d'),
				'Time' => date('H\:i\:s'),
				'IPaddress' => $ip,
				'UserID' => $userid,
				'Username' => $handle,
				'CookieID' => $cookieid,
				'Event' => $event,
			);

			$fields = array_map(array($this, 'line_value'), $fixedfields);

			foreach ($params as $key => $value) {
				$fields['param_' . $key] = $this->line_value($key) . '=' . $this->value_to_text($value);
			}

			$string = implode("\t", $fields);

			// Build the full path and file name

			$directory = trim((string)qa_opt('event_logger_directory'));
			// Never fall back to the filesystem root when the directory is unset.
			if ($this->directory_error($directory) !== null)
				return;
			$directory = rtrim($directory, '/\\') . '/';

			$filename = $directory . 'q2a-log-' . date('Y\-m\-d') . '.txt';

			// Open, lock, write, unlock, close (to prevent interference between multiple writes)

			$file = @fopen($filename, 'ab');

			if (is_resource($file)) {
				if (flock($file, LOCK_EX)) {
					// Inspect size while holding the lock, not before another writer finishes.
					$stat = fstat($file);
					if ($stat !== false && $stat['size'] === 0 && !qa_opt('event_logger_hide_header')) {
						$string = "Question2Answer " . QA_VERSION . " log file generated by Event Logger plugin.\n" .
							"This file is formatted as tab-delimited text with UTF-8 encoding.\n\n" .
							implode("\t", array_keys($fixedfields)) . "\textras...\n\n" . $string;
					}

					fwrite($file, $string . "\n");
					flock($file, LOCK_UN);
				}

				fclose($file);
			}
		}
	}
}
