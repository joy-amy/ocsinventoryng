<?php
/*
 -------------------------------------------------------------------------
 ocsinventoryng plugin for GLPI
 Copyright (C) 2015-2018 by the ocsinventoryng Development Team.

 https://github.com/pluginsGLPI/ocsinventoryng
 -------------------------------------------------------------------------

 LICENSE

 This file is part of ocsinventoryng.

 ocsinventoryng is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 ocsinventoryng is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with ocsinventoryng. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginOcsinventoryngOcslink
 */
class PluginOcsinventoryngOcslink extends CommonDBTM {
   const HISTORY_OCS_IMPORT     = 8;
   const HISTORY_OCS_DELETE     = 9;
   const HISTORY_OCS_IDCHANGED  = 10;
   const HISTORY_OCS_LINK       = 11;
   const HISTORY_OCS_TAGCHANGED = 12;

   static $rightname = "plugin_ocsinventoryng";

   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {
      return _n('OCSNG link', 'OCSNG links', $nb, 'ocsinventoryng');
   }

   /**
    * Show simple inventory information of an computer child item
    *
    * @param $item                   CommonDBTM object
    *
    * @return nothing
    **/
   static function showSimpleForChild(CommonDBTM $item) {

      $dbu = new DbUtils();
      if ($item->isDynamic()
          && $item->isField('computers_id')
          && $dbu->countElementsInTable('glpi_plugin_ocsinventoryng_ocslinks',["computers_id" => $item->getField('computers_id')]) > 0) {
         echo __('OCS Inventory NG');
      }
   }

   /**
    * Show simple inventory information of an item
    *
    * @param $item                   CommonDBTM object
    *
    * @return nothing
    **/
   static function showSimpleForItem(CommonDBTM $item) {
      global $DB, $CFG_GLPI;

      $target = Toolbox::getItemTypeFormURL(__CLASS__);

      if (in_array($item->getType(), ['Computer'])) {

         $items_id = $item->getField('id');

         if (!empty($items_id)
             && $item->fields["is_dynamic"]
             && Session::haveRight("plugin_ocsinventoryng_link", READ)) {
            $dbu = new DbUtils();
            $query = "SELECT *
                      FROM `glpi_plugin_ocsinventoryng_ocslinks`
                      WHERE `computers_id` = $items_id " .
                     $dbu->getEntitiesRestrictRequest("AND", "glpi_plugin_ocsinventoryng_ocslinks");

            $result = $DB->query($query);
            if ($DB->numrows($result) > 0) {
               $data = $DB->fetch_assoc($result);

               if (count($data)) {

                  // Manage locks pictures
                  self::showLockIcon($item->getField('id'), $data);

                  $ocs_config = PluginOcsinventoryngOcsServer::getConfig($data['plugin_ocsinventoryng_ocsservers_id']);

                  echo "<tr class='tab_bg_1'><th colspan='4'>" . __('OCS Inventory NG Import informations', 'ocsinventoryng') . "</th>";

                  echo "<tr class='tab_bg_1'><td>" . __('Last OCSNG inventory date', 'ocsinventoryng');
                  echo "</td><td>" . Html::convDateTime($data["last_ocs_update"]) . "</td>";
                  echo "<td>" . __('Inventory agent', 'ocsinventoryng');
                  echo "</td><td>" . $data["ocs_agent_version"] . "</td></tr>";

                  echo "<tr class='tab_bg_1'><td>" . __('GLPI import date', 'ocsinventoryng');
                  echo "</td><td>" . Html::convDateTime($data["last_update"]) . "</td>";
                  echo "<td>" . __('Server');
                  echo "</td><td>";
                  if (Session::haveRight("plugin_ocsinventoryng", READ)) {
                     echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsserver.form.php?id="
                          . $ocs_config['id'] . "'>" . $ocs_config['name'] . "</a>";
                  } else {
                     echo $ocs_config['name'];
                  }
                  echo "</td></tr>";
                  echo "<tr class='tab_bg_1'>";
                  if (isset($data["last_ocs_conn"])) {
                     echo "<td>" . __('Last OCSNG connection date', 'ocsinventoryng');
                     echo "</td><td>" . Html::convDateTime($data["last_ocs_conn"]) . "</td>";
                  } else {
                     echo "<td colspan='2'></td>";
                  }

                  if (isset($data["ip_src"])) {
                     echo "<td>" . __('IP Source', 'ocsinventoryng');
                     echo "</td><td>" . $data["ip_src"] . "</td>";
                  } else {
                     echo "<td colspan='2'></td>";
                  }

                  echo "<tr class='tab_bg_1'>";
                  echo "<td>" . __('OCSNG TAG', 'ocsinventoryng') .
                       "</td>";
                  echo "<td>";
                  echo $data["tag"];
                  echo "</td>";

                  if (Session::haveRight("plugin_ocsinventoryng_link", READ)
                      && Session::haveRight("plugin_ocsinventoryng_sync", UPDATE)) {
                     echo "<td>" . __('Automatic update OCSNG', 'ocsinventoryng') .
                          "</td>";
                     echo "<td>";
                     echo Dropdown::getYesNo($data["use_auto_update"]);
                     echo "</td>";
                  } else {
                     echo "<td colspan='2'></td>";
                  }
                  echo "</tr>";

                  if ($data['uptime'] != null) {
                     echo "<tr class='tab_bg_1'>";
                     echo "<td class='left'>";
                     echo __('Uptime', 'ocsinventoryng');
                     echo "</td>";
                     echo "<td class='left'>";
                     echo $data['uptime'];
                     echo "</td>";
                     echo "<td colspan='2'></td>";
                     echo "</tr>";
                  }

                  PluginOcsinventoryngTeamviewer::showForSimpleForItem($item);

                  echo "<tr class='tab_bg_1'>";
                  //If have write right on OCS and ocsreports url is not empty in OCS config
                  if (Session::haveRight("plugin_ocsinventoryng", UPDATE)
                      && ($ocs_config["ocs_url"] != '')) {
                     echo "<td class='center'>";
                     echo PluginOcsinventoryngOcsServer::getComputerLinkToOcsConsole($ocs_config['id'],
                                                                                     $data["ocsid"],
                                                                                     __('OCS NG Interface', 'ocsinventoryng'));
                     echo "</td>";
                  } else {
                     echo "<td></td>";
                  }

                  if (Session::haveRight("plugin_ocsinventoryng_sync", UPDATE)) {
                     echo "<td class='center' colspan='2'>";
                     Html::showSimpleForm($target, 'launch_ocs_resynch',
                                          _sx('button', 'Launch synchronization', 'ocsinventoryng'),
                                          ['id'         => $items_id,
                                           'resynch_id' => $data["id"]]);
                     echo "</td>";

                  } else {
                     echo "<td></td>";
                  }

                  if (Session::haveRight("plugin_ocsinventoryng_sync", UPDATE)) {
                     echo "<td class='center' colspan='2'>";
                     Html::showSimpleForm($target, 'force_ocs_resynch',
                                          _sx('button', 'Force full import', 'ocsinventoryng'),
                                          ['id'         => $items_id,
                                           'resynch_id' => $data["id"]]);
                     echo "</td>";

                  } else {
                     echo "<td></td>";
                  }
                  echo "</tr>";
               }
            }

            //SNMP Link
            $query = "SELECT *
                      FROM `glpi_plugin_ocsinventoryng_snmpocslinks`
                      WHERE `items_id` = " . $items_id . " AND  `itemtype` = '" . $item->getType() . "'";

            $result = $DB->query($query);
            if ($DB->numrows($result) > 0) {
               $data = $DB->fetch_assoc($result);

               if (count($data)) {
                  $target = Toolbox::getItemTypeFormURL("PluginOcsinventoryngSnmpOcslink");
                  echo "<tr class='tab_bg_1'><th colspan='4'>" . __('OCS Inventory NG SNMP Import informations', 'ocsinventoryng') . "</th>";
                  $linked = __('Imported object', 'ocsinventoryng');
                  if ($data["linked"]) {
                     $linked = __('Linked object', 'ocsinventoryng');
                  }
                  echo "<tr class='tab_bg_1'><td>" . __('Import date in GLPI', 'ocsinventoryng');
                  echo "</td><td>" . Html::convDateTime($data["last_update"]) . " (" . $linked . ")</td>";
                  if (Session::haveRight("plugin_ocsinventoryng_sync", UPDATE)) {
                     echo "<td class='center' colspan='2'>";
                     Html::showSimpleForm($target, 'force_ocssnmp_resynch',
                                          _sx('button', 'Force SNMP synchronization', 'ocsinventoryng'),
                                          ['items_id'                            => $items_id,
                                           'itemtype'                            => $item->getType(),
                                           'id'                                  => $data["id"],
                                           'plugin_ocsinventoryng_ocsservers_id' => $data["plugin_ocsinventoryng_ocsservers_id"]]);
                     echo "</td>";

                  }
                  echo "</tr>";

                  $linked_ids [] = $data['ocs_id'];
                  $ocsClient     = PluginOcsinventoryngOcsServer::getDBocs($data['plugin_ocsinventoryng_ocsservers_id']);
                  $ocsResult     = $ocsClient->getSnmp([
                                                          'MAX_RECORDS' => 1,
                                                          'FILTER'      => [
                                                             'IDS' => $linked_ids,
                                                          ]
                                                       ]);
                  if (isset($ocsResult['SNMP'])) {
                     if (count($ocsResult['SNMP']) > 0) {
                        foreach ($ocsResult['SNMP'] as $snmp) {
                           $LASTDATE = $snmp['META']['LASTDATE'];
                           $UPTIME   = $snmp['META']['UPTIME'];

                           echo "<tr class='tab_bg_1'><td>" . __('Last OCSNG SNMP inventory date', 'ocsinventoryng');
                           echo "</td><td>" . Html::convDateTime($LASTDATE) . "</td>";

                           echo "<td>" . __('Uptime', 'ocsinventoryng');
                           echo "</td><td>" . $UPTIME . "</td></tr>";
                        }
                     }
                  }
               }
            }
            if (in_array($item->getType(), PluginOcsinventoryngIpdiscoverOcslink::$hardwareItemTypes)) {
               $items_id = $item->getField('id');

               if (!empty($items_id)
                   //&& $item->fields["is_dynamic"]
                   && Session::haveRight("plugin_ocsinventoryng_link", READ)) {
                  $query = "SELECT *
                            FROM `glpi_plugin_ocsinventoryng_ipdiscoverocslinks`
                            WHERE `items_id` = " . $items_id . " AND  `itemtype` = '" . $item->getType() . "'";

                  $result = $DB->query($query);
                  if ($DB->numrows($result) > 0) {
                     $data = $DB->fetch_assoc($result);

                     if (count($data)) {
                        echo "<tr class='tab_bg_1'><th colspan='4'>" . __('OCS Inventory NG IPDiscover Import informations', 'ocsinventoryng') . "</th>";

                        echo "<tr class='tab_bg_1'><td>" . __('Import date in GLPI', 'ocsinventoryng');
                        echo "</td><td>" . Html::convDateTime($data["last_update"]) . "</td><td colspan='2'>&nbsp;</td></tr>";
                     }
                  }
               }
            }
         }
      }
   }


   /**
    * Read ocslink for a given computer
    *
    * @param $ID   Integer   ID of the computer
    *
    * @return boolean
    **/
   function getFromDBforComputer($ID) {

      $field = "`" . $this->getTable() . "`.`computers_id`";
      if ($this->getFromDBByCrit([$field => $ID])) {
         return true;
      }
      return false;
   }


   /**
    * Show OcsLink of an item
    *
    * @param $item                   CommonDBTM object
    *
    * @return nothing
    * @internal param int|string $withtemplate integer  withtemplate param (default '')
    */
   static function showForItem(CommonDBTM $item) {
      global $DB;

      $target = Toolbox::getItemTypeFormURL(__CLASS__);

      if (in_array($item->getType(), ['Computer'])) {

         $items_id = $item->getField('id');

         if (!empty($items_id)
             && $item->fields["is_dynamic"]
             && Session::haveRight("plugin_ocsinventoryng_link", READ)) {
            $dbu = new DbUtils();
            $query = "SELECT *
                      FROM `glpi_plugin_ocsinventoryng_ocslinks`
                      WHERE `computers_id` = $items_id " .
                     $dbu->getEntitiesRestrictRequest("AND", "glpi_plugin_ocsinventoryng_ocslinks");

            $result = $DB->query($query);
            if ($DB->numrows($result) > 0) {
               $data = $DB->fetch_assoc($result);
               $data = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($data));

               if (count($data)) {

                  echo "<div class='center'>";
                  echo "<form method='post' action=\"$target\">";
                  echo "<input type='hidden' name='id' value='$items_id'>";
                  echo "<table class='tab_cadre_fixe'>";
                  echo "<tr><th colspan = '4'>" . __('OCSNG link', 'ocsinventoryng') . "</th>";

                  echo "<tr class='tab_bg_1'>";
                  echo "<td class='left'>";
                  echo __('OCSNG DEVICE ID', 'ocsinventoryng');
                  echo "</td>";
                  echo "<td class='left'>";
                  echo $data['ocs_deviceid'];
                  echo "</td>";

                  echo "<td class='left'>";
                  echo __('OCSNG TAG', 'ocsinventoryng');
                  echo "</td>";
                  echo "<td class='left'>";
                  echo $data['tag'];
                  echo "</td>";

                  echo "<tr class='tab_bg_1'>";
                  echo "<td class='left'>" . __('Automatic update OCSNG', 'ocsinventoryng') .
                       "</td>";
                  echo "<td class='left'>";
                  Dropdown::showYesNo("use_auto_update", $data["use_auto_update"]);
                  echo "</td>";

                  echo "<td class='left'>";
                  echo __('Uptime', 'ocsinventoryng');
                  echo "</td>";
                  echo "<td class='left'>";
                  echo $data['uptime'];
                  echo "</td>";

                  echo "</tr>";

                  if (Session::haveRight("plugin_ocsinventoryng_link", UPDATE)) {
                     echo "<tr class='tab_bg_1'>";
                     echo "<td class='center'>";
                     echo "<input type='hidden' name='link_id' value='" . $data["id"] . "'>";
                     echo "<input class=submit type='submit' name='update' value=\"" .
                          _sx('button', 'Save') . "\">";
                     echo "</td>";

                     echo "<td class='center'>";
                     echo "<input type='hidden' name='resynch_id' value='" . $data["id"] . "'>";
                     echo "<input class=submit type='submit' name='launch_ocs_resynch' value=\"" .
                          _sx('button', 'Launch synchronization', 'ocsinventoryng') . "\">";
                     echo "</td>";

                     echo "<td class='center'>";
                     echo "<input type='hidden' name='resynch_id' value='" . $data["id"] . "'>";
                     echo "<input class=submit type='submit' name='force_ocs_resynch' value=\"" .
                          _sx('button', 'Force full import', 'ocsinventoryng') . "\">";
                     echo "</td>";

                     echo "<td class='center'>";
                     echo "<input type='hidden' name='items_id' value='" . $items_id . "'>";
                     echo "<input class=submit type='submit' name='delete_link' value=\"" .
                          _sx('button', 'Delete link', 'ocsinventoryng') . "\">";
                     echo "</td>";

                     echo "</tr>";
                  }

                  echo "</table>\n";
                  Html::closeForm();
                  echo "</div>";
                  if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                     $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($data["plugin_ocsinventoryng_ocsservers_id"]);

                     $options         = [
                        'DISPLAY'  => [
                           'CHECKSUM' => PluginOcsinventoryngOcsClient::CHECKSUM_HARDWARE,
                           'PLUGINS'  => PluginOcsinventoryngOcsClient::PLUGINS_ALL
                        ],
                        'COMPLETE' => 1
                     ];
                     $checksum_client = 0;
                     $computer        = $ocsClient->getComputer($data["ocsid"], $options);
                     echo "<br><table class='tab_cadre_fixe'>";
                     echo "<tr>";
                     echo "<th colspan='2'>" . __('DEBUG') . " " . __('OCSNG', "ocsinventoryng") . "</th>";
                     echo "</tr>";
                     if (is_array($computer) && count($computer) > 0) {
                        foreach ($computer as $key => $val) {
                           echo "<tr class='tab_bg_1'>";
                           echo "<td>";
                           print_r($key);
                           echo "</td>";
                           echo "<td>";
                           foreach ($val as $name => $value) {
                              if (is_array($value)) {
                                 echo "<table class='tab_cadre' width='100%' border='0'>";
                                 foreach ($value as $k => $v) {
                                    echo "<tr class='tab_bg_1'>";
                                    echo "<td>";
                                    printf(__('%1$s: %2$s'), $k,
                                           $v);
                                    echo "</td>";
                                    echo "</tr>";
                                 }
                                 echo "</table>";
                              } else {
                                 printf(__('%1$s: %2$s'), $name,
                                        $value);
                              }
                              if ($name == "CHECKSUM") {
                                 $checksum_client = intval($value);
                              }
                              echo "</br>";
                           }
                           echo "</td>";
                           echo "</tr>";
                        }
                     } else {
                        echo "<tr class='tab_bg_1'>";
                        echo "<td colspan='2' class='red'>";
                        echo __('No computer found into OCSNG Database', 'ocsinventoryng');
                        echo "</td>";
                        echo "</tr>";
                     }
                     $server = new PluginOcsinventoryngOcsServer();
                     if ($server->getFromDB($data["plugin_ocsinventoryng_ocsservers_id"])
                         && $checksum_client > 0) {
                        echo "<tr class='tab_bg_1'>";
                        echo "<td>";
                        echo __('Checksum test', 'ocsinventoryng');
                        echo "</td>";
                        echo "<td>";
                        $format = '(%1$2d = %1$04b) = (%2$2d = %2$04b)'
                                  . ' %3$s (%4$2d = %4$04b)' . "\n";

                        $checksum_server = intval($server->fields["checksum"]);
                        $result          = $checksum_server & $checksum_client;
                        printf($format, $result, $checksum_server, '&', $checksum_client);
                        echo "</td>";
                        echo "</tr>";
                     }
                     echo "</table>";

                  }
               } else {

                  echo "<div class='center'>";
                  echo "<table class='tab_cadre_fixe'>";
                  echo "<tr><th colspan = '2'>" . __('OCSNG link', 'ocsinventoryng') . "</th>";
                  echo "<tr class='tab_bg_1'>";
                  echo "<td colspan='2' >";
                  echo __('No computer found into OCSNG Database', 'ocsinventoryng');
                  echo "</td>";
                  echo "</tr>";
                  echo "</table>";
                  echo "</div>";
               }
            } else {

               echo "<div class='center'>";
               echo "<table class='tab_cadre_fixe'>";
               echo "<tr><th colspan = '2'>" . __('OCSNG link', 'ocsinventoryng') . "</th>";
               echo "<tr class='tab_bg_1'>";
               echo "<td colspan='2' '>";
               echo __('No computer found into OCSNG Database', 'ocsinventoryng');
               echo "</td>";
               echo "</tr>";
               echo "</table>";
               echo "</div>";
            }
         } else {

            echo "<div class='center'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th colspan = '2'>" . __('OCSNG link', 'ocsinventoryng') . "</th>";
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='2' '>";
            echo __('No computer found into OCSNG Database', 'ocsinventoryng');
            echo "</td>";
            echo "</tr>";
            echo "</table>";
            echo "</div>";
         }
      }
   }


   /**
    * Update lockable fields of an item
    *
    * @param $item                     CommonDBTM object
    *
    * @return nothing
    * @internal param int|string $withtemplate integer  withtemplate param (default '')
    */
   static function updateComputer(CommonDBTM $item) {
      global $DB;
      // Manage changes for OCS if more than 1 element (date_mod)
      // Need dohistory==1 if dohistory==2 no locking fields

      $ocslink = new self();
      if ($item->fields["is_dynamic"]
          && $ocslink->getFromDBforComputer($item->getID())
          && (count($item->updates) > 1)
          && (!isset($item->input["_nolock"]))) {

         $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($ocslink->fields["plugin_ocsinventoryng_ocsservers_id"]);
         if ($cfg_ocs["use_locks"]) {
            foreach ($item->updates as $k => $field) {
               if (!array_key_exists($field, self::getLockableFields($ocslink->fields["plugin_ocsinventoryng_ocsservers_id"], $ocslink->fields["ocsid"]))) {
                  unset($item->updates[$k]);
               }
            }
            PluginOcsinventoryngOcslink::mergeOcsArray($item->fields["id"], $item->updates);
         }
      }
      if (isset($item->input["_auto_update_ocs"])) {
         $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                   SET `use_auto_update` = " . $item->input["_auto_update_ocs"] . "
                   WHERE `computers_id` = " . $item->input["id"];
         $DB->query($query);
      }
   }

   /**
    * Update lockable fields of an item
    *
    * @param $item                     CommonDBTM object
    *
    * @return nothing
    * @internal param int|string $withtemplate integer  withtemplate param (default '')
    */
   static function updateComputerOS($item) {

      $ocslink = new self();
      if ($item->fields["is_dynamic"]
          && $item->fields["itemtype"] == 'Computer'
          && $ocslink->getFromDBforComputer($item->fields["items_id"])
          && (count($item->updates) > 1)
          && (!isset($item->input["_nolock"]))) {

         $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($ocslink->fields["plugin_ocsinventoryng_ocsservers_id"]);
         if ($cfg_ocs["use_locks"]) {

            PluginOcsinventoryngOcslink::mergeOcsArray($item->fields["items_id"], $item->updates);
         }
      }
   }


   /**
    * Update linked items of an item
    *
    * @param $item                     CommonDBTM object
    *
    * @internal param int|string $withtemplate integer  withtemplate param (default '')
    * @return bool
    */
   static function addComputer_Item(CommonDBTM $item) {
      global $DB;

      $link = new $item->input['itemtype'];
      if (!$link->getFromDB($item->input['items_id'])) {
         return false;
      }
      if (!$link->getField('is_global')) {
         // Handle case where already used, should never happen (except from OCS sync)
         $query  = "SELECT `id`, `computers_id`
                   FROM `glpi_computers_items`
                   WHERE `glpi_computers_items`.`items_id` = " . $item->input['items_id'] . "
                         AND `glpi_computers_items`.`itemtype` = '" . $item->input['itemtype'] . "'";
         $result = $DB->query($query);

         while ($data = $DB->fetch_assoc($result)) {
            $temp = clone $item;
            $temp->delete($data, true);
         }
      }
   }


   /**
    * if Computer deleted
    *
    * @param $comp   Computer object
    **/
   static function purgeComputer(Computer $comp) {
      $link = new self();
      $link->deleteByCriteria(['computers_id' => $comp->getField("id")]);

      $reg = new PluginOcsinventoryngRegistryKey();
      $reg->deleteByCriteria(['computers_id' => $comp->getField("id")]);
   }


   /**
    * if Computer_Item deleted
    *
    * @param $comp   Computer_Item object
    **/
   static function purgeComputer_Item(Computer_Item $comp) {
      Global $DB;
      $dbu = new DbUtils();
      if ($device = $dbu->getItemForItemtype($comp->fields['itemtype'])) {
         if ($device->getFromDB($comp->fields['items_id'])) {

            if (isset($comp->input['_ocsservers_id'])) {
               $ocsservers_id = $comp->input['_ocsservers_id'];
            } else {
               $ocsservers_id = PluginOcsinventoryngOcsServer::getServerByComputerID($comp->fields['computers_id']);
            }

            if ($ocsservers_id > 0) {
               //Get OCS configuration
               $ocs_config = PluginOcsinventoryngOcsServer::getConfig($ocsservers_id);

               //Get the management mode for this device
               $mode     = PluginOcsinventoryngOcsServer::getDevicesManagementMode($ocs_config,
                                                                                   $comp->fields['itemtype']);
               $decoConf = $ocs_config["deconnection_behavior"];

               //Change status if :
               // 1 : the management mode IS NOT global
               // 2 : a deconnection's status have been defined
               // 3 : unique with serial

               if (($mode >= 2)
                   && (strlen($decoConf) > 0)
               ) {

                  //Delete periph from glpi
                  // if ($decoConf == "delete") {
                  // $tmp["id"] = $comp->fields['items_id'];
                  // $device->delete(array('id'  => $tmp['id']), 1);

                  // Put periph in dustbin
                  // } else if ($decoConf == "trash") {
                  // $tmp["id"] = $comp->fields['items_id'];
                  // $device->delete(array('id'  => $tmp['id']), 0);
                  // }

                  if ($decoConf == "delete") {
                     $tmp["id"] = $comp->getID();
                     $query     = "DELETE
                         FROM `glpi_computers_items`
                         WHERE `id`= " . $tmp['id'];
                     $DB->query($query);
                     //Put periph in dustbin
                  } else if ($decoConf == "trash") {
                     $tmp["id"] = $comp->getID();
                     $query     = "UPDATE
                         `glpi_computers_items`
                   SET `is_deleted` = 1
                         WHERE `id`= " . $tmp['id'];
                     $DB->query($query);
                  }
               }
            } // $ocsservers_id>0
         }
      }
   }

   /**
    * @see inc/CommonGLPI::getTabNameForItem()
    *
    * @param $item               CommonGLPI object
    * @param $withtemplate (default 0)
    *
    * @return array|string
    * @return array|string
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (in_array($item->getType(), PluginOcsinventoryngOcsServer::getTypes(true))
          && Session::haveRight("plugin_ocsinventoryng_link", READ)) {

         switch ($item->getType()) {
            case 'Computer' :
               if (!$withtemplate) {
                  return ['1' => _n('OCSNG link', 'OCSNG links', 1, 'ocsinventoryng')];
               }
         }
      }
      return '';
   }


   /**
    * @param $item            CommonGLPI object
    * @param $tabnum (default 1)
    * @param $withtemplate (default 0)
    *
    * @return bool|true
    * @return bool|true
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if (in_array($item->getType(), PluginOcsinventoryngOcsServer::getTypes(true))) {
         switch ($item->getType()) {
            case 'Computer' :
               self::showForItem($item);
               break;
         }
      }
      return true;
   }


   /**
    * Add an history entry to a computer
    *
    * @param $computers_id Integer, ID of the computer
    * @param $changes      Array, see Log::history
    * @param $action       Integer in PluginOcsinventoryngOcslink::HISTORY_OCS_*
    *
    * @return Integer id of the inserted entry
    **/
   static function history($computers_id, $changes, $action) {

      return Log::history($computers_id, 'Computer', $changes, __CLASS__,
                          Log::HISTORY_PLUGIN + $action);
   }

   /**
    * Get an history entry message
    *
    * @param $data Array from glpi_logs table
    *
    * @return string
    **/
   static function getHistoryEntry($data) {

      if (Session::haveRight("plugin_ocsinventoryng", READ)) {
         switch ($data['linked_action'] - Log::HISTORY_PLUGIN) {
            case self::HISTORY_OCS_IMPORT :
               return sprintf(__('%1$s: %2$s'), __('Imported from OCSNG', 'ocsinventoryng'),
                              $data['new_value']);

            case self::HISTORY_OCS_DELETE :
               return sprintf(__('%1$s: %2$s'), __('Deleted in OCSNG', 'ocsinventoryng'),
                              $data['old_value']);

            case self::HISTORY_OCS_LINK :
               return sprintf(__('%1$s: %2$s'), __('Linked with an OCSNG computer', 'ocsinventoryng'),
                              $data['new_value']);

            case self::HISTORY_OCS_IDCHANGED :
               return sprintf(__('The OCSNG ID of the computer changed from %1$s to %2$s',
                                 'ocsinventoryng'),
                              $data['old_value'], $data['new_value']);

            case self::HISTORY_OCS_TAGCHANGED :
               return sprintf(__('The OCSNG TAG of the computer changed from %1$s to %2$s',
                                 'ocsinventoryng'),
                              $data['old_value'], $data['new_value']);
         }
      }
      return '';
   }

   /**
    * @param CommonDBTM $item
    *
    * @return int
    */
   static function getOCSServerForItem(CommonDBTM $item) {
      global $DB;
      $dbu = new DbUtils();
      $query = "SELECT *
                FROM `glpi_plugin_ocsinventoryng_ocslinks`
                WHERE `computers_id` = " . $item->getID() . " " .
               $dbu->getEntitiesRestrictRequest("AND", "glpi_plugin_ocsinventoryng_ocslinks");

      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {
         $data = $DB->fetch_assoc($result);

         if (count($data)) {

            return $data['plugin_ocsinventoryng_ocsservers_id'];
         }

         return false;
      }
   }

   /**
    * Make the item link between glpi and ocs.
    *
    * This make the database link between ocs and glpi databases
    *
    * @param $ocsid integer : ocs item unique id.
    * @param $plugin_ocsinventoryng_ocsservers_id integer : ocs server id
    * @param $glpi_computers_id integer : glpi computer id
    *
    * return integer : link id.
    *
    * @return bool|item
    */
   static function ocsLink($ocsid, $plugin_ocsinventoryng_ocsservers_id, $glpi_computers_id) {
      global $DB;

      // Retrieve informations from computer
      $comp = new Computer();
      $comp->getFromDB($glpi_computers_id);
      if (isset($glpi_computers_id)
          && $glpi_computers_id > 0
      ) {
         $input["is_dynamic"] = 1;
         $input["id"]         = $glpi_computers_id;
         $comp->update($input);
      }
      PluginOcsinventoryngOcsServer::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);

      $ocsComputer = $ocsClient->getComputer($ocsid);

      if (is_null($ocsComputer)) {
         return false;
      }
      $link = new self();
      $data = $link->find("`ocsid` = " . $ocsid . " 
                           AND `plugin_ocsinventoryng_ocsservers_id` = " . $plugin_ocsinventoryng_ocsservers_id);
      if (count($data) > 0) {
         return false;
      }
      $query  = "INSERT INTO `glpi_plugin_ocsinventoryng_ocslinks`
                       (`computers_id`, `ocsid`, `ocs_deviceid`,
                        `last_update`, `plugin_ocsinventoryng_ocsservers_id`,
                        `entities_id`, `tag`)
                VALUES ($glpi_computers_id, '$ocsid', '" . $ocsComputer['META']['DEVICEID'] . "',
                        '" . $_SESSION["glpi_currenttime"] . "', '$plugin_ocsinventoryng_ocsservers_id',
                        " . $comp->fields['entities_id'] . ", '" . addslashes($ocsComputer['META']['TAG']) . "')";
      $result = $DB->query($query);

      if ($result) {
         return ($DB->insert_id());
      }

      return false;
   }

   /**
    * Clean links between GLPI and OCS from a list.
    *
    * @param $plugin_ocsinventoryng_ocsservers_id int : id of ocs server in GLPI
    * @param $ocslinks_id array : ids of ocslinks to clean
    *
    * @return nothing
    * */
   static function cleanLinksFromList($plugin_ocsinventoryng_ocsservers_id, $ocslinks_id) {
      global $DB;

      $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);

      foreach ($ocslinks_id as $key => $val) {

         $query = "SELECT *
                   FROM `glpi_plugin_ocsinventoryng_ocslinks`
                   WHERE `id` = '$key'
                         AND `plugin_ocsinventoryng_ocsservers_id`
                                 = $plugin_ocsinventoryng_ocsservers_id";

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               $data = $DB->fetch_array($result);

               $comp = new Computer();
               if ($cfg_ocs['deleted_behavior']) {
                  if ($cfg_ocs['deleted_behavior'] == 1) {
                     $comp->delete(["id" => $data["computers_id"]], 0);
                  } else {
                     if (preg_match('/STATE_(.*)/', $cfg_ocs['deleted_behavior'], $results)) {
                        $tmp['id']          = $data["computers_id"];
                        $tmp['states_id']   = $results[1];
                        $tmp['entities_id'] = $data['entities_id'];
                        $tmp["_nolock"]     = true;
                        $comp->update($tmp);
                     }
                  }
               }

               //Add history to indicates that the machine was deleted from OCS
               $changes[0] = '0';
               $changes[1] = $data["ocsid"];
               $changes[2] = "";
               self::history($data["computers_id"], $changes, self::HISTORY_OCS_DELETE);

               $query = "DELETE
                         FROM `glpi_plugin_ocsinventoryng_ocslinks`
                         WHERE `id` = " . $data["id"];
               $DB->query($query);
            }
         }
      }
   }

   /**
    * @param $computers_id
    * @param $tomerge
    * @param $field
    *
    * @return bool
    */
   static function mergeOcsArray($computers_id, $tomerge) {
      global $DB;

      $query = "SELECT `computer_update`,`ocsid`, `plugin_ocsinventoryng_ocsservers_id`
                FROM `glpi_plugin_ocsinventoryng_ocslinks`
                WHERE `computers_id` = $computers_id";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            $dbu                                 = new DbUtils();
            $tab                                 = $dbu->importArrayFromDB($DB->result($result, 0, "computer_update"));
            $plugin_ocsinventoryng_ocsservers_id = $DB->result($result, 0, "plugin_ocsinventoryng_ocsservers_id");
            $ocsid                               = $DB->result($result, 0, "ocsid");
            foreach ($tab as $k => $field) {
               if (!array_key_exists($field, self::getLockableFields($plugin_ocsinventoryng_ocsservers_id, $ocsid))) {
                  unset($tab[$k]);
               }
            }

            $newtab = array_merge($tomerge, $tab);
            $newtab = array_unique($newtab);
            $dbu    = new DbUtils();
            $query  = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                      SET `computer_update` = '" . addslashes($dbu->exportArrayToDB($newtab)) . "'
                      WHERE `computers_id` = $computers_id";

            if ($DB->query($query)) {
               return true;
            }
         }
      }
      return false;
   }

   /**
    * @param      $computers_id
    * @param      $todel
    * @param      $field
    * @param bool $is_value_to_del
    *
    * @return bool
    */
   static function deleteInOcsArray($computers_id, $todel, $is_value_to_del = false) {
      global $DB;

      $query = "SELECT `computer_update`, `plugin_ocsinventoryng_ocsservers_id` 
                FROM `glpi_plugin_ocsinventoryng_ocslinks`
                WHERE `computers_id` = $computers_id";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {

            $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($DB->result($result, 0, "plugin_ocsinventoryng_ocsservers_id"));
            if ($cfg_ocs["use_locks"]) {
               $dbu = new DbUtils();
               $tab = $dbu->importArrayFromDB($DB->result($result, 0, 'computer_update'));

               if ($is_value_to_del) {
                  $todel = array_search($todel, $tab);
               }
               if (isset($tab[$todel])) {
                  unset($tab[$todel]);
                  $dbu   = new DbUtils();
                  $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                            SET `computer_update` = '" . addslashes($dbu->exportArrayToDB($tab)) . "'
                            WHERE `computers_id` = $computers_id";
                  if ($DB->query($query)) {
                     return true;
                  }
               }
            }
         }
      }
      return false;
   }

   /**
    * @param      $computers_id
    * @param      $newArray
    * @param      $field
    * @param bool $lock
    *
    * @return bool
    */
   static function replaceOcsArray($computers_id, $newArray, $lock = true) {
      global $DB;

      $dbu      = new DbUtils();
      $newArray = addslashes($dbu->exportArrayToDB($newArray));

      $query = "SELECT `computer_update`, `plugin_ocsinventoryng_ocsservers_id` 
                FROM `glpi_plugin_ocsinventoryng_ocslinks`
                WHERE `computers_id` = $computers_id";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {

            $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($DB->result($result, 0, "plugin_ocsinventoryng_ocsservers_id"));
            if ($lock && $cfg_ocs["use_locks"]) {

               $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                         SET `computer_update` = '" . $newArray . "'
                         WHERE `computers_id` = $computers_id";
               $DB->query($query);

               return true;
            }
         }
      }
      return false;
   }


   /**
    * @param $computers_id
    * @param $toadd
    * @param $field
    *
    * @return bool
    */
   static function addToOcsArray($computers_id, $toadd, $field) {
      global $DB;

      $query = "SELECT `$field`, `plugin_ocsinventoryng_ocsservers_id` 
                FROM `glpi_plugin_ocsinventoryng_ocslinks`
                WHERE `computers_id` = $computers_id";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {

            $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($DB->result($result, 0, "plugin_ocsinventoryng_ocsservers_id"));
            if ($cfg_ocs["use_locks"]) {

               $dbu = new DbUtils();
               $tab = $dbu->importArrayFromDB($DB->result($result, 0, $field));

               // Stripslashes because importArray get clean array
               foreach ($toadd as $key => $val) {
                  $tab[] = stripslashes($val);
               }
               $dbu   = new DbUtils();
               $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                         SET `$field` = '" . addslashes($dbu->exportArrayToDB($tab)) . "'
                         WHERE `computers_id` = $computers_id";
               $DB->query($query);

               return true;
            }
         }
      }
      return false;
   }

   /**
    * @param $ID
    *
    * @return array|null
    */
   static function getLocksForComputer($ID) {
      global $DB;

      $query  = "SELECT *
      FROM `glpi_plugin_ocsinventoryng_ocslinks`
      WHERE `computers_id` = $ID";
      $locks  = [];
      $result = $DB->query($query);
      if ($DB->numrows($result) == 1) {
         $data = $DB->fetch_assoc($result);

         $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($data["plugin_ocsinventoryng_ocsservers_id"]);
         if ($cfg_ocs["use_locks"]) {
            // Print lock fields for OCSNG
            $lockable_fields = self::getLockableFields($data["plugin_ocsinventoryng_ocsservers_id"], $data["ocsid"]);

            $dbu    = new DbUtils();
            $locked = $dbu->importArrayFromDB($data["computer_update"]);

            //            if (!in_array(PluginOcsinventoryngOcsProcess::IMPORT_TAG_078, $locked)) {
            //               $locked = self::migrateComputerUpdates($ID, $locked);
            //            }
            if (count($locked) > 0) {
               foreach ($locked as $key => $val) {
                  if (!isset($lockable_fields[$val])) {
                     unset($locked[$key]);
                  }
               }
            }

            if (count($locked)) {

               foreach ($locked as $key => $val) {
                  $locks[$key] = $val;
               }
            }
         } else {
            $locks = null;
         }

         return $locks;
      }
   }

   /**
    * @return array
    */
   static function getLockableFields($plugin_ocsinventoryng_ocsservers_id = 0, $ocsid = 0) {

      $locks = array_merge(self::getHardwareLockableFields($plugin_ocsinventoryng_ocsservers_id),
                           self::getBiosLockableFields($plugin_ocsinventoryng_ocsservers_id),
                           self::getRuleLockableFields($plugin_ocsinventoryng_ocsservers_id, $ocsid),
                           self::getOSLockableFields($plugin_ocsinventoryng_ocsservers_id),
                           self::getAdministrativeInfosLockableFields($plugin_ocsinventoryng_ocsservers_id));

      return $locks;
   }

   static function getHardwareLockableFields($plugin_ocsinventoryng_ocsservers_id = 0) {

      if ($plugin_ocsinventoryng_ocsservers_id > 0) {

         $locks   = [];
         $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);

         if (intval($cfg_ocs["import_general_name"]) > 0) {
            $locks["name"] = __('Name');
         }

         if (intval($cfg_ocs["import_general_comment"]) > 0) {
            $locks["comment"] = __('Comments');
         }

         if (intval($cfg_ocs["import_general_contact"]) > 0) {
            $locks["contact"] = __('Alternate username');
         }

         if (intval($cfg_ocs["import_general_type"]) > 0
             && intval($cfg_ocs["import_device_bios"]) > 0) {
            $locks["computertypes_id"] = __('Type');
         }

         if (intval($cfg_ocs["import_general_domain"]) > 0) {
            $locks["domains_id"] = __('Domain');
         }

         if (intval($cfg_ocs["import_user"]) > 0) {
            $locks["users_id"] = __('User');
         }

         if (intval($cfg_ocs["import_general_uuid"]) > 0) {
            $locks["uuid"] = __('UUID');
         }

      } else {
         $locks = ["name"       => __('Name'),
                   "comment"    => __('Comments'),
                   "contact"    => __('Alternate username'),
                   "domains_id" => __('Domain'),
                   "uuid"       => __('UUID'),
                   "users_id"   => __('User')];
      }

      return $locks;
   }

   static function getBiosLockableFields($plugin_ocsinventoryng_ocsservers_id = 0) {

      if ($plugin_ocsinventoryng_ocsservers_id > 0) {

         $locks   = [];
         $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);

         if (intval($cfg_ocs["import_general_manufacturer"]) > 0
             && intval($cfg_ocs["import_device_bios"]) > 0) {
            $locks["manufacturers_id"] = __('Manufacturer');
         }

         if (intval($cfg_ocs["import_general_model"]) > 0
             && intval($cfg_ocs["import_device_bios"]) > 0) {
            $locks["computermodels_id"] = __('Model');
         }

         if (intval($cfg_ocs["import_general_serial"]) > 0
             && intval($cfg_ocs["import_device_bios"]) > 0) {
            $locks["serial"] = __('Serial number');
         }

         if (intval($cfg_ocs["import_general_type"]) > 0
             && intval($cfg_ocs["import_device_bios"]) > 0) {
            $locks["computertypes_id"] = __('Type');
         }
      } else {
         $locks = ["manufacturers_id"  => __('Manufacturer'),
                   "computermodels_id" => __('Model'),
                   "serial"            => __('Serial number'),
                   "computertypes_id"  => __('Type')];
      }

      return $locks;
   }

   static function getRuleLockableFields($plugin_ocsinventoryng_ocsservers_id = 0, $ocsid = 0) {

      if ($plugin_ocsinventoryng_ocsservers_id > 0) {

         $locks   = [];
         $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);

         $rule         = new RuleImportEntityCollection();
         $locations_id = 0;
         $groups_id    = 0;
         $data         = $rule->processAllRules(['ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                                                 '_source'       => 'ocsinventoryng',
                                                 'locations_id'  => $locations_id,
                                                 'groups_id'     => $groups_id],
                                                ['locations_id' => $locations_id,
                                                 'groups_id'    => $groups_id],
                                                ['ocsid' => $ocsid]);

         if (intval($cfg_ocs["import_user_group"]) > 0) {
            $locks["groups_id"] = __('Group');
         } else if (isset($data['groups_id']) && $data['groups_id'] > 0) {
            $locks["groups_id"] = __('Group');
         }

         if (intval($cfg_ocs["import_user_location"]) > 0) {
            $locks["locations_id"] = __('Location');
         } else if (isset($data['locations_id']) && $data['locations_id'] > 0) {
            $locks["locations_id"] = __('Location');
         }
      } else {
         $locks = ["locations_id" => __('Location'),
                   "groups_id"    => __('Group')];
      }

      return $locks;
   }

   static function getOSLockableFields($plugin_ocsinventoryng_ocsservers_id = 0) {

      if ($plugin_ocsinventoryng_ocsservers_id > 0) {

         $locks   = [];
         $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);

         if (intval($cfg_ocs["import_general_os"]) > 0) {
            $locks["operatingsystems_id"]             = __('Operating system');
            $locks["operatingsystemservicepacks_id"]  = __('Service pack');
            $locks["operatingsystemversions_id"]      = __('Version of the operating system');
            $locks["operatingsystemarchitectures_id"] = __('Operating system architecture');//Enable 9.1
         }

         if (intval($cfg_ocs["import_os_serial"]) > 0) {
            $locks["license_number"] = __('Serial of the operating system');
            $locks["license_id"]     = __('Product ID of the operating system');
         }

      } else {
         $locks = ["operatingsystems_id"             => __('Operating system'),
                   "operatingsystemservicepacks_id"  => __('Service pack'),
                   "operatingsystemversions_id"      => __('Version of the operating system'),
                   'operatingsystemarchitectures_id' => __('Operating system architecture'),//Enable 9.1
                   "license_number"                  => __('Serial of the operating system'),
                   "license_id"                      => __('Product ID of the operating system')];
      }

      return $locks;

   }

   static function getAdministrativeInfosLockableFields($plugin_ocsinventoryng_ocsservers_id = 0) {

      if ($plugin_ocsinventoryng_ocsservers_id > 0) {

         $locks = [];
         $link  = new PluginOcsinventoryngOcsAdminInfosLink();

         $link->getFromDBbyOcsServerIDAndGlpiColumn($plugin_ocsinventoryng_ocsservers_id, "networks_id");
         if (!empty($link->fields["ocs_column"])) {
            $locks["networks_id"] = __('Network');
         }
         $link->getFromDBbyOcsServerIDAndGlpiColumn($plugin_ocsinventoryng_ocsservers_id, "use_date");
         if (!empty($link->fields["ocs_column"])) {
            $locks["use_date"] = __('Startup date');
         }
         $link->getFromDBbyOcsServerIDAndGlpiColumn($plugin_ocsinventoryng_ocsservers_id, "otherserial");
         if (!empty($link->fields["ocs_column"])) {
            $locks["otherserial"] = __('Inventory number');
         }
         $link->getFromDBbyOcsServerIDAndGlpiColumn($plugin_ocsinventoryng_ocsservers_id, "contact_num");
         if (!empty($link->fields["ocs_column"])) {
            $locks["contact_num"] = __('Alternate username number');
         }
         $link->getFromDBbyOcsServerIDAndGlpiColumn($plugin_ocsinventoryng_ocsservers_id, "locations_id");
         if (!empty($link->fields["ocs_column"])) {
            $locks["locations_id"] = __('Location');
         }
         $link->getFromDBbyOcsServerIDAndGlpiColumn($plugin_ocsinventoryng_ocsservers_id, "groups_id");
         if (!empty($link->fields["ocs_column"])) {
            $locks["groups_id"] = __('Group');
         }
      } else {
         $locks = ["networks_id"  => __('Network'),
                   "use_date"     => __('Startup date'),
                   "otherserial"  => __('Inventory number'),
                   "contact_num"  => __('Alternate username number'),
                   "locations_id" => __('Location'),
                   "groups_id"    => __('Group')];
      }

      return $locks;

   }

   /**
    * Display lock icon in main item form
    *
    * @param string $itemtype
    */
   static function showLockIcon($computers_id, $data) {
      global $CFG_GLPI;

      $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($data['plugin_ocsinventoryng_ocsservers_id']);
      if ($cfg_ocs["use_locks"]) {
         if (isset($computers_id)
             && $computers_id > 0) {
            $locks = self::getLocksForComputer($computers_id);
            //print_r($locks);
            $text = __('Unlock field and import OCSNG data', 'ocsinventoryng');
            foreach ($locks as $field) {
               if ($field == "contact"
                   || $field == "contact_num"
                   || $field == "serial"
                   || $field == "name"
                   || $field == "otherserial"
                   || $field == "license_id"
                   || $field == "contact_num"
                   || $field == "license_number"
                   || $field == "use_date"
               ) {
                  $js = '$("input[name=' . $field . ']").closest("td").prev().append("<i class=\"lockfield' . $field . ' fa fa-lock\"></i>");';
               } else if ($field == "comment") {
                  $js = '$("textarea[name=' . $field . ']").closest("td").prev().append("<i class=\"lockfield' . $field . ' fa fa-lock\"></i>");';
               } else {
                  $js = '$("select[name=' . $field . ']").closest("td").prev().append("<i class=\"lockfield' . $field . ' fa fa-lock\"></i>");';
               }
               $rootdoc                             = $CFG_GLPI["root_doc"];
               $plugin_ocsinventoryng_ocsservers_id = $data['plugin_ocsinventoryng_ocsservers_id'];
               $js                                  .= '
            $(document).ready(function() {
               $(".lockfield' . $field . '").click(function(e) {
                  lastClickedElement = e.target;
                  var check = confirm("' . $text . ' ?");
                  var lock_data = {
                  "field" : "' . $field . '",
                  "computers_id" : "' . $computers_id . '",
                  "plugin_ocsinventoryng_ocsservers_id" : "' . $plugin_ocsinventoryng_ocsservers_id . '",
                  "ocsid" : "' . $data['ocsid'] . '",
                  "ocs_linkid" : "' . $data['id'] . '",
                  "update_lock" : "update_lock",                 
                  };
                 if (check) {
                        $.ajax({
                        type: "POST",
                        url: "' . $rootdoc . '/plugins/ocsinventoryng/ajax/updatelock.php",
                        data:lock_data,
                        success: function(){
                           window.location.reload();
                        },
                     });
                 } else {
                     return false;
                 }
             });
           });';
               echo Html::scriptBlock($js);
            }
         }
      }
   }

   /**
    * @param $computers_id
    * @param $computer_update
    *
    * @return array
    */
   static function migrateComputerUpdates($computers_id, $computer_update) {

      $new_computer_update = [self::IMPORT_TAG_078];

      $updates = ['ID'                 => 'id',
                  'FK_entities'        => 'entities_id',
                  'tech_num'           => 'users_id_tech',
                  'comments'           => 'comment',
                  'os'                 => 'operatingsystems_id',
                  'os_version'         => 'operatingsystemversions_id',
                  'os_sp'              => 'operatingsystemservicepacks_id',
                  'os_license_id'      => 'license_id',
                  'auto_update'        => 'autoupdatesystems_id',
                  'location'           => 'locations_id',
                  'domain'             => 'domains_id',
                  'network'            => 'networks_id',
                  'model'              => 'computermodels_id',
                  'type'               => 'computertypes_id',
                  'tplname'            => 'template_name',
                  'FK_glpi_enterprise' => 'manufacturers_id',
                  'deleted'            => 'is_deleted',
                  'notes'              => 'notepad',
                  'ocs_import'         => 'is_dynamic',
                  'FK_users'           => 'users_id',
                  'FK_groups'          => 'groups_id',
                  'state'              => 'states_id'];

      if (count($computer_update)) {
         foreach ($computer_update as $field) {
            if (isset($updates[$field])) {
               $new_computer_update[] = $updates[$field];
            } else {
               $new_computer_update[] = $field;
            }
         }
      }

      //Add the new tag as the first occurence in the array
      PluginOcsinventoryngOcslink::replaceOcsArray($computers_id, $new_computer_update, false);
      return $new_computer_update;
   }
}