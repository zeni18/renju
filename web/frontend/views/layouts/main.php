<?php
use common\services\CommonService;
use common\services\GameService;
use yii\helpers\Html;
use frontend\assets\AppAsset;

AppAsset::register($this);
unset($this->assetBundles['yii\web\JqueryAsset']);
unset($this->assetBundles['yii\web\YiiAsset']);
unset($this->assetBundles['yii\bootstrap\BootstrapAsset']);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?>--OnlineRenjuCommunity</title>
    <link href="/css/style.css?v=7" type="text/css" media="screen" rel="stylesheet" />
    <link rel="stylesheet" href="/layui/css/layui.css"  media="all">
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<div class="layui-layout layui-layout-admin">
    <div class="layui-header header header-doc">
        <div class="layui-main" style="width: 1140px;margin: 0 auto;">
            <ul class="layui-nav">
                <?php if(!Yii::$app->user->isGuest):?><li class="layui-nav-item"><?= Yii::t('app','Welcome')?> ,<?php echo Html::encode(Yii::$app->user->identity->nickname);?>. </li><?php endif;?>
                <li class="layui-nav-item ">
                    <a href="/"><?= Yii::t('app','Hall')?></a>
                </li>
                <li class="layui-nav-item">
                    <a href="mailto:xsir317@gmail.com"><?= Yii::t('app','Contact Us')?></a>
                </li>
                <li class="layui-nav-item">
                    <a href="/about.html"><?= Yii::t('app','Rules&ELO')?></a>
                </li>
                <li class="layui-nav-item">
                    <a href="/site/top100">TOP100</a>
                </li>
                <li class="layui-nav-item">
                    <!--<span class="layui-badge-dot" style="margin: -4px 3px 0;"></span>-->
                    <a href="javascript:void(0);"><?= Yii::t('app','Language')?></a>
                    <dl class="layui-nav-child">
                    <?php foreach (Yii::$app->params['languages'] as $k => $name):?>
                        <dd><a href="javascript:void(0);" onclick="pager.switch_language('<?= $k ?>');"><?= $name ?></a></dd>
                    <?php endforeach;?>
                    </dl>
                </li>
            </ul>
        </div>
    </div>
</div>
<div class="layui-main site-inline" style="margin-top: 20px;overflow: hidden;">
    <?php echo $content?>
</div>
<div class="layui-footer footer footer-doc">
    <div class="layui-main">
        <p>
            <?= Yii::t('app','Please use Chrome or Firefox.')?>
        </p>
        <p>
            <?= Yii::t('app','This is an open source project, project page at')?>: <a href="https://github.com/xsir317/renju" target="_blank">https://github.com/xsir317/renju</a>
        </p>
    </div>
</div>
<div id="invite_box" style="display:none;">
    <?= Html::beginForm("","post",["id" => "invite_form", "onsubmit"=>"return false;"])?>
        <input type="hidden" value="" name="to_user" />
        <input type="hidden" value="" name="id" />
        <div class="field odd">
            <span><?= Yii::t('app','Opponent')?>: </span><span class="opponent_name"></span>
        </div>
        <div class="field">
            <label><input type="radio" name="use_black" value="1" id="use_black" /><img src="/images/black.png" /><span><?= Yii::t('app','I use Black')?></span></label>
        </div>
        <div class="field odd">
            <label><input type="radio" name="use_black" value="0" id="use_white" /><img src="/images/white.png" /><span><?= Yii::t('app','I use White')?></span></label>
        </div>
        <div class="field">
            <span><?= Yii::t('app','Time')?>: </span><label>
            <label><input name="hours" value="" style="width: 22px;" /><?= Yii::t('app','Hours')?></label>
            <label><input name="minutes" value="" style="width: 22px;" /><?= Yii::t('app','Minutes')?></label>
        </div>
        <div class="field odd"><span><a href="/about.html"><?= Yii::t('app','Rule')?>: </a></span>
            <?= Html::dropDownList('rule',null,CommonService::getRules()) ?>
        </div>
        <div class="field">
            <span><?= Yii::t('app','Comment')?>: </span>
            <input name="comment" value="" />
        </div>
        <div class="field odd">
            <span><?= Yii::t('app','Free Opening')?>: </span>
            <label><input name="free_open" value="1" type="checkbox" id="free_open" /><?= Yii::t('app','(No limits on opening)')?></label>
        </div>
        <div class="field">
            <span><?= Yii::t('app','Allow Undo')?>: </span>
            <label><input name="allow_undo" value="1" type="checkbox" id="allow_undo" /> </label>
        </div>
            <input type="submit" class="button" value="<?= Yii::t('app','Send Invite')?>" id="invite_submit_button" />
    <?= Html::endForm();?>
</div>
<audio src="" id="global-audio"></audio>
<script src="/layui/layui.all.js" charset="utf-8"></script>
<script type="text/javascript" src="/site/languages?language=<?= \Yii::$app->session['language'] ?>"></script>
<script type="text/javascript">
const _debug_mode = (<?php echo intval(YII_DEBUG);?>);
let debug_log = function(log){
    if (typeof console == "undefined") return false;
    if(_debug_mode)
    {
        console.log(log);
    }
};
layui.config({
    base: '/layui/'
});

const result_defines = (<?php echo json_encode(GameService::$status_define) ?>);
const rule_defines = (<?php echo json_encode(CommonService::getRules()) ?>);

const ts_delta = (<?php echo time() ?> - Math.round(new Date().getTime()/1000));
</script>
<script src="//cdn.bootcss.com/jquery/1.12.4/jquery.min.js" type="text/javascript"></script>
<script src="/js/all.js?v=4"  type="text/javascript" charset="utf-8"></script>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
