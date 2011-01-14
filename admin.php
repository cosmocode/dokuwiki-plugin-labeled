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
                if (isset($_POST['action']['delete'])) {
                   $this->delLabel();
                }
                if (isset($_POST['action']['create'])) {
                    $this->create();
                } else if (isset($_POST['action']['save'])) {
                    $this->applyChanges();
                    $this->create(true);
                }
            }
        }
    }

    function html() {
        global $ID;
        $labels = $this->hlp->getAllLabels();
        include dirname(__FILE__) . '/admin_tpl.php';
    }

    /**
     * Try to delete a label
     */
    private function delLabel() {
        $labels = array_keys($_POST['action']['delete']);
        foreach ($labels as $label) {
            $this->hlp->deleteLabel($label);
        }
        msg($this->getLang('label deleted'));
    }

    private function applyChanges() {
        $labels = $this->hlp->getAllLabels();

        if (!isset($_POST['labels'])) return; // nothing to do

        foreach ($_POST['labels'] as $oldName => $newValues) {

            // apply color
            if ($labels[$oldName]['color'] != $newValues['color']) {
                if ($this->validateColor($newValues['color'])) {
                    $this->hlp->changeColor($oldName, $newValues['color']);
                } else {
                    msg('invalid color', -1);
                }
            }

            // apply order number
            if ($labels[$oldName]['ordernr'] != $newValues['order']) {
                $this->hlp->changeOrder($oldName, $newValues['order']);
            } else if (empty($newValues['order'])) {
                $this->hlp->changeOrder($oldName, 2147483647);
                $labels = $this->hlp->getAllLabels();
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
    private function create($applyMode = false) {
        if (!isset($_POST['newlabel'])) return;

        $name = (isset($_POST['newlabel']['name']))?$_POST['newlabel']['name']:'';
        $color = (isset($_POST['newlabel']['color']))?$_POST['newlabel']['color']:'';
        $order = (isset($_POST['newlabel']['order']))?$_POST['newlabel']['order']:'';

        if (empty($order)) $order = 2147483647; // maxint - last element
        $order = floatval($order);

        if ($applyMode && empty($name) && empty($color)) return;

        if (!$this->validateName($name)) {
            return;
        }

        if (!$this->validateColor($color)) {
            return;
        }

        $this->hlp->createLabel($name, $color);
        $this->hlp->changeOrder($name, $order);
        msg($this->getLang('label created'));
        $this->hlp->getAllLabels(true);
    }

    /**
     * validate if a color is correct.
     * @param string $color the color string
     * @return boolean true if the color is correct
     */
    private function validateColor($color) {
        if (!preg_match('/^#[0-9a-f]{3}([0-9a-f]{3})?$/i', $color)) {
            msg($this->getLang('invalid color', -1));
            return false;
        }
        return true;
    }

    /**
     * check if a label name is correct
     * @param string $name label name
     * @return boolean true if everything is correct
     */
    private function validateName($name) {
        if ($this->hlp->labelExists($name)) {
            msg($this->getLang('label already exists', -1));
            return false;
        }
        if (empty($name)) {
            msg($this->getLang('no name', 1));
            return false;
        }
        return true;
    }

}

// vim:ts=4:sw=4:et:enc=utf-8:
