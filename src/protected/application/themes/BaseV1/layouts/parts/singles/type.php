<?php 
$_entity = $this->controller->id; 
$entityTypeClass = isset($disable_editable) ? '' : 'js-editable-type';
?>
<?php $this->applyTemplateHook('type', 'before', ['entity' => $entity, 'entityTypeClass' => &$entityTypeClass]);?>
<div class="entity-type <?php echo $_entity ?>-type">
    <div class="icon icon-<?php echo $_entity ?>"></div>
    <a href="#" id="entityType" class='<?php echo $entityTypeClass ?> required' data-original-title="<?php \MapasCulturais\i::esc_attr_e("Tipo");?>" data-emptytext="<?php \MapasCulturais\i::esc_attr_e("Selecione um tipo");?>" data-entity='<?php echo $_entity ?>' data-value='<?php echo $entity->type ?>'>
        <?php echo $entity->type ? $entity->type->name : ''; ?>
    </a>
</div>
<!--.entity-type-->
<?php $this->applyTemplateHook('type','after'); ?>