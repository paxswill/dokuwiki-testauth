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

        $this->cando['getGroups']   = false; // can a list of available groups be retrieved?
        //$this->cando['external']    = false; // does the module do external auth checking?
        //$this->cando['logout']      = true; // can the user logout again? (eg. not possible with HTTP auth)

        // FIXME intialize your auth system and set success to true, if successful
        $this->success = true;
    }

    /**
     * Log off the current user [ OPTIONAL ]
     */
    //public function logOff() {
    //}

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
        // TODO Report why things failed
        $hashed_password = hash('sha1', $pass);
        $params = array(
            'user' => $user,
            'pass' => $hashed_password
        );
        $response = $this->testAuthAPI('login', $params);
        if ($response['auth'] != 'ok') {
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
        $user_data = array();
        // FIXME Figure out how to get the primary character name without using
        // the login endpoint
        $user_data['name'] = $response['username'];
        $user_data['mail'] = $response['email'];
        $groups = array();
        foreach ($response['groups'] as $group) {
            $groups[] = $group['name'];
        }
        $user_data['groups'] = $groups;
        return $user_data;
    }

    /**
     * Retrieve groups [implement only where required/possible]
     *
     * Set getGroups capability when implemented
     *
     * @param   int $start
     * @param   int $limit
     * @return  array
     */
    //public function retrieveGroups($start = 0, $limit = 0) {
        // FIXME implement
    //    return array();
    //}

    /**
     * Return case sensitivity of the backend
     *
     * When your backend is caseinsensitive (eg. you can login with USER and
     * user) then you need to overwrite this method and return false
     *
     * @return bool
     */
    public function isCaseSensitive() {
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
        return $group;
    }

    /**
     * Check Session Cache validity [implement only where required/possible]
     *
     * DokuWiki caches user info in the user's session for the timespan defined
     * in $conf['auth_security_timeout'].
     *
     * This makes sure slow authentication backends do not slow down DokuWiki.
     * This also means that changes to the user database will not be reflected
     * on currently logged in users.
     *
     * To accommodate for this, the user manager plugin will touch a reference
     * file whenever a change is submitted. This function compares the filetime
     * of this reference file with the time stored in the session.
     *
     * This reference file mechanism does not reflect changes done directly in
     * the backend's database through other means than the user manager plugin.
     *
     * Fast backends might want to return always false, to force rechecks on
     * each page load. Others might want to use their own checking here. If
     * unsure, do not override.
     *
     * @param  string $user - The username
     * @return bool
     */
    //public function useSessionCache($user) {
      // FIXME implement
    //}

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
    private testAuthAPI($method, $params=array()) {
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
        $query_string = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        if ($query_string != '') {
            $url = $url . '?' . $query_string
        }
        // Process the request
        $http_client = new DokuHTTPClient();
        $json_response = $http_client->get($url);
        if ($json_response == false) {
            curl_close($curl);
            return false;
        }
        curl_close($curl);
        // the API returns JSON, so pre-process it
        $json = new JSON();
        $response = $json->decode($json_response, true);
        return $response;
    }
}

// vim:ts=4:sts=4:sw=4:et:
