<?php

assert($manager instanceof UOJOssObjectManager);

?>
<p>OSS是阿里云的对象存储服务，可以用来存储题目图片。</p>
<p>你可以使用 Markdown 语法 <code>![图挂了的时候需要显示的文字](图片链接)</code> 将上传好的图片贴在题面中。</p>
<?= $manager->objectTable() ?>
<script>
function updateObjectTable() {
    $.get(<?= json_encode($manager->url) ?>, {
        'table': true
    }, function(html) {
        $(<?= json_encode("#{$manager->objectTableID()}") ?>).replaceWith(html);
    })
}
</script>
<?php $manager->dropzone_form->printHTML() ?>