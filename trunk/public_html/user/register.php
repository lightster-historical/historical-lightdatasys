<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/LightDataSysResponder.php';
require_once PATH_LIB . 'com/mephex/captcha/Captcha.php';
require_once PATH_LIB . 'com/mephex/captcha/CaptchaImage.php';
require_once PATH_LIB . 'com/mephex/core/Pair.php';
require_once PATH_LIB . 'com/mephex/form/Form.php';
require_once PATH_LIB . 'com/mephex/form/field/CaptchaField.php';
require_once PATH_LIB . 'com/mephex/form/field/EmailField.php';
require_once PATH_LIB . 'com/mephex/form/field/InputField.php';
require_once PATH_LIB . 'com/mephex/form/field/PasswordField.php';
require_once PATH_LIB . 'com/mephex/form/field/SetField.php';
require_once PATH_LIB . 'com/mephex/form/field/SubmitField.php';
require_once PATH_LIB . 'com/mephex/form/fieldset/Fieldset.php';
require_once PATH_LIB . 'com/mephex/form/outputter/DescriptiveFormOutputter.php';
require_once PATH_LIB . 'com/mephex/input/EmailInput.php';
require_once PATH_LIB . 'com/mephex/input/FormInputsException.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';
require_once PATH_LIB . 'com/mephex/user/User.php';


class RegisterResponder extends LightDataSysResponder
{
    protected $form;


    public function init($args, $cacheDir = null)
    {
        parent::init($args, $cacheDir);

        $this->initForm();

        if(!is_null($this->user))
            HttpHeader::forwardTo('/');
    }

    public function post($args)
    {
        $this->form->setValuesUsingInput($this->input);
        if($this->form->validateUsingInput($this->input))
        {
            //User::createUsingFormInput(0, $this->input);
        }
        else
        {
            $this->get($args);
        }
    }


    public function get($args)
    {
        $this->printHeader();

        $this->form->printFormAsHTML(new MXT_DescriptiveFormOutputter());

        /*
        ?>
         <form action="<?php $_SERVER['PHP_SELF']; ?>" class="form-default" method="post">
          <fieldset>
           <legend>User Information</legend>
           <div class="field">
            <input type="text" name="username" value="<?php echo $this->input->get('username'); ?>" />
            <label>
             <em>Username</em>will be your sign in. Your username will also be visible to other users on various parts of the site.
             <?php echo $this->printAnyErrorForField('username'); ?>
            </label>
           </div>
           <div class="field">
            <input type="text" name="first_name" value="<?php echo $this->input->get('first_name'); ?>" />
            <label>
             <em>First Name</em>is your... first name! Currently we use first names and the first initial of last names to list fantasy standings and results. We encourage you to use your real name, but it is important you know that your name will be visible to the public.
             <?php echo $this->printAnyErrorForField('first_name'); ?>
            </label>
           </div>
           <div class="field">
            <input type="text" name="last_name" value="<?php echo $this->input->get('last_name'); ?>" maxlength="1" />
            <label>
             <em>Last Name Initial</em>is used in conjunction with your first name.
             <?php echo $this->printAnyErrorForField('last_name'); ?>
            </label>
           </div>
           <div class="field">
            <input type="text" name="email" value="<?php echo $this->input->get('email'); ?>" />
            <label>
             <em>E-mail Address</em>is the e-mail address we should send your activation e-mail. We will not give away or sell your e-mail address (or other personal information), but we may send you reminders and other useful information. If you ever want us to stop contacting you, simply send us an e-mail.
             <?php echo $this->printAnyErrorForField('email'); ?>
            </label>
           </div>
           <div class="field">
            <input type="text" name="email_confirm" value="<?php echo $this->input->get('email_confirm'); ?>" />
            <label>
             <em>E-mail Address Confirmation</em>is used to help verify that your e-mail address was typed correctly so that your account can be properly activated.
             <?php echo $this->printAnyErrorForField('email_confirm'); ?>
            </label>
           </div>
          </fieldset>
          <fieldset>
           <legend>Password</legend>
           <div class="field">
            <input type="password" name="password" value="" />
            <label>
             <em>Password</em>makes sure that no other users can change information related to your account. Your password must be at least 6 characters.
             <?php echo $this->printAnyErrorForField('password'); ?>
            </label>
           </div>
           <div class="field">
            <input type="password" name="password_confirm" value="" />
            <label>
             <em>Password Confirmation</em>verifies that you typed what you thought you typed as your password.
             <?php echo $this->printAnyErrorForField('password_confirm'); ?>
            </label>
           </div>
          </fieldset>
          <fieldset>
           <legend>Preferences</legend>
           <div class="field">
            <select name="timezone">
        <?php
        $selected = '';
        if($this->input->get('timezone') == '100')
            $selected = ' selected="selected"';
        ?>
             <option value="100"<?php echo $selected; ?>>Auto-detect</option>
        <?php
        for($i = -13; $i <= 13; $i++)
        {
            $selected = '';
            if($this->input->get('timezone') == $i)
                $selected = ' selected="selected"';

            ?>
             <option value="<?php echo $i; ?>"<?php echo $selected; ?>>
            <?php
            if($i == 0)
                echo 'GMT';
            else
                printf('GMT %+d', $i);
            ?>
             </option>
            <?php
        }
        ?>
            </select>
            <label>
             <em>Timezone</em>allows dates and times to be displayed in your timezone. It is recommended that you choose the auto-detect option, which allows us to attempt to determine your timezone everytime you sign in. If auto-detect does not properly detect your timezone, you can change your timezone preference in <i>Your Account</i> &raquo; <i>Preferences</i>.
             <?php echo $this->printAnyErrorForField('timezone'); ?>
            </label>
           </div>
          </fieldset>
          <fieldset>
           <legend>Are you human?</legend>
           <div class="field">
            <img src="<?php echo $_SERVER['PHP_SELF']; ?>?captchaImage=<?php echo $captcha->getId(); ?>" width="175" height="50" alt="Captcha Image" style="margin: 2px 0; border: 1px solid #000000; " />
            <input type="text" name="captcha_value" value="" />
            <input type="hidden" name="captcha" value="<?php echo $captcha->getId(); ?>" />
            <label>
             <em>Verification Code</em>helps prevent bots from registering users. Please type the code in the above image to verify you are at least part human.
             <?php echo $this->printAnyErrorForField('captcha'); ?>
            </label>
           </div>
          </fieldset>
          <fieldset class="submit">
           <div class="field">
            <input type="submit" name="submit_import_results" value="Register" />
           </div>
          </fieldset>
         </form>
        <?php
        */

        $this->printFooter();
    }


    public function initForm()
    {
        $form = new MXT_Form($_SERVER['PHP_SELF']);

        $fieldset = new MXT_Fieldset('user_info', 'User Information');
            $fieldset->addField(new MXT_InputField(
                'username',
                'Username',
                'will be your sign in. Your username will also be visible to ' .
                    'other users on various parts of the site.',
                '',
                true
            ));
            $fieldset->addField(new MXT_InputField(
                'first_name',
                'First Name',
                'is your... first name! Currently we use first names and the ' .
                    'first initial of last names to list fantasy standings and ' .
                    'results. We encourage you to use your real name, but it is ' .
                    'important you know that your name will be visible to the public.',
                '',
                true
            ));
            $fieldset->addField(new MXT_InputField(
                'last_name',
                'Last Name Initial',
                'is used in conjunction with your first name.',
                '',
                true
            ));

            $emailField = new MXT_EmailField
            (
                'email',
                'E-mail Address',
                'is the e-mail address we should send your activation e-mail. We will not give away or sell your e-mail address (or other personal information), but we may send you reminders and other useful information. If you ever want us to stop contacting you, simply send us an e-mail.',
                '',
                true
            );
            $emailConfirmField = new MXT_EmailField
            (
                'email_confirm',
                'Email Address Confirmation',
                'is used to help verify that your e-mail address was typed correctly so that your account can be properly activated.',
                '',
                true
            );
            $fieldset->addField($emailField->makeConfirmableBy($emailConfirmField));
            $fieldset->addField($emailConfirmField);
        $form->addFieldset($fieldset);

        $fieldset = new MXT_Fieldset('password', 'Password');
            $passwordField = new MXT_PasswordField(
                'password',
                'Password',
                'makes sure that no other users can change information related to your account. Your password must be at least 6 characters.',
                '',
                true
            );
            $passwordConfirmField = new MXT_PasswordField
            (
                'password_confirm',
                'Password Confirmation',
                'verifies that you typed what you thought you typed as your password.',
                '',
                true
            );
            $fieldset->addField($passwordField->makeConfirmableBy($passwordConfirmField));
            $fieldset->addField($passwordConfirmField);
        $form->addFieldset($fieldset);

        $fieldset = new MXT_Fieldset('prefs', 'Preferences');
            $timezones = array(new MXT_Pair(100, 'Auto-detect'));
            for($i = -13; $i <= 13; $i++)
            {
                if($i == 0)
                    $timezones[] = new MXT_Pair($i, 'GMT');
                else
                    $timezones[] = new MXT_Pair($i, sprintf('GMT %+d', $i));
            }
            $fieldset->addField(new MXT_SetField(
                'timezone',
                'Timezone',
                'allows dates and times to be displayed in your timezone. It is recommended that you choose the auto-detect option, which allows us to attempt to determine your timezone everytime you sign in. If auto-detect does not properly detect your timezone, you can change your timezone preference in <i>Your Account</i> &raquo; <i>Preferences</i>.',
                $timezones,
                100
            ));
        $form->addFieldset($fieldset);

        $fieldset = new MXT_Fieldset('captcha', 'Are you human?');
            $fieldset->addField(new MXT_CaptchaField(
                'captcha',
                'Human Verification',
                'helps prevent bots from registering users. Please type the code in the above image to verify you are at least part human.'
            ));
        $form->addFieldset($fieldset);

        $fieldset = new MXT_Fieldset('submit');
            $fieldset->addField(new MXT_SubmitField(
                'submit_register',
                'Register'
            ));
        $form->addFieldset($fieldset);

        $form->setInputs($this->input);

        $this->form = $form;
    }
}



?>
