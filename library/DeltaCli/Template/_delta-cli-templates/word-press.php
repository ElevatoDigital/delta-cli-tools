
$project->applyTemplate(
    $project->wordPressTemplate()
    <?php foreach ($plugins as $plugin):?>
        ->addSyncedPlugin('<?php echo $plugin;?>')
    <?php endforeach;?>
    <?php foreach ($themes as $theme):?>
        ->addSyncedTheme('<?php echo $theme;?>')
    <?php endforeach;?>
);
