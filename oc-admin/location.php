<?php

/*
 *      OSCLass – software for creating and publishing online classified
 *                           advertising platforms
 *
 *                        Copyright (C) 2010 OSCLASS
 *
 *       This program is free software: you can redistribute it and/or
 *     modify it under the terms of the GNU Affero General Public License
 *     as published by the Free Software Foundation, either version 3 of
 *            the License, or (at your option) any later version.
 *
 *     This program is distributed in the hope that it will be useful, but
 *         WITHOUT ANY WARRANTY; without even the implied warranty of
 *        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *             GNU Affero General Public License for more details.
 *
 *      You should have received a copy of the GNU Affero General Public
 * License along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'oc-load.php';

$prefManager = Preference::newInstance();
$preferences = $prefManager->toArray();

$action = osc_readAction();


function makeRedirect($section, $vars) {
    $string = "?";
    if(isset($vars['countryId']) && $vars['countryId']!="") {
        $string .= "&countryId=".$vars['countryId'];
    }
    if(isset($vars['regionId']) && $vars['regionId']!="") {
        $string .= "&regionId=".$vars['regionId'];
    }
    $string .= '&action='.$section;
    osc_redirectTo('location.php'.$string);
}

switch ($action) {
    case 'countries':
        $mCountries = new Country();
        $aCountries = $mCountries->listAll() ;
        osc_renderAdminSection('location/countries.php', __('Countries'));
        break;
    case 'regions':
        $mCountries = new Country();
        $aCountries = $mCountries->listAll();

        $mRegions = new Region();
        $aRegions = $mRegions->getByCountry( isset( $_GET['countryId'] ) ? strtolower($_GET['countryId']) : '' );

        osc_renderAdminSection('location/regions.php', __('Regions'));
        break;
    case 'cities':
        $mCountries = new Country();
        $aCountries = $mCountries->listAll() ;

        $mRegions = new Region();
        $aRegions = $mRegions->getByCountry( isset( $_GET['countryId'] ) ? strtolower($_GET['countryId']) : '' );

        $mCities = new City();
        $aCities = $mCities->getByRegion( isset( $_GET['regionId'] ) ? strtolower( $_GET['regionId'] ) : '' );
        osc_renderAdminSection('location/cities.php', __('Cities'));
        break;
    case 'countries_add':
        install_location_by_country();

        osc_redirectTo('location.php?action=countries');
        break;
    case 'countries_edit':
        $mCountries = new Country();
        foreach ($_REQUEST['country'] as $k => $_data) {
            foreach($_data as $code => $name) {
                $mCountries->update( array('s_name' => $name, 'fk_c_locale_code' => $code), array('pk_c_code' => $k) ) ;
            }
        }
        makeRedirect('countries', $_REQUEST);
        break;
    case 'country_delete':
        $mCountries = new Country();
        $mRegions = new Region();
        $mCities = new City();
        if(isset($_REQUEST['id'])) {
            $country = $mCountries->findByCode($_REQUEST['id']) ;
            $aRegions = $mRegions->listWhere('fk_c_country_code = \'' . $country['pk_c_code'] . '\'') ;
            foreach ($aRegions as $region) {
                $mCities->delete( array('fk_i_region_id' => $region['pk_i_id']) ) ;
                $mRegions->delete( array('pk_i_id' => $region['pk_i_id']) ) ;
            }
        }
        $mCountries->delete( array('pk_c_code' => $_REQUEST['id']) ) ;
        makeRedirect('countries', $_REQUEST);
        break;
    case 'regions_add':
        install_location_by_region() ;
        
        makeRedirect('regions', $_REQUEST);
        break;
    case 'regions_edit':
        if(isset($_REQUEST['countryId']) && $_REQUEST['countryId']!="" && isset($_REQUEST['region']) && is_array($_REQUEST['region'])) {
            foreach($_REQUEST['region'] as $k => $v) {
                $conn = getConnection() ;
                $conn->osc_dbExec("UPDATE %st_region SET s_name = '%s' WHERE pk_i_id = %d AND fk_c_country_code = '%s'", DB_TABLE_PREFIX, $v, $k, strtolower($_REQUEST['countryId']));
            }
        }
        makeRedirect('regions', $_REQUEST);
        break;
    case 'region_delete':
        $mRegions = new Region();
        $mCities = new City();
        if( isset($_REQUEST['id']) ) {
            $mCities->delete( array('fk_i_region_id' => $_REQUEST['id']) );
            $mRegions->deleteByID($_REQUEST['id']);
        }
        makeRedirect('regions', $_REQUEST);
        break;
    case 'cities_add':
        install_location_by_city();

        makeRedirect('cities', $_REQUEST);
        break;
    case 'cities_edit':
        if(isset($_REQUEST['countryId']) && $_REQUEST['countryId']!="" && isset($_REQUEST['regionId']) && $_REQUEST['regionId']!="" && isset($_REQUEST['city']) && is_array($_REQUEST['city'])) {
            foreach($_REQUEST['city'] as $k => $v) {
                $conn = getConnection();
                $conn->osc_dbExec("UPDATE  `%st_city` SET  `s_name` =  '%s' WHERE  `pk_i_id` = %d", DB_TABLE_PREFIX, $v, $k);
            }
        }
        makeRedirect('cities', $_REQUEST);
        break;
    case 'city_delete':
        if(isset($_REQUEST['id'])) {
            City::newInstance()->delete(array('pk_i_id' => $_REQUEST['id']));
        }
        makeRedirect('cities', $_REQUEST);
        break;
    default:
        $languages = Locale::newInstance()->listAllEnabled();

        osc_renderAdminSection('location/countries.php', __('Location management'));
}

function install_location_by_country() {
    $country[] = trim($_POST['i_country']);

    $countries_json = file_get_contents('http://geo.osclass.org/geo.download.php?action=country&term='.  implode(',', $country) . "&install=true");
    $countries = json_decode($countries_json);

    $manager_country = new Country();

    foreach($countries as $c) {
        $manager_country->insert(array(
            "pk_c_code" => addslashes($c->id),
            "fk_c_locale_code" => addslashes($c->locale_code),
            "s_name" => addslashes($c->name)
        ));
    }

    $manager_region = new Region();

    $regions_json = file_get_contents('http://geo.osclass.org/geo.download.php?action=region&country=' . implode(',', $country) . '&term=all');
    $regions = json_decode($regions_json);

    foreach($regions as $r) {
        $manager_region->insert(array(
            "pk_i_id" => addslashes($r->id),
            "fk_c_country_code" => addslashes($r->country_code),
            "s_name" => addslashes($r->name)
        ));
    }

    $manager_city = new City();

    foreach($countries as $c) {
        $cities_json = file_get_contents('http://geo.osclass.org/geo.download.php?action=city&country=' . $c->name . '&term=all');
        $cities = json_decode($cities_json);

        if(!isset($cities->error)) {
            foreach($cities as $ci) {
                $manager_city->insert(array(
                    "pk_i_id" => addslashes($ci->id),
                    "fk_i_region_id" => addslashes($ci->region_id),
                    "s_name" => addslashes($ci->name),
                    "fk_c_country_code" => addslashes($ci->country_code)
                ));
            }
        }

        unset($cities);
        unset($cities_json);
    }

}

function install_location_by_region() {
    if(!isset($_POST['countryId']))
        return false;

    if(!isset($_POST['region']))
        return false;

    $manager_country = new Country();
    
    $aCountry = $manager_country->findByCode($_POST['countryId']);
    
    $country = array();
    $region = array();

    $country[] = $aCountry['s_name'];
    $region[] = $_POST['region'];

    $countries_json = file_get_contents('http://geo.osclass.org/geo.download.php?action=country&term='.  implode(',', $country) . "&install=true");
    $countries = json_decode($countries_json);

    foreach($countries as $c) {
        $manager_country->insert(array(
            "pk_c_code" => addslashes($c->id),
            "fk_c_locale_code" => addslashes($c->locale_code),
            "s_name" => addslashes($c->name)
        ));
    }

    $regions_json = file_get_contents('http://geo.osclass.org/geo.download.php?action=region&country=' . implode(',', $country) . '&term=' . implode(',', $region));
    $regions = json_decode($regions_json);

    $manager_region = new Region();
    foreach($regions as $r) {
        $manager_region->insert(array(
            "pk_i_id" => addslashes($r->id),
            "fk_c_country_code" => addslashes($r->country_code),
            "s_name" => addslashes($r->name)
        ));
    }

    $manager_city = new City();
    foreach($countries as $c) {
        $cities_json = file_get_contents('http://geo.osclass.org/geo.download.php?action=city&country=' . $c->name . '&region=' . implode(',', $region) . '&term=');
        $cities = json_decode($cities_json);
        if(!isset($cities->error)) {
            foreach($cities as $ci) {
                $manager_city->insert(array(
                    "pk_i_id" => addslashes($ci->id),
                    "fk_i_region_id" => addslashes($ci->region_id),
                    "s_name" => addslashes($ci->name),
                    "fk_c_country_code" => addslashes($ci->country_code)
                ));
            }
        }
        unset($cities);
        unset($cities_json);
    }
}

function install_location_by_city() {
    if(!isset($_POST['country']))
        return false;

    if(!isset($_POST['city']))
        return false;

    $country = $_POST['country'];
    $city = $_POST['city'];

    $countries_json = file_get_contents('http://geo.osclass.org/geo.download.php?action=country&term='.  implode(',', $country) . "&install=true");
    $countries = json_decode($countries_json);

    $manager_country = Country::newInstance();
    foreach($countries as $c) {
        $manager_country->insert(array(
            "pk_c_code" => addslashes($c->id),
            "fk_c_locale_code" => addslashes($c->locale_code),
            "s_name" => addslashes($c->name)
        ));
    }

    $manager_city = City::newInstance();
    $manager_region = Region::newInstance();
    foreach($countries as $c) {
        $cities_json = file_get_contents('http://geo.osclass.org/geo.download.php?action=city&country=' . $c->name . '&term=' . implode(',', $city) );
        $cities = json_decode($cities_json);
        if(!isset($cities->error)) {
            foreach($cities as $ci) {
                $regions_json = file_get_contents('http://geo.osclass.org/geo.download.php?action=region&country=&id=' . $ci->region_id);
                $regions = json_decode($regions_json);

                foreach($regions as $r) {
                    $manager_region->insert(array(
                        "pk_i_id" => addslashes($r->id),
                        "fk_c_country_code" => addslashes($r->country_code),
                        "s_name" => addslashes($r->name)
                    ));
                }

                $manager_city->insert(array(
                    "pk_i_id" => addslashes($ci->id),
                    "fk_i_region_id" => addslashes($ci->region_id),
                    "s_name" => addslashes($ci->name),
                    "fk_c_country_code" => addslashes($ci->country_code)
                ));
            }
        }
        unset($cities);
        unset($cities_json);
    }
}

?>