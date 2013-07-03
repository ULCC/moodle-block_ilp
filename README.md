ILP enhancements additional information

====*+*====*+*====*+*====*+*====*+*====

Please create a new table manually as below, until I add the script for upgrade

--------------------------------------------------------------------

delimiter $$

CREATE TABLE `mdl_block_ilp_user_choice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `element_id` varchar(100) NOT NULL,
  `choice` varchar(255) NOT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1$$


