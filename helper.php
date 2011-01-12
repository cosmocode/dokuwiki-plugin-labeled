<?php

if(!defined('DOKU_INC')) die();

class helper_plugin_labeled extends DokuWiki_Plugin {

    private $labels = null;

    function getDB() {
        static $db;
        if (!is_null($db)) {
            return $db;
        }

        $db = plugin_load('helper', 'sqlite');
        if (is_null($db)) {
            msg('The labeled plugin needs the sqlite plugin', -1);
            return false;
        }
        if($db->init('labeled', dirname(__FILE__).'/db/')){
            return $db;
        }
        return false;
    }

    public function tpl_labels() {
        global $ID;
        $all = $this->getAllLabels();
        $current = $this->getLabels($ID);
        echo '<div class="plugin_labeled"><ul>';

        $edit = auth_quickaclcheck($ID) >= AUTH_EDIT;
        foreach ($all as $label => $opts) {

            $active = in_array($label, $current);

            $color = ($active)?$opts['color']:'aaa';

            echo '<li class="labeled_'.($active?'':'in').'active" style="border-color:#'.$color.'">';
            if ($edit) {
                $link = wl($ID,
                    array(
                        'do' => 'labeled',
                        action_plugin_labeled_change::$act => $active?'remove':'add',
                        'label' => $label
                    )
                );
                $title = '';
                printf('<a href="%s" title="%s"  style="color:#'.$color.'">', $link, $title);
            }
            echo hsc($label);

            if ($edit) echo '</a>';
            echo '</li>';
        }

        echo '</ul></div>';

    }

    /**
     * parse a string of tags.
     * @param string $tags
     * @return array single tags as array.
     */
    public function parseLabels($labels) {
        $labels = explode(',', $labels);
        foreach ($lables as &$label) {
            $label = trim($label);
        }
        return $labels;
    }

    public function setLabels($labels, $id) {
        if (auth_quickaclcheck($id) < AUTH_EDIT) {
            return false;
        }

        $this->deleteLabels($id);

        $db = $this->getDb();
        foreach ($labels as $label) {
            if (!$this->labelExists($label)) continue;
            $db->query('INSERT INTO labeled (id, label) VALUES (?,?)', $id, $label);
        }

    }

    /**
     * remove a label form a page
     * @param string $label label to remove
     * @param string $id    wiki page id
     */
    public function removeLabel($label, $id) {
        if (auth_quickaclcheck($id) < AUTH_EDIT) {
            return false;
        }
        $db = $this->getDb();
        $db->query('DELETE FROM labeled WHERE id=? AND label=?', $id, $label);
    }

    /**
     * delete all labels from a wikipage
     * @param string $id the wikipage
     * @return void
     */
    private function deleteLabels($id) {
        $db = $this->getDb();
        $db->query('DELETE FROM labeled WHERE id=?', $id);
    }

    /**
     * add a single label to a wiki page
     * @param string $label
     * @param string $id wiki page
     * @return void
     */
    public function addLabel($label, $id) {
        $labels = $this->getLabels($id);
        $labels[] = $label;
        $labels = array_unique($labels);

        $this->setLabels($labels, $id);
    }

    /**
     * get all labels
     * @param string $id from wiki page id
     * @return array list of all labels
     */
    public function getLabels($id) {
        if (auth_quickaclcheck($id) < AUTH_READ) {
            return false;
        }

        $db = $this->getDb();
        $res = $db->query('SELECT label FROM labeled WHERE id=?', $id);

        $labels = $db->res2arr($res);
        $result = array();
        foreach ($labels as $label) {
            $result[] = $label['label'];
        }
        return $result;
    }

    /**
     * check if a label exists
     * @param string $label label to check
     * @return boolean true if exists
     */
    private function labelExists($label) {
        $labels = $this->getAllLabels();
        return isset($labels[$label]);
    }

    /**
     * @return array get an array of all available labels
     */
    public function getAllLabels() {
        if ($this->labels !== null) return $this->labels;

        $db = $this->getDb();
        $res = $db->query('SELECT name, color, namespace FROM labels');

        $labels = $db->res2arr($res);

        $this->labels = array();
        foreach ($labels as $label) {
            $this->labels[$label['name']] = $label;
        }

        return $this->labels;
    }

    /**
     * create a new label
     * @param string $name  new name of the label
     * @param string $color hex color of the label
     * @param string $ns    namespace filter for the label
     * @todo check color
     */
    public function createLabel($name, $color, $ns = '') {
        global $ID;
        if (auth_quickaclcheck($ID) < AUTH_ADMIN) {
            return;
        }

        if ($this->labelExists($name)) return;

        $ns = cleanID($ns);
        $db = $this->getDb();
        $db->query('INSERT INTO labels (name, color, namespace) VALUES (?,?,?)', $name, $color, $ns);
    }

    /**
     * delete a label (and all uses of it)
     * @param string $label label to delete
     */
    public function deleteLabel($label) {
        global $ID;
        if (auth_quickaclcheck($ID) < AUTH_ADMIN) {
            return;
        }

        $db = $this->getDb();
        $db->query('DELETE FROM labels WHERE name=?', $label);
        $db->query('DELETE FROM labeled WHERE label=?', $label);
    }
}