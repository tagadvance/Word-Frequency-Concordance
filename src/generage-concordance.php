#!/usr/bin/php
<?php

/**
 * @author Taggart Spilman <tagspilman@gmail.com>
 */

define('EXPECTED_ARGUMENTS', 2);

if ($argc < EXPECTED_ARGUMENTS) {
	$script_name = $argv[0];
	$message = <<<ERROR
Example usage:
$script_name arbitrary.txt
ERROR;
	println($message, STDERR);
	exit(1);
}

$filename = $argv[1];
if (!is_readable($filename)) {
	println("ERROR: $filename could not be read", STDERR);
	exit(1);
}

$input = file_get_contents($filename);
$words = explode ( $delimiter = ' ', $input );
$sentenceIndex = 1;

$concordance = [ ];
foreach ( $words as $word ) {
	$word = trim($word);
	if (empty($word)) {
		continue;
	}
	
	$is_abbreviation = is_abbreviation($word);
	$is_end_of_sentence = ends_with($word, $needle = '.') && !$is_abbreviation;
	if (!$is_abbreviation) {
		$word = remove_punctuation($word);
	}
	
	$word = strtolower($word);
	if (isset($concordance[$word])) {
		$entry = $concordance[$word];
		$entry->incrementOccurrences();
		$entry->addSentenceIndex($sentenceIndex);
	} else {
		$concordance[$word] = new Word ( $word, $sentenceIndex );
	}
	
	if ($is_end_of_sentence) {
		$sentenceIndex++;
	}
}
ksort($concordance);

foreach ($concordance as $entry) {
	println($entry);
	println();
}

/*
 * CLASSES AND FUNCTIONS
 */

/**
 * 
 * @param string $string
 * @param resource $stream
 */
function println($string = '', $stream = STDOUT) {
	$string .= PHP_EOL;
	if ($stream === null) {
		$stream = fopen('php://output', $mode = 'w');
	}
	fwrite ( $stream , $string );
}

/**
 * 
 * @param string $haystack
 * @param string $needle
 * @return boolean
 * @see http://stackoverflow.com/a/834355/625688
 */
function ends_with($haystack, $needle) {
	$length = strlen ( $needle );
	if ($length == 0) {
		return true;
	}
	
	return (substr ( $haystack, - $length ) === $needle);
}

/**
 * 
 * @param string $word
 * @return boolean
 */
function is_abbreviation($word) {
	$pattern = '/^(\w\.){2,}$/';
	return preg_match ( $pattern, $word ) === 1;
}

/**
 * 
 * @param string $word
 * @return string
 */
function remove_punctuation($word) {
	$pattern = '/[^\w\d]+$/';
	return preg_replace($pattern, $replacement = '', $word);
}

class Word {

	private $word;
	private $occurrences;
	private $sentenceIndexes = [];

	/**
	 * 
	 * @param string $word
	 * @param int $sentenceIndex
	 * @param int $occurrences
	 */
	public function __construct($word, $sentenceIndex, $occurrences = 1) {
		$this->word = $word;
		$this->sentenceIndexes[] = $sentenceIndex;
		$this->occurrences = $occurrences;
	}

	public function incrementOccurrences() {
		$this->occurrences++;
	}

	/**
	 * 
	 * @param int $sentenceIndex
	 */
	public function addSentenceIndex($sentenceIndex) {
		$this->sentenceIndexes[] = $sentenceIndex;
	}

	public function __toString() {
		$indexes = implode($glue = ',', $this->sentenceIndexes);
		return sprintf('%s {%d:%s}', $this->word, $this->occurrences, $indexes);
	}

}