<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

/**
 * 聊天主逻辑
 * 主要是处理 onMessage onClose
 */

use \GatewayWorker\Lib\Gateway;
use JPush\Client as JPush;

require "dbconfig.php";

class Events
{
    /**
     * 有消息时
     * @param int $client_id
     * @param mixed $message
     * @return bool|void
     * @throws Exception
     */
    public static function onMessage($client_id, $message)
    {
        // debug
        echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id session:" . json_encode($_SESSION) . " onMessage:" . $message . "\n";

        echo "收到客户端数据:" . $message;

        // 客户端传递的是json数据
        $message_data = json_decode($message, true);

        if (!$message_data) {
            return;
        }

        // 根据类型执行不同的业务
        switch ($message_data['type']) {
            // 客户端回应服务端的心跳
            case 'pong':
                return;
            // 客户端登录 message格式: {type:login, name:xx, room_id:1} ，添加到客户端，广播给所有客户端xx进入聊天室
            case 'login':
                // 判断是否有房间号
                if (!isset($message_data['room_id'])) {
                    throw new \Exception("\$message_data['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']} \$message:$message");
                }

                // 把房间号昵称放到session中
                $room_id = $message_data['room_id'];
                $client_name = htmlspecialchars($message_data['client_name']);
                $client_nickname = htmlspecialchars($message_data['client_nickname']);
                $_SESSION['room_id'] = $room_id;
                $_SESSION['client_name'] = $client_name;
                $_SESSION['client_nickname'] = $client_nickname;

                // 获取房间内所有用户列表
                $clients_list = Gateway::getClientSessionsByGroup($room_id);
                foreach ($clients_list as $tmp_client_id => $item) {

                    $clients_list[$tmp_client_id] = $item['client_name'];

                    //重复登录检测
//                    if ($item['client_name'] === $client_name) {
//                        break;
//                    }
                }

                $clients_list[$client_id] = $client_name;
                DRedis::getInstance()->set($client_name, $client_id);

                // 转播给当前房间的所有客户端，xx进入聊天室 message {type:login, client_id:xx, name:xx}
                $new_message = array('type' => $message_data['type'], 'client_id' => $client_id, 'client_name' => htmlspecialchars($client_name), 'client_nickname' => htmlspecialchars($client_nickname), 'time' => date('Y-m-d H:i:s'));
//                Gateway::sendToGroup($room_id, json_encode($new_message));
                //修改为只给自己发送登录信号，为了本地缓存$client_id
                Gateway::sendToCurrentClient(json_encode($new_message));
                Gateway::joinGroup($client_id, $room_id);

                // 给当前用户发送用户列表
                //TODO:: client_list 为web端测试时使用
//                $new_message['client_list'] = $clients_list;
//                Gateway::sendToCurrentClient(json_encode($new_message));

                //查询未读数据
                $arr = [];
                $conn = new mysqli(HOST, USER, PASS, DBNAME);
                if ($conn != null) {
                    $query_result = $conn->query("select * from unread where to_client_name = " . $client_name);
                    while ($row = $query_result->fetch_row()) {
                        array_push($arr, $row);
                    }
                    //清空未读数据
                    $conn->query("delete from unread where to_client_name = " . $client_name);
                    $conn->close();
                }
                $unread_message = array(
                    'type' => 'unread',
                    'data' => $arr,
                    'time' => date('Y-m-d H:i:s')
                );

                echo '收到登录信号';
                return Gateway::sendToCurrentClient(json_encode($unread_message));

            // 客户端发言 message: {type:say, to_client_id:xx, content:xx}
            case 'singleTalk':
                // 非法请求
                if (!isset($_SESSION['room_id'])) {
                    throw new \Exception("\$_SESSION['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
                }
                $room_id = $_SESSION['room_id'];
                $client_name = $_SESSION['client_name'];
                $client_nickname = $_SESSION['client_nickname'];
                $to_client_name = $message_data['to_client_name'];

//                $message_data['to_client_id']
                $to_client_id = DRedis::getInstance()->seget($to_client_name);
                Logger::printStr("向哪个用户发送：", $to_client_id);
                // 私聊
                if ($message_data['to_client_id'] != 'all') {
                    //如果不在线，则推送
//                    if (Gateway::isOnline($to_client_id) === 0) {
                    if (!$to_client_id) {
                        Logger::printStr("状态:", "不在线");

                        $conn = new mysqli(HOST, USER, PASS, DBNAME);
                        if ($conn != null) {
                            $query_result = $conn->query("insert into unread (type,from_client_id,from_client_name,from_client_nickname,to_client_id,to_client_name,content,time,msg_type) values('singleTalk','" . $client_id . "','" . $client_name . "','" . $client_nickname . "','" . $message_data['to_client_id'] . "','" . $to_client_name . "','" . nl2br(htmlspecialchars($message_data['content'])) . "','" . date('Y-m-d H:i:s') . "','text')");
                            $disturb_result = $conn->query("select count(*) from disturb where from_id = " . $client_name . " and to_id = " . $to_client_name . " and disturb_type = 'singleTalk';");
                            $disturb_count = $disturb_result->fetch_row()[0];
                            Logger::printStr("免打扰状态：", $disturb_count);
                            //未设置免打扰
                            if ($disturb_count === "0") {
                                Logger::printStr("状态:", "发送离线通知");

                                $app_key = '27837b1c1fed6927c288e3df';
                                $master_secret = 'c7664b0d3f55056db560ecab';

                                $client = new JPush($app_key, $master_secret);
                                $push = $client->push();

                                $cid = $push->getCid($count = 1, $type = 'push');
                                $cid = $cid['body']['cidlist'][0];
                                // 完整的推送示例
                                // 这只是使用样例,不应该直接用于实际生产环境中 !!
                                try {
                                    $alias = 'qy_' . $to_client_name;
                                    Logger::printStr('发送的alias', $alias);
                                    $response = $push
                                        ->setCid($cid)
                                        ->setPlatform(['ios', 'android'])
                                        ->addAlias([$alias])
                                        ->setNotificationAlert('未读信息')
                                        ->iosNotification($client_nickname . ":" . $message_data['content'], [
                                            'badge' => '+1',
                                            'extras' => [
                                                'content' => $message_data['content'],
                                                'client_name' => $client_name,
                                                'client_nickname' => $client_nickname
                                            ]
                                        ])
                                        ->androidNotification('未读信息')
                                        ->message('未读信息', [
                                            'title' => 'Hello',
                                            'content_type' => 'text',
                                            'extras' => [
                                                'key' => 'value'
                                            ]
                                        ])
                                        ->send();
                                } catch (\JPush\Exceptions\APIConnectionException $e) {
                                    // try something here
                                    print $e;
                                } catch (\JPush\Exceptions\APIRequestException $e) {
                                    // try something here
                                    print $e;
                                }
                            }
                            $conn->close();
                        }

                    } else {
                        $new_message = array(
                            'type' => 'singleTalk',
                            'from_client_id' => $client_id,
                            'from_client_name' => $client_name,
                            'from_client_nickname' => $client_nickname,
                            'to_client_id' => $to_client_id,
                            'to_client_name' => $to_client_name,
                            'content' => nl2br(htmlspecialchars($message_data['content'])),
                            'time' => date('Y-m-d H:i:s'),
                        );
//                        Gateway::sendToClient($message_data['to_client_id'], json_encode($new_message));
                        Gateway::sendToClient($to_client_id, json_encode($new_message));
                    }
//                    $new_message['content'] = "<b>你对" . htmlspecialchars($message_data['to_client_name']) . "说: </b>" . nl2br(htmlspecialchars($message_data['content']));
//                    return Gateway::sendToCurrentClient(json_encode($new_message));
                    return;
                }


//                $new_message = array(
//                    'type' => 'say',
//                    'from_client_id' => $client_id,
//                    'from_client_name' => $client_name,
//                    'to_client_id' => 'all',
//                    'content' => nl2br(htmlspecialchars($message_data['content'])),
//                    'time' => date('Y-m-d H:i:s'),
//                );
//                return Gateway::sendToGroup($room_id, json_encode($new_message));
                return;

            case 'setDisturb':
                $disturb_type = $message_data['disturb_type'];
                //动作:add设置免打扰，del删除免打扰
                $action = $message_data['action'];
                $from_id = $message_data['from_id'];
                $to_id = $message_data['to_id'];
                $conn = new mysqli(HOST, USER, PASS, DBNAME);
                $result = -1;
                if ($conn != null) {
                    if ($action === 'add') {
                        $result = $conn->query("insert into disturb (from_id,to_id,disturb_type) values(" . $from_id . "," . $to_id . ", '" . $disturb_type . "');");
                    } else if ($action === 'del') {
                        $result = $conn->query("delete from disturb where from_id = " . $from_id . " and to_id = " . $to_id . " and disturb_type = '" . $disturb_type . "';");
                    }
                }
                $conn->close();
                $new_message = array(
                    'type' => 'setDisturbResult',
                    'from_id' => $from_id,
                    'to_id' => $to_id,
                    'disturb_type' => $disturb_type,
                    'action'=>$action,
                    'result' => $result
                );
                Gateway::sendToCurrentClient(json_encode($new_message));
                return;
            case 'getDisturb':
                print_r('消息:getDisturb');
                $disturb_type = $message_data['disturb_type'];
                $from_id = $message_data['from_id'];
                $to_id = $message_data['to_id'];
                $conn = new mysqli(HOST, USER, PASS, DBNAME);
                if ($conn != null) {
                    $query_result = $conn->query("select count(*) from disturb where from_id = " . $from_id . " and to_id = " . $to_id . " and disturb_type = '" . $disturb_type . "';");
                    while ($row = $query_result->fetch_row()) {
                        $new_message = array(
                            'type' => 'getDisturbResult',
                            'from_id' => $from_id,
                            'to_id' => $to_id,
                            'result' => $row
                        );
                        print_r($row);
                        Gateway::sendToCurrentClient(json_encode($new_message));
                    }
                }
                $conn->close();
                return;
        }
    }

    /**
     * 当客户端断开连接时
     * @param integer $client_id 客户端id
     */
    public static function onClose($client_id)
    {
        // debug
        echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id onClose:''\n";

        // 从房间的客户端列表中删除
        if (isset($_SESSION['room_id'])) {
            $room_id = $_SESSION['room_id'];
            $new_message = array('type' => 'logout', 'from_client_id' => $client_id, 'from_client_name' => $_SESSION['client_name'], 'time' => date('Y-m-d H:i:s'));
            DRedis::getInstance()->del($_SESSION['client_name']);
            //修改为不广播账户登出操作
//            Gateway::sendToGroup($room_id, json_encode($new_message));
        }
    }

}
