<?php
    /*
    RosCMS - ReactOS Content Management System
    Copyright (C) 2009  Danny G�tte <dangerground@web.de>

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
    */


/**
 * class Admin_ACL
 * 
 */
class Admin_ACL extends Admin
{



  /**
   *
   *
   * @access protected
   */
  protected function showNew( )
  {
    $stmt=&DBConnection::getInstance()->prepare("SELECT id, name, description FROM ".ROSCMST_RIGHTS." ORDER BY name ASC");
    $stmt->execute();
    $rights=$stmt->fetchAll(PDO::FETCH_ASSOC);

    echo_strip('
      <h2>Create new Access Control List (ACL)</h2>
      <form onsubmit="return false;">
        <fieldset>
          <legend>Access Control List Options</legend>
          <label for="access_name">Name</label>
          <input id="access_name" name="access_name" maxlength="100" value="" />
          <br />

          <label for="access_short">Short Name (Identifier)</label>
          <input id="access_short" name="access_short" maxlength="50" value="" />
          <br />

          <label for="access_desc">Description</label>
          <input id="access_desc" name="access_desc" maxlength="255" value="" />
        </fieldset>
        <br />
        <fieldset>
          <legend>Groups Access Rights</legend>
          <table>
            <tr>
              <th title="Security Level">SecLvl</th>
              <th>Group Name</th>');
    foreach ($rights as $right) {
      echo '<th style="vertical-align:bottom;" title="'.$right['name'].': '.$right['description'].'"><img src="?page=presentation&amp;type=vtext&amp;text='.$right['name'].'" alt="'.$right['name'].'" /></th>';
    }
    echo '</tr>';

    $stmt=&DBConnection::getInstance()->prepare("SELECT id, name, security_level, description FROM ".ROSCMST_GROUPS." ORDER BY security_level ASC, name ASC");
    $stmt->execute();
    while ($group = $stmt->fetch(PDO::FETCH_ASSOC)) {
      echo_strip('
        <tr title="'.htmlspecialchars($group['description']).'">
          <td>'.$group['security_level'].'</td>
          <td>'.htmlspecialchars($group['name']).'</td>');

      foreach ($rights as $right) {
        echo '<td title="'.$group['name'].'--'.$right['name'].': '.$right['description'].'"><input type="checkbox" value="1" name="valid'.$group['id'].'_'.$right['id'].'" /></td>';
      }
      echo '</tr>';
    }

    echo_strip('
          </table>
        </fieldset>
        <button onclick="'."submitNew('acl')".'">Create new ACL</button>
      </form>
    ');
  } // end of member function showNew



  /**
   *
   *
   * @access protected
   */
  protected function submitNew( )
  {
    $success = true;
  
    // try to insert new access list
    $stmt=&DBConnection::getInstance()->prepare("INSERT INTO ".ROSCMST_ACCESS." (name, name_short, description) VALUES (:name, :short, :description)");
    $stmt->bindParam('name',$_POST['access_name'],PDO::PARAM_STR);
    $stmt->bindParam('short',$_POST['access_short'],PDO::PARAM_STR);
    $stmt->bindParam('description',$_POST['access_desc'],PDO::PARAM_STR);
    if ($stmt->execute()) {
    
      // check for new access list id
      $stmt=&DBConnection::getInstance()->prepare("SELECT id FROM ".ROSCMST_ACCESS." WHERE name=:name");
      $stmt->bindParam('name',$_POST['access_name'],PDO::PARAM_STR);
      $stmt->execute();
      $access_id = $stmt->fetchColumn();
      if ($access_id !== false) {

        $stmt=&DBConnection::getInstance()->prepare("SELECT id, name, description FROM ".ROSCMST_RIGHTS." ORDER BY name ASC");
        $stmt->execute();
        $rights=$stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt=&DBConnection::getInstance()->prepare("INSERT INTO ".ROSCMST_ACL." (access_id, group_id, right_id) VALUES (:access_id,:group_id,:right_id)");
        $stmt->bindParam('access_id',$access_id,PDO::PARAM_INT);
        foreach ($_POST as $item=>$val) {
          if (strpos($item,'valid')===0) {
            $item = substr($item, 5);
            $id = explode('_',$item);
            if($id[0] > 0 && $id[1] > 0 && $val=='true') {
              $stmt->bindParam('right_id',$id[1],PDO::PARAM_INT);
              $stmt->bindParam('group_id',$id[0],PDO::PARAM_INT);
              $success = $success && $stmt->execute();
            }
          }
        } // end foreach
      } // end got list id
      else {
        $success = false;
      }
    } // end list inserted
    else {
      $success = false;
    }

    // give the user a success or failure message
    if ($success) {
      echo 'New ACL was created successfully';
    }
    else {
      echo 'Error, while creating new ACL';
    }
  } // end of member function submitNew



  /**
   *
   *
   * @access protected
   */
  protected function showSearch( )
  {
    echo_strip('
      <h2>Select ACL to '.($_GET['for']=='edit' ? 'edit' : 'delete').'</h2>
      <form onsubmit="return false;">
        <select name="access" id="access">
          <option value="0">&nbsp;</option>');

    $stmt=&DBConnection::getInstance()->prepare("SELECT id, name, description FROM ".ROSCMST_ACCESS." ORDER BY name ASC");
    $stmt->execute();
    while ($access = $stmt->fetch(PDO::FETCH_ASSOC)) {
      echo '<option value="'.$access['id'].'" title="'.$access['description'].'">'.$access['name'].'</option>';
    }

    echo_strip('
        </select>
        <button onclick="'."submitSearch('acl','".($_GET['for'] == 'edit' ? 'edit' : 'delete')."')".'">go on</button>
      </form>');
  }



  /**
   *
   *
   * @access protected
   */
  protected function submitSearch( )
  {
    // show edit / delete form, if entry was selected
    if ($_POST['access'] > 0) {
      if ($_GET['for'] == 'edit') {
        self::showEdit();
      }
      elseif ($_GET['for'] == 'delete') {
        self::showDelete();
      }
    }

    // show search again
    else {
      self::showSearch();
    }
  }



  /**
   *
   *
   * @access protected
   */
  protected function showEdit( )
  {
    $stmt=&DBConnection::getInstance()->prepare("SELECT id, name, description FROM ".ROSCMST_RIGHTS." ORDER BY name ASC");
    $stmt->execute();
    $rights=$stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt=&DBConnection::getInstance()->prepare("SELECT name, name_short, description, id FROM ".ROSCMST_ACCESS." WHERE id=:access_id");
    $stmt->bindParam('access_id',$_POST['access'],PDO::PARAM_INT);
    $stmt->execute();
    $access = $stmt->fetchOnce(PDO::FETCH_ASSOC);

    echo_strip('
      <h2>Edit Access Control List (ACL)</h2>
      <form onsubmit="return false;">
        <fieldset>
          <legend>Access Control List Options</legend>
          <input type="hidden" name="access_id" id="access_id" value="'.$access['id'].'" />
          
          <label for="access_name">Name</label>
          <input id="access_name" name="access_name" maxlength="100" value="'.$access['name'].'" />
          <br />

          <label for="access_short">Short Name (Identifier)</label>
          <input id="access_short" name="access_short" maxlength="50" value="'.$access['name_short'].'" />
          <br />

          <label for="access_desc">Description</label>
          <input id="access_desc" name="access_desc" maxlength="255" value="'.$access['description'].'" />
        </fieldset>
        <br />
        <fieldset>
          <legend>Groups Access Rights</legend>
          <table>
            <tr>
              <th title="Security Level">SecLvl</th>
              <th>Group Name</th>');
    foreach ($rights as $right) {
      echo '<th style="vertical-align:bottom;" title="'.$right['name'].': '.$right['description'].'"><img src="?page=presentation&amp;type=vtext&amp;text='.$right['name'].'" alt="'.$right['name'].'" /></th>';
    }
    echo '</tr>';


    // for usage in loop
    $stmt_is=&DBConnection::getInstance()->prepare("SELECT TRUE FROM ".ROSCMST_ACL." WHERE group_id=:group_id AND right_id=:right_id AND access_id=:access_id LIMIT 1");
    $stmt_is->bindParam('access_id',$access['id'],PDO::PARAM_INT);

    $stmt=&DBConnection::getInstance()->prepare("SELECT id, name, security_level, description FROM ".ROSCMST_GROUPS." ORDER BY security_level ASC, name ASC");
    $stmt->execute();
    while ($group = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $stmt_is->bindParam('group_id',$group['id'],PDO::PARAM_INT);
      echo_strip('
        <tr title="'.htmlspecialchars($group['description']).'">
          <td>'.$group['security_level'].'</td>
          <td>'.htmlspecialchars($group['name']).'</td>');
      foreach ($rights as $right) {
        $stmt_is->bindParam('right_id',$right['id'],PDO::PARAM_INT);
        $stmt_is->execute();
        $is = $stmt_is->fetchColumn();

        echo '<td title="'.$group['name'].'--'.$right['name'].': '.$right['description'].'"><input type="checkbox" value="1" name="valid'.$group['id'].'_'.$right['id'].'" '.($is ? 'checked="checked"' : '').' /></td>';
      }
      echo '</tr>';
    }

    echo_strip('
          </table>
        </fieldset>
        <button onclick="'."submitEdit('acl')".'">edit ACL</button>
      </form>
    ');
  }



  /**
   *
   *
   * @access protected
   */
  protected function submitEdit( )
  {
    $success = true;

    // try to insert new access list
    $stmt=&DBConnection::getInstance()->prepare("UPDATE ".ROSCMST_ACCESS." SET name=:name, name_short=:short, description=:description WHERE id=:access_id");
    $stmt->bindParam('name',$_POST['access_name'],PDO::PARAM_STR);
    $stmt->bindParam('short',$_POST['access_short'],PDO::PARAM_STR);
    $stmt->bindParam('description',$_POST['access_desc'],PDO::PARAM_STR);
    $stmt->bindParam('access_id',$_POST['access_id'],PDO::PARAM_INT);
    $success = $success && $stmt->execute();

    $stmt=&DBConnection::getInstance()->prepare("DELETE FROM ".ROSCMST_ACL." WHERE access_id=:access_id");
    $stmt->bindParam('access_id',$_POST['access_id'],PDO::PARAM_INT);
    $success = $success && $stmt->execute();

    if ($success) {
      $stmt=&DBConnection::getInstance()->prepare("INSERT INTO ".ROSCMST_ACL." (access_id, group_id, right_id) VALUES (:access_id,:group_id,:right_id)");
      $stmt->bindParam('access_id',$_POST['access_id'],PDO::PARAM_INT);
      foreach ($_POST as $item=>$val) {
        if (strpos($item,'valid')===0) {
          $item = substr($item, 5);
          $id = explode('_',$item);
          if($id[0] > 0 && $id[1] > 0 && $val=='true') {
            $stmt->bindParam('right_id',$id[1],PDO::PARAM_INT);
            $stmt->bindParam('group_id',$id[0],PDO::PARAM_INT);
            $success = $success && $stmt->execute();
          }
        }
      }
    }

    // give the user a success or failure message
    if ($success) {
      echo 'ACL was edited successfully';
    }
    else {
      echo 'Error, while editing ACL';
    }
  }



  /**
   *
   *
   * @access protected
   */
  protected function showDelete( )
  {
    $stmt=&DBConnection::getInstance()->prepare("SELECT COUNT(id) FROM ".ROSCMST_ENTRIES." WHERE access_id=:access_id");
    $stmt->bindParam('access_id',$_POST['access'],PDO::PARAM_INT);
    $stmt->execute();
    $data_count = $stmt->fetchColumn();

    // check if
    if ($data_count > 0) {
      echo '<div>Can\'t delete entry: It\'s used in '.$data_count.' entries. Remove usage first, and try again later.</div>';
    }
    else {
      $stmt=&DBConnection::getInstance()->prepare("SELECT name, name_short, description, id FROM ".ROSCMST_ACCESS." WHERE id=:access_id");
      $stmt->bindParam('access_id',$_POST['access'],PDO::PARAM_INT);
      $stmt->execute();
      $access = $stmt->fetchOnce(PDO::FETCH_ASSOC);

      echo_strip('
        <form onsubmit="return false;">
          <div>
            <input type="hidden" name="access_id" id="access_id" value="'.$access['id'].'" />

            Do you really want to delete the access &quot;<span title="'.$access['description'].'">'.$access['name'].'</span>&quot; ?
            <button style="color: red;" onclick="'."submitDelete('acl')".'" name="uaq" value="yes">Yes, Delete it.</button>
            <button style="color: green;" name="uaq" value="no">No</button>
          </div>
        </form>');
    }
  }



  /**
   *
   *
   * @access protected
   */
  protected function submitDelete( )
  {
    $success = true;

    // check if it is used anywhere
    $stmt=&DBConnection::getInstance()->prepare("SELECT COUNT(id) FROM ".ROSCMST_ENTRIES." WHERE access_id=:access_id");
    $stmt->bindParam('access_id',$_POST['access_id'],PDO::PARAM_INT);
    $stmt->execute();
    $data_count = $stmt->fetchColumn();
    if ($data_count > 0) {
      echo '<div>Can\'t delete entry: It\'s used in '.$data_count.' entries. Remove usage first, and try again later.</div>';
    }
    else {

      // delete acl
      $stmt=&DBConnection::getInstance()->prepare("DELETE FROM ".ROSCMST_ACCESS." WHERE id=:access_id");
      $stmt->bindParam('access_id',$_POST['access_id'],PDO::PARAM_INT);
      $success = $success && $stmt->execute();

      // delete rights list
      if ($success) {
        $stmt=&DBConnection::getInstance()->prepare("DELETE FROM ".ROSCMST_ACL." WHERE access_id=:access_id");
        $stmt->bindParam('access_id',$_POST['access_id'],PDO::PARAM_INT);
        $success = $success && $stmt->execute();
      }

      // status message
      if ($success) {
        echo 'ACL was deleted successfully';
      }
      else {
        echo 'Error, while deleting ACL';
      }
    }
  }

} // end of Admin_ACL
?>