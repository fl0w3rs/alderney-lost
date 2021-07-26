<?php

namespace App\Facades;

use \RedBeanPHP\R as R;

class Auth {
    public static function getForumSession() {
        if(isset($_COOKIE['ips4_login_key'])) {
            R::selectDatabase('forum');
            $row = R::getRow('SELECT * FROM core_members_known_devices WHERE login_key = ?', [$_COOKIE['ips4_login_key']]);

            if(!isset($row['member_id'])) return false;

            return $row;
        } else return false;
    }

    public static function getForumUser() {
        $session = self::getForumSession();

        if($session == false) return false;

        R::selectDatabase('forum');
        $row = R::getRow("SELECT * FROM core_members WHERE member_id = ?", [$session['member_id']]);

        if(!isset($row['member_id'])) return false;

        return $row;
    }

    public static function isAuthorized() {
        return self::getForumUser() !== false;
    }

    public static function getForumUserGroups() {
        $user = self::getForumUser();
        
        if($user == false) return false;

        $user_groups_all = [$user['member_group_id']];
        $user_groups_other = $user['mgroup_others'];
        if(substr_count($user_groups_other, ",") >= 1) {
            $user_groups_all = array_merge($user_groups_all, explode(',', $user_groups_other));
        } else {
            if($user_groups_other !== '') {
                array_push($user_groups_all, $user_groups_other);
            }
        }

        return $user_groups_all;
    }

    public static function haveAccessToAlderney() {
        $user = self::getForumUser();

        if($user == false) return false;

        if($user['member_group_id'] == 3) return false;
        
        return true;
    }

    public static function isAdmin() {
        $groups = self::getForumUserGroups();

        if($groups == false) {
            return false;
        }

        if(count( array_intersect( $groups, config['admin_groups'] ) ) > 0) {
            return true;
        }

        return false;
    }

    public static function isTrainer() {
        $groups = self::getForumUserGroups();

        if($groups == false) {
            return false;
        }

        if(count( array_intersect( $groups, config['trainer_groups'] ) ) > 0) {
            return true;
        }

        return false;
    }
}