<?php

/**
 * XAMS Password Driver
 *
 * Driver for passwords stored in XAMS database
 *
 * @version 0.1.2
 * @author Michael Kefeder <mike@weird-birds.org>
 *
 * Modified by Stephane Leclerc <sleclerc@actionweb.fr>
 *
 * Based on sql.php by Aleksander 'A.L.E.C' Machniak <alec@alec.pl>
 *
 */

function password_save($curpass, $passwd)
{
    $rcmail = rcmail::get_instance();

    $dsn = array();
    $dsn['new_link'] = true;
    $dsn['phptype'] = 'mysql';
    // Change to your own setting
    $dsn['hostspec'] = 'localhost';
    $dsn['database'] = 'database';
    $dsn['username'] = 'user';
    $dsn['password'] = 'password';

    $db = new rcube_mdb2($dsn, '', FALSE);
    $db->set_debug((bool)$rcmail->config->get('sql_debug'));
    $db->db_connect('w');

    if ($err = $db->is_error())
    {
        return PASSWORD_ERROR;
    }

    $user_info = explode('@', $_SESSION['username']);

    if (count($user_info) == 2) 
    {
        $user = $user_info[0];
        $domain = $user_info[1];
    }
    else
    {
        return PASSWORD_ERROR;
    } 

    $sql = "UPDATE _users SET password = ";
    $sql = str_replace('%h', $db->quote($_SESSION['imap_host'],'text'), $sql);
    $sql = str_replace('%p', $db->quote($passwd,'text'), $sql);

    $types = array('text');
    $sql = 'SELECT siteid FROM pm_domains WHERE name = %domainname';
    $sql = str_replace('%domainname', $db->quote($domain,'text'), $sql);
    $res = $db->query($sql);

    if (!$db->is_error())
    { 
        $values = $db->fetch_array($res);
    }
    else
    {
        return PASSWORD_ERROR;
    }

    if (count($values) < 1)
    {
        return PASSWORD_ERROR;
    }

    $siteid = $values[0];
    
    $sql = 'UPDATE pm_users SET password = :newpass WHERE siteid = :siteid AND name = :username';
    $sql = str_replace(':siteid', $db->quote($siteid,'integer'), $sql);
    $sql = str_replace(':newpass', $db->quote(md5($passwd),'text'), $sql);
    $sql = str_replace(':username', $db->quote($user,'text'), $sql);
    $res = $db->query($sql);

    if (!$db->is_error())
    {
            return PASSWORD_SUCCESS;
    }
    return PASSWORD_ERROR;
}

?>
