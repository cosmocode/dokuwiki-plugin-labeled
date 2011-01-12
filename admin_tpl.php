<?php
$label = hsc($label);
?>
<h1><?php echo $this->getLang('admin headline')?></h1>

<table class="inline">
    <tr>
        <th><?php echo $this->getLang('admin label name')?></th>
        <th><?php echo $this->getLang('admin label color')?></th>
        <th><?php echo $this->getLang('admin action')?></th>
    </tr>
    <tr>
        <td><input type="text" name="newlabel[name]" class="edit" /></td>
        <td><input type="" name="newlabel[color]" class="edit" /></td>
        <td><input type="submit" class="button" name="action[create]" value="<?php echo $this->getLang('admin create')?>" /></td>
    </tr>
<?php foreach ($labels as $label => $opts): ?>
    <tr>
        <td>
            <input class="edit" type="text" value="<?php echo $label ?>" name="labels[<?php echo $label ?>][name]" />
        </td>
        <td>
            <input class="edit" style="color: #<?php echo $opts['color'] ?>" type="text"
                value="#<?php echo $opts['color'] ?>" name="labels[<?php echo $label ?>][color]" />
        </td>
        <td>
            <a href="#"><?php echo $this->getLang('admin delete')?></a>
        </td>
    </tr>
<?php endforeach; ?>
</table>

<input type="submit" name="action[save]" value="<?php echo $this->getLang('admin save')?>" class="button" />