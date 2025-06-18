/*
 Navicat Premium Dump SQL

 Source Server         : localhost_3306
 Source Server Type    : MySQL
 Source Server Version : 50726 (5.7.26)
 Source Host           : localhost:3306
 Source Schema         : cloudshopping

 Target Server Type    : MySQL
 Target Server Version : 50726 (5.7.26)
 File Encoding         : 65001

 Date: 16/06/2025 21:07:08
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for cart
-- ----------------------------
DROP TABLE IF EXISTS `cart`;
CREATE TABLE `cart`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `product_id`(`product_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 15 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Records of cart
-- ----------------------------
INSERT INTO `cart` VALUES (3, 6, 2, 2);
INSERT INTO `cart` VALUES (4, 6, 4, 1);

-- ----------------------------
-- Table structure for categories
-- ----------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `parent_id` int(11) NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `parent_id`(`parent_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 9 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of categories
-- ----------------------------
INSERT INTO `categories` VALUES (1, '电子配件', NULL, '2023-06-08 10:05:13');
INSERT INTO `categories` VALUES (2, '手机', 1, '2025-06-08 09:58:31');
INSERT INTO `categories` VALUES (3, '电视', 1, '2023-06-08 10:05:13');
INSERT INTO `categories` VALUES (4, '家电', NULL, '2024-06-08 21:05:13');
INSERT INTO `categories` VALUES (5, '耳机', 1, '2023-06-08 21:05:13');
INSERT INTO `categories` VALUES (6, '相机', 4, '2023-06-08 21:05:13');
INSERT INTO `categories` VALUES (7, '无人机', 4, '2023-06-08 21:05:13');
INSERT INTO `categories` VALUES (8, '吸尘器', 4, '2022-06-08 21:05:13');

-- ----------------------------
-- Table structure for customer_service
-- ----------------------------
DROP TABLE IF EXISTS `customer_service`;
CREATE TABLE `customer_service`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NULL DEFAULT NULL,
  `type` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `status` enum('pending','processing','resolved') CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 10 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of customer_service
-- ----------------------------
INSERT INTO `customer_service` VALUES (1, 5, 1, '退货', '电视屏幕有坏点', 'resolved', '2023-10-03 10:00:00');
INSERT INTO `customer_service` VALUES (2, 5, 2, '物流', '发货后未更新物流信息', 'processing', '2023-11-16 14:20:00');
INSERT INTO `customer_service` VALUES (3, 6, NULL, '咨询', '无人机是否支持避障功能', 'resolved', '2023-11-10 09:30:00');
INSERT INTO `customer_service` VALUES (4, 6, 3, '退款', '误操作多买了一件', 'resolved', '2023-11-21 11:40:00');
INSERT INTO `customer_service` VALUES (5, 5, NULL, '咨询', '有没有促销活动', 'resolved', '2025-06-14 13:49:15');

-- ----------------------------
-- Table structure for order_details
-- ----------------------------
DROP TABLE IF EXISTS `order_details`;
CREATE TABLE `order_details`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10, 2) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE,
  INDEX `product_id`(`product_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 13 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Records of order_details
-- ----------------------------
INSERT INTO `order_details` VALUES (1, 1, 1, 1, 8999.00);
INSERT INTO `order_details` VALUES (2, 1, 3, 1, 12999.00);
INSERT INTO `order_details` VALUES (3, 2, 4, 1, 3990.00);
INSERT INTO `order_details` VALUES (4, 3, 2, 2, 6999.00);
INSERT INTO `order_details` VALUES (5, 4, 6, 1, 6988.00);
INSERT INTO `order_details` VALUES (6, 5, 1, 3, 8999.00);
INSERT INTO `order_details` VALUES (7, 6, 3, 1, 12999.00);

-- ----------------------------
-- Table structure for orders
-- ----------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `total` decimal(10, 2) NOT NULL,
  `status` enum('pending','paid','shipped','completed','cancelled') CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 12 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Records of orders
-- ----------------------------
INSERT INTO `orders` VALUES (1, 5, 21998.00, 'completed', '2023-10-01 14:30:00');
INSERT INTO `orders` VALUES (2, 5, 3990.00, 'shipped', '2023-11-15 09:15:00');
INSERT INTO `orders` VALUES (3, 6, 13998.00, 'shipped', '2023-11-20 16:45:00');
INSERT INTO `orders` VALUES (4, 6, 6988.00, 'pending', '2023-11-21 10:00:00');
INSERT INTO `orders` VALUES (5, 5, 26997.00, 'cancelled', '2025-06-12 18:45:39');
INSERT INTO `orders` VALUES (6, 5, 12999.00, 'cancelled', '2025-06-12 18:58:50');
INSERT INTO `orders` VALUES (7, 5, 8999.00, 'cancelled', '2025-06-12 19:16:47');

-- ----------------------------
-- Table structure for payments
-- ----------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `amount` decimal(10, 2) NOT NULL,
  `method` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `status` enum('pending','success','failed') CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 12 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of payments
-- ----------------------------
INSERT INTO `payments` VALUES (1, 1, 21998.00, '支付宝', 'success', '2023-10-01 14:35:00');
INSERT INTO `payments` VALUES (2, 2, 3990.00, '微信支付', 'success', '2023-11-15 09:20:00');
INSERT INTO `payments` VALUES (3, 3, 13998.00, '银联', 'success', '2023-11-20 16:50:00');

-- ----------------------------
-- Table structure for products
-- ----------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
  `price` decimal(10, 2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `category_id` int(11) NULL DEFAULT NULL,
  `audit_status` enum('pending','approved','rejected') CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT 'pending',
  `image_url` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `seller_id`(`seller_id`) USING BTREE,
  INDEX `category_id`(`category_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 10 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of products
-- ----------------------------
INSERT INTO `products` VALUES (1, 2, 'iPhone 15 Pro智能手机', 'A17芯片 6.1英寸超视网膜屏', 8999.00, 50, '2023-06-08 21:05:13', 2, 'approved', 'uploads/684d4d22bdcec.jpg');
INSERT INTO `products` VALUES (2, 2, '华为Mate 60智能手机', '麒麟9000s 卫星通话', 6999.00, 30, '2023-06-08 21:05:13', 2, 'approved', 'uploads/684d4d1539488.jpg');
INSERT INTO `products` VALUES (3, 3, '索尼4K电视 75\"', 'XR认知芯片 120Hz刷新率', 12999.00, 15, '2023-06-08 21:05:13', 3, 'approved', 'uploads/684d4ccc995bd.jpg');
INSERT INTO `products` VALUES (4, 3, '戴森吸尘器 V12', '激光探测 150AW吸力', 3990.00, 40, '2023-06-08 21:05:13', 8, 'approved', 'uploads/684d4cc277c8f.jpg');
INSERT INTO `products` VALUES (5, 4, '佳能 EOS R5相机', '8K视频 45MP全画幅', 25999.00, 8, '2023-06-08 21:05:13', 6, 'approved', 'uploads/684d4df878152.jpg');
INSERT INTO `products` VALUES (6, 4, '大疆 Air 3无人机', '双主摄 46分钟续航', 6988.00, 12, '2024-06-08 21:05:13', 7, 'approved', 'uploads/684d4ded4f79d.jpg');

-- ----------------------------
-- Table structure for seller_services
-- ----------------------------
DROP TABLE IF EXISTS `seller_services`;
CREATE TABLE `seller_services`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `service_type` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `status` enum('pending','processing','resolved') CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `seller_id`(`seller_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 5 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of seller_services
-- ----------------------------
INSERT INTO `seller_services` VALUES (1, 2, '平台咨询', '如何提升店铺曝光率？', 'resolved', '2025-06-08 21:05:13');
INSERT INTO `seller_services` VALUES (2, 3, '费用问题', '本月佣金计算有疑问', 'processing', '2025-06-08 21:05:13');
INSERT INTO `seller_services` VALUES (3, 4, '功能请求', '希望增加销售数据分析功能', 'pending', '2025-06-08 21:05:13');
INSERT INTO `seller_services` VALUES (4, 2, '平台咨询', '如何参与促销活动', 'processing', '2025-06-14 14:15:20');

-- ----------------------------
-- Table structure for shops
-- ----------------------------
DROP TABLE IF EXISTS `shops`;
CREATE TABLE `shops`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `shop_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
  `contact_phone` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `seller_id`(`seller_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 4 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of shops
-- ----------------------------
INSERT INTO `shops` VALUES (1, 2, '手机旗舰', '主营高端智能手机', '13900139000', '2023-06-08 09:05:13');
INSERT INTO `shops` VALUES (2, 3, '家电王国', '家电一站式购物平台', '13700137000', '2023-06-08 11:05:13');
INSERT INTO `shops` VALUES (3, 4, '数码世界', '专业数码产品供应商', '13500135000', '2023-06-08 21:05:13');

-- ----------------------------
-- Table structure for user_profiles
-- ----------------------------
DROP TABLE IF EXISTS `user_profiles`;
CREATE TABLE `user_profiles`  (
  `user_id` int(11) NOT NULL,
  `real_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `avatar_url` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `address` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`) USING BTREE
) ENGINE = MyISAM CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of user_profiles
-- ----------------------------
INSERT INTO `user_profiles` VALUES (1, '曾轩', NULL, '北京市海淀区', '2025-06-08 21:05:13');
INSERT INTO `user_profiles` VALUES (2, '高晓云', NULL, '上海市浦东新区', '2025-06-08 21:05:13');
INSERT INTO `user_profiles` VALUES (3, '徐义诚', NULL, '广州市天河区', '2025-06-08 21:05:13');
INSERT INTO `user_profiles` VALUES (4, '赵志康', NULL, '深圳市南山区', '2025-06-08 21:05:13');
INSERT INTO `user_profiles` VALUES (5, '王伟强', NULL, '杭州市西湖区', '2025-06-14 12:54:02');
INSERT INTO `user_profiles` VALUES (6, '李明兰', NULL, '成都市武侯区', '2025-06-08 21:05:13');

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `role` enum('admin','seller','user') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `phone`(`phone`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 7 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES (1, '平台管理员', 'admin123', '13800138000', 'admin', '2020-06-08 09:05:13');
INSERT INTO `users` VALUES (2, '手机旗舰店', 'seller123', '13900139000', 'seller', '2023-06-07 21:05:13');
INSERT INTO `users` VALUES (3, '家电王国', 'seller456', '13700137000', 'seller', '2022-06-08 10:05:10');
INSERT INTO `users` VALUES (4, '数码达人', 'seller789', '13500135000', 'seller', '2021-06-08 15:05:13');
INSERT INTO `users` VALUES (5, '酷酷的小王', 'user123', '13600136000', 'user', '2023-06-08 08:05:13');
INSERT INTO `users` VALUES (6, '靓女小李', 'user456', '13100131000', 'user', '2022-06-10 21:05:13');

SET FOREIGN_KEY_CHECKS = 1;
