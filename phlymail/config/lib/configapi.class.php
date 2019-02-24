<?php
class configapi
{
    function __construct(&$conf, &$DB = null) { return true; }
    function create_user($uid, $name, $pass, $email, $acctype = 'pop3') { return true; }
    function edit_user($uid, $name, $pass, $email, $newname) { return true; }
    function delete_user($uid, $name) { return true; }
}
