<h1><?php echo $this->getLang('admin headline')?></h1>

<form action="<?php echo script()?>" method="post">
    <div class="no">
        <input type="hidden" name="do" value="admin" />
        <input type="hidden" name="page" value="labeled" />
        <input type="hidden" name="id" value="<?php echo hsc($ID)?>" />
        <input type="hidden" name="sectok" value="<?php echo hsc(getSecurityToken())?>" />
    </div>

<table class="inline">
    <tr>
        <th><?php echo $this->getLang('admin label name')?></th>
        <th><?php echo $this->getLang('admin label color')?></th>
        <th><?php echo $this->getLang('admin order')?></th>
        <th><?php echo $this->getLang('admin action')?></th>
    </tr>
    <tr>
        <td><input type="text" name="newlabel[name]" class="edit" /></td>
        <td><input type="" name="newlabel[color]" class="edit" /></td>
        <td><input type="" name="newlabel[order]" class="edit" /></td>
        <td><input type="submit" class="button" name="action[create]" value="<?php echo $this->getLang('admin create')?>" /></td>
    </tr>
<?php foreach ($labels as $label => $opts): ?>
<?php $label = hsc($label); ?>
    <tr>
        <td>
            <input class="edit" type="text" value="<?php echo $label ?>" name="labels[<?php echo $label ?>][name]" />
        </td>
        <td>
            <input class="edit" style="color: <?php echo $opts['color'] ?>" type="text"
                value="<?php echo $opts['color'] ?>" name="labels[<?php echo $label ?>][color]" />
        </td>
        <td>
            <input class="edit" type="text" value="<?php echo $opts['ordernr'] ?>" name="labels[<?php echo $label ?>][order]" />
        </td>
        <td>
            <input type="submit" name="action[delete][<?php echo $label ?>]" class="button"
                value="<?php echo $this->getLang('admin delete')?>" />
        </td>
    </tr>
<?php endforeach; ?>
</table>

<input type="submit" name="action[save]" value="<?php echo $this->getLang('admin save')?>" class="button" />
</form>