<?php
	/*
	 Parses a USFX bible translation file to add to our translation DB
	 */

	class BfoxXml
	{
		private $reader;

		private $elements;

		private $element_cbs = array();
		private $element_stack = array();

		protected $open_element_cb;
		protected $close_element_cb;
		protected $text_element_cb;


		function __construct($schema_file = NULL)
		{
			if (!empty($schema_file))
				$this->load_schema($schema_file);

			$this->reader = new XMLReader();
		}

		/*
		 * PUBLIC FUNCTIONS
		 */

		/**
		 * Sets the open and close callbacks for an element
		 *
		 * @param string $element
		 * @param callback $open_cb
		 * @param callback $close_cb
		 */
		public function set_element_cbs($element, $open_cb, $close_cb = NULL)
		{
			$array = array('is_supported' => TRUE);
			if (is_callable($open_cb)) $array['open_callback'] = $open_cb;
			if (is_callable($close_cb)) $array['close_callback'] = $close_cb;
			$this->modify_element($element, $array);
		}

		/**
		 * Sets the open and close tags for an element
		 *
		 * @param string $element
		 * @param string $new_tag The tag to use for open and close
		 * @param string $new_attributes The attributes to use in the open tag
		 */
		public function set_tag_conv($element, $new_tag, $new_attributes = '')
		{
			$open_tag = $new_tag;
			if (!empty($new_attributes)) $open_tag .= ' ' . $new_attributes;

			$this->modify_element($element, array('is_supported' => TRUE, 'open_tag' => "<$open_tag>", 'close_tag' => "</$new_tag>"));
		}

		/**
		 * Sets the open and close tag for an element that should only have an open tag
		 *
		 * @param string $element
		 * @param string $open_tag
		 */
		public function set_tag_conv_empty($element, $open_tag)
		{
			$this->modify_element($element, array('is_supported' => TRUE, 'open_tag' => "<$open_tag />", 'close_tag' => ''));
		}

		/**
		 * Loads a schema file to store information for elements of the schema
		 *
		 * @param string $schema_file The schema file to read
		 */
		public function load_schema($schema_file)
		{
			$reader = new XMLReader();
			$reader->open($schema_file);

			$elements = array();
			while ($reader->read())
			{
				if ((XMLReader::ELEMENT == $reader->nodeType) && ('element' == $reader->localName))
				{
					$element = $reader->getAttribute('name');
					$elements[$element] = '';
				}
				else if ((XMLReader::ELEMENT == $reader->nodeType) && ('documentation' == $reader->localName))
				{
					$reader->read();
					$elements[$element] = $reader->value;
				}
			}
			$reader->close();

			foreach ($elements as $element => $description)
				$this->add_schema_element($element, $description);
		}

		/**
		 * Reads an XML file
		 *
		 * @param string $file The file name
		 */
		public function read_file($file)
		{
			$this->reader->open($file);

			// Read the file, one xml node at a time
			while ($this->reader->read())
			{
				// Check the XML node type
				if (XMLReader::ELEMENT == $this->reader->nodeType)
				{
					$is_empty = $this->reader->isEmptyElement;
					$element = $this->reader->localName;
					$this->open_element($element);

					if ($is_empty) $this->close_element($element);
				}
				else if (XMLReader::END_ELEMENT == $this->reader->nodeType)
				{
					$this->close_element($this->reader->localName);
				}
				else if (XMLReader::TEXT == $this->reader->nodeType)
				{
					if (is_callable($this->text_element_cb)) call_user_func($this->text_element_cb, preg_replace('/\s+/', ' ', $this->reader->value));
				}
				unset($this->element);
				unset($this->attributes);
			}

			$this->reader->close();
		}

		/**
		 * Returns the value for an attribute for the element that is currently opened during a file read.
		 *
		 * @param unknown_type $attr
		 * @return unknown
		 */
		public function get_attribute($attr)
		{
			$value = $this->attributes[$attr];
			$this->used_attributes[$attr] = TRUE;
			return $value;
		}

		/**
		 * Sets an attribute to be unused
		 *
		 * @param unknown_type $attr
		 */
		public function invalidate_attribute($attr)
		{
			$this->used_attributes[$attr] = FALSE;
		}

		/*
		 * PRIVATE FUNCTIONS
		 */

		/**
		 * Adds an element from the schema to the element list
		 *
		 * @param string $element
		 * @param string $description
		 */
		private function add_schema_element($element, $description)
		{
			$array = array(
				'description' => $description,
				'schema' => TRUE,
			);
			$this->modify_element($element, $array);
		}

		/**
		 * Adds an element which was not found in the schema to the element list
		 *
		 * @param string $element
		 */
		private function add_nonschema_element($element)
		{
			$this->modify_element($element, array('schema' => FALSE));
		}

		/**
		 * Modifies an element's entry in the element list
		 *
		 * @param string $element
		 * @param array $mod_array The modifications for the element
		 */
		private function modify_element($element, $mod_array)
		{
			if (!isset($this->elements[$element]))
			{
				$this->elements[$element] = array(
					'unused_attributes' => array()
				);
			}

			$this->elements[$element] = array_merge($this->elements[$element], $mod_array);
		}

		/**
		 * Called when an open element is read
		 *
		 * @param string $element
		 */
		private function open_element($element)
		{
			// If we don't already have info for this element, we should add it
			if (!isset($this->elements[$element])) $this->add_nonschema_element($element);

			// Count how many times we open this element
			$this->elements[$element]['open_count']++;

			$data = array(
				'element' => $element,
				'open_tag' => $this->elements[$element]['open_tag'],
				'close_tag' => $this->elements[$element]['close_tag']
			);

			// Read any attributes for this element
			$this->read_attributes($element);

			// Call an open callback if set
			$cb = $this->elements[$element]['open_callback'];
			if (is_callable($cb)) $data = call_user_func($cb, $data);

			// We're done with the attributes
			$this->clear_attributes($element);

			array_push($this->element_stack, $data);

			// Call the open tag callback
			if (is_callable($this->open_element_cb)) call_user_func($this->open_element_cb, $data);
		}

		/**
		 * Called when a close element is read
		 *
		 * @param string $element
		 */
		private function close_element($element)
		{
			if (!isset($this->elements[$element])) $this->add_nonschema_element($element);

			$this->elements[$element]['close_count']++;

			$data = array_pop($this->element_stack);

			// Call an open callback if set
			$cb = $this->elements[$element]['close_callback'];
			if (is_callable($cb)) call_user_func($cb);

			// Call the close tag callback
			if (is_callable($this->close_element_cb)) call_user_func($this->close_element_cb, $data);
		}

		/**
		 * Reads all the attributes for an element
		 *
		 * @param string $element
		 */
		private function read_attributes($element)
		{
			$this->attributes = array();
			$this->used_attributes = array();

			while ($this->reader->moveToNextAttribute()) $this->attributes[$this->reader->localName] = $this->reader->value;
		}

		/**
		 * Clears all the attributes for an element
		 *
		 * @param unknown_type $element
		 */
		private function clear_attributes($element)
		{
			// Save the attribute counts
			foreach ($this->attributes as $attribute => $value)
			{
				if (!$this->used_attributes[$attribute])
				{
					if (!isset($this->elements[$element]['unused_attributes'][$attribute])) $this->elements[$element]['unused_attributes'][$attribute] = array();
					$this->elements[$element]['unused_attributes'][$attribute][$value]++;
				}
			}
			unset($this->attributes);
			unset($this->used_attributes);
		}

		/*
		 * STATISTIC FUNCTIONS
		 */

		function get_all_elements()
		{
			return array_keys($this->elements);
		}

		function get_key_value_elements($key, $value)
		{
			$list = array();
			foreach ($this->elements as $element => $data)
				if ($value == $data[$key]) $list[] = $element;
			return $list;
		}

		function get_unsupported()
		{
			$list = array();
			foreach ($this->elements as $element => $data)
				if (!$data['is_supported']) $list[] = $element;
			return $list;
		}

		function get_supported()
		{
			$list = array();
			foreach ($this->elements as $element => $data)
				if ($data['is_supported']) $list[] = $element;
			return $list;
		}

		function list_elements($list, $header = '')
		{
			foreach ($list as $element)
			{
				if (!empty($content)) $content .= ', ';
				$content .= "<a href='#$element'>$element</a>";
			}
			if (!empty($header)) $content = "<h3>$header</h3>" . $content;
			return $content;
		}

		function get_all_element_content()
		{
			foreach ($this->elements as $element => $data)
			{
				$content .= "<p id='$element'><strong>$element</strong><br/>";
				foreach ($data as $key => $value)
				{
					$content .= "$key: ";
					if (is_string($value)) $content .= htmlspecialchars($value);
					else if (is_bool($value)) $content .= ($value) ? 'TRUE' : 'FALSE';
					else if (is_int($value)) $content .= $value;
					else $content .= 'unknown';
					$content .= '<br/>';
				}
				$content .= '</p>';
			}
			return $content;
		}

		function echo_stats()
		{
			if (0 < count($this->verses))
			{
				echo "\nVerses:\n";
				foreach ($this->verses as $element => $count)
					echo "$element:$count\n";
			}
		}

		function get_stat_attr_str($attr_counts)
		{
			$str = '';
			$total = 0;
			foreach ($attr_counts as $attr => $values)
			{
				$attr_str = '';
				$attr_total = 0;
				foreach ($values as $value => $count)
				{
					$attr_str .= "$value ($count), ";
					$attr_total += $count;
				}
				if (0 < $attr_total)
				{
					$str .= "<li>$attr:$attr_total\n$attr_str</li>";
					$total += $attr_total;
				}
			}

			return "Attributes:$total<ul>$str</ul>";
		}

		function get_stat_str()
		{
			foreach ($this->elements as $element => $stats)
			{
				$is_used = (0 < $stats['count']);
				$is_supported = !empty($stats['open_callback']);
				$has_unused_attributes = !empty($stats['unused_attributes']);

				if ($is_used && (!$is_supported || $has_unused_attributes))
				{
					$str .= "\n\n<p><strong>Element:</strong> $element<br/>";
					$str .= $stats['description'] . '<br/>';
					$str .= '<strong>Supported:</strong> ' . ($is_supported ? 'true' : 'false') . '<br/>';
					$str .= "<strong>Count:</strong> " . $stats['count'] . "<br/>";
					if ($is_supported) $str .= 'Unused ' . $this->get_stat_attr_str($stats['unused_attributes']);
//					$str .= "\n<strong>Example:</strong> " . $stats['example'] . "<br/>";
					$str .= "</p>";
				}
			}
			echo $str;
		}

	}

	class BfoxBibleText extends BfoxXml
	{
		private $table_name = '';
		private $vs = array();
		private $verse_tag_stack = array();
		public $verse_samples = array();

		function __construct($schema_file = NULL)
		{
			parent::__construct($schema_file);

			// Sets the callbacks
			$this->open_element_cb = array($this, 'add_open_tag');
			$this->close_element_cb = array($this, 'add_close_tag');
			$this->text_element_cb = array($this, 'add_text');
		}

		function set_table_name($table_name)
		{
			$this->table_name = $table_name;
		}

		/**
		 * Sets the elements delimiting books, chapters, and verses
		 *
		 * @param unknown_type $book
		 * @param unknown_type $chapter
		 * @param unknown_type $verse
		 */
		function set_bcv($book, $chapter, $verse)
		{
			$this->set_element_cbs($book, array($this, 'open_book'));
			$this->set_element_cbs($chapter, array($this, 'open_chapter'));
			$this->set_element_cbs($verse, array($this, 'open_verse'));
		}

		/**
		 * Sets hidden tags
		 *
		 * @param array $tags
		 */
		function set_hidden_tags($tags)
		{
			foreach ((array) $tags as $tag) $this->set_tag_conv($tag, 'div', 'class="bible-hidden" style="display:none"');
		}

		function add_open_tag($data)
		{
			if (isset($this->vs['verse']))
			{
				$this->vs['text'] .= $data['open_tag'];
				array_push($this->verse_tag_stack, $data);
			}
		}

		function add_close_tag($data = NULL)
		{
			if (isset($this->vs['verse']))
			{
				$data = array_pop($this->verse_tag_stack);
				$this->vs['text'] .= $data['close_tag'];
			}
		}

		function add_text($text)
		{
			$this->vs['text'] .= $text;
		}

		/**
		 * Saves verse text to the DB and resets the verse data for the next verse
		 *
		 */
		private function save_verse()
		{
			$open_tags = array();
			if (!empty($this->vs) && !empty($this->vs['text']))
			{
				// Store the currently open tags, so we can close them at the end of this verse,
				// and reopen them at the beginning of the next verse
				$open_tags = $this->verse_tag_stack;

				// Close any open tags
				$count = count($open_tags);
				for ($i = 0; $i < $count; $i++) $this->add_close_tag();

				// Save some stat data
				$id = 'book'.$this->vs['book'].' '.$this->vs['chapter'].':'.$this->vs['verse'];
				if (15 > count($this->verse_samples))
				{
					// Save the verse
					$this->verse_samples[$id] = htmlspecialchars($this->vs['text']);
				}

				// Save the verse data to the DB
				if (!empty($this->table_name) && isset($this->vs['book']))
				{
					$vector = new BibleRefVector(array($this->vs['book'], $this->vs['chapter'], $this->vs['verse']));
					bfox_translation_update_verse($this->table_name, $vector, $this->vs['text']);
				}
			}

			// Reset the verse text
			$this->vs['text'] = '';
			$this->verse_tag_stack = array();

			// Reopen any tags that we had to close
			foreach ($open_tags as $tag) $this->add_open_tag($tag);
		}

		function open_book($data)
		{
			$this->save_verse();
			unset($this->vs['book']);

			// Try to get the book's name from the 'id' attribute
			// If we can't the ID attribute is invalid
			if ($book_id = bfox_find_book_id($this->get_attribute('id')))
			{
				$this->vs['book'] = $book_id;
				$this->vs['book_name'] = bfox_get_book_name($book_id);
			}
			else
			{
				$this->invalidate_attribute('id');
				$this->vs['book_name'] = 'Chapter';
			}

			$this->vs['chapter'] = 0;
			$this->vs['verse'] = 0;

			return $data;
		}

		function open_chapter($data)
		{
			$this->save_verse();

			// The id attribute holds the chapter ID, but we are just incrementing anyway, so we don't need it
			$this->get_attribute('id');

			$this->vs['chapter']++;
			$this->vs['verse'] = 0;
			$this->vs['text'] .= '<p class="bible-chapter-id">' . $this->vs['book_name'] . ' ' . $this->vs['chapter'] . '</p>';

			return $data;
		}

		function open_verse($data)
		{
			$this->save_verse();
			$this->verse_tag_stack = array();

			// The id attribute holds the verse ID, but we are just incrementing anyway, so we don't need it
			$this->get_attribute('id');

			$this->vs['verse']++;

			return $data;
		}

	}

	class BfoxUsfx extends BfoxBibleText
	{
		function __construct()
		{
			parent::__construct(BFOX_DIR . '/usfx-2005-09-08.xsd.xml');

			$this->set_bcv('book', 'c', 'v');
			$this->set_element_cbs('p', array($this, 'open_paragraph'));
			$this->set_element_cbs('q', array($this, 'open_poetry'));
			$this->set_tag_conv('wj', 'em', 'class="bible-jesus"');
			$this->set_tag_conv('f', 'footnote');
			$this->set_tag_conv('add', 'em', 'class="bible-added-words"');
			$this->set_tag_conv('d', 'p', 'class="bible-psalm-desc"');
			$this->set_tag_conv('s', 'h3');
			$this->set_tag_conv_empty('b', 'br class="bible-poetry"');
			$this->set_hidden_tags(array('id', 'h', 'ide'));
			$this->set_tag_conv('usfx', 'div');
		}

		function open_paragraph($data)
		{
			$sfm = $this->get_attribute('sfm');
			$style = $this->get_attribute('style');

			switch ($sfm)
			{
				case 'mt':
					$is_valid = ('Book Title' == $style);
					$data['open_tag'] = '<h2>';
					$data['close_tag'] = '</h2>';
					break;
				default:
					$is_valid = FALSE;
					$data['open_tag'] = '';
					$data['close_tag'] = '<br class="bible_end_p" />';
					break;
			}

			if (!$is_valid)
			{
				$this->invalidate_attribute('sfm');
				$this->invalidate_attribute('style');
			}

			return $data;
		}

		function open_poetry($data)
		{
			$data['open_tag'] = '';
			$data['close_tag'] = '<br class="bible_end_p" />';

			$level = (int) $this->get_attribute('level');

			if ((1 != $level) && (2 != $level))
				$this->invalidate_attribute('level');

			for ($index = 0; $index < $level; $index++)
				$data['open_tag'] .= '&nbsp;';
		}

		function open_tag_conv()
		{
			// Ignoring the following attributes
			switch ($element)
			{
				case 'id':
					$this->get_attribute('id');
					break;
				case 'usfx':
					$this->get_attribute('ns0');
					$this->get_attribute('xsi');
					$this->get_attribute('noNamespaceSchemaLocation');
					break;
				case 'ide':
					$this->get_attribute('charset');
					break;
				case 'f':
					$this->get_attribute('caller');
					break;
				case 's':
					$this->get_attribute('level');
					break;
			}

			$tag = $this->tag_conv[$this->element][0];
			$attr = $this->tag_conv[$this->element][1];
			if (!empty($attr)) $tag .= ' ' . $attr;
			$this->vs['text'] .= "<$tag>";
		}


	}

	function bfox_usfx_install($table_name, $file)
	{
		$usfx = new BfoxUsfx();
		$usfx->set_table_name($table_name);
		$usfx->read_file($file);
	}

	function bfox_usfx_menu()
	{
		$usfx = new BfoxUsfx();
		$usfx->read_file(BFOX_TRANSLATIONS_DIR . '/asv-usfx.xml');
		$all = $usfx->get_all_elements();

		$schema = $usfx->get_key_value_elements('schema', TRUE);
		$nonschema = array_diff($all, $schema);

		$supported = $usfx->get_key_value_elements('is_supported', TRUE);
		$unsupported = array_diff($all, $supported);

		?>
		<div class="wrap">
			<h2>Verse Samples</h2>
			<?php foreach ($usfx->verse_samples as $id => $sample) echo "$id: $sample<br/>"; ?>
			<h2>Element Overviews</h2>
			<?php echo $usfx->list_elements($all, 'All') ?>
			<?php echo $usfx->list_elements($schema, 'Schema') ?>
			<?php echo $usfx->list_elements($nonschema, 'Non-Schema') ?>
			<?php echo $usfx->list_elements($supported, 'Supported') ?>
			<?php echo $usfx->list_elements($unsupported, 'Unsupported') ?>
			<br/><br/>
			<h2>Element Details</h2>
			<?php echo $usfx->get_all_element_content() ?>
		</div>
		<?php
//		$usfx->read_file(BFOX_TRANSLATIONS_DIR . '/web-usfx.xml');
//		$usfx->get_stat_str();
//		$usfx->echo_stats();
	}

?>
