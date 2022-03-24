<h3><span class="glyphicon glyphicon-chevron-right"></span> 上传数据</h3>

<p>UOJ 使用 SVN 管理题目数据。本题 SVN 仓库地址为：<a><?= UOJProblem::cur()->getSVNRepoURL() ?></a></p>

<div class="btn-group btn-group-sm btn-group-justified" role="group">
    <a class="btn btn-primary uoj-text" href="<?= UOJProblem::cur()->getUri('/data/candidate') ?>" target="_blank" rel="noopener noreferrer"><span class="glyphicon glyphicon-new-window"></span> 浏览SVN仓库</a>
    <a class="btn btn-default uoj-text" data-toggle="modal" data-target="#UploadDataModal"><span class="glyphicon glyphicon-upload"></span> 直接上传一个压缩包</a>
    <a id="button-getsvn" class="btn btn-default uoj-text"><span class="glyphicon glyphicon-envelope"></span> 我会用 SVN 客户端<br/>请发我 SVN 账号密码</a>
</div>

<p class="top-buffer-md">你可以使用上面三种方式之一上传数据。你上传的数据会被存放于 SVN 仓库中，不会被直接用于测评。请在上传完成后点击右侧的 “与svn仓库同步”，让 UOJ 进行格式检查，并最终生成测评时使用的数据包。</p>


<script type="text/javascript">
$('#button-getsvn').click(function(e){
    e.preventDefault();

    if (!confirm("确定要发送你的svn密码到<?= Auth::property('email') ?>吗")) {
        return;
    }
    $.post('<?= UOJContext::requestURI() ?>', {
        getsvn : ''
    }, function(res) {
        if (res == "good") {
            BootstrapDialog.show({
                title   : "操作成功",
                message : "svn密码已经发送至您的邮箱，请查收。",
                type    : BootstrapDialog.TYPE_SUCCESS,
                buttons: [{
                    label: '好的',
                    action: function(dialog) {
                        dialog.close();
                    }
                }],
            });
        } else {
            BootstrapDialog.show({
                title   : "操作失败",
                message : "邮件未发送成功",
                type    : BootstrapDialog.TYPE_DANGER,
                buttons: [{
                    label: '好吧',
                    action: function(dialog) {
                        dialog.close();
                    }
                }],
            });
        }
    });
});
</script>

<h3><span class="glyphicon glyphicon-chevron-right"></span> 相关文件下载</h3>

<ul class="nav nav-pills nav-justified">
    <li><a class="uoj-text" href="<?= UOJProblem::cur()->getAttachmentUri() ?>"><span class="glyphicon glyphicon-download-alt"></span> 题目附件下载</a></li>
    <li><a class="uoj-text" href="<?= UOJProblem::cur()->getMainDataUri() ?>"><span class="glyphicon glyphicon-download-alt"></span> 完整的测评用数据包下载</a></li>
    <li><a class="uoj-text" href="/download.php?type=testlib.h"><span class="glyphicon glyphicon-download-alt"></span> testlib.h下载</a></li>
</ul>

<h3><span class="glyphicon glyphicon-chevron-right"></span> 各种配置</h3>

<h4>1. 提交文件配置</h4>
<pre><?= HTML::escape(json_encode(json_decode(UOJProblem::info('submission_requirement')), JSON_PRETTY_PRINT)) ?></pre>

<h4>2. 其他配置</h4>
<pre><?= HTML::escape(json_encode(json_decode(UOJProblem::info('extra_config')), JSON_PRETTY_PRINT)) ?></pre>
