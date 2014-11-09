<?php

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

/**
 * Class syntax_plugin_pagemod
 */
class syntax_plugin_pagemod extends DokuWiki_Syntax_Plugin {

    /**
     * Syntax Type
     *
     * @return string
     */
    public function getType() {
        return 'substition';
    }

    /**
     * Sort for applying this mode
     *
     * @return int
     */
    public function getSort() {
        return 321;
    }

    /**
     * @param string $mode
     */
    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern("<pagemod \w+(?: .+?)?>.*?</pagemod>", $mode, 'plugin_pagemod');
    }

    // We just want to hide this from view

    /**
     * Handler to prepare matched data for the rendering process
     *
     * @param   string       $match   The text matched by the patterns
     * @param   int          $state   The lexer state for the match
     * @param   int          $pos     The character position of the matched text
     * @param   Doku_Handler $handler The Doku_Handler object
     * @return  array Return an array with all data you want to use in render
     */
    public function handle($match, $state, $pos, Doku_Handler $handler) {
        return '';
    }

    /**
     * Handles the actual output creation.
     *
     * @param   $mode   string        output format being rendered
     * @param   $renderer Doku_Renderer the current renderer object
     * @param   $data     array         data created by handler()
     * @return  boolean                 rendered correctly?
     */
    public function render($mode, Doku_Renderer $renderer, $data) {
        return true;
    }
}
