<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2017/12/11
 * Time: 18:59
 */

namespace frontend\modules\games\controllers;


use common\components\Gateway;
use common\components\MsgHelper;
use common\models\Games;
use common\models\GameUndoLog;
use common\services\GameService;
use frontend\components\Controller;


/**
 * Class UndoController
 * @package frontend\modules\games\controllers
 * 悔棋和同意。 这个也是由原先GameController拆出来。
 */
class UndoController extends Controller
{

    /**
     * 提出悔棋申请
     * 悔棋申请提出时 记录当前局面，提出者id，时间，回到第几手。
     * 不能提出涉及前5手的悔棋。
     * render棋局时，如果是正在进行的棋局，则先update悔棋记录，检查状态0的悔棋申请，和当前盘面不一致的全部-1掉。 将最新的有效的悔棋申请附在数据结构里。
     * 同意：验证盘面与申请时一致，然后恢复到指定手数，render，然后发广播通知。
     * 同意的话，可以获得10%时间的补偿。
     * 终局时清理所有未同意的悔棋申请。
     */
    public function actionCreate()
    {
        $game_id = intval($this->post('game_id'));
        //悔棋到第几手。 最终会保留前$to_step - 1手。 to_step 意思是悔棋成功后，应当落下哪一手， 比如to_step=8，则 悔棋之后轮到白棋落8
        $to_step = intval($this->post('to_step'));
        $comment = trim($this->post('comment'));

        if(!$this->_user())
        {
            return $this->renderJSON([],\Yii::t('app','Please Login'),-1);
        }
        $game_info = GameService::renderGame($game_id);
        if(!$game_info)
        {
            return $this->renderJSON([],\Yii::t('app',"Game doesn't exist"),-1);
        }
        if($game_info['black_id'] != $this->_user()->id && $game_info['white_id'] != $this->_user()->id)
        {
            return $this->renderJSON([],\Yii::t('app',"This is not your game"),-1);
        }
        //特殊逻辑： 黑方悔棋，则保留偶数个棋子； 白方悔棋，则保留奇数个棋子。 以保证悔棋之后，轮到悔棋提出方落子。
        if($game_info['black_id'] == $this->_user()->id)
        {
            if($to_step % 2 == 0) //黑方提出悔棋，是偶数 则 -1
            {
                $to_step -- ;
            }
        }
        else
        {
            if($to_step % 2 == 1) //白方提出悔棋，是奇数 则 -1
            {
                $to_step -- ;
            }
        }

        if($game_info['status'] != GameService::PLAYING)
        {
            return $this->renderJSON([],\Yii::t('app','This game is currently not playing'),-1);
        }

        if(!$game_info['allow_undo'])
        {
            return $this->renderJSON([],\Yii::t('app','Undo is not allowed in this game.'),-1);
        }

        if($to_step <= 5)
        {
            return $this->renderJSON([],\Yii::t('app',"You can't undo to before 6th move"),-1);
        }
        if(strlen($game_info['game_record']) / 2 < $to_step)
        {
            return $this->renderJSON([],\Yii::t('app','This undo is beyond the game itself'),-1);
        }

        GameUndoLog::updateAll(['status' => -1],['game_id' => $game_id,'status' => 0,]);

        $undo = new GameUndoLog();
        $undo->game_id = $game_id;
        $undo->uid = $this->_user()->id;
        $undo->current_board = $game_info['game_record'];
        $undo->to_number = $to_step;
        $undo->comment = $comment;
        $undo->status = 0;
        $undo->created_time = date('Y-m-d H:i:s');
        $undo->save(0);

        Gateway::sendToGroup($game_id,MsgHelper::build('game_info',[
            'game' => GameService::renderGame($game_id)
        ]));
        return $this->renderJSON([],\Yii::t('app','Undo apply successfully sent.'));
    }

    public function actionReply()
    {
        if(!$this->_user())
        {
            return $this->renderJSON([],\Yii::t('app','Please Login'),-1);
        }

        $undo_id = intval($this->post('undo_id'));
        $action = trim($this->post('action'));
        $log = GameUndoLog::findOne(['id' => $undo_id,'status' => 0]);

        if(!$log || $log->uid == $this->_user()->id)
        {
            return $this->renderJSON([],\Yii::t('app','This undo apply is not available'),-1);
        }

        $game_info = GameService::renderGame($log->game_id);
        if(!$game_info)
        {
            return $this->renderJSON([],\Yii::t('app',"Game doesn't exist"),-1);
        }
        if($game_info['black_id'] != $this->_user()->id && $game_info['white_id'] != $this->_user()->id)
        {
            return $this->renderJSON([],\Yii::t('app',"This is not your game"),-1);
        }

        if($game_info['status'] != GameService::PLAYING)
        {
            return $this->renderJSON([],\Yii::t('app','This game is currently not playing'),-1);
        }

        if($action == 'accept')
        {
            //校验数据
            if($log->current_board != $game_info['game_record'])
            {
                $log->status = -1;
                $log->save(0);
                return $this->renderJSON([],\Yii::t('app','This undo apply is not available'),-1);
            }
            if($log->to_number <= 5 || $log->to_number > (strlen($log->current_board)/2))
            {
                $log->status = -1;
                $log->save(0);
                return $this->renderJSON([],\Yii::t('app','This undo apply is not available'),-1);
            }
            //校验数据end
            //接受悔棋，退回盘面，给当前玩家增加时间。
            $game = Games::findOne($log->game_id);
            $game->game_record = substr($game->game_record,0,(2*($log->to_number-1)));
            $game->movetime = date('Y-m-d H:i:s');
            if($this->_user()->id == $game->black_id)
            {
                $game->black_time += intval(0.1 * $game->totaltime);
            }
            else
            {
                $game->white_time += intval(0.1 * $game->totaltime);
            }
            $game->save(0);

            $log->status = 1;
            $log->save(0);
            \Yii::$app->cache->delete('undo_log_cache::'.$game->id);
            //render游戏，进行广播。
            Gateway::sendToGroup($game->id,MsgHelper::build('game_info',[
                'game' => GameService::renderGame($game->id)
            ]));
            GameService::sendGamesList();
        }
        else
        {
            $log->status = -1;
            $log->save(0);
        }
        return $this->renderJSON([]);
    }
}