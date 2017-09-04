<?php

require_once 'sblocationbased.civix.php';
use CRM_Sblocationbased_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function sblocationbased_civicrm_config(&$config) {
  _sblocationbased_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function sblocationbased_civicrm_xmlMenu(&$files) {
  _sblocationbased_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function sblocationbased_civicrm_install() {
  _sblocationbased_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function sblocationbased_civicrm_postInstall() {
  _sblocationbased_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function sblocationbased_civicrm_uninstall() {
  //delete custom groups and field
  $customGroup = civicrm_api3('CustomGroup', 'getsingle', array('return' => "id",'name' => "sb_proximity_search",));
  civicrm_api3('CustomGroup', 'delete', array('id' => $customGroup['id']));

  return _sblocationbased_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function sblocationbased_civicrm_enable() {
    sblocationbased_setActiveFields( 1 );
  _sblocationbased_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function sblocationbased_civicrm_disable() {
    sblocationbased_setActiveFields( 0 );
  _sblocationbased_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function sblocationbased_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _sblocationbased_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function sblocationbased_civicrm_managed(&$entities) {
  _sblocationbased_civix_civicrm_managed($entities);
}


function sblocationbased_getCustomGroupId() {
  $groups = CRM_Core_PseudoConstant::get('CRM_Core_BAO_CustomField', 'custom_group_id', array('labelColumn' => 'name'));
  return array_search('sb_proximity_search', $groups);
}

function sblocationbased_setActiveFields($setActive) {
  //disable all custom group and fields
  $sql = "UPDATE civicrm_custom_field
JOIN civicrm_custom_group ON civicrm_custom_group.id = civicrm_custom_field.custom_group_id
SET civicrm_custom_field.is_active = {$setActive}
WHERE civicrm_custom_group.name IN ('sb_proximity_search')";

  CRM_Core_DAO::executeQuery($sql);
  CRM_Core_DAO::executeQuery("UPDATE civicrm_custom_group SET is_active = {$setActive} WHERE name IN ('sb_proximity_search')");
  
//  CRM_Core_DAO::executeQuery("UPDATE civicrm_uf_group SET is_active = {$setActive} WHERE name = 'hrcareer_tab'");

}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function sblocationbased_civicrm_caseTypes(&$caseTypes) {
  _sblocationbased_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function sblocationbased_civicrm_angularModules(&$angularModules) {
  _sblocationbased_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function sblocationbased_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _sblocationbased_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function sblocationbased_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function sblocationbased_civicrm_navigationMenu(&$menu) {
  _sblocationbased_civix_insert_navigation_menu($menu, NULL, array(
    'label' => E::ts('The Page'),
    'name' => 'the_page',
    'url' => 'civicrm/the-page',
    'permission' => 'access CiviReport,access CiviContribute',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _sblocationbased_civix_navigationMenu($menu);
} // */

function sblocationbased_civicrm_postProcess($formName, &$form){
    
//    (new CRM_Contact_BAO_Query())->whereClauseSingle( $form );
//    pre_print( $_POST );
//    pre_print( $form->getVar( 'prox_distance' ) );
//    
//    exit;
}

function sblocationbased_validateForm($formName, &$fields, &$files, &$form, &$errors){
//    pre_print( $fields );
//    return CRM_Contact_BAO_ProximityQuery::process($form, $fields);
}

function sblocationbased_civicrm_buildForm( $formName, &$form ){
    CRM_Utils_Request::retrieve($name, $type);
}




  function pre_print( $data, $text = '' ){
        echo "<pre> <br>------------------------------DEBUG MODE START------------------------------<Br><Br>";
        echo $text;
        $bt = debug_backtrace();
        $caller = array_shift($bt);
        echo "File Name: " . $caller['file'];
        echo "<br>Line No: " . $caller['line'] . '<br><br>';
        print_r( $data );
        die( '<br><br>------------------------------DEBUG MODE END------------------------------');
  }