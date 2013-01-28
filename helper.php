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
        if (count($all) === 0) return false;
        $current = $this->getLabels($ID);
        $result = '';
        $result .=  '<div class="plugin_labeled"><ul>';

        $edit = auth_quickaclcheck($ID) >= AUTH_EDIT;
        foreach ($all as $label => $opts) {

            $active = in_array($label, $current);

            $color = ($active)?$opts['color']:'aaa';

            $result .=  '<li class="labeled_'.($active?'':'in').'active" style="border-color:'.$color.';background-color:'.$color.'">';
            if ($edit) {
                $link = wl($ID,
                    array(
                        'do' => 'labeled',
                        action_plugin_labeled_change::$act => $active?'remove':'add',
                        'label' => $label
                    )
                );
                $title = '';
                $result .= sprintf('<a href="%s" title="%s">', $link, $title);
            }
            $result .=  hsc($this->getLabelLanguage($label));

            if ($edit) $result .=  '</a>';
            $result .=  '</li>';
        }

        $result .=  '</ul></div>';
        return $result;
    }

    /**
     * check if conf/lang/.../labeled.php exists and languages for param label name is set
     * @param string $label name
     * @return string translated label name
     */
    public function getLabelLanguage($label) {

        global $conf;

        if (isset($conf['lang'])) {
            $path = DOKU_INC.'conf/lang/'.$conf['lang'].'/labeled.php';
            if (file_exists($path)) {
                @include_once($path);
                return (isset($lang[$label])) ? $lang[$label] : $label;
            }
        }

        return $label;
    }

    /**
     * parse a string of tags.
     * @param string $tags
     * @return array single tags as array.
     * FIXME can be deleted?
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

    public function changeColor($label, $newColor) {
        global $INFO;
        if (!$INFO['isadmin']) return;

        $db = $this->getDb();
        $db->query('UPDATE labels SET color=? WHERE name=?', $newColor, $label);
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
        if (auth_quickaclcheck($id) < AUTH_EDIT) {
            return false;
        }

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
     * rename a label
     * @param string $label old label name
     * @param string $newLabel new label name
     */
    public function renameLabel($label, $newName) {
        global $INFO;
        if (!$INFO['isadmin']) return;

        if (!$this->labelExists($label)) return;
        $db = $this->getDb();
        $db->query('UPDATE labels set name=? WHERE name=?', $newName, $label);
        $db->query('UPDATE labeled set label=? WHERE label=?', $newName, $label);

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
    public function labelExists($label) {
        $labels = $this->getAllLabels();
        return isset($labels[$label]);
    }

    /**
     * @return array get an array of all available labels
     * @param boolean $reload on true force a reload
     */
    public function getAllLabels($reload = false) {
        if ($this->labels !== null && !$reload) return $this->labels;

        $db = $this->getDb();
        $res = $db->query('SELECT name, color, namespace, ordernr FROM labels ORDER BY ordernr');

        $labels = $db->res2arr($res);

        $this->labels = array();
        foreach ($labels as $label) {
            $this->labels[$label['name']] = $label;
        }

        return $this->labels;
    }

    /**
     * Change the order of the labels
     *
     * @param string $name label name to change
     * @param float $order ordering number
     */
    public function changeOrder($name, $order) {
        global $INFO;
        if (!$INFO['isadmin']) return;
        if ($order === '') $order = 2147483647;
        $db = $this->getDb();

        $labels = $this->getAllLabels(true);
        $labels[$name]['ordernr'] = $order;
        uasort($labels, array($this, 'cmpOrder'));

        $keys = array_keys($labels);
        $kc = count($keys);
        for ($i=0; $i<$kc; $i++) {
            if ($labels[$keys[$i]]['ordernr'] == ($i+1)) {
                continue;
            }
            $labels[$keys[$i]]['ordernr'] = $i+1;
            $db->query('UPDATE labels SET ordernr=? WHERE name=?', ($i+1), $keys[$i]);
        }
        $this->getAllLabels(true);
    }

    public function cmpOrder($a, $b) {
        if ($a['ordernr'] == $b['ordernr']) return 0;
        return ($a['ordernr'] > $b['ordernr']) ? 1 : -1;
    }

    /**
     * create a new label
     * @param string $name   new name of the label
     * @param string $color  hex color of the label
     * @param boolean $order Ordering number
     * @param string $ns     namespace filter for the label
     */
    public function createLabel($name, $color, $order = false, $ns = '') {
        global $INFO;
        if (!$INFO['isadmin']) return;

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
        global $INFO;
        if (!$INFO['isadmin']) return;

        $db = $this->getDb();
        $db->query('DELETE FROM labels WHERE name=?', $label);
        $db->query('DELETE FROM labeled WHERE label=?', $label);
    }
}
