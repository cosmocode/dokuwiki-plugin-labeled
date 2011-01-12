<?php

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class admin_plugin_labeled extends DokuWiki_Admin_Plugin {

    private $hlp;

    function getMenuSort() { return 400; }
    function forAdminOnly() { return false; }

    function handle() {
        $this->hlp = plugin_load('helper', 'labeled');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (checkSecurityToken()) {
                if (isset($_REQUEST['action']['delete'])) {
                   $this->delLabel();
                }
                if (isset($_REQUEST['action']['create'])) {
                    $this->create();
                } else if (isset($_REQUEST['action']['save'])) {
                    $this->applyChanges();
                }
            }
        }
    }

    function html() {
        global $ID;
        $labels = $this->hlp->getAllLabels();
        include dirname(__FILE__) . '/admin_tpl.php';
    }

    private function delLabel() {
        $labels = array_keys($_REQUEST['action']['delete']);
        foreach ($labels as $label) {
            $this->hlp->deleteLabel($label);
        }
        msg($this->getLang('label deleted'));
    }

    private function applyChanges() {
        $labels = $this->hlp->getAllLabels();

        if (!isset($_REQUEST['labels'])) return; // nothing to do

        foreach ($_REQUEST['labels'] as $oldName => $newValues) {

            // apply color
            if ($labels[$oldName]['color'] != $newValues['color']) {
                if ($this->validateColor($newValues['color'])) {
                    $this->hlp->changeColor($oldName, $newValues['color']);
                } else {
                    msg('invalid color', -1);
                }
            }

            // apply renaming
            if ($oldName !== $newValues['name'] && !empty($newValues['name'])) {
                if ($this->validateName($newValues['name'])) {
                    $this->hlp->renameLabel($oldName, $newValues['name']);
                } else {
                    msg('name already exists');
                }
            }
        }

        $this->hlp->getAllLabels(true);

    }

    /**
     * create a label using the request parameter
     */
    private function create() {
        if (!isset($_REQUEST['newlabel'])) {
            msg($this->getLang('no input'), -1);
            return;
        }
        if (!isset($_REQUEST['newlabel']['name'])) {
            msg($this->getLang('no name', -1));
            return;
        }
        $name = $_REQUEST['newlabel']['name'];
        if (!isset($_REQUEST['newlabel']['color'])) {
            msg($this->getLang('no color', -1));
            return;
        }
        $color = $_REQUEST['newlabel']['color'];

        if (!$this->validateName($name)) {
            return;
        }

        if (!$this->validateColor($color)) {
            msg($this->getLang('invalid color', -1));
            return;
        }

        $this->hlp->createLabel($name, $color);
        msg($this->getLang('label created'));
        $this->hlp->getAllLabels(true);
    }

    /**
     * validate if a color is correct.
     * @param string $color the color string
     * @return boolean true if the color is correct
     */
    private function validateColor($color) {
        return preg_match('/^#[0-9a-f]{3}([0-9a-f]{3})?$/i', $color) == 1;
    }

    /**
     * check if a label name is correct
     * @param string $name label name
     * @return boolean true if everything is correct
     */
    private function validateName($name) {
        return !$this->hlp->labelExists($name);
    }

}

// vim:ts=4:sw=4:et:enc=utf-8:
