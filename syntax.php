<?php
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
 
class syntax_plugin_pagemod extends DokuWiki_Syntax_Plugin {

	function getInfo(){
		return array(
			'author' => 'Baseline IT',
			'email'  => 'info@baseline-remove-this-it.co.za',
			'date'   => '2010-09-29',
			'name'   => 'Inline Page Modifier',
			'desc'   => 'Allows you create structured ways pages can be modified',
			'url'    => 'http://wiki.splitbrain.org/plugin:pagemod',
		);
	}

	function getType(){ return 'substition'; }
	function getSort(){ return 321; }

	function connectTo($mode) {
		$this->Lexer->addSpecialPattern("<pagemod \w+(?: .+?)?>.*?</pagemod>", $mode, 'plugin_pagemod');
	}

	// We just want to hide this from view

	function handle($match, $state, $pos, &$handler){ return ''; }            
	function render($mode, &$renderer, $data) { return true; }
}

?>
