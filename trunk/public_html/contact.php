<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/LightDataSysResponder.php';
require_once PATH_LIB . 'com/mephex/captcha/Captcha.php';
require_once PATH_LIB . 'com/mephex/core/Pair.php';
require_once PATH_LIB . 'com/mephex/form/Form.php';
require_once PATH_LIB . 'com/mephex/form/FormError.php';
require_once PATH_LIB . 'com/mephex/form/field/CaptchaField.php';
require_once PATH_LIB . 'com/mephex/form/field/EmailField.php';
require_once PATH_LIB . 'com/mephex/form/field/InputField.php';
require_once PATH_LIB . 'com/mephex/form/field/PasswordField.php';
require_once PATH_LIB . 'com/mephex/form/field/SetField.php';
require_once PATH_LIB . 'com/mephex/form/field/SubmitField.php';
require_once PATH_LIB . 'com/mephex/form/field/TextareaField.php';
require_once PATH_LIB . 'com/mephex/form/fieldset/Fieldset.php';
require_once PATH_LIB . 'com/mephex/form/outputter/DescriptiveFormOutputter.php';
require_once PATH_LIB . 'com/mephex/input/EmailInput.php';
require_once PATH_LIB . 'com/mephex/user/User.php';


class ContactResponder extends LightDataSysResponder
{
    protected $form;
    protected $subjects;
    protected $mailer;


    public function init($args, $cacheDir = null)
    {
        parent::init($args, $cacheDir);

        $this->initSubjects();
        $this->initForm();

        $this->mailer = $this->getMailer();
    }


    public function post($args)
    {
        $this->form->setValuesUsingInput($this->input);
        if($this->form->validate())
        {
            $mailer = $this->mailer;
            $name = $this->form->getValue('contact_info', 'from_name');
            $email = $this->form->getValue('contact_info', 'from_email');
            $subject = $this->form->getValue('message', 'subject')->right;
            $message  = $this->form->getValue('message', 'message');
            $success = $mailer->sendMessage('commissioner@lightdatasys.com'
                , $email, $subject, 'From: ' . $name . "\n" . $message);

            if($success === true)
            {
                $this->printHeader();
                ?>
                 <div class="info-message">
                  Thank you for your e-mail.
                 </div>
                <?php
                $this->printFooter();
            }
            else
            {
                $this->form->addError(new MXT_FormError($this->form, 0, 'Your e-mail could not be sent due to technical difficulties. Please try again later.'));

                $this->get($args);
            }
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

        $this->printFooter();
    }


    public function initSubjects()
    {
        $this->subjects = array
        (
            null,
            new MXT_Pair(1, 'Fantasy Football'),
            new MXT_Pair(2, 'Fantasy Racing'),
            new MXT_Pair(3, 'Technical Support'),
            new MXT_Pair(4, 'Other')
        );
    }

    public function initForm()
    {
        $this->form = new MXT_Form($_SERVER['PHP_SELF']);

        $fieldset = new MXT_Fieldset('contact_info', 'Contact Information');
            $fieldset->addField
            (
                new MXT_InputField
                (
                    'from_name',
                    'Your Name',
                    '... because you\'re not just a number!',
                    '',
                    true
                )
            );
            $fieldset->addField
            (
                new MXT_EmailField
                (
                    'from_email',
                    'Your E-mail Address',
                    'We need your e-mail address so we can reply to you.',
                    '',
                    true
                )
            );
        $this->form->addFieldset($fieldset);

        $fieldset = new MXT_Fieldset('message', 'Message');
            $fieldset->addField
            (
                new MXT_SetField
                (
                    'subject',
                    'Subject',
                    'This gives us a general idea of why you are contacting us. ',
                    $this->subjects,
                    '',
                    true
                )
            );
            $fieldset->addField
            (
                new MXT_TextareaField
                (
                    'message',
                    'Message',
                    'What do you want us to know? Please, let it all out!',
                    '',
                    true
                )
            );
        $this->form->addFieldset($fieldset);

        $fieldset = new MXT_Fieldset('captcha', 'Human Verification');
            $fieldset->addField
            (
                new MXT_CaptchaField
                (
                    'captcha',
                    'Verification Code',
                    'To prevent spam generated by bots, we require that the code from the verification image be typed.'
                )
            );
        $this->form->addFieldset($fieldset);

        $fieldset = new MXT_Fieldset('submit');
            $fieldset->addField
            (
                new MXT_SubmitField
                (
                    'submit_send',
                    'Send'
                )
            );
        $this->form->addFieldset($fieldset);

        $this->form->setInputs($this->input);
    }
}



?>
