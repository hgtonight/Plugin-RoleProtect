<?php if(!defined('APPLICATION')) exit();
/* 	Copyright 2014 Zachary Doll
 * 	This program is free software: you can redistribute it and/or modify
 * 	it under the terms of the GNU General Public License as published by
 * 	the Free Software Foundation, either version 3 of the License, or
 * 	(at your option) any later version.
 *
 * 	This program is distributed in the hope that it will be useful,
 * 	but WITHOUT ANY WARRANTY; without even the implied warranty of
 * 	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * 	GNU General Public License for more details.
 *
 * 	You should have received a copy of the GNU General Public License
 * 	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
$PluginInfo['RoleProtect'] = array(
    'Name' => 'Role Protect',
    'Description' => 'Adds the ability to lock roles, disallowing them from being added or removed by any user with `Garden.Roles.Selective` permissions.',
    'Version' => '1.0',
    'RequiredApplications' => array('Vanilla' => '2.0.18.10'),
    'RequiredTheme' => FALSE,
    'RequiredPlugins' => FALSE,
    'MobileFriendly' => TRUE,
    'HasLocale' => TRUE,
    'RegisterPermissions' => 'Garden.Roles.Selective',
    'SettingsUrl' => '/role/protect',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Author' => 'Zachary Doll',
    'AuthorEmail' => 'hgtonight@daklutz.com',
    'AuthorUrl' => 'http://www.daklutz.com',
    'License' => 'GPLv3'
);

class RoleProtect extends Gdn_Plugin {

  public function UserController_BeforeUserEdit_Handler($Sender) {
    $Session = Gdn::Session();
    // Since super admins always pass all permission checks, we make sure the user
    // isn't a super admin before checking the selective permission
    if(!$Session->User->Admin && $Session->CheckPermission('Garden.Roles.Selective')) {
      $Roles = & $Sender->EventArguments['RoleData'];
      $LockedRoles = C('Plugins.RoleProtect.LockedRoles', array());

      // Remove the locked roles from the form data
      foreach($LockedRoles as $RoleID) {
        unset($Roles[$RoleID]);
      }
    }
  }

  public function RoleController_Protect_Create($Sender) {
    $Sender->Permission('Garden.Settings.Manage');
    $Sender->AddCssFile($this->GetResource('design/roleprotect.css', FALSE, FALSE));

    // Set data used by the view
    $Sender->SetData('PluginDescription', $this->GetPluginKey('Description'));
    $Sender->Title($this->GetPluginName() . ' ' . T('Settings'));
    $RoleModel = new RoleModel();
    $Sender->SetData('RoleData', $RoleModel->GetArray());

    $Validation = new Gdn_Validation();
    $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
    $ConfigurationModel->SetField(array('Plugins.RoleProtect.LockedRoles' => C('Plugins.RoleProtect.LockedRoles', array())));

    // Set the model on the form.
    $Sender->Form->SetModel($ConfigurationModel);

    // If seeing the form for the first time...
    if($Sender->Form->AuthenticatedPostBack() === FALSE) {
      // Apply the config settings to the form.
      $Sender->Form->SetData($ConfigurationModel->Data);
    }
    else {
      $Saved = $Sender->Form->Save();
      if($Saved) {
        $Sender->InformMessage('<span class="InformSprite Sliders"></span>' . T('Your changes have been saved.'), 'HasSprite');
      }
    }

    // add the admin side menu
    $Sender->AddSideMenu('settings/testingground');

    $Sender->Render($this->GetView('settings.php'));
  }

}
