<?php

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class admin_plugin_labeled extends DokuWiki_Admin_Plugin {

    var $hlp;

    function getMenuSort() { return 400; }
    function forAdminOnly() { return false; }

    function handle() {
        $this->hlp = plugin_load('helper', 'labeled');
    }

    function html() {
        global $ID;
        $labels = $this->hlp->getAllLabels();
        include dirname(__FILE__) . '/admin_tpl.php';
    }
}

// vim:ts=4:sw=4:et:enc=utf-8:
