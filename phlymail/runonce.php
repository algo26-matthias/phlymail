<?php
/**
 * Runonce.php - updating the DB structure of phlyMail if necessary
 * @package phlyMail Nahariya 4.0+
 * @subpackage phlyMail MessageCenter
 * @subpackage Core application
 * @author Matthias Sommerfeld
 * @copyright 2005-2015 phlyLabs, http://phlylabs.de/
 * @version 1.8.8mod1 2015-12-09
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();
// Try to run as long as any way possible
@set_time_limit(0);
// Only necessary when updating from phlyMail < 3.7.21
if (!function_exists('uuid')) {
    function uuid($prefix = '')
    {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid  = substr($chars, 0, 8) . '-' . substr($chars, 8, 4) . '-' . substr($chars, 12, 4) . '-'
                . substr($chars, 16, 4) . '-' . substr($chars, 20, 12);

        return strval($prefix) . $uuid;
    }
}
$structure = array
        ('adb_adr' => array
                ('fields' => array
                        ('aid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'owner' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'visibility' => array('type' => 'enum(\'private\',\'public\')', 'null' => 0, 'default' => 'private', 'key' => 0, 'extra' => 0)
                        ,'uuid' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'nick' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'firstname' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'thirdname' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'lastname' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'title' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'customer_number' => array('type' => 'varchar(32)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'company' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'comp_dep' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'comp_address' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'comp_address2' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'comp_street' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'comp_zip' => array('type' => 'varchar(16)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'comp_location' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'comp_region' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'comp_country' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'comp_role' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'comp_geo_lat' => array('type' => 'double(9,6)', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'comp_geo_long' => array('type' => 'double(9,6)', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'comp_cellular' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'comp_fax' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'tel_business' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'comp_www' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'address' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'address2' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'street' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'zip' => array('type' => 'varchar(16)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'location' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'country' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'region' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'personal_geo_lat' => array('type' => 'double(9,6)', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'personal_geo_long' => array('type' => 'double(9,6)', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'birthday' => array('type' => 'date', 'null' => 0, 'default' => '0000-00-00', 'key' => 0, 'extra' => 0)
                        ,'bday_cal_evt_id' => array('type' => 'bigint(20) unsigned', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'email1' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'email2' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'tel_private' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'cellular' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'fax' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'www' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'comments' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'image' => array('type' => 'blob', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'imagemeta' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'type' => array('type' => 'enum(\'user\',\'contact\')', 'null' => 0, 'default' => 'contact', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('owner' => 'owner', 'nick' => 'nick', 'visibility' => 'visibility', 'lastname' => 'lastname'
                        ,'firstname' => 'firstname', 'email1' => 'email1', 'email2' => 'email2', 'tel_private' => 'tel_private'
                        ,'tel_business' => 'tel_business', 'cellular' => 'cellular', 'fax' => 'fax', 'type' => 'type')
                )
        ,'adb_adr_group' => array
                ('fields' => array
                        ('agid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'aid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'gid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        )
                ,'unique' => array('aguid' => '`aid`,`gid`,`uid`')
                )
        ,'adb_attachemnts' => array
                ('fields' => array
                        ('id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'aid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'files_id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('aid' => 'aid', 'files_id' => 'files_id')
                )
        ,'adb_freefield' => array(
                'fields' => array(
                        'id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1),
                        'aid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0),
                        'type_id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0),
                        'value' => array('type' => 'text', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        )
                ,'unique' => array('aid_type' => '`aid`,`type_id`')
                )
        ,'adb_freefield_type' => array(
                'fields' => array(
                        'id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1),
                        'uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0),
                        'name' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0),
                        'token' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0),
                        'type' => array('type' => 'enum(\'text\',\'textarea\',\'number\',\'date\')', 'null' => 0, 'default' => 'text', 'key' => 0, 'extra' => 0),
                        'status' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '1', 'key' => 0, 'extra' => 0)
                        )
                ,'unique' => array('uid_name' => '`uid`,`name`', 'uid_token' => '`uid`,`token`')
                )
        ,'adb_group' => array
                ('fields' => array
                        ('gid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'owner' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'name' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'type' => array('type' => 'tinyint(4)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'uri' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'ext_un' => array('type' => 'varchar(255)', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'ext_pw' => array('type' => 'varchar(255)', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'mime' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'laststatus' => array('type' => 'tinyint(4)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'lasterror' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('owner' => 'owner', 'name' => 'name', 'type' => 'type')
                )
        ,'admin' => array
                ('fields' => array
                        ('uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'is_root' => array('type' => 'enum(\'no\',\'yes\')', 'null' => 0, 'default' => 'no', 'key' => 0, 'extra' => 0)
                        ,'username' => array('type' => 'varbinary(64)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'password' => array('type' => 'varchar(32)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'pw_digesta1' => array('type' => 'varchar(32)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'token' => array('type' => 'varchar(32)', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'token_valid' => array('type' => 'datetime', 'null' => 1, 'default' => '0000-00-00 00:00:00', 'key' => 0, 'extra' => 0)
                        ,'externalemail' => array('type' => 'varchar(255)', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'active' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'fail_count' => array('type' => 'tinyint(4)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'fail_time' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'choices' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'permissions' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'logintime' => array('type' => 'datetime', 'null' => 0, 'default' => '0000-00-00 00:00:00', 'key' => 0, 'extra' => 0)
                        ,'logouttime' => array('type' => 'datetime', 'null' => 0, 'default' => '0000-00-00 00:00:00', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('active' => 'active', 'token_valid' => 'token_valid', 'externalemail' => 'externalemail')
                ,'unique' => array('username' => 'username', 'token' => 'token')
                )
        ,'bookmarks_folder' => array
                ('fields' => array
                        ('id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'uuid' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'layered_id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'childof' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'owner' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'name' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'description' => array('type' => 'longtext', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'type' => array('type' => 'tinyint(4)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'uri' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'ext_un' => array('type' => 'varchar(255)', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'ext_pw' => array('type' => 'varchar(255)', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'mime' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'laststatus' => array('type' => 'tinyint(4)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'lasterror' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('owner' => 'owner', 'name' => 'name', 'childof' => 'childof')
                )
        ,'bookmarks_item' => array
                ('fields' => array
                        ('id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'uuid' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'owner' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'fid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'name' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'url' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'description' => array('type' => 'longtext', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'favourite' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'added' => array('type' => 'datetime', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'modified' => array('type' => 'datetime', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'visited' => array('type' => 'datetime', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('owner' => 'owner', 'name' => 'name', 'fid' => 'fid')
                )
        ,'calendar_events' => array
                ('fields' => array
                        ('id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'gid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'pid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'uuid' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'title' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'location' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'description' => array('type' => 'longtext', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'starts' => array('type' => 'datetime', 'null' => 0, 'default' => '0000-00-00 00:00:00', 'key' => 0, 'extra' => 0)
                        ,'ends' => array('type' => 'datetime', 'null' => 0, 'default' => '0000-00-00 00:00:00', 'key' => 0, 'extra' => 0)
                        ,'lastmod' => array('type' => 'datetime', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'type' => array('type' => 'int(11)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'status' => array('type' => 'int(11)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'opaque' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '1', 'key' => 0, 'extra' => 0)
                        ,'archived' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('starts' => 'starts', 'ends' => 'ends', 'uid' => 'uid', 'gid' => 'gid', 'pid' => 'pid',
                        'lastmod' => 'lastmod', 'type' => 'type', 'status' => 'status', 'opaque' => 'opaque', 'archived' => 'archived')
                )
        ,'calendar_event_attachments' => array
                ('fields' => array
                        ('id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'eid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'ref' => array('type' => 'enum(\'evt\',\'tsk\',\'jou\')', 'null' => 0, 'default' => 'evt', 'key' => 0, 'extra' => 0)
                        ,'files_id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('eid' => 'eid', 'ref' => 'ref', 'files_id' => 'files_id')
                )
        ,'calendar_event_attendees' => array
                ('fields' => array
                        ('id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'eid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'ref' => array('type' => 'enum(\'evt\',\'tsk\',\'jou\')', 'null' => 0, 'default' => 'evt', 'key' => 0, 'extra' => 0)
                        ,'name' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'email' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'role' => array('type' => 'enum(\'chair\',\'req\',\'opt\',\'non\')', 'null' => 0, 'default' => 'opt', 'key' => 0, 'extra' => 0)
                        ,'type' => array('type' => 'enum(\'person\',\'group\',\'resource\',\'room\',\'unknown\')', 'null' => 0, 'default' => 'person', 'key' => 0, 'extra' => 0)
                        ,'mailhash' => array('type' => 'varchar(64)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'invited' => array('type' => 'datetime', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'rsvp' => array('type' => 'datetime', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'status' => array('type' => 'int(11)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('eid' => 'eid', 'ref' => 'ref', 'name' => 'name', 'rsvp' => 'rsvp', 'status' => 'status')
                ,'unique' => array('eid_ref_name_email' => '`ref`,`eid`,`name`,`email`', 'mailhash' => 'mailhash')
                )
        ,'calendar_event_reminders' => array
                ('fields' => array
                        ('id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'eid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'ref' => array('type' => 'enum(\'evt\',\'tsk\',\'jou\')', 'null' => 0, 'default' => 'evt', 'key' => 0, 'extra' => 0)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'time' => array('type' => 'bigint(20) unsigned', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'snooze' => array('type' => 'datetime', 'null' => 0, 'default' => '0000-00-00 00:00:00', 'key' => 0, 'extra' => 0)
                        ,'lastinfo' => array('type' => 'datetime', 'null' => 0, 'default' => '0000-00-00 00:00:00', 'key' => 0, 'extra' => 0)
                        ,'mode' => array('type' => 'enum(\'-\',\'s\',\'e\')', 'null' => 0, 'default' => '-', 'key' => 0, 'extra' => 0)
                        ,'text' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'mailto' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'smsto' => array('type' => 'varchar(20)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('eid' => 'eid', 'ref' => 'ref', 'uid' => 'uid', 'time' => 'time', 'lastinfo' => 'lastinfo', 'mailto' => 'mailto', 'smsto' => 'smsto')
                )
        ,'calendar_event_repetitions' => array
                ('fields' => array
                        ('id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'eid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'ref' => array('type' => 'enum(\'evt\',\'tsk\',\'jou\')', 'null' => 0, 'default' => 'evt', 'key' => 0, 'extra' => 0)
                        ,'type' => array('type' => 'enum(\'-\',\'day\',\'week\',\'month\',\'year\',\'disable\')', 'null' => 0, 'default' => '-', 'key' => 0, 'extra' => 0)
                        ,'repeat' => array('type' => 'int(11)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'extra' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'until' => array('type' => 'datetime', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('eid' => 'eid', 'ref' => 'ref', 'type' => 'type', 'repeat' => 'repeat', 'until' => 'until')
                )
        ,'calendar_groups' => array
                ('fields' => array
                        ('gid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'owner' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'name' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'colour' => array('type' => 'varchar(6)', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'type' => array('type' => 'tinyint(4)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'uri' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'ext_un' => array('type' => 'varchar(255)', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'ext_pw' => array('type' => 'varchar(255)', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'mime' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'rw' => array('type' => 'enum(\'ro\',\'rw\')', 'null' => 0, 'default' => 'rw', 'key' => 0, 'extra' => 0)
                        ,'laststatus' => array('type' => 'tinyint(4)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'lasterror' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('owner' => 'owner', 'name' => 'name', 'type' => 'type')
                )
        ,'calendar_holidays' => array
                ('fields' => array
                        ('hid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'gid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'hname' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'hdate' => array('type' => 'date', 'null' => 0, 'default' => '0000-00-00', 'key' => 0, 'extra' => 0)
                        ,'recurring' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('uid' => 'uid', 'gid' => 'gid', 'hdate' => 'hdate', 'recurring' => 'recurring')
                )
        ,'calendar_projects' => array
                ('fields' => array
                        ('id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'gid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'uuid' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'title' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'location' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'description' => array('type' => 'longtext', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'starts' => array('type' => 'datetime', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'ends' => array('type' => 'datetime', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'lastmod' => array('type' => 'datetime', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'importance' => array('type' => 'int(11)', 'null' => 0, 'default' => 1, 'key' => 0, 'extra' => 0)
                        ,'completion' => array('type' => 'int(11)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'status' => array('type' => 'int(11)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('starts' => 'starts', 'ends' => 'ends', 'completion' => 'completion', 'importance' => 'importance'
                        ,'uid' => 'uid', 'gid' => 'gid', 'status' => 'status')
                )
        ,'calendar_tasks' => array
                ('fields' => array
                        ('id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'gid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'pid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'uuid' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'title' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'location' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'description' => array('type' => 'longtext', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'starts' => array('type' => 'datetime', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'ends' => array('type' => 'datetime', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'lastmod' => array('type' => 'datetime', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'importance' => array('type' => 'int(11)', 'null' => 0, 'default' => 1, 'key' => 0, 'extra' => 0)
                        ,'completion' => array('type' => 'int(11)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'type' => array('type' => 'int(11)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'status' => array('type' => 'int(11)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'archived' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('starts' => 'starts', 'ends' => 'ends', 'completion' => 'completion', 'importance' => 'importance'
                        ,'uid' => 'uid', 'gid' => 'gid', 'pid' => 'pid', 'type' => 'type', 'status' => 'status', 'archived' => 'archived')
                )
        ,'core_crontab' => array
                ('fields' => array
                        ('handler' => array('type' => 'varchar(64)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'job' => array('type' => 'varchar(64)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'item' => array('type' => 'bigint(20)', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'interval' => array('type' => 'bigint(20)', 'null' => 0, 'default' => '1', 'key' => 0, 'extra' => 0)
                        ,'runonce' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'prio' => array('type' => 'int(11)', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'reference_time' => array('type' => 'time', 'null' => 0, 'default' => '00:00', 'key' => 0, 'extra' => 0)
                        ,'pid' => array('type' => 'int(11)', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'at_once' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'laststart' => array('type' => 'datetime', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'laststop' => array('type' => 'datetime', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('interval' => 'interval', 'prio' => 'prio', 'reference_time' => 'reference_time', 'at_once' => 'at_once', 'runonce' => 'runonce',
                        'laststart' => 'laststart', 'laststop' => 'laststop')
                ,'unique' => array('jobkey' => '`handler`,`job`,`item`')
                )
        ,'core_derefer' => array
                ('fields' => array
                        ('nonce' => array('type' => 'varchar(64)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'uri' => array('type' => 'text', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'expires' => array('type' => 'datetime', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('expires' => 'expires')
                ,'unique' => array('nonce' => 'nonce')
                )
        ,'core_lock' => array
                ('fields' => array
                        ('owner' => array('type' => 'varchar(128)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'timeout' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'created' => array('type' => 'bigint(20)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'token' => array('type' => 'varchar(128)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'uri' => array('type' => 'text', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'scope' => array('type' => 'tinyint(4)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'depth' => array('type' => 'tinyint(4)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('uri' => '`uri`(200)', 'owner' => 'owner', 'timeout' => 'timeout', 'created' => 'created', 'depth' => 'depth')
                ,'unique' => array('token' => 'token')
                )
        ,'core_noauth' => array
                ('fields' => array
                        ('uuid' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'handler' => array('type' => 'varchar(64)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'load' => array('type' => 'varchar(64)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'action' => array('type' => 'varchar(64)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        )
                ,'unique' => array('uuid' => 'uuid')
                )
        ,'core_thumbs' => array
                ('fields' => array
                        ('id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'handler' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'item' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'uuid' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'type' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'mime' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'len' => array('type' => 'int(11) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'w' => array('type' => 'int(11) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'h' => array('type' => 'int(11) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'body' => array('type' => 'mediumblob', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('handler' => 'handler', 'item' => 'item', 'type' => 'type')
                )
        ,'email_boilerplates' => array
                ('fields' => array
                        ('id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'gid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'owner' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'type' => array('type' => 'enum(\'text\',\'html\')', 'null' => 0, 'default' => 'text', 'key' => 0, 'extra' => 0)
                        ,'name' => array('type' => 'varchar(48)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'body' => array('type' => 'blob', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('gid' => 'gid', 'owner' => 'owner', 'type' => 'type')
                )
        ,'email_boilerplate_groups' => array
                ('fields' => array
                        ('id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'owner' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'layered_id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'name' => array('type' => 'varchar(64)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'childof' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('owner' => 'owner', 'childof' => 'childof', 'layered_id' => 'layered_id')
                )
        ,'email_filterrules' => array
                ('fields' => array
                        ('id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'filter' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'field' => array('type' => 'varchar(32)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'operator' => array('type' => 'varchar(32)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'search' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        )
                )
        ,'email_filters' => array
                ('fields' => array
                        ('filter' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'name' => array('type' => 'varchar(64)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'type' => array('type' => 'enum(\'incoming\',\'outgoing\',\'system\')', 'null' => 0, 'default' => 'incoming', 'key' => 0, 'extra' => 0)
                        ,'active' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'layered_id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'match' => array('type' => 'enum(\'any\',\'all\')', 'null' => 0, 'default' => 'any', 'key' => 0, 'extra' => 0)
                        ,'move' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'move_to' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'copy' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'copy_to' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'forward' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'forward_to' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'bounce' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'bounce_to' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'set_prio' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'new_prio' => array('type' => 'tinyint(4)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'mark_read' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'markread_status' => array('type' => 'varchar(32)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'set_colour' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'new_colour' => array('type' => 'varchar(6)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'mark_junk' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'delete' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'archive' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'run_script' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'script_name' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'alert_sms' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'sms_to' => array('type' => 'varchar(32)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'sms_timeframe' => array('type' => 'varchar(32)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'sms_minpause' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'sms_lastuse' => array('type' => 'datetime', 'null' => 0, 'default' => '0000-00-00 00:00:00', 'key' => 0, 'extra' => 0)
                        ,'alert_email' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'email_to' => array('type' => 'varchar(32)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'email_timeframe' => array('type' => 'varchar(32)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'email_minpause' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'email_lastuse' => array('type' => 'datetime', 'null' => 0, 'default' => '0000-00-00 00:00:00', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('uid' => 'uid', 'type' => 'type', 'active' => 'active')
                )
        ,'email_folders' => array
                ('fields' => array
                        ('idx' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'layered_id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'folder_path' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'friendly_name' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'childof' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'uuid' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'att_type' => array('type' => 'tinyint(4)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'att_icon' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'att_big_icon' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'att_has_folders' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'att_has_items' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'mailnum' => array('type' => 'int(11)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'mailsize' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'unread' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'unseen' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'srv_unseen' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'uidnext' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'uidvalidity' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'stale' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'visible' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '1', 'key' => 0, 'extra' => 0)
                        ,'secure' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('uid' => 'uid', 'childof' => 'childof', 'layered_id' => 'layered_id', 'unread' => 'unread', 'unseen' => 'unseen'
                        ,'stale' => 'stale', 'visible' => 'visible' ,'secure' => 'secure')
                )
        ,'email_index' => array
                ('fields' => array
                        ('idx' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'folder_id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'uidl' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'type' => array('type' => 'enum(\'mail\',\'sms\',\'ems\',\'mms\',\'fax\',\'appointment\',\'away\',\'receipt\',\'sysmail\')', 'null' => 0, 'default' => 'mail', 'key' => 0, 'extra' => 0)
                        ,'hfrom' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'hto' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'hsubject' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'hdate_sent' => array('type' => 'datetime', 'null' => 0, 'default' => '0000-00-00 00:00:00', 'key' => 0, 'extra' => 0)
                        ,'hdate_recv' => array('type' => 'datetime', 'null' => 0, 'default' => '0000-00-00 00:00:00', 'key' => 0, 'extra' => 0)
                        ,'hcc' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'hbcc' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'search_body' => array('type' => 'text', 'null' => 0, 'default' => 'www', 'key' => 0, 'extra' => 0)
                        ,'search_body_type' => array('type' => 'varchar(64)', 'null' => 0, 'default' => 'none', 'key' => 0, 'extra' => 0)
                        ,'hmessage_id' => array('type' => 'text', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'hsize' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'hpriority' => array('type' => 'tinyint(4)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'attachments' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'read' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'answered' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'forwarded' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'bounced' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'cached' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'seen' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'struct' => array('type' => 'longblob', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'profile' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'ouidl' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'dsn_sent' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'colour' => array('type' => 'varchar(6)', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'htmlunblocked' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('folder_id' => 'folder_id', 'uid' => 'uid', 'uidl' => 'uidl', 'type' => 'type', 'read' => 'read',
                        'answered' => 'answered', 'forwarded' => 'forwarded', 'bounced' => 'bounced', 'cached' => 'cached',
                        'seen' => 'seen', 'hmessage_id' => '`hmessage_id`(200)', 'profile' => 'profile', 'hdate_sent' => 'hdate_sent',
                        'search_body_type' => 'search_body_type')
                ,'fulltext' => array(/**
                        'search_allfields' => '`hsubject`,`hfrom`,`hto`,`hcc`,`search_body`', 'search_subject' => 'hsubject',
                        'search_from' => 'hfrom', 'search_to' => 'hto', 'search_cc' => 'hcc', 'search_body' => 'search_body',
                        'search_subject_from' => '`hsubject`,`hfrom`', 'search_subject_to' => '`hsubject`,`hto`',
                        'search_subject_cc' => '`hsubject`,`hcc`', 'search_subject_body' => '`hsubject`,`search_body`',
                        'search_subject_from_to' => '`hsubject`,`hfrom`,`hto`', 'search_subject_from_cc' => '`hsubject`,`hfrom`,`hcc`',
                        'search_subject_from_body' => '`hsubject`,`hfrom`,`search_body`', 'search_subject_from_to_cc' => '`hsubject`,`hfrom`,`hto`,`hcc`',
                        'search_subject_from_to_body' => '`hsubject`,`hfrom`,`hto`,`search_body`', 'search_subject_to_cc' => '`hsubject`,`hto`,`hcc`',
                        'search_subject_to_body' => '`hsubject`,`hto`,`search_body`', 'search_subject_to_cc_body' => '`hsubject`,`hto`,`hcc`,`search_body`',
                        'search_subject_cc_body' => '`hsubject`,`hcc`,`search_body`', 'search_from_to' => '`hfrom`,`hto`',
                        'search_from_cc' => '`hfrom`,`hcc`', 'search_from_body' => '`hfrom`,`hto`,`search_body`',
                        'search_from_to_cc' => '`hfrom`,`hto`,`hcc`', 'search_from_to_body' => '`hfrom`,`hto`,`search_body`',
                        'search_from_to_cc_body' => '`hfrom`,`hto`,`hcc`,`search_body`', 'search_to_cc' => '`hto`,`hcc`',
                        'search_to_body' => '`hto`,`search_body`', 'search_to_cc_body' => '`hto`,`hcc`,`search_body`',
                        'search_cc_body' => '`hcc`,`search_body`'*/
                        )
                )
        ,'email_threads' => array
                ('fields' => array
                        ('idx' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'date_first' => array('type' => 'datetime', 'null' => 0, 'default' => '0000-00-00 00:00:00', 'key' => 0, 'extra' => 0)
                        ,'date_last' => array('type' => 'datetime', 'null' => 0, 'default' => '0000-00-00 00:00:00', 'key' => 0, 'extra' => 0)
                        ,'known_mails' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'last_message_id' => array('type' => 'text', 'null' => 0, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('uid' => 'uid', 'date_first' => 'date_first', 'date_last' => 'date_last', 'last_message_id' => '`last_message_id`(200)')
                )
        ,'email_thread_items' => array
                ('fields' => array
                        ('id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'thread_id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'mail_id' => array('type' => 'bigint(20) unsigned', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'hmessage_id' => array('type' => 'text', 'null' => 0, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('uid' => 'uid', 'thread_id' => 'thread_id', 'mail_id' => 'mail_id', 'hmessage_id' => '`hmessage_id`(200)')
                ,'unique' => array('threadmailkey' => '`thread_id`,`mail_id`')
                )
        ,'email_uidlcache' => array
                ('fields' => array
                        ('idx' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'profile' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'uidl' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'deleted' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('profile' => 'profile', 'uidl' => 'uidl', 'deleted' => 'deleted')
                )
        ,'email_whitelist' => array
                ('fields' => array
                        ('id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'filter' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'htmlunblocked' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'process_cal' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'process_vcf' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('uid' => 'uid', 'filter' => 'filter')
                ,'unique' => array('xkey' => '`uid`,`filter`')
                )
        ,'files_folders' => array
                ('fields' => array
                        ('idx' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'layered_id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'folder_path' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'friendly_name' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'childof' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'uuid' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'att_type' => array('type' => 'tinyint(4)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'att_icon' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'att_big_icon' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'att_has_folders' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'att_has_items' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'filenum' => array('type' => 'int(11)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'filesize' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'ctime' => array('type' => 'datetime', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'mtime' => array('type' => 'datetime', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'shared' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('uid' => 'uid', 'childof' => 'childof', 'layered_id' => 'layered_id', 'shared' => 'shared')
                )
        ,'files_index' => array
                ('fields' => array
                        ('idx' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'folder_id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'uuid' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'file_name' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'friendly_name' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'type' => array('type' => 'varchar(255)', 'null' => 0, 'default' => 'application/octet-stream', 'key' => 0, 'extra' => 0)
                        ,'img_w' => array('type' => 'int(11)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'img_h' => array('type' => 'int(11)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'size' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'ctime' => array('type' => 'datetime', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'atime' => array('type' => 'datetime', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'mtime' => array('type' => 'datetime', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'is_locked' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('folder_id' => 'folder_id', 'uid' => 'uid', 'file_name' => 'file_name', 'type' => 'type')
                )
        ,'groups' => array
                ('fields' => array
                        ('gid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'childof' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'friendly_name' => array('type' => 'varchar(64)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'description' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'active' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('childof' => 'childof')
                )
        ,'group_permissions' => array
                ('fields' => array
                        ('gpid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'gid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'handler' => array('type' => 'varchar(64)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'action' => array('type' => 'varchar(64)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'perm' => array('type' => 'enum(\'0\',\'1\',\'2\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('gid' => 'gid', 'handler' => 'handler', 'action' => 'action', 'perm' => 'perm')
                )
        ,'group_quota' => array
                ('fields' => array
                        ('qid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'gid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'handler' => array('type' => 'varchar(64)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'what' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'setting' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('gid' => 'gid', 'handler' => 'handler', 'what' => 'what')
                )
        ,'profiles' => array
                ('fields' => array
                        ('id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'accid' => array('type' => 'int(11)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'order' => array('type' => 'int(11)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'accname' => array('type' => 'varchar(32)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'acctype' => array('type' => 'enum(\'pop3\',\'imap\')', 'null' => 0, 'default' => 'pop3', 'key' => 0, 'extra' => 0)
                        ,'sig_on' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'checkevery' => array('type' => 'int(11)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'leaveonserver' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'localkillserver' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'cachetype' => array('type' => 'enum(\'none\',\'struct\',\'full\')', 'null' => 0, 'default' => 'struct', 'key' => 0, 'extra' => 0)
                        ,'onlysubscribed' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'checkspam' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'trustspamfilter' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '1', 'key' => 0, 'extra' => 0)
                        ,'inbox' => array('type' => 'int(11)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'sent' => array('type' => 'int(11)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'drafts' => array('type' => 'int(11)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'junk' => array('type' => 'int(11)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'waste' => array('type' => 'int(11)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'templates' => array('type' => 'int(11)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'archive' => array('type' => 'int(11)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'popserver' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'popport' => array('type' => 'varchar(8)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'popuser' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'poppass' => array('type' => 'varchar(64)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'popsec' => array('type' => 'enum(\'SSL\',\'STARTTLS\',\'AUTO\',\'none\')', 'null' => 0, 'default' => 'none', 'key' => 0, 'extra' => 0)
                        ,'popallowselfsigned' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'smtpserver' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'smtpport' => array('type' => 'int(11)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'smtpuser' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'smtppass' => array('type' => 'varchar(64)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'smtpsec' => array('type' => 'enum(\'SSL\',\'STARTTLS\',\'AUTO\',\'none\')', 'null' => 0, 'default' => 'AUTO', 'key' => 0, 'extra' => 0)
                        ,'smtpallowselfsigned' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'real_name' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'address' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'signature' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'sendvcf' => array('type' => 'enum(\'none\',\'priv\',\'busi\',\'all\')', 'null' => 0, 'default' => 'none', 'key' => 0, 'extra' => 0)
                        ,'logintime' => array('type' => 'datetime', 'null' => 0, 'default' => '0000-00-00 00:00:00', 'key' => 0, 'extra' => 0)
                        ,'imapprefix' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'userheaders' => array('type' => 'blob', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'server_lastcheck' => array('type' => 'datetime', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'server_top' => array('type' => 'enum(\'0\',\'1\')', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'server_user' => array('type' => 'enum(\'0\',\'1\')', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'server_uidl' => array('type' => 'enum(\'0\',\'1\')', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'server_stls' => array('type' => 'enum(\'0\',\'1\')', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'server_resp_codes' => array('type' => 'enum(\'0\',\'1\')', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'server_pipelining' => array('type' => 'enum(\'0\',\'1\')', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'server_expire' => array('type' => 'int(11)', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'server_login_delay' => array('type' => 'int(11)', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'server_implementation' => array('type' => 'varchar(255)', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('uid' => 'uid', 'accid' => 'accid', 'order' => 'order', 'checkevery' => 'checkevery')
                )
        ,'profile_alias' => array
                ('fields' => array
                        ('aid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'profile' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'real_name' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'email' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'signature' => array('type' => 'bigint(20) unsigned', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'sendvcf' => array('type' => 'enum(\'none\',\'priv\',\'busi\',\'all\',\'default\')', 'null' => 0, 'default' => 'default', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('uid' => 'uid', 'profile' => 'profile')
                )
        ,'rss_feed' => array
                ('fields' => array
                        ('id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'uuid' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'owner' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'childof' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'name' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'description' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'xml_uri' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'html_uri' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'ext_un' => array('type' => 'varchar(255)', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'ext_pw' => array('type' => 'varchar(255)', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'mime' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'view' => array('type' => 'enum(\'preview\',\'fullpage\',\'original\')', 'null' => 0, 'default' => 'preview', 'key' => 0, 'extra' => 0)
                        ,'updated' => array('type' => 'datetime', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'laststatus' => array('type' => 'tinyint(4)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'lasterror' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('owner' => 'owner', 'name' => 'name', 'view' => 'view', 'updated' => 'updated')
                )
        ,'rss_feed_item' => array
                ('fields' => array
                        ('id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'uuid' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'owner' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'feed_id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'title' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'url' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'content' => array('type' => 'longtext', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'author' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'published' => array('type' => 'datetime', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'added' => array('type' => 'datetime', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'read' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'forwarded' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'cached' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'seen' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('owner' => 'owner', 'feed_id' => 'feed_id', 'read' => 'read', 'seen' => 'seen')
                )
        ,'rss_feed_uuid' => array
                ('fields' => array
                        ('feed_id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'uuid' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('feed_id' => 'feed_id')
                ,'unique' => array('uniqueness' => '`feed_id`,`uuid`')
                )
        ,'rss_folder' => array
                ('fields' => array
                        ('id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'name' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'childof' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'uuid' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'owner' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('owner' => 'owner', 'childof' => 'childof')
                )
        ,'sendto_handler' => array
                ('fields' => array
                        ('id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'behaviour' => array('type' => 'enum(\'accept\',\'ignore\')', 'null' => 0, 'default' => 'accept', 'key' => 0, 'extra' => 0)
                        ,'mimetype' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'handler' => array('type' => 'varchar(64)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'on_context' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '1', 'key' => 0, 'extra' => 0)
                        ,'on_fetch' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('behaviour' => 'behaviour', 'mimetype' => 'mimetype', 'handler' => 'handler')
                )
        ,'share_folder' => array
                ('fields' => array
                        ('shid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'handler' => array('type' => 'varchar(64)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'fid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'owner' => array('type' => 'bigint(20) unsigned', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'gid' => array('type' => 'bigint(20) unsigned', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'may_list' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '1', 'key' => 0, 'extra' => 0)
                        ,'may_read' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '1', 'key' => 0, 'extra' => 0)
                        ,'may_write' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '1', 'key' => 0, 'extra' => 0)
                        ,'may_delete' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '1', 'key' => 0, 'extra' => 0)
                        ,'may_newfolder' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '1', 'key' => 0, 'extra' => 0)
                        ,'may_delitems' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '1', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('handler' => 'handler', 'fid' => 'fid', 'uid' => 'uid', 'gid' => 'gid', 'owner' => 'owner')
                )
        ,'share_item' => array
                ('fields' => array
                        ('shid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'handler' => array('type' => 'varchar(64)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'idx' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'owner' => array('type' => 'bigint(20) unsigned', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'gid' => array('type' => 'bigint(20) unsigned', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'may_read' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '1', 'key' => 0, 'extra' => 0)
                        ,'may_write' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '1', 'key' => 0, 'extra' => 0)
                        ,'may_delete' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '1', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('handler' => 'handler', 'idx' => 'idx', 'uid' => 'uid', 'gid' => 'gid', 'owner' => 'owner')
                )
        ,'signatures' => array
                ('fields' => array
                        ('id' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'title' => array('type' => 'varchar(32)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'signature' => array('type' => 'blob', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'signature_html' => array('type' => 'blob', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('uid' => 'uid', 'title' => 'title')
                )
        ,'user' => array
                ('fields' => array
                        ('uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'gid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'username' => array('type' => 'varbinary(64)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'password' => array('type' => 'varchar(32)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'pw_digesta1' => array('type' => 'varchar(32)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'token' => array('type' => 'varchar(32)', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'token_valid' => array('type' => 'datetime', 'null' => 1, 'default' => '0000-00-00 00:00:00', 'key' => 0, 'extra' => 0)
                        ,'externalemail' => array('type' => 'varchar(255)', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'contactid' => array('type' => 'bigint(20) unsigned', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'active' => array('type' => 'enum(\'0\',\'1\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        ,'fail_count' => array('type' => 'tinyint(4)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'fail_time' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'choices' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'logintime' => array('type' => 'datetime', 'null' => 0, 'default' => '0000-00-00 00:00:00', 'key' => 0, 'extra' => 0)
                        ,'logouttime' => array('type' => 'datetime', 'null' => 0, 'default' => '0000-00-00 00:00:00', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('contactid' => 'contactid', 'active' => 'active', 'gid' => 'gid', 'token_valid' => 'token_valid', 'externalemail' => 'externalemail')
                ,'unique' => array('username' => 'username', 'token' => 'token')
                )
        ,'user_accounting' => array
                ('fields' => array
                        ('rkey' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'when' => array('type' => 'varchar(16)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'what' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'setting' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('uid' => 'uid', 'what' => 'what', 'when' => 'when')
                )
        ,'user_favouritefolders' => array
                ('fields' => array
                        ('ffid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'handler' => array('type' => 'varchar(64)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'fid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'order' => array('type' => 'tinyint(4)', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('uid' => 'uid', 'handler' => 'handler', 'fid' => 'fid', 'order' => 'order')
                )
        ,'user_foldersettings' => array
                ('fields' => array
                        ('handler' => array('type' => 'varchar(64)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'fid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'key' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'val' => array('type' => 'blob', 'null' => 0, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('handler' => 'handler', 'uid' => 'uid', 'fid' => 'fid')
                ,'unique' => array('xkey' => '`handler`,`fid`,`uid`,`key`')
                )
        ,'user_group' => array
                ('fields' => array
                        ('ugid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'gid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('uid' => 'uid', 'gid' => 'gid')
                )
        ,'user_permissions' => array
                ('fields' => array
                        ('upid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'handler' => array('type' => 'varchar(64)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'action' => array('type' => 'varchar(64)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'perm' => array('type' => 'enum(\'0\',\'1\',\'2\')', 'null' => 0, 'default' => '0', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('uid' => 'uid', 'handler' => 'handler', 'action' => 'action', 'perm' => 'perm')
                )
        ,'user_quota' => array
                ('fields' => array
                        ('qid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'handler' => array('type' => 'varchar(64)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'what' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'setting' => array('type' => 'varchar(255)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        )
                ,'index' => array('uid' => 'uid', 'handler' => 'handler', 'what' => 'what')
                )
        ,'user_smslogging' => array
                ('fields' => array
                        ('sid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => false, 'key' => 1, 'extra' => 1)
                        ,'uid' => array('type' => 'bigint(20) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'moment' => array('type' => 'datetime', 'null' => 0, 'default' => '0000-00-00 00:00:00', 'key' => 0, 'extra' => 0)
                        ,'target_number' => array('type' => 'varchar(32)', 'null' => 0, 'default' => '', 'key' => 0, 'extra' => 0)
                        ,'size' => array('type' => 'int(10) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        ,'content' => array('type' => 'text', 'null' => 1, 'default' => 'NULL', 'key' => 0, 'extra' => 0)
                        ,'type' => array('type' => 'int(10) unsigned', 'null' => 0, 'default' => 0, 'key' => 0, 'extra' => 0)
                        )
                )
        );

$script = array
        ('calendar_events.warn_time' => array
                ('datetime' => array
                        ('sql' => array
                                ('ALTER TABLE {prefix}calendar_events ADD warntime_new bigint NULL DEFAULT NULL AFTER warn_time'
                                ,'UPDATE {prefix}calendar_events set warntime_new = if(warn_mode="s", unix_timestamp(starts)-unix_timestamp(warn_time), unix_timestamp(ends)-unix_timestamp(warn_time)) where warn_mode != "-" AND warn_time != "0000-00-00 00:00:00"'
                                ,'ALTER TABLE {prefix}calendar_events DROP warn_time, CHANGE warntime_new warn_time bigint NULL DEFAULT NULL'
                                )
                        )
                )
        ,'calendar_events.warn_snooze' => array
                ('datetime' => array
                        ('sql' => array
                                ('INSERT IGNORE INTO {prefix}calendar_event_reminders SELECT NULL,`id`,uid,warn_time,warn_snooze,warn_lastinfo,warn_mode,mailto, smsto FROM {prefix}calendar_events where warn_mode!="-"'
                                ,'INSERT IGNORE INTO {prefix}calendar_event_repetitions SELECT NULL,`id`,repeat_type,`repeat`,repeat_until FROM {prefix}calendar_events'
                                ,'ALTER TABLE {prefix}calendar_events DROP warn_time, DROP warn_snooze, DROP warn_lastinfo, DROP warn_mode, DROP mailto, DROP smsto, DROP repeat_type, DROP `repeat`, DROP repeat_until'
                                )
                        )
                )
        ,'profiles.signature' => array
                ('blob' => array
                        ('sql' => array
                                ('INSERT IGNORE INTO {prefix}signatures SELECT NULL,uid,accname,signature FROM {prefix}profiles where signature!=""'
                                ,'UPDATE {prefix}profiles SET signature="0"'
                                ,'ALTER TABLE {prefix}profiles CHANGE signature signature bigint(20) NULL DEFAULT NULL'
                                )
                        )
                )
        ,'email_folders.att_settings' => array
                ('blob' => array
                        ('sql' => array
                                ('INSERT IGNORE INTO {prefix}email_foldersettings SELECT idx,uid,att_settings FROM {prefix}email_folders where att_settings!=""'
                                ,'ALTER TABLE {prefix}email_folders DROP att_settings'
                                )
                        )
                )
        ,'email_folders.meta_stale' => array
                ('enum(\'0\',\'1\')' => array
                        ('sql' => array
                                ('UPDATE {prefix}email_folders SET stale=meta_stale,mailnum=meta_mailnum,mailsize=meta_mailsize,unread=meta_unread,unseen=meta_unseen'
                                ,'ALTER TABLE {prefix}email_folders DROP meta_stale, DROP meta_unseen, DROP meta_unread, DROP meta_mailsize, DROP meta_mailnum'
                                )
                        )
                )
        ,'email_index.meta_read' => array
                ('enum(\'u\',\'r\',\'a\',\'f\',\'ua\',\'uf\',\'ra\',\'rf\')' => array
                        ('sql' => array
                                ('UPDATE {prefix}email_index SET answered="1" WHERE meta_read in("a", "ua", "ra")'
                                ,'UPDATE {prefix}email_index SET forwarded="1" WHERE meta_read in("f", "uf", "rf")'
                                ,'UPDATE {prefix}email_index SET `read`="1" WHERE meta_read in("r", "a", "f", "ra", "rf")'
                                ,'UPDATE {prefix}email_index SET `type`=meta_type,`cached`=meta_cached,`seen`=meta_seen,`struct`=meta_struct,`profile`=meta_profile,ouidl=meta_ouidl,dsn_sent=meta_dsn_sent,`colour`=meta_colour,htmlunblocked=meta_htmlunblocked'
                                ,'ALTER TABLE {prefix}email_index DROP meta_read, DROP meta_type, DROP meta_cached, DROP meta_seen, DROP meta_struct, DROP meta_profile, DROP meta_ouidl, DROP meta_dsn_sent, DROP meta_colour, DROP meta_htmlunblocked'
                                )
                        )
                )
        ,'files_folders.meta_filesize' => array
                ('bigint(20)' => array
                        ('sql' => array
                                ('UPDATE {prefix}files_folders SET filesize=meta_filesize,filenum=meta_filenum,shared=meta_shared'
                                ,'ALTER TABLE {prefix}files_folders DROP meta_filesize, DROP meta_filenum, DROP meta_shared'
                                )
                        )
                )
        ,'files_folders.att_settings' => array('blob' => array('sql' => array('ALTER TABLE {prefix}files_folders DROP att_settings')))
        ,'files_index.meta_type' => array
                ('varchar(255)' => array
                        ('sql' => array
                                ('UPDATE {prefix}files_index SET type=meta_type,size=meta_size,ctime=meta_ctime,atime=meta_atime,mtime=meta_mtime'
                                ,'ALTER TABLE {prefix}files_index DROP meta_type, DROP meta_thumb, DROP meta_size, DROP meta_ctime, DROP meta_atime, DROP meta_mtime'
                                )
                        )
                )
        ,'files_index.thumb' => array('blob' => array('sql' => array('ALTER TABLE {prefix}files_index DROP thumb')))
        ,'user.firstname' => array
                ('varchar(32)' => array
                        ('sql' => array
                                ('INSERT IGNORE INTO {prefix}adb_adr (`owner`,`visibility`,`type`,`nick`,`email1`,`firstname`,`lastname`) SELECT `uid`,"private","user",`username`,`externalemail`,`firstname`,`lastname` FROM {prefix}user u'
                                ,'UPDATE {prefix}user u,{prefix}adb_adr a SET u.`contactid`=a.`aid` WHERE a.`nick`=u.`username` AND a.`type`="user"'
                                ,'ALTER TABLE {prefix}user DROP `firstname`, DROP `lastname`'
                                )
                        )
                )
        ,'adb_adr.gid' => array
                ('bigint(20) unsigned' => array
                        ('sql' => array
                                ('INSERT IGNORE INTO {prefix}adb_adr_group (`aid`,`gid`,`uid`) SELECT `aid`,`gid`,`owner` FROM {prefix}adb_adr WHERE `gid`!=0'
                                ,'ALTER TABLE {prefix}adb_adr DROP `gid`'
                                )
                        )
                )
        ,'calendar_groups.show_in_root' => array
                ('enum(\'0\',\'1\')' => array
                        ('sql' => array
                                ('INSERT IGNORE INTO {prefix}user_foldersettings (`handler`,`uid`,`fid`,`key`,`val`) SELECT "calendar",`owner`,`gid`,"not_in_root","1" FROM {prefix}calendar_groups WHERE `owner`!=0 AND `show_in_root`="0"'
                                ,'INSERT IGNORE INTO {prefix}user_foldersettings (`handler`,`uid`,`fid`,`key`,`val`) SELECT "calendar",`owner`,`gid`,"not_in_sync","1" FROM {prefix}calendar_groups WHERE `owner`!=0 AND `show_in_sync`="0"'
                                ,'ALTER TABLE {prefix}calendar_groups DROP `show_in_sync`,DROP `show_in_root`'
                                )
                        )
                )
        ,'adb_group.show_in_root' => array
                ('enum(\'0\',\'1\')' => array
                        ('sql' => array
                                ('INSERT IGNORE INTO {prefix}user_foldersettings (`handler`,`uid`,`fid`,`key`,`val`) SELECT "contacts",`owner`,`gid`,"not_in_root","1" FROM {prefix}adb_group WHERE `owner`!=0 AND `show_in_root`="0"'
                                ,'INSERT IGNORE INTO {prefix}user_foldersettings (`handler`,`uid`,`fid`,`key`,`val`) SELECT "contacts",`owner`,`gid`,"not_in_sync","1" FROM {prefix}adb_group WHERE `owner`!=0 AND `show_in_sync`="0"'
                                ,'ALTER TABLE {prefix}adb_group DROP `show_in_sync`,DROP `show_in_root`'
                                )
                        )
                )
        ,'profiles.popnoapop' => array('enum(\'0\',\'1\')' => array('sql' => array('ALTER TABLE {prefix}profiles DROP `popnoapop`, DROP `smtpafterpop`')))
        ,'profiles.be_checkevery' => array
                ('int(11)' => array
                        ('sql' => array
                                ('INSERT IGNORE INTO {prefix}core_crontab (`handler`,`job`,`item`,`interval`,`reference_time`) SELECT "email", "fetchmails", `id`, `be_checkevery`, NOW() FROM {prefix}profiles WHERE `be_checkevery`>0'
                                ,'INSERT IGNORE INTO {prefix}core_crontab (`handler`,`job`,`item`,`interval`,`reference_time`) SELECT "email", "syncfoldertree", `id`, 1, NOW() FROM {prefix}profiles WHERE `acctype`="imap"'
                                ,'ALTER TABLE {prefix}profiles DROP INDEX `be_checkevery`, DROP `be_checkevery`'
                                )
                        )
                )
        ,'calendar_groups.checkevery' => array
                ('int(11)' => array
                        ('sql' => array
                                ('INSERT IGNORE INTO {prefix}core_crontab (`handler`,`job`,`item`,`interval`,`reference_time`,`laststart`,`laststop`) SELECT "calendar", "remotefolders",`gid`,`checkevery`/60, NOW(),`lastcheck`,`lastcheck` FROM {prefix}calendar_groups WHERE `checkevery`>0'
                                ,'ALTER TABLE {prefix}calendar_groups DROP `checkevery`, DROP `lastcheck`, DROP INDEX `checkevery`, DROP INDEX `lastcheck`'
                                ,'ALTER TABLE {prefix}adb_group DROP `checkevery`, DROP `lastcheck`, DROP INDEX `checkevery`, DROP INDEX `lastcheck`'
                                ,'ALTER TABLE {prefix}bookmarks_folder DROP INDEX `checkevery`, DROP INDEX `lastcheck`, DROP `checkevery`, DROP `lastcheck`'
                                )
                        )
                )
        ,'calendar_groups.colour' => array
                ('int(11)' => array
                        ('sql' => array
                                ('INSERT IGNORE INTO {prefix}user_foldersettings (`handler`,`uid`,`fid`,`key`,`val`) SELECT "calendar", owner,`gid`, "foldercolour", `colour` FROM {prefix}calendar_groups WHERE `colour`!=""'
                                ,'ALTER TABLE {prefix}calendar_groups DROP `colour`'
                                )
                        )
                )
        ,'adb_adr.free1' => array
                ('varchar(255)' => array
                        ('sql' => array
                                ('DROP TABLE IF EXISTS {prefix}adb_free'
                                ,'ALTER TABLE {prefix}adb_adr DROP `free1`, DROP `free2`, DROP `free3`, DROP `free4`, DROP `free5`, DROP `free6`, DROP `free7`, DROP `free8`, DROP `free9`, DROP `free10`'
                                )
                        )
                )
        );

$sendto = array
        (array('behaviour' => 'accept', 'mimetype' => '%', 'handler' => 'files', 'on_context' => 1, 'on_fetch' => 0)
        ,array('behaviour' => 'accept', 'mimetype' => 'message/%', 'handler' => 'email', 'on_context' => 1, 'on_fetch' => 0)
        ,array('behaviour' => 'accept', 'mimetype' => 'text/calendar', 'handler' => 'calendar', 'on_context' => 1, 'on_fetch' => 0)
        ,array('behaviour' => 'accept', 'mimetype' => 'text/icalendar', 'handler' => 'calendar', 'on_context' => 1, 'on_fetch' => 0)
        ,array('behaviour' => 'accept', 'mimetype' => 'text/vcalendar', 'handler' => 'calendar', 'on_context' => 1, 'on_fetch' => 0)
        ,array('behaviour' => 'accept', 'mimetype' => 'text/x-lotus-calendar', 'handler' => 'calendar', 'on_context' => 1, 'on_fetch' => 0)
        ,array('behaviour' => 'accept', 'mimetype' => 'text/x-vcard', 'handler' => 'contacts', 'on_context' => 1, 'on_fetch' => 0)
        ,array('behaviour' => 'accept', 'mimetype' => 'text/vcard', 'handler' => 'contacts', 'on_context' => 1, 'on_fetch' => 0)
        ,array('behaviour' => 'accept', 'mimetype' => 'text/x-directory', 'handler' => 'contacts', 'on_context' => 1, 'on_fetch' => 0)
        );
$TS = new DB_Controller_TableStructure();
// Need to be run
$sqls = array(
        'DROP TABLE IF EXISTS {prefix}mailboxes',
        'UPDATE {prefix}files_folders SET `att_has_items`="1" WHERE `att_icon` IN(":files",":waste")'
        );
$orig_struct = $TS->deepSync($structure, $script, $sqls);
// Transfer old data, if exists
if (isset($orig_struct['email_foldersettings'])) {
    fix_email_foldersettings();
}

//
// This routine should allow for a transition from the old file based UIDL caching scheme over to a DB based one
//
$d = opendir($_PM_['path']['storage']);
while (false !== ($f = readdir($d))) {
    if ('.' == $f || '..' == $f) {
        continue;
    }
    if ('.htaccess' == $f) {
        continue;
    }
    if (!file_exists($_PM_['path']['storage'].'/'.$f.'/email/.tmp')) {
        continue;
    }
    $unlinks = array();
    $d2 = opendir($_PM_['path']['storage'].'/'.$f.'/email/.tmp');
    while (false !== ($cf = readdir($d2))) {
        if (!preg_match('!(\d+)\.listcache$!', $cf, $matches)) {
            continue;
        }
        $listcache = unserialize(file_get_contents($_PM_['path']['storage'].'/'.$f.'/email/.tmp/'.$cf));
        foreach ($listcache as $uidl) {
            $sqls[] = 'INSERT INTO {prefix}email_uidlcache SET profile='.$matches[1].', uidl="'.$DB->esc($uidl).'"';
        }
        $unlinks[] = $_PM_['path']['storage'].'/'.$f.'/email/.tmp/'.$cf;
    }
    closedir($d2);
    foreach ($unlinks as $unlink) {
        unlink($unlink);
    }
}
closedir($d);

//
// Keep the sendto DB up to date...
//
$dbST = new DB_Controller_SendTo();
$DBsendto = $dbST->listAll();
foreach ($sendto as $entry) {
    if (!isset($DBsendto[$entry['handler']][$entry['mimetype']])
            || $DBsendto[$entry['handler']][$entry['mimetype']] != $entry['behaviour']) {
        $dbST->addMimeHandler(array($entry['mimetype'] => $entry['behaviour']), $entry['handler'], $entry['on_context'], $entry['on_fetch']);
    }
}
// Fix crontab
// try / catch to prevent damage on incomplete updates
try {
    $Cron = new DB_Controller_Cron();
    if (!$Cron->jobExists('calendar', 'externalalerts')) {
        $Cron->setJob('calendar', 'externalalerts', null, 1, 100); // run every minute
    }
    if (!$Cron->jobExists('maintenance', 'makeinnodb')) {
        $Cron->setJob('maintenance', 'makeinnodb', null, 43829, 100, null, true); // run only once
        $Cron->markJobAtOnce('maintenance', 'makeinnodb', null);
    }
    if (!$Cron->jobExists('maintenance', 'cleanupfs')) {
        $Cron->setJob('maintenance', 'cleanupfs', null, 1440, 1); // run daily
    }
    if (!$Cron->jobExists('core', 'archive')) {
        $Cron->setJob('core', 'archive', null, 720, 1); // run twice a day
    }
    if (!$Cron->jobExists('maintenance', 'enableemailfulltextsearch')) {
        $Cron->setJob('maintenance', 'enableemailfulltextsearch', null, 1440, 1); // check daily
    }
} catch (Exception $e) {
    // this is not considered severe
}

// Template cache
if (!file_exists($_PM_['path']['storage'].'/tplcache')) {
    // If the TPL cache folder does not exist, create it
    mkdir($_PM_['path']['storage'].'/tplcache');
} else {
    // Invalidate template cache
    basics::emptyDir($_PM_['path']['storage'].'/tplcache');
}
// Filtering scripts
if (!file_exists($_PM_['path']['storage'].'/filter_scripts')) {
    // If the TPL cache folder does not exist, create it
    mkdir($_PM_['path']['storage'].'/filter_scripts');
}

/**
 *
 * Keep the basic choices file up to date
 *
 */
$config = [];
foreach (['defaults.ini.php', 'choices.ini.php'] as $choices) {
    if (!file_exists($choices) || !is_readable($choices)) {
        continue;
    }
    $config = array_replace_recursive($_PM_, parse_ini_file($choices, true));
}

// This installation needs a salt
if (empty($_PM_['auth']['system_salt'])) {
    $config['auth']['system_salt'] = uniqid();
}

// Maybe an old installation needs some path variable added to the config
if (!isset($_PM_['path']['extauth']) || $_PM_['path']['message'] == 'frontend/messages') {
    $config['path']['extauth'] = 'shared/extauth';
    $config['path']['message'] = 'shared/messages';
}

// Add template cache path to the choices
if (!isset($_PM_['path']['tplcache'])) {
    $config['path']['tplcache'] = $_PM_['path']['storage'].'/tplcache/';
// We did it wrong in the past and left out the trailing slash
} elseif (substr($_PM_['path']['tplcache'], -1) != '/') {
    $config['path']['tplcache'] = $config['path']['tplcache'].'/';
}
// New installs store user dirs below storage/user/; old ones did it under storage/ directly
if (!isset($_PM_['path']['userbase'])) {
    $config['path']['userbase'] = $_PM_['path']['storage'];
}
if (!isset($_PM_['path']['au_tmp'])) {
    // Move
    $status = @rename($_PM_['path']['base'].'/update', $_PM_['path']['storage'].'/autoupdate');
    $config['path']['au_tmp'] = $_PM_['path']['storage'].'/autoupdate';
}
if (!isset($_PM_['path']['temp'])) {
    // New
    $config['path']['temp'] = $_PM_['path']['storage'].'/temp';
    mkdir($config['path']['temp']);
}
// Always try to create the temp/ folder
basics::create_dirtree($config['path']['temp']);

// Settings for thumbnail generation
if (empty($_PM_['size']['thumb_filesize']) || empty($_PM_['size']['thumb_pixelsize'])) {
    $config['size']['thumb_filesize'] = 8*1024*1024;
    $config['size']['thumb_pixelsize'] = 16000000;
}
// Changes made, store file again...
basics::save_config('./choices.ini.php', $config, true);
/**
 * Done with choices.ini.php
 */

function fix_email_foldersettings()
{
    $DB = new DB_Controller();
    $qid = $DB->query('SELECT * FROM '.$DB->DB['db_pref'].'email_foldersettings');
    if (false === $qid || !$DB->numrows($qid)) {
        return; // Looks like we did it already
    }
    while ($line = $DB->fetchassoc($qid)) {
        $sql = 'REPLACE INTO '.$DB->DB['db_pref'].'user_foldersettings (`handler`,`uid`,`fid`,`key`,`val`) VALUES ';
        $i = 0;
        foreach (unserialize($line['settings']) as $k => $v) {
            if ($i) {
                $sql .= ',';
            }
            $sql .= '("email", '.$line['uid'].', '.$line['fid'].', "'.$DB->esc($k).'", "'.$DB->esc(is_array($v) ? serialize($v) : $v).'")';
            $i++;
        }
        $state = $DB->query($sql);
        if (!$state) {
            echo $sql.LF.$DB->error();
            exit;
        }
    }
    $DB->query('DROP TABLE '.$DB->DB['db_pref'].'email_foldersettings');
}