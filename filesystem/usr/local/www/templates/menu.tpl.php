<?
/**
 * @desc Build HTML for menu. The menu works by click handlers in init.js
 */
?>

<div id="menu">
    <?foreach ($this->modules as $module):?>
    <h2 id="<?=$module['key']?>"><a href="#"><?= $module['name']?></a></h2>
    <div>
        <?foreach ($module['pages'] as $page): ?>
        <ul class="menu_submenu">
            <li><a href="#<?=$module['key'].'_'.$page['key']?>" rel="<?=$module['key']?>-><?=$page['key']?>" id="<?=$module['key'].'_'.$page['key']?>"><?=$page['name']?></a></li>
        </ul>
        <?endforeach?>
    </div>
    <?endforeach?>
    <h2><a href="#" id="logout_link">Logout</a></h2>
</div>