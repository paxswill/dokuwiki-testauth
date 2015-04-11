<?php
/**
 * DokuWiki Plugin testauth (Auth Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Will Ross <paxswill@paxswill.com>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class auth_plugin_testauth_test extends DokuWiki_Auth_Plugin {


    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(); // for compatibility

        $this->cando['addUser']      = false;
        $this->cando['delUser']      = false;
        $this->cando['modLogin']     = false;
        $this->cando['modPass']      = false;
        $this->cando['modName']      = false;
        $this->cando['modMail']      = false;
        $this->cando['modGroups']    = false;
        $this->cando['getUsers']     = false;
        $this->cando['getUserCount'] = false;
        $this->cando['getGroups']    = false;
        $this->cando['external']     = false;
        $this->cando['logout']       = true;

        $this->success = true;
    }


    /**
     * Check user+password
     *
     * May be ommited if trustExternal is used.
     *
     * @param   string $user the user name
     * @param   string $pass the clear text password
     * @return  bool
     */
    public function checkPass($user, $pass) {
        $hashed_password = hash('sha1', $pass);
        $params = array(
            'user' => $user,
            'pass' => $hashed_password
        );
        $response = $this->testAuthAPI('login', $params);
        if ($response['code'] != 200 || $response['json']->auth != 'ok') {
            dbglog("Authentication for user $name failed." .
                   " Reason: " . $response['error']);
            return false;
        } else {
            return true;
        }
    }

    /**
     * Return user info
     *
     * Returns info about the given user needs to contain
     * at least these fields:
     *
     * name string  full name of the user
     * mail string  email addres of the user
     * grps array   list of groups the user is in
     *
     * @param   string $user the user name
     * @return  array containing user data or false
     */
    public function getUserData($user) {
        $params = array(
            'apikey' => $this->getConf('apikey'),
            'user' => $user
        );
        // FIXME response validity isn't checked
        $response = $this->testAuthAPI('user', $params);
        if ($response['code'] != 200) {
            dbglog("User info lookup for user $user failed." .
                   " Reason: " . $response['error']);
            return false;
        }
        $user_data = array();
        $user_data['name'] = $response['json']->primarycharacter->name;
        $user_data['mail'] = $response['json']->email;
        # Start with the user group to keep with the pattern that the default
        # DokuWiki auth mechanism provides (@user for authenticated users).
        $groups = array('user');
        if (property_exists($response['json'], 'groups')) {
            foreach ($response['json']->groups as $group) {
                $groups[] = $group->name;
            }
        }
        $user_data['grps'] = $groups;
        return $user_data;
    }

    /**
     * Return case sensitivity of the backend
     *
     * When your backend is caseinsensitive (eg. you can login with USER and
     * user) then you need to overwrite this method and return false
     *
     * @return bool
     */
    public function isCaseSensitive() {
        // TODO double check that this is the case
        return true;
    }

    /**
     * Sanitize a given username
     *
     * This function is applied to any user name that is given to
     * the backend and should also be applied to any user name within
     * the backend before returning it somewhere.
     *
     * This should be used to enforce username restrictions.
     *
     * @param string $user username
     * @return string the cleaned username
     */
    public function cleanUser($user) {
        return $user;
    }

    /**
     * Sanitize a given groupname
     *
     * This function is applied to any groupname that is given to
     * the backend and should also be applied to any groupname within
     * the backend before returning it somewhere.
     *
     * This should be used to enforce groupname restrictions.
     *
     * Groupnames are to be passed without a leading '@' here.
     *
     * @param  string $group groupname
     * @return string the cleaned groupname
     */
    public function cleanGroup($group) {
        $group = preg_replace('[\s]', '_', $group);
        return $group;
    }

    /**
     * Query the Auth API
     *
     * Queries the Auth API using the given endpoint with the given paramters.
     *
     * @param string $method - The endpoint to query
     * @param array $params - An associative array of the parameters
     * @return array
     *
     */
    private function testAuthAPI($method, $params=array()) {
        // Check that it's a valid endpoint
        static $methods = array(
            'anm',
            'announce',
            'authchar',
            'blacklist',
            'character',
            'edkapi',
            'eveapi',
            'group',
            'group',
            'info',
            'login',
            'optimer',
            'user',
        );
        if (!in_array($method, $methods)) {
            return false;
        }
        // Build the query string+URL
        $url = "https://auth.pleaseignore.com/api/1.0/${method}";
        // Process the request
        $http_client = new DokuHTTPClient();
        $json_response = $http_client->dget($url, $params);
        $response = array(
            'code' => $http_client->status,
            'body' => $http_client->resp_body,
            'headers' => $http_client->resp_headers,
        );
        if ($json_response == false) {
            $response['error'] = $http_client->error;
            return $response;
        }
        // the API returns JSON, so pre-process it
        $json = new JSON();
        $response['json'] = $json->decode($json_response);
        return $response;
    }
}

// vim:ts=4:sts=4:sw=4:et:
