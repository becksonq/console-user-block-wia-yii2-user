<?php
/**
 * File: BlockController.php
 * Email: becksonq@gmail.com
 * Date: 10.01.2018
 * Time: 19:35
 */

namespace console\controllers;

use Yii;
use yii\console\Controller;
use dektrium\user\Finder;
use yii\helpers\Console;
use yii\web\NotFoundHttpException;
use dektrium\user\traits\EventTrait;
use dektrium\user\controllers\AdminController;
use dektrium\user\models\User;

class BlockController extends Controller
{
    /**
     * Block a user.
     * To enable console commands, you need to add module into console
     * config of you app. /config/console.php in yii2-app-basic template,
     * or /console/config/main.php in yii2-app-advanced.
     *
     * return [ 'id' => 'app-console', 'modules' => [ 'user' => [ 'class' => 'dektrium\user\Module', ], ],
     */

    use EventTrait;

    /** @var Finder */
    protected $finder;

    /** @var int */
    protected $days = 3 * 86400;

    /**
     * BlockController constructor.
     * @param string $id
     * @param \yii\base\Module $module
     * @param Finder $finder
     * @param array $config
     */
    public function __construct( $id, $module, Finder $finder, $config = [] )
    {
        $this->finder = $finder;
        parent::__construct( $id, $module, $config );
    }

    /**
     * Blocks the user.
     *
     * @command ./yii block/block <id>
     * @param $id integer
     */
    public function actionBlock( $id )
    {
        $user = $this->_findModel( $id );
        $event = $this->getUserEvent( $user );

        /**
         * Если пользователь подтвержден, выходим
         */
        if ( $user->_getIsConfirmed() ) {
            $this->stdout( 'User confirmed' . PHP_EOL, Console::FG_BLUE );
            return;
        }

        /**
         * Если пользователь уже заблокирован, выходим
         */
        if ( $user->_getIsBlocked() ) {
            $this->stdout( 'User already blocked' . PHP_EOL, Console::FG_BLUE );
            return;
        }

        /**
         * Если текущее время < времени для блокирования, то выходим
         * Если текущее время > времени для блокирования, блокируем юзера
         */
        if ( time() > $user->created_at + $this->days ) {
            $this->trigger( AdminController::EVENT_BEFORE_BLOCK, $event );
            $user->block();
            $this->trigger( AdminController::EVENT_AFTER_BLOCK, $event );
            $this->stdout( 'User has been blocked' . PHP_EOL, Console::FG_GREEN );
        }
        else {
            $this->stdout( 'User not blocked' . PHP_EOL, Console::FG_RED );
            return;
        }
    }

    /**
     * Блокировка пользователей
     *
     * * @command ./yii block/batch-block
     */
    public function actionBatchBlock()
    {
        $users = $this->_findUnconfirmedUsers();

        foreach ( $users as $value ) {
            $event = $this->getUserEvent( $value );

            /**
             * Если пользователь подтвержден, выходим
             */
            if ( $value->_getIsConfirmed() ) {
                $this->stdout( 'User confirmed' . PHP_EOL, Console::FG_BLUE );
                return;
            }

            /**
             * Если пользователь уже заблокирован, выходим
             */
            if ( $value->_getIsBlocked() ) {
                $this->stdout( 'User already blocked' . PHP_EOL, Console::FG_BLUE );
                return;
            }

            /**
             * Если текущее время < времени для блокирования, то выходим
             * Если текущее время > времени для блокирования, блокируем юзера
             */
            if ( time() > $value->created_at + $this->days ) {
                $this->trigger( AdminController::EVENT_BEFORE_BLOCK, $event );
                $value->block();
                $this->trigger( AdminController::EVENT_AFTER_BLOCK, $event );
                $this->stdout( 'User has been blocked' . PHP_EOL, Console::FG_GREEN );
            }
            else {
                $this->stdout( 'User not blocked' . PHP_EOL, Console::FG_RED );
                return;
            }
        }
    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    protected function _findUnconfirmedUsers()
    {
        $notConfirmedUsers = User::find()->where( [ 'confirmed_at' => null ] )->all();
        return $notConfirmedUsers;
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param int $id
     *
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function _findModel( $id )
    {
        $user = $this->finder->findUserById( $id );
        if ( $user === null ) {
            throw new NotFoundHttpException( 'The requested page does not exist' );
        }

        return $user;
    }

    /**
     * @return bool Whether the user is confirmed or not.
     */
    protected function _getIsConfirmed()
    {
        return $this->confirmed_at != null;
    }

    /**
     * @return bool Whether the user is blocked or not.
     */
    protected function _getIsBlocked()
    {
        return $this->blocked_at != null;
    }

}
