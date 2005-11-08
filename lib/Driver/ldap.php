<?php

# Make Shout methods available
require_once SHOUT_BASE . '/lib/Shout.php';

// {{{ Shout_Driver_ldap class
class Shout_Driver_ldap extends Shout_Driver
{
    var $_ldapKey;  // Index used for storing objects
    var $_appKey;   // Index used for moving info to/from the app

    // {{{ Class local variables
    /**
     * Handle for the current database connection.
     * @var object LDAP $_LDAP
     */
    var $_LDAP;

    /**
     * Boolean indicating whether or not we're connected to the LDAP
     * server.
     * @var boolean $_connected
     */
    var $_connected = false;

    // }}}

    // {{{ Shout_Driver_ldap constructor
    /**
    * Constructs a new Shout LDAP driver object.
    *
    * @param array  $params    A hash containing connection parameters.
    */
    function Shout_Driver_ldap($params = array())
    {
        parent::Shout_Driver($params);
        $this->_connect();
    }
    // }}}

    // {{{ getContexts method
    /**
    * Get a list of contexts from the backend
    *
    * @param string $filter Search filter
    *
    * @return array Contexts valid for this system
    *
    * @access private
    */
    function &getContexts($searchfilters = SHOUT_CONTEXT_ALL,
                         $filterperms = null)
    {
        static $entries = array();
        if (isset($entries[$searchfilters])) {
            return $entries[$searchfilters];
        }

        if ($filterperms == null) {
            $filterperms = PERMS_SHOW|PERMS_READ;
        }

        # TODO Add caching mechanism here.  Possibly cache results per
        # filter $this->contexts['customer'] and return either data
        # or possibly a reference to that data

        # Determine which combination of contexts need to be returned
        if ($searchfilters == SHOUT_CONTEXT_ALL) {
            $searchfilter="(objectClass=asteriskObject)";
        } else {
            $searchfilter = "(&";
            # FIXME Change this to non-V-Office specific objectClass
            if ($searchfilters & SHOUT_CONTEXT_CUSTOMERS) {
                # FIXME what does this objectClass really do for us?
                $searchfilter.="(objectClass=vofficeCustomer)";
            }
            if ($searchfilters & SHOUT_CONTEXT_EXTENSIONS) {
                $searchfilter.="(objectClass=asteriskExtensions)";
            }
            if ($searchfilters & SHOUT_CONTEXT_MOH) {
                $searchfilter.="(objectClass=asteriskMusicOnHold)";
            }
            if ($searchfilters & SHOUT_CONTEXT_CONFERENCE) {
                $searchfilter.="(objectClass=asteriskMeetMe)";
            }
            $searchfilter .= ")";
        }

        $attributes = array(SHOUT_ACCOUNT_ID_ATTRIBUTE, 'objectClass', 'context');

        # Collect all the possible contexts from the backend
        $res = @ldap_search($this->_LDAP,
            SHOUT_ASTERISK_BRANCH.','.$this->_params['basedn'],
            "$searchfilter");
            #array('context', 'associatedDomain'));
        if (!$res) {
            return PEAR::raiseError("Unable to locate any contexts " .
            "underneath ".SHOUT_ASTERISK_BRANCH.",".$this->_params['basedn'] .
            " matching those search filters" . ldap_error($this->_LDAP));
        }

        $res = ldap_get_entries($this->_LDAP, $res);
        $i = 0;
        $entries[$searchfilters] = array();
        while ($i < $res['count']) {
            $context = $res[$i]['context'][0];
            $type = SHOUT_CONTEXT_NONE;
            foreach ($res[$i][strtolower('objectClass')] as $objectClass) {
                switch ($objectClass) {
                    case SHOUT_CONTEXT_CUSTOMERS_OBJECTCLASS:
                        # FIXME What does this objectClass really get us?
                        $type = $type | SHOUT_CONTEXT_CUSTOMERS;
                        break;
                    case SHOUT_CONTEXT_EXTENSIONS_OBJECTCLASS:
                        $type = $type | SHOUT_CONTEXT_EXTENSIONS;
                        break;
                    case SHOUT_CONTEXT_MOH_OBJECTCLASS:
                        $type = $type | SHOUT_CONTEXT_MOH;
                        break;
                    case SHOUT_CONTEXT_CONFERENCE_OBJECTCLASS:
                        $type = $type | SHOUT_CONTEXT_CONFERENCE;
                        break;
                    case SHOUT_CONTEXT_VOICEMAIL_OBJECTCLASS:
                        $type = $type | SHOUT_CONTEXT_VOICEMAIL;
                        break;
                }
            }
            if (Shout::checkRights("shout:contexts:$context", $filterperms)) {
                $entries[$searchfilters][$context] =
                    array(
                        'custid' => SHOUT_ACCOUNT_ID_ATTRIBUTE,
                        'type' => $type,
                    );
            }
            $i++;
        }
        # return the array
        return $entries[$searchfilters];
    }
    // }}}

    // {{{ _checkContextType method
    /**
     * For the given context and type, make sure the context has the
     * appropriate properties, that it is effectively of that "type"
     *
     * @param string $context the context to check type for
     *
     * @param string $type the type to verify the context is of
     *
     * @return boolean true of the context is of type, false if not
     *
     * @access public
     */
    function checkContextType($context, $type) {
        switch ($type) {
            case "users":
                $searchfilter = "(objectClass=vofficeCustomer)";
                break;
            case "dialplan":
                $searchfilter = "(objectClass=asteriskExtensions)";
                break;
            case "moh":
                $searchfilter="(objectClass=asteriskMusicOnHold)";
                break;
            case "conference":
                $searchfilter="(objectClass=asteriskMeetMe)";
                break;
            case "all":
            default:
                $searchfilter="";
                break;
        }

        $res = @ldap_search($this->_LDAP,
            SHOUT_ASTERISK_BRANCH.','.$this->_params['basedn'],
            "(&(objectClass=asteriskObject)$searchfilter(context=$context))",
            array("context"));
        if (!$res) {
            return PEAR::raiseError("Unable to search directory for context
type");
        }

        $res = ldap_get_entries($this->_LDAP, $res);
        if (!$res) {
            return PEAR::raiseError("Unable to get results from LDAP query");
        }

        if ($res['count'] == 1) {
            return true;
        } else {
            return false;
        }
    }
    // }}}

    // {{{ getUsers method
    /**
     * Get a list of users valid for the contexts
     *
     * @param string $context Context on which to search
     *
     * @return array User information indexed by voice mailbox number
     */
    function &getUsers($context)
    {

        static $entries = array();
        if (isset($entries[$context])) {
            return $entries[$context];
        }

        $registry = &Registry::singleton();
        require_once $registry->applicationFilePath('%application%/lib/defines.php', 'congregation');
        $users = $registry->callByPackage('congregation', 'getUsersByContext',
            array($context, CONGREGATION_USER_PHONE));

        foreach ($users as $user) {
            $extension = $user['extension'];
            $entries[$context][$extension] = $user;
        }
        ksort($entries[$context]);

        return($entries[$context]);
    }
    // }}}

    // {{{ getHomeContext method
    /**
     * Returns the name of the user's default context
     *
     * @return string User's default context
     */
    function getHomeContext()
    {
        # FIXME Probably should key this off the domain part of the user's
        # FIXME Auth::getAuth() and match context with associatedDomain
        # FIXME Also, cache this lookup
        $res = @ldap_search($this->_LDAP,
            SHOUT_USERS_BRANCH.','.$this->_params['basedn'],
            "(&(mail=".Auth::getAuth().")(objectClass=asteriskUser))",
            array('context'));
        if (!$res) {
            return PEAR::raiseError("Unable to locate any customers " .
            "underneath ".SHOUT_ASTERISK_BRANCH.",".$this->_params['basedn'] .
            " matching those search filters");
        }

        $res = ldap_get_entries($this->_LDAP, $res);

        # Assume the user only has one context.  The schema enforces this
        # FIXME: Handle cases where the managing user isn't a valid telephone
        # system user
        return $res[0]['context'][0];
    }
    // }}}

    // {{{
    /**
     * Get a context's properties
     *
     * @param string $context Context to get properties for
     *
     * @return integer Bitfield of properties valid for this context
     */
    function getContextProperties($context)
    {

        $res = @ldap_search($this->_LDAP,
            SHOUT_ASTERISK_BRANCH.','.$this->_params['basedn'],
            "(&(objectClass=asteriskObject)(context=$context))",
            array('objectClass'));
        if(!$res) {
            return PEAR::raiseError(_("Unable to get properties for $context"));
        }

        $res = ldap_get_entries($this->_LDAP, $res);

        $properties = 0;
        if ($res['count'] != 1) {
            return PEAR::raiseError(_("Incorrect number of properties found
for $context"));
        }

        foreach ($res[0]['objectclass'] as $objectClass) {
            switch ($objectClass) {
                case "vofficeCustomer":
                    # FIXME What does this objectClass really do for us?
                    $properties = $properties | SHOUT_CONTEXT_CUSTOMERS;
                    break;

                case "asteriskExtensions":
                    $properties = $properties | SHOUT_CONTEXT_EXTENSIONS;
                    break;

                case "asteriskMusicOnHold":
                    $properties = $properties | SHOUT_CONTEXT_MOH;
                    break;

                case "asteriskMeetMe":
                    $properties = $properties | SHOUT_CONTEXT_CONFERENCE;
                    break;
            }
        }
        return $properties;
    }
    // }}}

    // {{{ getDialplan method
    /**
     * Get a context's dialplan and return as a multi-dimensional associative
     * array
     *
     * @param string $context Context to return extensions for
     *
     * @param boolean $preprocess Parse includes and barelines and add their
     *                            information into the extensions array
     *
     * @return array Multi-dimensional associative array of extensions data
     *
     */
    function &getDialplan($context, $preprocess = false)
    {
        # FIXME Implement preprocess functionality.  Don't forget to cache!
        static $dialplans = array();
        if (isset($dialplans[$context])) {
            return $dialplans[$context];
        }

        $res = @ldap_search($this->_LDAP,
            SHOUT_ASTERISK_BRANCH.','.$this->_params['basedn'],
            "(&(objectClass=".SHOUT_CONTEXT_EXTENSIONS_OBJECTCLASS.")(context=$context))",
            array(SHOUT_DIALPLAN_EXTENSIONLINE_ATTRIBUTE, SHOUT_DIALPLAN_INCLUDE_ATTRIBUTE,
                SHOUT_DIALPLAN_IGNOREPAT_ATTRIBUTE, 'description',
                SHOUT_DIALPLAN_BARELINE_ATTRIBUTE));
        if (!$res) {
            return PEAR::raiseError("Unable to locate any extensions " .
            "underneath ".SHOUT_ASTERISK_BRANCH.",".$this->_params['basedn'] .
            " matching those search filters");
        }

        $res = ldap_get_entries($this->_LDAP, $res);
        $dialplans[$context] = array();
        $i = 0;
        while ($i < $res['count']) {
            # Handle extension lines
            if (isset($res[$i][strtolower(SHOUT_DIALPLAN_EXTENSIONLINE_ATTRIBUTE)])) {
                $j = 0;
                while ($j < $res[$i][strtolower(SHOUT_DIALPLAN_EXTENSIONLINE_ATTRIBUTE)]['count']) {
                    @$line = $res[$i][strtolower(SHOUT_DIALPLAN_EXTENSIONLINE_ATTRIBUTE)][$j];

                    # Basic sanity check for length.  FIXME
                    if (strlen($line) < 5) {
                        break;
                    }
                    # Can't use strtok here because there may be commass in the
                    # arg string

                    # Get the extension
                    $token1 = strpos($line, ',');
                    $token2 = strpos($line, ',', $token1 + 1);
                    $token3 = strpos($line, '(', $token2 + 1);

                    $extension = substr($line, 0, $token1);
                    if (!isset($dialplans[$context]['extensions'][$extension])) {
                        $dialplan[$context]['extensions'][$extension] = array();
                    }
                    $token1++;
                    # Get the priority
                    $priority = substr($line, $token1, $token2 - $token1);
                    $dialplans[$context]['extensions'][$extension][$priority] =
                        array();
                    $token2++;

                    # Get Application and args
                    $application = substr($line, $token2);

                    #$args = strpos($)

                    # Merge all that data into the returning array
                    $dialplans[$context]['extensions'][$extension][$priority] =
                        $application;
                    $j++;
                }

                # Sort the extensions data
                foreach ($dialplans[$context]['extensions'] as
                    $extension => $data) {
                    ksort($dialplans[$context]['extensions'][$extension]);
                }
                uksort($dialplans[$context]['extensions'],
                    array(new Shout, "extensort"));
            }
            # Handle include lines
            if (isset($res[$i]['asteriskincludeline'])) {
                $j = 0;
                while ($j < $res[$i]['asteriskincludeline']['count']) {
                    @$line = $res[$i]['asteriskincludeline'][$j];
                    $dialplans[$context]['includes'][$j] = $line;
                    $j++;
                }
            }

            # Handle ignorepat
            if (isset($res[$i]['asteriskignorepat'])) {
                $j = 0;
                while ($j < $res[$i]['asteriskignorepat']['count']) {
                    @$line = $res[$i]['asteriskignorepat'][$j];
                    $dialplans[$context]['ignorepats'][$j] = $line;
                    $j++;
                }
            }
            # Handle ignorepat
            if (isset($res[$i]['asteriskextensionbareline'])) {
                $j = 0;
                while ($j < $res[$i]['asteriskextensionbareline']['count']) {
                    @$line = $res[$i]['asteriskextensionbareline'][$j];
                    $dialplans[$context]['barelines'][$j] = $line;
                    $j++;
                }
            }

            # Increment object
            $i++;
        }
        return $dialplans[$context];
    }
    // }}}

    // {{{
    /**
     * Get the limits for the current user, the user's context, and global
     * Return the most specific values in every case.  Return default values
     * where no data is found.  If $extension is specified, $context must
     * also be specified.
     *
     * @param optional string $context Context to search
     *
     * @param optional string $extension Extension/user to search
     *
     * @return array Array with elements indicating various limits
     */
     # FIXME Figure out how this fits into Shout/Congregation better
    function &getLimits($context = null, $extension = null)
    {

        $limits = array('telephonenumbersmax',
                        'voicemailboxesmax',
                        'asteriskusers');

        if(!is_null($extension) && is_null($context)) {
            return PEAR::raiseError("Extension specified but no context " .
                "given.");
        }

        if (!is_null($context) && isset($limits[$context])) {
            if (!is_null($extension) &&
                isset($limits[$context][$extension])) {
                return $limits[$context][$extension];
            }
            return $limits[$context];
        }

        # Set some default limits (to unlimited)
        static $cachedlimits = array();
        # Initialize the limits with defaults
        if (count($cachedlimits) < 1) {
            foreach ($limits as $limit) {
                $cachedlimits[$limit] = 99999;
            }
        }

        # Collect the global limits
        $res = @ldap_search($this->_LDAP,
            SHOUT_ASTERISK_BRANCH.','.$this->_params['basedn'],
            '(&(objectClass=asteriskLimits)(cn=globals))',
            $limits);

        if (!$res) {
            return PEAR::raiseError('Unable to search the LDAP server for ' .
                'global limits');
        }

        $res = ldap_get_entries($this->_LDAP, $res);
        # There should only have been one object returned so we'll just take the
        # first result returned
        if ($res['count'] > 0) {
            foreach ($limits as $limit) {
                if (isset($res[0][$limit][0])) {
                    $cachedlimits[$limit] = $res[0][$limit][0];
                }
            }
        } else {
            return PEAR::raiseError("No global object found.");
        }

        # Get limits for the context, if provided
        if (isset($context)) {
            $res = ldap_search($this->_LDAP,
                SHOUT_ASTERISK_BRANCH.','.$this->_params['basedn'],
                "(&(objectClass=asteriskLimits)(cn=$context))");

            if (!$res) {
                return PEAR::raiseError('Unable to search the LDAP server ' .
                    "for $context specific limits");
            }

            $cachedlimits[$context][$extension] = array();
            if ($res['count'] > 0) {
                foreach ($limits as $limit) {
                    if (isset($res[0][$limit][0])) {
                        $cachedlimits[$context][$limit] = $res[0][$limit][0];
                    } else {
                        # If no value is provided use the global limit
                        $cachedlimits[$context][$limit] = $cachedlimits[$limit];
                    }
                }
            } else {

                foreach ($limits as $limit) {
                    $cachedlimits[$context][$limit] =
                        $cachedlimits[$limit];
                }
            }

            if (isset($extension)) {
                $res = @ldap_search($this->_LDAP,
                    SHOUT_USERS_BRANCH.','.$this->_params['basedn'],
                    "(&(objectClass=asteriskLimits)(voiceMailbox=$extension)".
                    "(context=$context))");

                if (!$res) {
                    return PEAR::raiseError('Unable to search the LDAP server '.
                        "for Extension $extension, $context specific limits");
                }

                $cachedlimits[$context][$extension] = array();
                if ($res['count'] > 0) {
                    foreach ($limits as $limit) {
                        if (isset($res[0][$limit][0])) {
                            $cachedlimits[$context][$extension][$limit] =
                                $res[0][$limit][0];
                        } else {
                            # If no value is provided use the context limit
                            $cachedlimits[$context][$extension][$limit] =
                                $cachedlimits[$context][$limit];
                        }
                    }
                } else {
                    foreach ($limits as $limit) {
                        $cachedlimits[$context][$extension][$limit] =
                            $cachedlimits[$context][$limit];
                    }
                }
                return $cachedlimits[$context][$extension];
            }
            return $cachedlimits[$context];
        }
    }
    // }}}

    // {{{
    /**
     * Save a user to the LDAP tree
     *
     * @param string $context Context to which the user should be added
     *
     * @param string $extension Extension to be saved
     *
     * @param array $userdetails Phone numbers, PIN, options, etc to be saved
     *
     * @return TRUE on success, PEAR::Error object on error
     */
    function saveUser($context, $extension, $userdetails)
    {
        # FIXME Access Control/Authorization
        if (!Shout::checkRights("shout:contexts:$context:users",
            PERMS_DELETE, 1)) {
            return PEAR::raiseError("No permission to modify users in this " .
                "context.");
        }
        $ldapKey = &$this->_ldapKey;
        $appKey = &$this->_appKey;

        $contexts = &$this->getContexts();
        $domain = $contexts[$context]['domain'];

        # Check to ensure the extension is unique within this context
        $filter = '(&(objectClass=asteriskVoiceMailbox)(context='.$context.'))';
        $reqattrs = array('dn', $ldapKey);
        $res = @ldap_search($this->_LDAP,
            SHOUT_USERS_BRANCH . ',' . $this->_params['basedn'],
            $filter, $reqattrs);
        if (!$res) {
            return PEAR::raiseError('Unable to check directory for duplicate extension: ' .
                ldap_error($this->_LDAP));
        }
        if (($res['count'] > 1) ||
            ($res['count'] != 0 &&
            !in_array($res[0][$ldapKey], $userdetails[$appKey]))) {
            return PEAR::raiseError('Duplicate extension found.  Not saving changes.');
        }

        $validusers = &$this->getUsers($context);
        $userId = $validusers[$extension][$appKey];

        $registry = &Registry::singleton();
        require_once $registry->applicationFilePath('%application%/lib/defines.php', 'congregation');
        $userModes = $registry->callByPackage('congregation', 'getUserModes',
            array($domain, $userId));
        # FIXME Handle error here

        $registry->callByPackage('congregation', 'saveUser',
            array($domain, $userId, $userModes | CONGREGATION_USER_PHONE, ));

        $entry = array(
            'cn' => $userdetails['name'],
            'mail' => $userdetails['email'],
            'voiceMailbox' => $userdetails['newextension'],
            'voiceMailboxPin' => $userdetails['pin'],
            'context' => $context,
            'asteriskUserDialOptions' => $userdetails['dialopts'],
        );

        if (!empty ($userdetails['telephonenumbers'])) {
            $entry['telephoneNumber'] = $userdetails['telephonenumbers'];
        }

        $validusers = &$this->getUsers($context);
        if (!isset($validusers[$extension])) {
            # Test to see if we're modifying an existing user that has
            # no telephone system objectClasses and update that object/user
            $rdn = "$ldapKey=".$userdetails[$appKey].',';
            $branch = SHOUT_USERS_BRANCH.','.$this->_params['basedn'];

            # This test is something of a hack.  I want a cheap way to check
            # for the existance of an object.  I don't want to do a full search
            # so instead I compare that the dn equals the dn.  If the object
            # exists then it'll return true.  If the object doesn't exist,
            # it'll return error.  If it ever returns false something wierd
            # is going on.
            $res = @ldap_compare($this->_LDAP, $rdn.$branch,
                    $ldapKey, $userdetails[$appKey]);
            if ($res === false) {
                # We should never get here: a DN should ALWAYS match itself
                return PEAR::raiseError("Internal Error: " . __FILE__ . " at " .
                    __LINE__);
            } elseif ($res === true) {
                # The object/user exists but doesn't have the Asterisk
                # objectClasses
                $extension = $userdetails['newextension'];

                # $tmp is the minimal information required to establish
                # an account in LDAP as required by the objectClasses.
                # The entry will be fully populated below.
                $tmp = array();
                $tmp['objectClass'] = array(
                    'asteriskUser',
                    'asteriskVoiceMailbox'
                );
                $tmp['voiceMailbox'] = $extension;
                $tmp['context'] = $context;
                $res = @ldap_mod_add($this->_LDAP, $rdn.$branch, $tmp);
                if (!$res) {
                    return PEAR::raiseError("Unable to modify the user: " .
                        ldap_error($this->_LDAP));
                }

                # Populate the $validusers array to make the edit go smoothly
                # below
                $validusers[$extension] = array();
                $validusers[$extension][$appKey] = $userdetails[$appKey];

                # The remainder of the work is done at the outside of the
                # parent if() like a normal edit.

            } elseif ($res === -1) {
                # We must be adding a new user.
                $entry['objectClass'] = array(
                    'top',
                    'person',
                    'organizationalPerson',
                    'inetOrgPerson',
                    'hordePerson',
                    'asteriskUser',
                    'asteriskVoiceMailbox'
                );

                # Check to see if the maximum number of users for this context
                # has been reached
                $limits = $this->getLimits($context);
                if (is_a($limits, "PEAR_Error")) {
                    return $limits;
                }
                if (count($validusers) >= $limits['asteriskusers']) {
                    print count($validusers).$limits['asteriskusers'];
                    return PEAR::raiseError('Maximum number of users reached.');
                }

                $res = @ldap_add($this->_LDAP, $rdn.$branch, $entry);
                if (!$res) {
                    return PEAR::raiseError('LDAP Add failed: ' .
                        ldap_error($this->_LDAP));
                }

                return true;
            }
        }

        # Anything after this point is an edit.

        # Check to see if the object needs to be renamed (DN changed)
        if ($validusers[$extension][$appKey] != $entry[$ldapKey]) {
            $oldrdn = $ldapKey.'='.$validusers[$extension][$appKey];
            $oldparent = SHOUT_USERS_BRANCH.','.$this->_params['basedn'];
            $newrdn = $ldapKey.'='.$entry[$ldapKey];
            $res = @ldap_rename($this->_LDAP, "$oldrdn,$oldparent",
                $newrdn, $oldparent, true);
            if (!$res) {
                return PEAR::raiseError('LDAP Rename failed: ' .
                    ldap_error($this->_LDAP));
            }
        }

        # Update the object/user
        $dn = $ldapKey.'='.$entry[$ldapKey];
        $dn .= ','.SHOUT_USERS_BRANCH.','.$this->_params['basedn'];
        $res = @ldap_modify($this->_LDAP, $dn, $entry);
        if (!$res) {
            return PEAR::raiseError('LDAP Modify failed: ' .
                ldap_error($this->_LDAP));
        }

        # We must have been successful
        return true;
    }
    // }}}

    // {{{ deleteUser method
    /**
     * Deletes a user from the LDAP tree
     *
     * @param string $context Context to delete the user from
     * @param string $extension Extension of the user to be deleted
     *
     * @return boolean True on success, PEAR::Error object on error
     */
    function deleteUser($context, $extension)
    {
        $ldapKey = &$this->_ldapKey;
        $appKey = &$this->_appKey;

        if (!Shout::checkRights("shout:contexts:$context:users",
            PERMS_DELETE, 1)) {
            return PEAR::raiseError("No permission to delete users in this " .
                "context.");
        }

        $validusers = $this->getUsers($context);
        if (!isset($validusers[$extension])) {
            return PEAR::raiseError("That extension does not exist.");
        }

        $dn = "$ldapKey=".$validusers[$extension][$appKey];
        $dn .= ',' . SHOUT_USERS_BRANCH . ',' . $this->_params['basedn'];

        $res = @ldap_delete($this->_LDAP, $dn);
        if (!$res) {
            return PEAR::raiseError("Unable to delete $extension from " .
                "$context: " . ldap_error($this->_LDAP));
        }
        return true;
    }
    // }}}


    /* Needed because uksort can't take a classed function as its callback arg */
    function _sortexten($e1, $e2)
    {
        print "$e1 and $e2\n";
        $ret =  Shout::extensort($e1, $e2);
        print "returning $ret";
        return $ret;
    }

    // {{{ connect method
    /**
     * Attempts to open a connection to the LDAP server.
     *
     * @return boolean    True on success; exits (Horde::fatal()) on error.
     *
     * @access private
     */
    function _connect()
    {
        if (!$this->_connected) {
            # FIXME What else is needed for this assert?
            Horde::assertDriverConfig($this->_params, 'storage',
                array('hostspec', 'basedn', 'binddn', 'password'));

            # FIXME Add other sane defaults here (mostly objectClass related)
            if (!isset($this->_params['userObjectclass'])) {
                $this->_params['userObjectclass'] = 'asteriskUser';
            }

            $this->_LDAP = ldap_connect($this->_params['hostspec'], 389); #FIXME
            if (!$this->_LDAP) {
                Horde::fatal("Unable to connect to LDAP server $hostname on
$port", __FILE__, __LINE__); #FIXME: $port
            }
            $res = ldap_set_option($this->_LDAP, LDAP_OPT_PROTOCOL_VERSION,
$this->_params['version']);
            if (!$res) {
                return PEAR::raiseError("Unable to set LDAP protocol version");
            }
            $res = ldap_bind($this->_LDAP, $this->_params['binddn'],
$this->_params['password']);
            if (!$res) {
                return PEAR::raiseError("Unable to bind to the LDAP server.
Check authentication credentials.");
            }

            $this->_connected = true;
        }
        return true;
    }
    // }}}
}