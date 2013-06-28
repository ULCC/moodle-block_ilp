ILP enhancements additional information

====*+*====*+*====*+*====*+*====*+*====

We need to make sure that, the following field has been add to the table called "mdl_block_ilp_plu_sts_items" in database:

1. icon - VARCHAR(45) - NULL

2. display_option - VARCHAR(4) - NULL

3. description - VARCHAR(255) - NULL

4. bg_colour - VARCHAR(45) - NULL


Please remember to clear the cache after you update the database, as this table is cached in moodle.


The upgrade script has been add, therefore please check your database, if you can see those fields, Please notify me (Abdul) if the upgrade script doesn't work.