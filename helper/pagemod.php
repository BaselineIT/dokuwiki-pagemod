<?php
/**
 * Simple page modifying action for the burreaucracy plugin
 *
 * @author Darren Hemphill <darren@baseline-remove-this-it.co.za>
 */
class helper_plugin_pagemod_pagemod extends helper_plugin_bureaucracy_action {

    var $patterns;
    var $values;

    function run($data, $thanks, $argv) {
        // Pickup the ID
        global $ID;
        // Pickup the conf
        global $conf;

        $page_to_modify = array_shift($argv);
        if($page_to_modify === '_self') {
            # shortcut to modify the same page as the submitter
            $page_to_modify = $ID;
        } else {
            $page_to_modify = cleanID($page_to_modify);
        }
        $template_section_id  = cleanID(array_shift($argv));

        $pagename = '';
        $patterns = array();
        $values   = array();

        // run through fields and prepare replacements
        foreach($data as $opt) {
            $label = preg_quote($opt->getParam('label'));
            $value = $opt->getParam('value');
#            if(in_array($opt->getParam('cmd'),$this->nofield)) continue;
            $label = preg_replace('/([\/])/','\\\$1',$label);
            $patterns[] = '/(@@|##)'.$label.'(@@|##)/i';
            $values[]   = $value;
        }

        /*
                // check pagename
                $pagename = cleanID($pagename);
                if(!$pagename) {
                    msg($this->getLang('e_pagename'), -1);
                    return false;
                }
                $pagename = $ns.':'.$pagename;
                if(page_exists($pagename)) {
                    msg(sprintf($this->getLang('e_pageexists'), html_wikilink($pagename)), -1);
                    return false;
                }
        */
        if(!page_exists($page_to_modify)) {
            msg(sprintf($this->getLang('e_pagenotexists') ? $this->getLang('e_pagenotexists') : "This page [%s] does not exist, cannot be modified", html_wikilink($page_to_modify)), -1);
            return false;
        }

        // check auth
        $runas = $this->getConf('runas');
        if($runas){
            $auth = auth_aclcheck($page_to_modify,$runas,array());
        }else{
            $auth = auth_quickaclcheck($page_to_modify);
        }
        // This is an important point.  In order to be able to modify a page via this method ALL you need is READ access to the page
        // This is good for admins to be able to only allow people to modify a page via a certain method.  If you want to protect the page
        // from people to WRITE via this method, deny access to the form page.
        if($auth < AUTH_READ) {
            msg($this->getLang('e_denied'), -1);
            return false;
        }

        // get page_to_be modified
        $tpl = cleanID($page_to_modify);

        // fetch template
        $template = rawWiki($tpl);
        if(empty($template)) {
            msg(sprintf($this->getLang('e_template'), $tpl), -1);
            return false;
        }

        // do the replacements
        $template = $this->updatePage($patterns,$values,$template,$template_section_id);
        if(!$template) {
            msg(sprintf($this->getLang('e_failedtoparse') ? $this->getLang('e_failedtoparse') : "Failed to parse the template", $tpl), -1);
            return false;
        }
        // save page and return
        saveWikiText($page_to_modify, $template, sprintf($this->getLang('summary'),$ID));
        $link_to_next = html_wikilink($page_to_modify);
        $raw_link = preg_replace('/.*?href="(.*?)".*/','$1',$link_to_next);
        return "Please wait while the page processes your results.... <script language='javascript'>location.replace('$raw_link')</script> or click ".html_wikilink($page_to_modify)." to see them";
    }

    function getMetaValue($arguments) {
        global $INFO;
        # this function gets a meta value (value generated at execution time
        $matches;
        $label = $arguments[1];
        if(preg_match('/^date$/',$label,$matches)) {
            return date("d/m/Y");
        } elseif(preg_match('/^datetime$/',$label,$matches)) {
            return date("c");
        } elseif(preg_match('/^date\.format\.(.*)$/',$label,$matches)) {
            return date($matches[1]);
        } elseif(preg_match('/^user(\.id){0,1}$/',$label,$matches)) {
            return $INFO['userinfo']['user'];
        } elseif(preg_match('/^user\.(mail|name)$/',$label,$matches)) {
            return $INFO['userinfo'][$matches[1]];
        } elseif(preg_match('/^page(\.id){0,1}$/',$label,$matches)) {
            return $INFO['id'];
        } elseif(preg_match('/^page\.namespace$/',$label,$matches)) {
            return $INFO['namespace'];
        } elseif(preg_match('/^page\.name$/',$label,$matches)) {
            preg_match('/([^:]*)$/',$INFO['id'],$matches);
            return $matches[1];
        }
    }

    function updatePage($patterns,$values,$template,$template_section_id) {
        $this->patterns = $patterns;
        $this->values = $values;
        $this->template_section_id = $template_section_id;
        return preg_replace_callback('/<pagemod (\w+)(?: (.+?))?>(.*?)<\/pagemod>/s',array($this,'parsePagemod'),$template);
    }

    function parsePagemod($matches) {
        // Get all the parameters
        $full_text = $matches[0];
        $id = $matches[1];
        $params_string = $matches[2];
        $contents = $matches[3];
        // First parse the parameters
        $output_before = true;
        if($params_string) {
            $params = explode(",",$params_string);
            foreach($params as $param) {
                if($param === 'output_after') {
                    $output_before = false;
                }
            }
        }
        $output = "";
        // We only parse if this template is being matched (Allow multiple forms to update multiple sections of a page)
        if($id === $this->template_section_id) {
            $output = preg_replace_callback('/@@meta\.(.*?)@@/',array($this,'getMetaValue'),$contents);
            $output = preg_replace($this->patterns,$this->values,$output);
            return $output_before ? $output.$full_text : $full_text.$output;
        } else {
            return $full_text;
        }
    }

}
// vim:ts=4:sw=4:et:enc=utf-8:
