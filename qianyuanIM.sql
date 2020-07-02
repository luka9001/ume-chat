/*
 Navicat Premium Data Transfer

 Source Server         : qianyuanIMtest
 Source Server Type    : MySQL
 Source Server Version : 50730
 Source Host           : 192.168.201.109
 Source Database       : qianyuanIM

 Target Server Type    : MySQL
 Target Server Version : 50730
 File Encoding         : utf-8

 Date: 07/02/2020 08:17:18 AM
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `disturb`
-- ----------------------------
DROP TABLE IF EXISTS `disturb`;
CREATE TABLE `disturb` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_id` int(11) DEFAULT NULL,
  `to_id` int(11) DEFAULT NULL,
  `disturb_type` varchar(255) DEFAULT NULL COMMENT '群聊或单聊',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `unread`
-- ----------------------------
DROP TABLE IF EXISTS `unread`;
CREATE TABLE `unread` (
  `id` int(100) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) DEFAULT NULL COMMENT '聊天类型',
  `from_client_id` varchar(255) DEFAULT NULL COMMENT '发送方聊天服务器id',
  `from_client_name` varchar(255) DEFAULT NULL COMMENT '发送方用户id',
  `from_client_nickname` varchar(255) DEFAULT NULL COMMENT '发送方用户昵称',
  `to_client_id` varchar(255) DEFAULT NULL COMMENT '接收方聊天服务器id',
  `to_client_name` varchar(255) DEFAULT NULL COMMENT '接收方用户id',
  `content` varchar(255) DEFAULT NULL COMMENT '聊天内容',
  `time` datetime DEFAULT NULL,
  `msg_type` varchar(255) DEFAULT 'text' COMMENT '消息类型',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
