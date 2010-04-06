<?foreach ($this->modules as $module):?>
    <div class="contentpart module" id="cp_<?= $module['key']?>">
        <?foreach ($module['pages'] as $page): ?>
            <div class="contentpart page" id="cp_<?= $module['key'].'_'.$page['key']?>">
                <? include $this->template($module['key'] . '/' . $page['key'] . '.tpl.php'); ?>
                <? if ($page['tabs']): ?>
                    <div class="tabset">
                        <ul>
                            <? foreach ($page['tabs'] as $tab): ?>
                                <li><a href="#cp_<?= $module['key'].'_'.$page['key'].'_'.$tab['key']?>"><?=$tab['name']?></a></li>
                            <? endforeach ?>
                        </ul>
                        <? foreach ($page['tabs'] as $tab): ?>
                            <div class="contentpart tab" id="cp_<?= $module['key'].'_'.$page['key'].'_'.$tab['key']?>">
                                <? include $this->template($module['key'] . '/' . $page['key'] . '_' . $tab['key'] . '.tpl.php'); ?>
                            </div>
                        <? endforeach ?>
                    </div>
                <? endif ?>
            </div>
        <?endforeach?>
    </div>
<?endforeach?>