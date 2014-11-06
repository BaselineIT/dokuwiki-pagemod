<?php
/**
 * Simple page modifying action for the bureaucracy plugin
 *
 * @author Darren Hemphill <darren@baseline-remove-this-it.co.za>
 */
class helper_plugin_pagemod_pagemod extends helper_plugin_bureaucracy_action {

    var $patterns;
    var $values;
    protected $template_section_id;

    /**
     * Handle the user input [required]
     *
     * @param helper_plugin_bureaucracy_field[] $fields the list of fields in the form
     * @param string                            $thanks the thank you message as defined in the form
     *                                                  or default one. Might be modified by the action
     *                                                  before returned
     * @param array                             $argv   additional arguments passed to the action
     * @return bool|string false on error, $thanks on success
     */
    public function run($fields, $thanks, $argv) {
        global $ID;

        // prepare replacements
        $this->prepareNamespacetemplateReplacements();
        $this->prepareDateTimereplacements();
        $this->prepareLanguagePlaceholder();
        $this->prepareNoincludeReplacement();
        $this->prepareFieldReplacements($fields);

        //handle arguments
        $page_to_modify = array_shift($argv);
        if($page_to_modify === '_self') {
            # shortcut to modify the same page as the submitter
            $page_to_modify = $ID;
        } else {
            //resolve against page which contains the form
            resolve_pageid(getNS($ID), $page_to_modify, $ignored);
        }

        $template_section_id = cleanID(array_shift($argv));

        if(!page_exists($page_to_modify)) {
            msg(sprintf($this->getLang('e_pagenotexists'), html_wikilink($page_to_modify)), -1);
            return false;
        }

        // check auth
        //
        // This is an important point.  In order to be able to modify a page via this method ALL you need is READ access to the page
        // This is good for admins to be able to only allow people to modify a page via a certain method.  If you want to protect the page
        // from people to WRITE via this method, deny access to the form page.
        $auth = $this->aclcheck($page_to_modify); // runas
        if($auth < AUTH_READ) {
            msg($this->getLang('e_denied'), -1);
            return false;
        }

        // fetch template
        $template = rawWiki($page_to_modify);
        if(empty($template)) {
            msg(sprintf($this->getLang('e_template'), $page_to_modify), -1);
            return false;
        }

        // do the replacements
        $template = $this->updatePage($template, $template_section_id);
        if(!$template) {
            msg(sprintf($this->getLang('e_failedtoparse'), $page_to_modify), -1);
            return false;
        }
        // save page
        saveWikiText($page_to_modify, $template, sprintf($this->getLang('summary'), $ID));

        //thanks message with redirect
        $link = wl($page_to_modify);
        return sprintf(
            $this->getLang('pleasewait'),
            "<script type='text/javascript' charset='utf-8'>location.replace('$link')</script>", // javascript redirect
            html_wikilink($page_to_modify) //fallback url
        );
    }

    /**
     * (callback) Returns replacement for meta variabels (value generated at execution time)
     *
     * @param $arguments
     * @return bool|string
     */
    public function getMetaValue($arguments) {
        global $INFO;
        # this function gets a meta value

        $label = $arguments[1];
                              //print_r($INFO);
        if($label == 'date') {
            return date("d/m/Y");

        } elseif($label == 'datetime') {
            return date("c");

        } elseif(preg_match('/^date\.format\.(.*)$/', $label, $matches)) {
            return date($matches[1]);

        } elseif($label == 'user' || $label == 'user.id') {
            return $INFO['client'];

        } elseif(preg_match('/^user\.(mail|name)$/', $label, $matches)) {
            return $INFO['userinfo'][$matches[1]];

        } elseif($label == 'page' || $label == 'page.id') {
            return $INFO['id'];

        } elseif($label == 'page.namespace') {
            return $INFO['namespace'];

        } elseif($label == 'page.name') {
            return noNs($INFO['id']);
        }
        return '';
    }

    /**
     * Update the page with new content
     *
     * @param string $template
     * @param string $template_section_id
     * @return string
     */
    protected function updatePage($template, $template_section_id) {
        $this->template_section_id = $template_section_id;
        return preg_replace_callback('/<pagemod (\w+)(?: (.+?))?>(.*?)<\/pagemod>/s', array($this, 'parsePagemod'), $template);
    }

    /**
     * (callback) Build replacement that is inserted before of after <pagemod> section
     *
     * @param $matches
     * @return string
     */
    public function parsePagemod($matches) {
        // Get all the parameters
        $full_text     = $matches[0];
        $id            = $matches[1];
        $params_string = $matches[2];
        $contents      = $matches[3];

        // First parse the parameters
        $output_before = true;
        if($params_string) {
            $params = array_map('trim', explode(",", $params_string));
            foreach($params as $param) {
                if($param === 'output_after') {
                    $output_before = false;
                }
            }
        }

        // We only parse if this template is being matched (Allow multiple forms to update multiple sections of a page)
        if($id === $this->template_section_id) {
            //replace meta variables
            $output = preg_replace_callback('/@@meta\.(.*?)@@/', array($this, 'getMetaValue'), $contents);
            //replace bureacracy variables
            $output = $this->replace($output);

            if($output_before) {
                return $output . $full_text;
            } else {
                return $full_text . $output;
            }
        } else {
            return $full_text;
        }
    }

}
// vim:ts=4:sw=4:et:enc=utf-8:
