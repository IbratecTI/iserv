<?php
// Copyright (C) 2011 Combodo SARL
//
//   This program is free software; you can redistribute it and/or modify
//   it under the terms of the GNU General Public License as published by
//   the Free Software Foundation; version 3 of the License.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of the GNU General Public License
//   along with this program; if not, write to the Free Software
//   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

/**
 * Sample script to import / synchronize users from an Active Directory server
 *
 * @author      Erwan Taloc <erwan.taloc@combodo.com>
 * @author      Romain Quetiez <romain.quetiez@combodo.com>
 * @author      Denis Flaven <denis.flaven@combodo.com>
 * @license     http://www.opensource.org/licenses/gpl-3.0.html LGPL
 */

////////////////////////////////////////////////////////////////////////////////
// Configuration parameters: adjust them to connect to your AD server
// And configure the mapping between AD groups and iTop profiles
$domain = '@ibratec.local';
$aConfig = array(
        // Configuration of the Active Directory connection 
        'host' 	=> '192.168.0.10', // IP or FQDN of your domain controller
        'port' 	=> '389', // LDAP port, 398=LDAP, 636= LDAPS
        'dn'		=> array(
            'ou=Usuarios Ibratec,dc=ibratec,dc=local', // Base DN Usuários Ibratec
            'ou=Terceiros,dc=ibratec,dc=local', // Base DN Terceiros
            ),
        'username'	=> 'itopuser@ibratec.local', // username with read access
        'password'	=> 'e2WTS1l8', // password for above

         // Query to retrieve and filter the users from AD
        // Example: retrieve all users from the AD Group "iTop Users"
        'ldap_query' => '(&(objectClass=user)(CN=*))',
        #'ldap_query' => '(&(objectClass=posixAccount)(member=CN=DP_TI,ou=people,dc=ibratecgrafica,dc=com,dc=br))',
        // Example 2: retrieves ALL the users from AD
        // 'ldap_query' => '(&(objectCategory=user))', // Retrieve all users
        //'ldap_query_group' => '(member:1.2.840.113556.1.4.1941:= #user#)',
        'ldap_query_group' => '(&(objectcategory=group)(distinguishedName= #group#))',    
        // Which field to use as the iTop login samaccountname or userprincipalname ?
        'login' => 'samaccountname',
        //'login' => 'userprincipalname',

        // Mapping between the AD groups and the iTop profiles
        'profiles_mapping' => array(
        //AD Group Name => iTop Profile Name
        'itop_administrator' => 'Administrator',
        'itop_change_approver' => 'Change Approver',
        'itop_change_implementor' => 'Change Implementor',
        'itop_change_supervisor' => 'Change Supervisor',
        'itop_configuration_manager' => 'Configuration Manager',
        'itop_document_author' => 'Document author',
        'itop_hostmaster' => 'Hostmaster',
        'itop_portal_power_user' => 'Portal power user',
        'itop_portal_user' => 'Portal user',
        'itop_problem_manager' => 'Problem Manager',
        'itop_service_desk_agent' => 'Service Desk Agent',
        'itop_service_manager' => 'Service Manager',
        'itop_support_agent' => 'Support Agent',
        ),

        // Since each iTop user must have at least one profile, assign the profile
        // Below to users for which there was no match in the above mapping
        'default_profile' => 'Portal user',
        'default_language' => 'PT BR', // Default language for creating new users
        'default_organization' => '4', // ID of the default organization for creating new contacts
        'default_location' => '1', //Localizacao padrao
);
// End of configuration
////////////////////////////////////////////////////////////////////////////////

if (file_exists('../approot.inc.php'))
{
	// iTop 1.0.2
	include('../approot.inc.php');
}
else // iTop 1.0 & 1.0.1
{
	define('APPROOT', '../');
}
require_once(APPROOT.'application/application.inc.php');
require_once(APPROOT.'application/webpage.class.inc.php');
require_once(APPROOT.'application/csvpage.class.inc.php');
require_once(APPROOT.'application/clipage.class.inc.php');
require_once(APPROOT.'application/startup.inc.php');

// List of attributes to retrieve
$aAttribs = array(
	'samaccountname',
	'sn',
	'givenname',
	'memberof',
	'displayname',
	'mail',
	'postofficebox', //Matricula
	'division', //Centro de custo
	'title', //Cargo
	'physicaldeliveryofficename', //unidade
	'l', //municipio
	'department', //departamento
        'manager', //gerente ou responsável direto
        'mobile', //telefone celular
        'postcode', //matricula
        'telephonenumber', //telefone fixo
        'streetaddress', //endereço
        'userprincipalname', //nome no dominio do AD
        'employeenumber', //matricula
);

$g_aUsersCache = null;   	// Cache of all the iTop users to speed up searches
$g_aProfilesCache = null;	// Cache of all iTop profiles

/**
 * Helper function to read attributes from LDAP data
 * @param hash The LDAP data for one item as returned by ldap_search
 * @param string The name of the attribute to retrieve
 * @return mixed null if no such attribute, a scalar or a array depending on the
 *                    number of values for the attribute.
 */   
function ReadLdapValue($aEntry, $sValueName)
{
	if (array_key_exists($sValueName, $aEntry))
	{
		$iCount = $aEntry[$sValueName]['count'];
		switch($iCount)
		{
			case 0:
			// No value, return null
			return null;
			
			case 1:
			// Just one value, return it
			return $aEntry[$sValueName][0];
			
			default:
			// Many values, return all of them as an array
			// except the 'count' entry
			$aValues = $aEntry[$sValueName];
			unset($aValues['count']);
			return $aValues;
		}
	}
	return null;
}
/**
 * Helper function that processes 1 user at a time
 * @param $aData hash The input data from Active Directory
 * @param $index integer The index of the current user in the AD query (for reporting)
 * @param $aConfig hash The configuration parameter
 * @param $oChange CMDBChange Change to record all the changes or null if simulation mode
 * @return string The action undertaken 'created', 'synchronized', 'error' 
 */ 
function ProcessUser($aData, $index, $aConfig, $oChange = null)
{
	$sAction = 'error';
	
	$sUserLogin = $aData['samaccountname'];
	if (!is_array($aData['memberof']))
	{
		$aADGroups = array($aData['memberof']);
	}
	else
	{
		$aADGroups = $aData['memberof'];
	}
	$aITopProfiles = array(); 
	foreach($aADGroups as $sGroupString)
	{
		$aMatches = array();
		$sShortGroupString = '';
		#if (preg_match('/^CN=([^,]+)/', $sGroupString, $aMatches))
		if (preg_match('/^CN=([^,]+)/', $sGroupString, $aMatches))
		{
			$sShortGroupString = $aMatches[1];
		}
		//echo "<p>GroupString: $sGroupString => $sShortGroupString</p>";
		if (isset($aConfig['profiles_mapping'][$sShortGroupString]))
		{
			$aITopProfiles[] = $aConfig['profiles_mapping'][$sShortGroupString];
		}
	}
	if (count($aITopProfiles) == 0)
	{
		// Each user must have at least one profile
		// Assign the 'default_profile' to this user
		$aITopProfiles[] = $aConfig['default_profile'];
	}
	echo "<h2>User#{$index}: {$aData['displayname']}</h2>\n";
	echo "<table>";
	foreach($aData as $sAttrib => $value)
	{
		echo "<tr><td style=\"vertical-align:top;background-color:eee;\">$sAttrib</td>";
		echo "<td style=\"vertical-align:top;background-color:eee;\">";
		if (is_array($value))
		{
			echo implode('<br/>', $value);
		}
		else
		{
			echo htmlentities($value);
		}
		echo "</td></tr>\n";
	}
	echo "<tr><td style=\"vertical-align:top;background-color:eee;\">iTop Profiles</td>";
	echo "<td style=\"vertical-align:top;background-color:eee;\">";
	echo implode('<br/>', $aITopProfiles);
	echo "</td></tr>\n";
	echo "</table>";
	$sLogin = $aData[$aConfig['login']];
	$oITopUser = GetUserByLogin($sLogin);
	if ($oITopUser == null)
	{
		// Check if a contact needs to be created or not
		$oPerson = GetPersonByEmail($aData['mail']);
		if (is_object($oPerson))
		{
			echo "<p>A person with the email='{$aData['mail']}' was found ".$oPerson->GetHyperlink().". This person will be used when creating the account.</p>";
		}
		else if ($oPerson == null)
		{
			echo "<p>A new person will be created.</p>";
			$oPerson = new Person();
			$oPerson->Set('name', str_replace($aData['givenname'],'',trim(ucwords(strtolower($aData['displayname'])))));
			$oPerson->Set('first_name', $aData['givenname']);
			$oPerson->Set('email', $aData['mail']);
			//$oPerson->Set('org_id', $aConfig['default_organization']);
			//echo ucwords(strtolower($aData['title']));
			$oPerson->Set('function', ucwords(strtolower($aData['title'])));
			$oPerson->Set('employee_number', $aData['postofficebox']);
                        //$oPerson->Set('cost_center', $aData['division']);
                        $oPerson->Set('location_id',FindUserLocation($aData['l'], $aData['department']));
                        $oPerson->Set('org_id',FindOrgId($aData['title'], $aData['department']));
			if ($oChange != null)
			{
				$oPerson->DBInsertTracked($oChange);
			}
		}
		else
		{
			// Error ! Several matches found ??
			throw new Exception($oPerson);
		}
		$sAction = 'created';
		echo "<h2>User $sLogin will be <em>created</em> in iTop</h2>";
		$oITopUser = new UserLDAP;
		$oITopUser->Set('login', $sLogin);
		$oITopUser->Set('contactid', $oPerson->GetKey());
		$oITopUser->Set('language', $aConfig['default_language']);
		// Update the profiles
		$oLinkSet = DBObjectSet::FromScratch('URP_UserProfile');
		foreach($aITopProfiles as $sProfile)
		{
			$oLink = new URP_UserProfile;
			$iProfileId = GetProfileByName($sProfile);
			if ($iProfileId != null)
			{
				$oLink->Set('profileid', $iProfileId);
				$oLinkSet->AddObject($oLink);
			}
			else
			{
				echo "<p><b>Error: the profile '$sProfile' does not exist in iTop, verify the profiles_mapping configuration!</b></p>";
			}
		}
		$oITopUser->Set('profile_list', $oLinkSet);
		if ($oChange != null)
		{
			$oITopUser->DBInsertTracked($oChange);
		}
	}
	else if(is_object($oITopUser))
	{
		$sAction = 'synchronized';
		echo "<h2>User $sLogin (UserLDAP::".$oITopUser->GetKey().") will be <em>synchronized</em> in iTop</h2>";
		// Update the profiles
		$oLinkSet = DBObjectSet::FromScratch('URP_UserProfile');
		$oITopUser->Set('login', $sLogin);
		foreach($aITopProfiles as $sProfile)
		{
			$oLink = new URP_UserProfile;
			$iProfileId = GetProfileByName($sProfile);
			if ($iProfileId != null)
			{
				$oLink->Set('profileid', $iProfileId);
				$oLinkSet->AddObject($oLink);
			}
			else
			{
				echo "<p><b>Error: the profile '$sProfile' does not exist in iTop, verify the profiles_mapping configuration!</b></p>";
			}
		}
		$oITopUser->Set('profile_list', $oLinkSet);
		if ($oChange != null)
		{
			$oITopUser->DBUpdateTracked($oChange);
		}
	}
	else
	{
		// Error, another kind of user already exists with the same login ?
		echo "<h2 style=\"color:#C00\">Error: $oITopUser</h2>";
	}
	return $sAction;
}

function FindUserLocation($location = 'Barueri', $department = 'ABC')
{
    
    static $oSearch = null; // OQL Query cache
    if ($oSearch == null)
    {
            $sOQL = 'SELECT Location WHERE city = :city AND name LIKE :name';
            $oSearch = DBObjectSearch::FromOQL($sOQL);
    }
    $oSet = new CMDBObjectSet($oSearch, array(), array('city' => $location, 'name' => '%'.$department.'%'),NULL,'1');
    switch($oSet->Count())
    {
            case 0:
                if ($location == 'Barueri')
                {
                        $location_id ='1';
                }
                else 
                {
                        $location_id ='2';
                }
            break;

            case 1:
                        $oLoc = $oSet->Fetch();
                        $location_id = $oLoc->GetKey();
            break;

            default:
            $location_id = '1';
    }
    return $location_id;
}

function FindOrgId($title = '', $department = 'ABC')
{
    $org_id = '1';
    $title = strtolower($title);
    $department = strtolower($department);
    if ($department=='ti')
    {
        $org_id = '6';
    } else if (substr_count($title, 'diretor')>=1)
    {
        $org_id = '2';
    } else if (substr_count($title, 'ger ')>=1 || substr_count($title, 'gerente')>=1)
    {
        $org_id = '3';
    } else if (substr_count($title, 'coord ')>=1 || substr_count($title, 'coordenador')>=1)
    {
        $org_id = '3';
    } else 
    {
        $org_id = '4';
    }
    return $org_id;
}

/**
 * Search the given user (identified by its login) in the iTop database
 * @param $sLogin string The login of the user
 * @return mixed null => nothing found, object => the user to synchronize, string => error message
 */
function GetUserByLogin($sLogin)
{
	global $g_aUsersCache;
	$result = null;
	if ($g_aUsersCache == null) InitUsersCache();
	if (isset($g_aUsersCache[$sLogin]))
	{
		$oUser = $g_aUsersCache[$sLogin];
		if (get_class($oUser) != 'UserLDAP')
		{
			$result = "A user with the same login ($sLogin), but not managed by LDAP already exists in iTop, the AD record will be ignored.";
		}
		else
		{
			$result = $oUser;
		}
	}
	return $result;
}

function GetNestedGroups($ad,$aGroup,$aConfigSearch,$aConfigDn)
{
    $sLdapSearch = str_replace('#group#', $aGroup, $aConfigSearch);
    $aAttribs = array('memberof');
    $search = ldap_search($ad, $aConfigDn, $sLdapSearch , $aAttribs) or die ("ldap search failed");
    $entries = ldap_get_entries($ad, $search);
    $aoutput=array();
    $a=array();
    foreach($entries as $adata)
    {
        if($adata['count'] != 0)
        {
            foreach($adata as $key=>$amemberofdata)
            {
                if($key==="memberof")
                {
                    foreach($amemberofdata as $keymgo=>$sgroup)
                    {
                        $a[$keymgo]=$sgroup;
                    }
                    foreach($a as $akey=>$soutgroup)
                    {
                        if($akey != 'count')
                        {
                        $aoutput[]=$soutgroup;
                        }
                    }
                }
            }
        }
    }
    if(empty($aoutput))
    {
        return null;
    }
    else
    {
        return $aoutput;
    }
}

/**
 * Initializes the cache for quickly searching iTop users
 * @param none
 * @return integer Number of users fetched from iTop  
 */
function InitUsersCache()
{
	global $g_aUsersCache;
	$sOQL = "SELECT User";
	$oSearch = DBObjectSearch::FromOQL($sOQL);
	$oSet = new CMDBObjectSet($oSearch);
	$iRet = $oSet->Count();
	while($oUser = $oSet->Fetch())
	{
		$g_aUsersCache[$oUser->Get('login')] = $oUser;
	}
	return $iRet;
}

/**
 * Retrieves the ID of a profile (in iTop) base on its name
 * @param $sProfile string Name of the profile
 * @return integer ID of the profile, or null is not found
 */
function GetProfileByName($sProfileName)
{
	global $g_aProfilesCache;
	$iRet = null;
	if ($g_aProfilesCache == null) InitProfilesCache();
	
	if (isset($g_aProfilesCache[$sProfileName]))
	{
		$iRet = $g_aProfilesCache[$sProfileName];
	}
	return $iRet;
}

/**
 * Initializes the cache of the iTop profiles
 * @param none
 * @return void
 */
function InitProfilesCache()
{
	global $g_aProfilesCache;
	$sOQL = "SELECT URP_Profiles";
	$oSearch = DBObjectSearch::FromOQL($sOQL);
	$oSet = new CMDBObjectSet($oSearch);
	while($oProfile = $oSet->Fetch())
	{
		$g_aProfilesCache[$oProfile->GetName()] = $oProfile->GetKey();
	}
}

/**
 * Search for a Person by email address
 * @param $sEmail string
 * @return mixed Person (if found) or null (not found) or string (error)
 */
function GetPersonByEmail($sEmail)
{
	static $oSearch = null; // OQL Query cache
	$person = null;
	if ($oSearch == null)
	{
		$sOQL = 'SELECT Person WHERE email = :email';
		$oSearch = DBObjectSearch::FromOQL($sOQL);
	}
	$oSet = new CMDBObjectSet($oSearch, array(), array('email' => $sEmail));
	switch($oSet->Count())
	{
		case 0:
		$person = null;
		break;
		
		case 1:
		$person = $oSet->Fetch();
		break;
		
		default:
		$person = ' several matches found: '.$oSet->Count()." persons have the email address '$sEmail'";
	}
	return $person;	
}             
/******************************************************************************
 *
 * Main program
 *  
 ******************************************************************************/
if (utils::IsModeCLI())
{
	$sAuthUser = utils::ReadParam('auth_user', '', true);
	$sAuthPwd = utils::ReadParam('auth_pwd', '', true);
	if (UserRights::CheckCredentials($sAuthUser, $sAuthPwd))
	{
		UserRights::Login($sAuthUser); // Login & set the user's language
	}
	else
	{
		echo "Access restricted or wrong credentials ('$sAuthUser')";
		exit;
	}
}
else
{
	$_SESSION['login_mode'] = 'basic';
	require_once('../application/loginwebpage.class.inc.php');
	LoginWebPage::DoLogin(); // Check user rights and prompt if needed

	$sCSVData = utils::ReadPostedParam('csvdata');
}
if (!UserRights::IsAdministrator())
{
	echo '<p>Access is restricted to administrators</p>';
	exit;
}
// By default, run in simulation mode (i.e do nothing)
$bSimulationMode = utils::ReadParam('simulation', 1, true);
$oMyChange = null;
if (!$bSimulationMode)
{
	$oMyChange = MetaModel::NewObject("CMDBChange");
	$oMyChange->Set("date", time());
	if (UserRights::IsImpersonated())
	{
		$sUserString = Dict::Format('UI:Archive_User_OnBehalfOf_User', UserRights::GetRealUser(), UserRights::GetUser());
	}
	else
	{
		$sUserString = UserRights::GetUser();
	}
	$oMyChange->Set("userinfo", $sUserString);
	$oMyChange->DBInsert();
}
else
{
	echo "<h1 style=\"color:#900\">Simulation mode -- no action will be performed</h1>";
	echo "<p>Set the parameter simulation=0 to trigger the actual execution.</p>";
} 
foreach($aConfig['dn'] as $bdn)
{
    $ad = ldap_connect($aConfig['host'], $aConfig['port']) or die( "Could not connect to {$aConfig['host']} on port {$aConfig['port']}!" );
    echo "<p>Connected to {$aConfig['host']} on port {$aConfig['port']}</p>\n";
    // Set version number
    ldap_set_option($ad, LDAP_OPT_PROTOCOL_VERSION, 3) or die ("Could not set ldap protocol");
    ldap_set_option($ad, LDAP_OPT_REFERRALS,0) or die ("could no se the ldap referrals");

    // Binding to ldap server
    $bd = ldap_bind($ad, $aConfig['username'], $aConfig['password']) or die ("Could not bind");
    echo "<p>Identified as {$aConfig['username']}</p>\n";

    $sLdapSearch = $aConfig['ldap_query'];

    echo "<p>LDAP Query: '$sLdapSearch'</p>";
    $search = ldap_search($ad, $bdn, $sLdapSearch /*, $aAttribs*/) or die ("ldap search failed");

    $entries = ldap_get_entries($ad, $search);
    //print_r($entries);
    $index = 1;
    $aStatistics = array(
            'created' => 0,
            'synchronized' => 0,
            'error' => 0,
    );
    $iCreated = 0;
    $iSynchronized = 0;
    $iErrors = 0;	
    if ($entries["count"] > 0)
    {
        $iITopUsers = InitUsersCache();
        echo "<h1>{$entries["count"]} user(s) found in Active Directory, $iITopUsers (including non-LDAP users) found in iTop.</h1>\n";
        foreach($entries as $key => $aEntry)
        {
            if (strcmp($key,'count') != 0)
            {
                $aData = array();
                foreach($aAttribs as $sName)
                {
                    $aData[$sName] = ReadLdapValue($aEntry, $sName);
                }
                $aDataMemberof=array();
                if(gettype($aData['memberof'])=='array')
                {
                    foreach($aData['memberof'] as $sGroup)
                    {
                        $aDataMemberof = GetNestedGroups($ad,$sGroup,$aConfig['ldap_query_group'],$bdn);
                        if(gettype($aDataMemberof)=='array')
                        {
                            foreach($aDataMemberof as $sNestedGroup)
                            {
                                $aData['memberof'][]=$sNestedGroup;
                            }
                        }
                    }
                }
                else if(gettype($aData['memberof'])=='string')
                {
                    $sGroup=$aData['memberof'];
                    unset($aData['memberof']);
                    $aData['memberof']=array();
                    $aData['memberof'][]=$sGroup;
                    $aDataMemberof = GetNestedGroups($ad,$sGroup,$aConfig['ldap_query_group'],$bdn);
                    if(gettype($aDataMemberof)=='array')
                    {
                        foreach($aDataMemberof as $sNestedGroup)
                        {

                            $aData['memberof'][]=$sNestedGroup;
                        }
                    }
                }
                try
                {
                    $sAction = ProcessUser($aData, $index, $aConfig, $oMyChange);
                }
                catch(Exception $e)
                {
                        echo "<p><b>An error occured while processing $index: ".$e->getMessage()."</b></p>";
                        $sAction = 'error';
                }
                echo "<hr/>\n";
                $aStatistics[$sAction]++;
                $index++;
            }
        }
    }
    else
    {
            echo "<p>Nothing found !</p>\n";
            echo "<p>LDAP query was: $sLdapSearch</p>\n";
    }
    ldap_unbind($ad);

    if ($bSimulationMode)
    {
            echo "<h1 style=\"color:#900\">Simulation mode -- no action was performed</h1>";
    }
    echo "<h1>Statistics:</h1>";
    echo "<table>";
    foreach($aStatistics as $sKey => $iValue)
    {
            echo "<tr><td style=\"vertical-align:top;background-color:eee;\">$sKey</td>\n";
            echo "<td style=\"vertical-align:top;background-color:eee;\">$iValue</td></tr>\n";
    }
    echo "</table>";
}
?>
