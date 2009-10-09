<?php
    /*
    ReactOS DynamicFrontend (RDF)
    Copyright (C) 2008  Klemens Friedl <frik85@reactos.org>

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
 * class HTML_User_Register
 * 
 * @package html
 * @subpackage user
 */
class HTML_User_Register extends HTML_User
{

  /**
   *
   *
   * @access public
   */
  public function __construct( )
  {
    session_start();
    parent::__construct('');
  }


  /**
   *
   *
   * @access private
   */
  protected function body( )
  {
    $config = &RosCMS::getInstance();

    $err_message = ''; // error message box text
    $name_exists = false; // username already exists in the database (true = username exists)
    $mail_exists = false; // email already exists in the database (true = email exists)
    $safename = true; // protected username ("" = not checked; "true" = fine; "false" =  match with a db entry => protected name)
    $safepwd = true; // unsafe password, common cracked passwords ("" = not checked; "true" = fine; "false" =  match with a db entry => protected name)

    echo_strip('
      <h1>Register to'. $config->siteName().'</h1>
      <p>Become a member of the '.$config->siteName().' Community, and have a single sign-on for all '.$config->siteName().' web services.</p>
      <ul>
        <li>Already a member? <a href="'.$config->pathInstance().'?page=login">Login now</a>! </li>
        <li><a href="'.$config->pathInstance().'?page=login&amp;subpage=lost">Lost username or password?</a></li>
      </ul>

      <form action="'.$config->pathInstance().'?page=register" method="post">
        <div class="bubble">
          <div class="corner_TL">
            <div class="corner_TR"></div>
          </div>');

    if (isset($_POST['registerpost']) && isset($_POST['username']) && preg_match('/^[a-z0-9_\-[:space:]\.]{'.$config->limitUserNameMin().','.$config->limitUsernameMax().'}$/i', trim($_POST['username']))) {

      // check if another account with the same username already exists
      $stmt=&DBConnection::getInstance()->prepare("SELECT name FROM ".ROSCMST_USERS." WHERE LOWER(REPLACE(name, '_', ' ')) = LOWER(REPLACE(:username, '_', ' ')) LIMIT 1");
      $stmt->bindParam('username',$_POST['username'],PDO::PARAM_STR);
      $stmt->execute();
      $name_exists = ($stmt->fetchColumn() !== false);

      // check if the username is equal to a protected name
      $stmt=&DBConnection::getInstance()->prepare("SELECT 1 FROM ".ROSCMST_FORBIDDEN." WHERE name = :forbidden LIMIT 1");
      $stmt->bindValue('forbidden','%'.$_POST['username'].'%',PDO::PARAM_STR);
      $stmt->execute();

      // name is not forbidden -> go on
      if ($stmt->fetchColumn() === false) {

        if (isset($_POST['registerpost']) && isset($_POST['useremail']) && $_POST['useremail'] != '') {

          // check if another account with the same email address already exists
          $stmt=&DBConnection::getInstance()->prepare("SELECT email FROM ".ROSCMST_USERS." WHERE email = :email LIMIT 1");
          $stmt->bindParam('email',$_POST['useremail'],PDO::PARAM_STR);
          $stmt->execute();
          
          $mail_exists = ($stmt->fetchColumn() !== false);
        }

        if (self::canRegister(true, $name_exists, $safepwd, $mail_exists)) {

          // user language (browser settings)
          $userlang = Language::validate($_SERVER['HTTP_ACCEPT_LANGUAGE']);

          // account activation code
          $activation_code = '';
          for ($n = 0; $n < 20; ++$n) {
            $activation_code .= chr(rand(0, 255));
          }
          $activation_code = base64_encode($activation_code);   // base64-set, but filter out unwanted chars
          $activation_code = preg_replace('/[+\/=IG0ODQRtl]/i', '', $activation_code);  // strips hard to discern letters, depends on used font type
          $activation_code = substr($activation_code, 0, rand(10, 15));

          // add new account
          $stmt=&DBConnection::getInstance()->prepare("INSERT INTO ".ROSCMST_USERS." ( name, password, created, activation, email, lang_id, modified ) VALUES ( :user_name, MD5( :password ), NOW(), :activation_code, :email, :lang, NOW() )");
          $stmt->bindValue('user_name',trim($_POST['username']),PDO::PARAM_STR);
          $stmt->bindParam('password',$_POST['userpwd1'],PDO::PARAM_STR);
          $stmt->bindParam('activation_code',$activation_code,PDO::PARAM_STR);
          $stmt->bindParam('email',$_POST['useremail'],PDO::PARAM_STR);
          $stmt->bindParam('lang',$userlang,PDO::PARAM_INT);
          $stmt->execute();

          $stmt=&DBConnection::getInstance()->prepare("SELECT id FROM ".ROSCMST_USERS." WHERE LOWER(name) = LOWER(:user_name)");
          $stmt->bindParam('user_name',$_POST['username'],PDO::PARAM_INT);
          $stmt->execute();
          $user_id = $stmt->fetchColumn();

          // give a 'user' group membership
          $stmt=&DBConnection::getInstance()->prepare("INSERT INTO ".ROSCMST_MEMBERSHIPS." (user_id, group_id) SELECT :user_id, id FROM ".ROSCMST_GROUPS." WHERE name_short = 'user' LIMIT 1");
          $stmt->bindParam('user_id',$user_id,PDO::PARAM_INT);
          $stmt->execute();

          // add subsystem accounts
          ROSUser::syncSubsystems($user_id);

          // subject
          $subject = $config->siteName()." - Account Activation";

          // message
          $message = $config->siteName()." - Account Activation\n\n\nYou have registered an account on ".$config->siteName().". The next step in order to enable the account is to activate it by using the hyperlink below.\n\nYou haven't registered an account? Oops, then someone has tried to register an account with your email address. Just ignore this email, no one can use it anyway as it is not activated and the account will get deleted soon.\n\n\nUsername: ".$_POST['username']."\nPassword: ".$_POST['userpwd1']."\n\nActivation-Hyperlink: ".$config->siteURL()."/".$config->pathInstance()."?page=login&subpage=activate&code=".$activation_code."\n\n\nBest regards,\nThe ".$config->siteName()." Team\n\n\n(please do not reply as this is an auto generated email!)";

          // send the mail
          if (Email::send($_POST['useremail'], $subject, $message)) {
            echo_strip('
              <h2>Account registered</h2>
              <div>Check your email inbox (and spam folder) for the <strong>account activation email</strong> that contains the activation hyperlink.</div>');
          }
          else {
            $err_message = 'error while trying to send E-Mail';
          }

          unset($_SESSION['rdf_security_code']);
        } // end registration process
      }
    }
    else {
      echo_strip('
        <h2>Register Account</h2>
        <div class="field">
          <label for="username"'.((isset($_POST['registerpost']) && (strlen($_POST['username']) < $config->limitUserNameMin() || strlen($_POST['username']) > $config->limitUserNameMax() || $safename === false  || substr_count($_POST['username'], ' ') >= 4 || $name_exists)) ? ' style="color:red;"' : '').'>Username</label>
          <input type="text" name="username"  tabindex="1" id="username"'.(isset($_POST['username']) ? 'value="'.$_POST['username'].'"' : '').' maxlength="50" />
          <div class="detail">uppercase letters, lowercase letters, numbers, and symbols (ASCII characters)</div>');

      if (isset($_POST['registerpost']) && (strlen($_POST['username']) < $config->limitUserNameMin() || $name_exists || $safename === false || strlen($_POST['username']) > $config->limitUserNameMax() || substr_count($_POST['username'], ' ') >= 4)) {
        echo_strip('
          <br />
          <em>Please try another username with at least '.$config->limitUserNameMin().' characters.</em>');
      }

      echo_strip('
        </div>
        <div class="field">
          <label for="userpwd1"'.(isset($_POST['registerpost']) ? ' style="color:red;"' : '').'>Password</label>
          <input type="password" name="userpwd1" tabindex="2" id="userpwd1" maxlength="50" />');

      if ($safepwd === false || (isset($_POST['userpwd1']) && strlen($_POST['userpwd1']) > $config->limitUserNameMax())) {
        echo_strip('
          <br />
          <em>Please use a stronger password! At least '.$config->limitPasswordMin().' characters, do not include common words or names, and combine three of these character types: uppercase letters, lowercase letters, numbers, or symbols (ASCII characters).</em>');
      }
      else {
        echo_strip('
          <div class="detail">uppercase letters, lowercase letters, numbers, and symbols (ASCII characters)</div>');
      }

      echo_strip('
        </div>
        <div class="field">
          <label for="userpwd2"'.(isset($_POST['registerpost']) ? ' style="color:red;"' : '').'>Re-type Password</label>
          <input name="userpwd2" type="password" tabindex="3" id="userpwd2" maxlength="50" />
        </div>
        <div class="field">
          <label for="useremail"'.(isset($_POST['registerpost']) && isset($_POST['useremail']) && EMail::isValid($_POST['useremail']) ? ' style="color:red;"' : '').'>E-Mail</label>
          <input name="useremail" type="text" class="input" tabindex="4" id="useremail"'.(isset($_POST['useremail']) ? 'value="' . $_POST['useremail'] . '"' : '').'maxlength="50" />');

      if (isset($_POST['registerpost']) && $mail_exists) {
        echo_strip('
          <br />
          <em>That email address is already with an account. Please <a href="'.$config->pathInstance().'?page=login" style="color:red !important; font-weight: bold; text-decoration:underline;">login</a>!</em>');
      }

      echo_strip('
        </div>
        <div class="field">
          <label for="usercaptcha"'.(isset($_POST['registerpost']) ? ' style="color:red;"' : '').'>Type the code shown</label>
          <input name="usercaptcha" type="text" tabindex="7" id="usercaptcha" maxlength="50" />
          <script type="text/javascript">');echo "
          <!--
            
            var BypassCacheNumber = 0;

            function CaptchaReload()
            {
              ++BypassCacheNumber;
              document.getElementById('captcha').src = '".$config->pathInstance()."?page=captcha&nr=' + BypassCacheNumber;
            }

            document.write('<br /><span style=\"color:#817A71; \">If you cannot read this, try <a href=\"javascript:CaptchaReload()\">another one</a>.</span>');
          
          -->";echo_strip('
          </script>
          <img id="captcha" src="'.$config->pathInstance().'?page=captcha" style="padding-top:10px;" alt="If you cannot read this, try another one or email '.$config->emailSupport().' for help." title="Are you human?" />
          <br />');
      if (isset($_POST['registerpost'])) { 
        echo_strip('
          <br />
          <em>Captcha code is case insensitive. <br />If you cannot read it, try another one.</em>');
      }

      echo_strip('
        </div>
        <div class="field">
          <input name="registerpost" type="hidden" id="registerpost" value="reg" />
          <button type="submit" name="submit">Register</button>
          <button type="button" onclick="'."window.location='".$config->pathInstance()."'".'" style="color:#777777;">Cancel</button>
        </div>');
    } // end registration form

    echo '
        <div class="corner_BL">
          <div class="corner_BR"></div>
        </div>
      </div>';

    // print error messages
    if ($err_message != '') {
      echo_strip('
        <div class="bubble message">
          <div class="corner_TL">
            <div class="corner_TR"></div>
          </div>
          <strong>');echo $err_message;echo_strip('</strong>
          <div class="corner_BL">
            <div class="corner_BR"></div>
          </div>
        </div>');
    }
    echo_strip('
      </form>');
  } // end of member function body

  /**
   * helper function
   *
   * @visible private
  **/
  private function canRegister($safename, $name_exists, $safepwd, $mail_exists)
  {
    $config = &RosCMS::getInstance();
  
  
    // <form> was send
    return (isset($_POST['registerpost'])

    // username
    && !$name_exists && $safename && isset($_POST['username']) && $_POST['username'] != '' && substr_count($_POST['username'], ' ') < 4 && strlen($_POST['username']) >= $config->limitUserNameMin() && strlen($_POST['username']) < $config->limitUserNameMax()

    // password
    && isset($_POST['userpwd1']) && $_POST['userpwd1'] != '' && isset($_POST['userpwd2']) && $_POST['userpwd2'] != '' && strlen($_POST['userpwd1']) >= $config->limitPasswordMin() && strlen($_POST['userpwd1']) < $config->limitPasswordMax() && $_POST['userpwd1'] == $_POST['userpwd2'] && $safepwd

    // email
    && isset($_POST['useremail']) && $_POST['useremail'] != '' && EMail::isValid($_POST['useremail']) && !$mail_exists

    // captcha
    && isset($_POST['usercaptcha']) && $_POST['usercaptcha'] != '' && isset($_SESSION['rdf_security_code']) && strtolower($_SESSION['rdf_security_code']) == strtolower($_POST['usercaptcha']));
  }


} // end of HTML_User_Register
?>
