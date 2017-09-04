<?php

/**
 * Description of MappingFilter
 *
 * @author Tuhin
 */

class CRM_SbLocationBased_Library_FilterSearchBuilder_MappingFilter extends CRM_Core_BAO_Mapping {
    
   /**
   * Build the mapping form.
   *
   * @param CRM_Core_Form $form
   * @param string $mappingType
   *   (Export/Search Builder). (Import apparently used to use this but does no longer).
   * @param int $mappingId
   * @param int $columnNo
   * @param int $blockCount
   *   (no of blocks shown).
   * @param NULL $exportMode
   */
  public static function buildMappingForm(&$form, $mappingType, $mappingId, $columnNo, $blockCount, $exportMode = NULL) {

    $hasProximityTypes = array();
    $hasLocationTypes = array();
    $hasRelationTypes = array();
    $fields = array();

    //get the saved mapping details

    if ($mappingType == 'Export') {
      $columnCount = array('1' => $columnNo);
      $form->applyFilter('saveMappingName', 'trim');

      //to save the current mappings
      if (!isset($mappingId)) {
        $saveDetailsName = ts('Save this field mapping');
        $form->add('text', 'saveMappingName', ts('Name'));
        $form->add('text', 'saveMappingDesc', ts('Description'));
      }
      else {
        $form->assign('loadedMapping', $mappingId);

        $params = array('id' => $mappingId);
        $temp = array();
        $mappingDetails = CRM_Core_BAO_Mapping::retrieve($params, $temp);

        $form->assign('savedName', $mappingDetails->name);

        $form->add('hidden', 'mappingId', $mappingId);

        $form->addElement('checkbox', 'updateMapping', ts('Update this field mapping'), NULL);
        $saveDetailsName = ts('Save as a new field mapping');
        $form->add('text', 'saveMappingName', ts('Name'));
        $form->add('text', 'saveMappingDesc', ts('Description'));
      }

      $form->addElement('checkbox', 'saveMapping', $saveDetailsName, NULL, array('onclick' => "showSaveDetails(this)"));
      $form->addFormRule(array('CRM_Export_Form_Map', 'formRule'), $form->get('mappingTypeId'));
    
    }
    elseif ($mappingType == 'Search Builder') {
      $columnCount = $columnNo;
      $form->addElement('submit', 'addBlock', ts('Also include contacts where'),
        array('class' => 'submit-link')
      );
    }
    
//    $config = CRM_Core_Config::singleton();
//    echo ' s - '. $config->geocodeMethod;
//      pre_print( $mappingId, 'here' );
      

        
    $contactType = array('Individual', 'Household', 'Organization');
    foreach ($contactType as $value) {
      if ($mappingType == 'Search Builder') {
        // get multiple custom group fields in this context
        $contactFields = CRM_Contact_BAO_Contact::exportableFields($value, FALSE, FALSE, FALSE, TRUE);
//        pre_print( $contactFields );
      }
      else {
        $contactFields = CRM_Contact_BAO_Contact::exportableFields($value, FALSE, TRUE);
      }
      $contactFields = array_merge($contactFields, CRM_Contact_BAO_Query_Hook::singleton()->getFields());

      // exclude the address options disabled in the Address Settings
      $fields[$value] = CRM_Core_BAO_Address::validateAddressOptions($contactFields);
      ksort($fields[$value]);
      
      
      
      //current debug here
//      pre_print( $fields['Individual']['city'] );
      
      if ($mappingType == 'Export') {
        $relationships = array();
        $relationshipTypes = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, NULL, NULL, $value, TRUE);
        asort($relationshipTypes);

        foreach ($relationshipTypes as $key => $var) {
          list($type) = explode('_', $key);

          $relationships[$key]['title'] = $var;
          $relationships[$key]['headerPattern'] = '/' . preg_quote($var, '/') . '/';
          $relationships[$key]['export'] = TRUE;
          $relationships[$key]['relationship_type_id'] = $type;
          $relationships[$key]['related'] = TRUE;
          $relationships[$key]['hasRelationType'] = 1;
        }

        if (!empty($relationships)) {
          $fields[$value] = array_merge($fields[$value],
            array('related' => array('title' => ts('- related contact info -'))),
            $relationships
          );
        }
      }
    }

    //get the current employer for mapping.
    if ($mappingType == 'Export') {
      $fields['Individual']['current_employer_id']['title'] = ts('Current Employer ID');
    }

    // add component fields
    $compArray = array();

    //we need to unset groups, tags, notes for component export
    if ($exportMode != CRM_Export_Form_Select::CONTACT_EXPORT) {
      foreach (array(
                 'groups',
                 'tags',
                 'notes',
               ) as $value) {
        unset($fields['Individual'][$value]);
        unset($fields['Household'][$value]);
        unset($fields['Organization'][$value]);
      }
    }
    
    
//    pre_print( $fields );
    

    if ($mappingType == 'Search Builder') {
      //build the common contact fields array.
      $fields['Contact'] = array();
      foreach ($fields['Individual'] as $key => $value) {
        if (!empty($fields['Household'][$key]) && !empty($fields['Organization'][$key])) {
          $fields['Contact'][$key] = $value;
          unset($fields['Organization'][$key],
            $fields['Household'][$key],
            $fields['Individual'][$key]);
        }
      }
      if (array_key_exists('note', $fields['Contact'])) {
        $noteTitle = $fields['Contact']['note']['title'];
        $fields['Contact']['note']['title'] = $noteTitle . ': ' . ts('Body and Subject');
        $fields['Contact']['note_body'] = array('title' => $noteTitle . ': ' . ts('Body Only'), 'name' => 'note_body');
        $fields['Contact']['note_subject'] = array(
          'title' => $noteTitle . ': ' . ts('Subject Only'),
          'name' => 'note_subject',
        );
      }
      
      $fields['Contact'][ 'prox_distance' ] = array(
          'name' => 'prox_distance',
          'type' => 2,
          'title' => ts('Find contacts within') ,
          'description' => 'Cache Addressee.',
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'table_name' => '',
          'entity' => 'Contact',
          'bao' => '',
          'localizable' => 0,
          'html' => array(
            'type' => 'select',
          ),
          'hasProximityTypes' => 1
        );
    }

    if (($mappingType == 'Search Builder') || ($exportMode == CRM_Export_Form_Select::CONTRIBUTE_EXPORT)) {
        if (CRM_Core_Permission::access('CiviContribute')) {
            $fields['Contribution'] = CRM_Contribute_BAO_Contribution::getExportableFieldsWithPseudoConstants();
            unset($fields['Contribution']['contribution_contact_id']);
            $compArray['Contribution'] = ts('Contribution');
        }
    }

    if (($mappingType == 'Search Builder') || ($exportMode == CRM_Export_Form_Select::EVENT_EXPORT)) {
      if (CRM_Core_Permission::access('CiviEvent')) {
        $fields['Participant'] = CRM_Event_BAO_Participant::exportableFields();
        //get the component payment fields
        if ($exportMode == CRM_Export_Form_Select::EVENT_EXPORT) {
          $componentPaymentFields = array();
          foreach (CRM_Export_BAO_Export::componentPaymentFields() as $payField => $payTitle) {
            $componentPaymentFields[$payField] = array('title' => $payTitle);
          }
          $fields['Participant'] = array_merge($fields['Participant'], $componentPaymentFields);
        }

        $compArray['Participant'] = ts('Participant');
      }
    }

    if (($mappingType == 'Search Builder') || ($exportMode == CRM_Export_Form_Select::MEMBER_EXPORT)) {
      if (CRM_Core_Permission::access('CiviMember')) {
        $fields['Membership'] = CRM_Member_BAO_Membership::getMembershipFields( $exportMode );
        unset($fields['Membership']['membership_contact_id']);
        $compArray['Membership'] = ts('Membership');
      }
    }

    if (($mappingType == 'Search Builder') || ($exportMode == CRM_Export_Form_Select::PLEDGE_EXPORT)) {
      if (CRM_Core_Permission::access('CiviPledge')) {
        $fields['Pledge'] = CRM_Pledge_BAO_Pledge::exportableFields();
        unset($fields['Pledge']['pledge_contact_id']);
        $compArray['Pledge'] = ts('Pledge');
      }
    }

    if (($mappingType == 'Search Builder') || ($exportMode == CRM_Export_Form_Select::CASE_EXPORT)) {
      if (CRM_Core_Permission::access('CiviCase')) {
        $fields['Case'] = CRM_Case_BAO_Case::exportableFields();
        $compArray['Case'] = ts('Case');

        $fields['Activity'] = CRM_Activity_BAO_Activity::exportableFields('Case');
        $compArray['Activity'] = ts('Case Activity');

        unset($fields['Case']['case_contact_id']);
      }
    }
    if (($mappingType == 'Search Builder') || ($exportMode == CRM_Export_Form_Select::GRANT_EXPORT)) {
      if (CRM_Core_Permission::access('CiviGrant')) {
        $fields['Grant'] = CRM_Grant_BAO_Grant::exportableFields();
        unset($fields['Grant']['grant_contact_id']);
        if ($mappingType == 'Search Builder') {
          unset($fields['Grant']['grant_type_id']);
        }
        $compArray['Grant'] = ts('Grant');
      }
    }

    if (($mappingType == 'Search Builder') || ($exportMode == CRM_Export_Form_Select::ACTIVITY_EXPORT)) {
      $fields['Activity'] = CRM_Activity_BAO_Activity::exportableFields('Activity');
      $compArray['Activity'] = ts('Activity');
    }

    //Contact Sub Type For export
    $contactSubTypes = array();
    $subTypes = CRM_Contact_BAO_ContactType::subTypeInfo();
    
    

    foreach ($subTypes as $subType => $val) {
      //adding subtype specific relationships CRM-5256
      $csRelationships = array();

      if ($mappingType == 'Export') {
        $subTypeRelationshipTypes
          = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, NULL, NULL, $val['parent'],
            FALSE, 'label', TRUE, $subType);

        foreach ($subTypeRelationshipTypes as $key => $var) {
          if (!array_key_exists($key, $fields[$val['parent']])) {
            list($type) = explode('_', $key);

            $csRelationships[$key]['title'] = $var;
            $csRelationships[$key]['headerPattern'] = '/' . preg_quote($var, '/') . '/';
            $csRelationships[$key]['export'] = TRUE;
            $csRelationships[$key]['relationship_type_id'] = $type;
            $csRelationships[$key]['related'] = TRUE;
            $csRelationships[$key]['hasRelationType'] = 1;
          }
        }
      }

      $fields[$subType] = $fields[$val['parent']] + $csRelationships;
      
      //debug here

      //custom fields for sub type
      $subTypeFields = CRM_Core_BAO_CustomField::getFieldsForImport($subType);
      $fields[$subType] += $subTypeFields;

      if (!empty($subTypeFields) || !empty($csRelationships)) {
        $contactSubTypes[$subType] = $val['label'];
      }
    }
    
    

        
    foreach ($fields as $key => $value) {

      foreach ($value as $key1 => $value1) {
        //CRM-2676, replacing the conflict for same custom field name from different custom group.
        $customGroupName = self::getCustomGroupName($key1);

//        pre_print( $key .' ' . $key1 .' '.$value1['title'] ); 
        
        if ($customGroupName) {
          $relatedMapperFields[$key][$key1] = $mapperFields[$key][$key1] = $customGroupName . ': ' . $value1['title'];
        }
        else {
          $relatedMapperFields[$key][$key1] = $mapperFields[$key][$key1] = $value1['title'];
        }
        if (isset($value1['hasLocationType'])) {
          $hasLocationTypes[$key][$key1] = $value1['hasLocationType'];
        }
        
        if (isset($value1['hasProximityTypes'])) {
            $hasProximityTypes[$key][$key1] = $value1['hasProximityTypes'];
        }

        if (isset($value1['hasRelationType'])) {
          $hasRelationTypes[$key][$key1] = $value1['hasRelationType'];
          unset($relatedMapperFields[$key][$key1]);
        }
      }

      if (array_key_exists('related', $relatedMapperFields[$key])) {
        unset($relatedMapperFields[$key]['related']);
      }
    }

    //add proximity units
    $prox_units = array(
        'miles' => ts('Miles'),
        'kilos' => ts('Kilometers')
    );
    
    
    $locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');

    $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();

    // FIXME: dirty hack to make the default option show up first.  This
    // avoids a mozilla browser bug with defaults on dynamically constructed
    // selector widgets.
    if ($defaultLocationType) {
      $defaultLocation = $locationTypes[$defaultLocationType->id];
      unset($locationTypes[$defaultLocationType->id]);
      $locationTypes = array($defaultLocationType->id => $defaultLocation) + $locationTypes;
    }

    $locationTypes = array(' ' => ts('Primary')) + $locationTypes;

    // since we need a hierarchical list to display contact types & subtypes,
    // this is what we going to display in first selector
    $contactTypes = CRM_Contact_BAO_ContactType::getSelectElements(FALSE, FALSE);
    if ($mappingType == 'Search Builder') {
      $contactTypes = array('Contact' => ts('Contacts')) + $contactTypes;
    }

    $sel1 = array('' => ts('- select record type -')) + $contactTypes + $compArray;

    foreach ($sel1 as $key => $sel) {
      if ($key) {
        // sort everything BUT the contactType which is sorted separately by
        // an initial commit of CRM-13278 (check ksort above)
        if (!in_array($key, $contactType)) {
          asort($mapperFields[$key]);
        }
        $sel2[$key] = array('' => ts('- select field -')) + $mapperFields[$key];
      }
    }
    

    

    $sel3[''] = NULL;
    $sel5[''] = NULL;
    $phoneTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id');
    $imProviders = CRM_Core_PseudoConstant::get('CRM_Core_DAO_IM', 'provider_id');
    asort($phoneTypes);

    foreach ($sel1 as $k => $sel) {
      if ($k) {
        foreach ($locationTypes as $key => $value) {
          if (trim($key) != '') {
            $sel4[$k]['phone'][$key] = &$phoneTypes;
            $sel4[$k]['im'][$key] = &$imProviders;
          }
        }
      }
    }

//    pre_print( $hasProximityTypes );
//    pre_print( $sel1 );
    
    foreach ($sel1 as $k => $sel) {
      if ($k) {
        foreach ($mapperFields[$k] as $key => $value) {
          if (isset($hasLocationTypes[$k][$key])) {
            $sel3[$k][$key] = $locationTypes;
          }
          else {
            $sel3[$key] = NULL;
          }
          //proximity search
          if( isset($hasProximityTypes[$k][$key])){
              $sel3[$k][$key] = $prox_units;
          }
          
        }
      }
    }
    
        

    // Array for core fields and relationship custom data
    $relationshipTypes = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, NULL, NULL, NULL, TRUE);

    
    if ($mappingType == 'Export') {
      foreach ($sel1 as $k => $sel) {
        if ($k) {
          foreach ($mapperFields[$k] as $field => $dontCare) {
            if (isset($hasRelationTypes[$k][$field])) {
              list($id, $first, $second) = explode('_', $field);
              // FIX ME: For now let's not expose custom data related to relationship
              $relationshipCustomFields = array();
              //$relationshipCustomFields    = self::getRelationTypeCustomGroupData( $id );
              //asort($relationshipCustomFields);

              $relatedFields = array();
              $relationshipType = new CRM_Contact_BAO_RelationshipType();
              $relationshipType->id = $id;
              if ($relationshipType->find(TRUE)) {
                $direction = "contact_sub_type_$second";
                $target_type = 'contact_type_' . $second;
                if (isset($relationshipType->$direction)) {
                  $relatedFields = array_merge((array) $relatedMapperFields[$relationshipType->$direction], (array) $relationshipCustomFields);
                }
                elseif (isset($relationshipType->$target_type)) {
                  $relatedFields = array_merge((array) $relatedMapperFields[$relationshipType->$target_type], (array) $relationshipCustomFields);
                }
                //CRM-20672 If contact target type not set e.g. "All Contacts" relationship - present user with all field options and let them determine what they expect to work
                else {
                  $types = CRM_Contact_BAO_ContactType::basicTypes(FALSE);
                  foreach ($types as $contactType => $label) {
                    $relatedFields = array_merge($relatedFields, (array) $relatedMapperFields[$label]);
                  }
                  $relatedFields = array_merge($relatedFields, (array) $relationshipCustomFields);
                }
              }
              $relationshipType->free();
              asort($relatedFields);
              $sel5[$k][$field] = $relatedFields;
            }
          }
        }
      }

      //Location Type for relationship fields
      foreach ($sel5 as $k => $v) {
        if ($v) {
          foreach ($v as $rel => $fields) {
            foreach ($fields as $field => $fieldLabel) {
              if (isset($hasLocationTypes[$k][$field])) {
                $sel6[$k][$rel][$field] = $locationTypes;
              }
            }
          }
        }
      }

      //PhoneTypes for  relationship fields
      $sel7[''] = NULL;
      foreach ($sel6 as $k => $rel) {
        if ($k) {
          foreach ($rel as $phonekey => $phonevalue) {
            foreach ($locationTypes as $locType => $loc) {
              if (trim($locType) != '') {
                $sel7[$k][$phonekey]['phone'][$locType] = &$phoneTypes;
                $sel7[$k][$phonekey]['im'][$locType] = &$imProviders;
              }
            }
          }
        }
      }
    }
    
   

    //special fields that have location, hack for primary location
    $specialFields = array(
      'street_address',
      'supplemental_address_1',
      'supplemental_address_2',
      'supplemental_address_3',
      'city',
      'postal_code',
      'postal_code_suffix',
      'geo_code_1',
      'geo_code_2',
      'state_province',
      'country',
      'phone',
      'email',
      'im',
    );

    if (isset($mappingId)) {
      list($mappingName, $mappingContactType, $mappingLocation, $mappingPhoneType, $mappingImProvider,
        $mappingRelation, $mappingOperator, $mappingValue
        ) = CRM_Core_BAO_Mapping::getMappingFields($mappingId);

      $blkCnt = count($mappingName);
      if ($blkCnt >= $blockCount) {
        $blockCount = $blkCnt + 1;
      }
      for ($x = 1; $x < $blockCount; $x++) {
        if (isset($mappingName[$x])) {
          $colCnt = count($mappingName[$x]);
          if ($colCnt >= $columnCount[$x]) {
            $columnCount[$x] = $colCnt;
          }
        }
      }
    }

    $form->_blockCount = $blockCount;
    $form->_columnCount = $columnCount;

    $form->set('blockCount', $form->_blockCount);
    $form->set('columnCount', $form->_columnCount);

    $defaults = $noneArray = $nullArray = array();

    for ($x = 1; $x < $blockCount; $x++) {

      for ($i = 0; $i < $columnCount[$x]; $i++) {

        $sel = &$form->addElement('hierselect', "mapper[$x][$i]", ts('Mapper for Field %1', array(1 => $i)), NULL);
        $jsSet = FALSE;

//        pre_print( $mappingLocation );
        
        if (isset($mappingId)) {
          $locationId = isset($mappingLocation[$x][$i]) ? $mappingLocation[$x][$i] : 0;
          
          
          if (isset($mappingName[$x][$i])) {
            if (is_array($mapperFields[$mappingContactType[$x][$i]])) {

              if (isset($mappingRelation[$x][$i])) {
                $relLocationId = isset($mappingLocation[$x][$i]) ? $mappingLocation[$x][$i] : 0;
                if (!$relLocationId && in_array($mappingName[$x][$i], $specialFields)) {
                  $relLocationId = " ";
                }

                $relPhoneType = isset($mappingPhoneType[$x][$i]) ? $mappingPhoneType[$x][$i] : NULL;

                $defaults["mapper[$x][$i]"] = array(
                  $mappingContactType[$x][$i],
                  $mappingRelation[$x][$i],
                  $locationId,
                  $phoneType,
                  $mappingName[$x][$i],
                  $relLocationId,
                  $relPhoneType,
                );

                if (!$locationId) {
                  $noneArray[] = array($x, $i, 2);
                }
                if (!$phoneType && !$imProvider) {
                  $noneArray[] = array($x, $i, 3);
                }
                if (!$mappingName[$x][$i]) {
                  $noneArray[] = array($x, $i, 4);
                }
                if (!$relLocationId) {
                  $noneArray[] = array($x, $i, 5);
                }
                if (!$relPhoneType) {
                  $noneArray[] = array($x, $i, 6);
                }
                $noneArray[] = array($x, $i, 2);
              }
              else {
                $phoneType = isset($mappingPhoneType[$x][$i]) ? $mappingPhoneType[$x][$i] : NULL;
                $imProvider = isset($mappingImProvider[$x][$i]) ? $mappingImProvider[$x][$i] : NULL;
                if (!$locationId && in_array($mappingName[$x][$i], $specialFields)) {
                  $locationId = " ";
                }

                $defaults["mapper[$x][$i]"] = array(
                  $mappingContactType[$x][$i],
                  $mappingName[$x][$i],
                  $locationId,
                  $phoneType,
                );
                if (!$mappingName[$x][$i]) {
                  $noneArray[] = array($x, $i, 1);
                }
                if (!$locationId) {
                  $noneArray[] = array($x, $i, 2);
                }
                if (!$phoneType && !$imProvider) {
                  $noneArray[] = array($x, $i, 3);
                }

                $noneArray[] = array($x, $i, 4);
                $noneArray[] = array($x, $i, 5);
                $noneArray[] = array($x, $i, 6);
              }

              $jsSet = TRUE;

             
              if (CRM_Utils_Array::value($i, CRM_Utils_Array::value($x, $mappingOperator))) {
                $defaults["operator[$x][$i]"] = CRM_Utils_Array::value($i, $mappingOperator[$x]);
              }

              if (CRM_Utils_Array::value($i, CRM_Utils_Array::value($x, $mappingValue))) {
                $defaults["value[$x][$i]"] = CRM_Utils_Array::value($i, $mappingValue[$x]);
              }
            }
          }
        }
        
         
        
        //Fix for Search Builder
        if ($mappingType == 'Export') {
          $j = 7;
        }
        else {
          $j = 4;
        }

        $formValues = $form->exportValues();
        if (!$jsSet) {
          if (empty($formValues)) {
            // Incremented length for third select box(relationship type)
            for ($k = 1; $k < $j; $k++) {
              $noneArray[] = array($x, $i, $k);
            }
          }
          else {
            if (!empty($formValues['mapper'][$x])) {
              foreach ($formValues['mapper'][$x] as $value) {
                for ($k = 1; $k < $j; $k++) {
                  if (!isset($formValues['mapper'][$x][$i][$k]) ||
                    (!$formValues['mapper'][$x][$i][$k])
                  ) {
                    $noneArray[] = array($x, $i, $k);
                  }
                  else {
                    $nullArray[] = array($x, $i, $k);
                  }
                }
              }
            }
            else {
              for ($k = 1; $k < $j; $k++) {
                $noneArray[] = array($x, $i, $k);
              }
            }
          }
        }
        
       
        
        //Fix for Search Builder
        if ($mappingType == 'Export') {
          if (!isset($mappingId) || $i >= count(reset($mappingName))) {
            if (isset($formValues['mapper']) &&
              isset($formValues['mapper'][$x][$i][1]) &&
              array_key_exists($formValues['mapper'][$x][$i][1], $relationshipTypes)
            ) {
              $sel->setOptions(array($sel1, $sel2, $sel5, $sel6, $sel7, $sel3, $sel4));
            }
            else {
              $sel->setOptions(array($sel1, $sel2, $sel3, $sel4, $sel5, $sel6, $sel7));
            }
          }
          else {
            $sel->setOptions(array($sel1, $sel2, $sel3, $sel4, $sel5, $sel6, $sel7));
          }
        }
        else {
//            pre_print( $sel2 );
            
          $sel->setOptions(array($sel1, $sel2, $sel3, $sel4));
        }

        if ($mappingType == 'Search Builder') {
            
            //CRM -2292, restricted array set
            $operatorArray = array('' => ts('-operator-')) + CRM_Core_SelectValues::getSearchBuilderOperators();
          $form->add('select', "operator[$x][$i]", '', $operatorArray);
          $form->add('text', "value[$x][$i]", '');
        }
      }
      //end of columnCnt for
      if ($mappingType == 'Search Builder') {
        $title = ts('Another search field');
      }
      else {
        $title = ts('Select more fields');
      }

      $form->addElement('submit', "addMore[$x]", $title, array('class' => 'submit-link'));
    }
    //end of block for
    

//    pre_print( $fields );
    
    
    

    $js = "<script type='text/javascript'>\n";
    $formName = "document." . (($mappingType == 'Export') ? 'Map' : 'SearchBuilderExt');
    if (!empty($nullArray)) {
      $js .= "var nullArray = [";
      $elements = array();
      $seen = array();
      foreach ($nullArray as $element) {
        $key = "{$element[0]}, {$element[1]}, {$element[2]}";
        if (!isset($seen[$key])) {
          $elements[] = "[$key]";
          $seen[$key] = 1;
        }
      }
      $js .= implode(', ', $elements);
      $js .= "]";
      $js .= "
                for (var i=0;i<nullArray.length;i++) {
                    if ( {$formName}['mapper['+nullArray[i][0]+']['+nullArray[i][1]+']['+nullArray[i][2]+']'] ) {
                        {$formName}['mapper['+nullArray[i][0]+']['+nullArray[i][1]+']['+nullArray[i][2]+']'].style.display = '';
                    }
                }
";
    }
    if (!empty($noneArray)) {
      $js .= "var noneArray = [";
      $elements = array();
      $seen = array();
      foreach ($noneArray as $element) {
        $key = "{$element[0]}, {$element[1]}, {$element[2]}";
        if (!isset($seen[$key])) {
          $elements[] = "[$key]";
          $seen[$key] = 1;
        }
      }
      $js .= implode(', ', $elements);
      $js .= "]";
      $js .= "
                for (var i=0;i<noneArray.length;i++) {
                    if ( {$formName}['mapper['+noneArray[i][0]+']['+noneArray[i][1]+']['+noneArray[i][2]+']'] ) {
  {$formName}['mapper['+noneArray[i][0]+']['+noneArray[i][1]+']['+noneArray[i][2]+']'].style.display = 'none';
                    }
                }
";
    }
    $js .= "</script>\n";

    $form->assign('initHideBoxes', $js);
    $form->assign('columnCount', $columnCount);
    $form->assign('blockCount', $blockCount);
    $form->setDefaults($defaults);

    $form->setDefaultAction('refresh');
  }

}
