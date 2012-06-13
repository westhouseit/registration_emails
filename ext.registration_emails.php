<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Registration_emails
 *
 * This extension takes newly published entries in a channel and uses the data in 
 * specific fields to create directories in the file structure.
 * 
 * @package   Registration Emails
 * @version   1.0
 * @author    Justin Koivisto <justin.koivisto@gmail.com>
 * @copyright Copyright © 2012 Justin Koivisto
 * @license   LGPL: http://www.gnu.org/licenses/lgpl.html
 * @link      http://koivi.com/ee-registration-emails
 */

class Registration_emails_ext {
// =================================================
// required variables for EE 2.0 extensions
// =================================================
  public $settings         = array();
    public $name           =  'Registration Emails';
    public $version        =  '1.0';
    public $description    =  'Custom registration welcome and admin notification emails using templates';
    public $settings_exist =  'y';
    public $docs_url       =  'http://koivi.com/Registration Emails';
  
  // internal EE object by reference
  protected $EE = NULL;
  
// =================================================
// this extension's custom variables for operation
// =================================================
  // somewhere to store error messages
  protected $errors = array();
  
  // default channel fields that are in the 
  
  
// =================================================
// required methods for EE 2.0 extensions
// =================================================
  public function __construct($settings='')
  {
    $this->EE =& get_instance();
    $this->settings = $settings;
  }

  /**
   * what to do when the extension is activated
   */
  public function activate_extension()
  {
      $this->EE->db->insert('extensions', array(
        'class'     => __CLASS__,
        'method'    => 'member_member_register',
        'hook'      => 'member_member_register',
        'settings'  => '',
        'priority'  => 10, // last thing to fire
        'version'   => $this->version,
        'enabled'   => 'y'
      ));
  }
  
  public function update_extension($current=''){
      if($current == '' || $current == $this->version){
          return FALSE;
      }
  
      if($current < '1.0'){
          // Update to version 1.0
      }
  
      $this->EE->db->where('class', __CLASS__);
      $this->EE->db->update(
        'extensions',
        array('version' => $this->version)
      );
  }

  /**
   * what to do when the extension is disabled
   */
  public function disable_extension()
  {
      $this->EE->db->where('class', __CLASS__);
      $this->EE->db->delete('extensions');
  }
  
  /**
   * The function that creates the admin settings page
   * @access public
   */
  public function settings()
  {
      $settings = array();
  
      // Creates a text input
      $settings['system_from_email'] = array('i', '', '');
      $settings['system_from_name'] = array('i', '', '');
      $settings['date_format'] = array('i', '', 'Y-m-d H:i e');
      
      $settings['send_notification'] = array('s', array('y' => lang('yes'), 'n' => lang('no')), 'y');
      $settings['notification_email'] = array('i', '', '');
      $settings['notification_subject'] = array('i', '', lang('default_notification_subject'));
      $settings['notification_template'] = array('i', '', '');
      
      $settings['send_welcome'] = array('s', array('y' => lang('yes'), 'n' => lang('no')), 'y');
      $settings['welcome_subject'] = array('i', '', lang('default_welcome_subject'));
      $settings['welcome_template'] = array('i', '', '');
      
      // General pattern:
      //
      // $settings[variable_name] => array(type, options, default);
      //
      // variable_name: short name for the setting and the key for the language file variable
      // type:          i - text input, t - textarea, r - radio buttons, c - checkboxes, s - select, ms - multiselect
      // options:       can be string (i, t) or array (r, c, s, ms)
      // default:       array member, array of members, string, nothing
  
      return $settings;
  }
  
// =================================================
// this extension's custom methods for operation
// =================================================
  /**
   * get any error messages that we came across
   * @param bool $html Whether or not to return HTML formatted errors
   * @return string
   */
  public function error($html=TRUE)
  {
    ob_start();
    foreach($this->errors as $e){
      if($html) echo '<p class="errorMsg">';
      echo $e;
      if($html) echo '</p>';
      echo "\n";
    }
    return ob_get_clean();
  }

// =================================================
// methods called on hooks
// =================================================
  /**
   * Called method for this extension at hook "entry_submission_absolute_end"
   * @param int $entry_id entry_id of submitted entry
   * @param array $fields Array of entry's metadata (channel_id, entry_date, i.e. fields for exp_channel_titles.)
   * @param array $data Array of entry's field data
   */
  public function member_member_register($entry_id, $fields, $data)
  {
    // Firstly, check if we want to send a notification email out
    if($this->settings['send_notification'] == 'y'){
      if($this->settings['notification_email'] && $this->settings['system_from_email']){
        // we have an address to attempt to send to
        if($this->settings['notification_template']){
          // admin template had some value there
          $tpl = explode('/', $this->settings['notification_template']);

          if(!class_exists('Registration_emails_EE_Template')){
            require_once dirname(__FILE__).'/Registration_emails_EE_Template.php';
          }
          $TMPL = new Registration_emails_EE_Template();
          $TMPL->run_template_engine($tpl[0], $tpl[1], $this->get_member_data($entry_id));
          $message_body = $TMPL->final_template;
          $this->send_notification($message_body);
        }else{
          $this->errors[] = 'No notification email template was defined.';
        }
      }else{
        $this->errors[] = 'You must have both a system sender email and notification recipeint email address defined in settings.';
        return FALSE;
      }
    }
    
    if($this->settings['send_welcome'] == 'y'){
      if($this->settings['welcome_email'] && $this->settings['system_from_email']){
        // we have an address to attempt to send to
        if($this->settings['welcome_template']){
          // admin template had some value there
          $tpl = explode('/', $this->settings['welcome_template']);

          if(!class_exists('Registration_emails_EE_Template')){
            require_once dirname(__FILE__).'/Registration_emails_EE_Template.php';
          }
          $TMPL = new Registration_emails_EE_Template();
          $vars = $this->get_member_data($entry_id);
          $TMPL->run_template_engine($tpl[0], $tpl[1], $vars);
          $message_body = $TMPL->final_template;
          $this->send_welcome($message_body, $vars['email']);
        }else{
          $this->errors[] = 'No welcome email template was defined.';
        }
      }else{
        $this->errors[] = 'You must have a system sender email address defined in settings.';
        return FALSE;
      }
    }
  }

  function get_member_data($entry_id){
    // I want to pull all possible information I can about this user now including
    // custom fields so I can use those in the email templates
    $member_fields = array();
    $sql = 'SELECT
        m_field_id
        , m_field_name as field_name
        , m_field_label as field_label
        , m_field_description as field_description
      FROM exp_member_fields
      WHERE m_field_reg = \'y\'
      ORDER BY m_field_order ASC';
    $result = $this->EE->db->query($sql);
    if($result->num_rows() > 0){
      foreach($result->result_array() as $row){
        $id = intval($row['m_field_id']);
        unset($row['m_field_id']);
        $member_fields['m_field_id_'.$id] = $row;
      }
    }
    
    $member_data = array();
    $sql = 'SELECT DISTINCT
      exp_members.group_id
      , exp_members.username
      , exp_members.screen_name
      , exp_members.email
      , exp_members.url
      , exp_members.location
      , exp_members.ip_address
      , exp_members.join_date
      , exp_members.language
      , exp_members.time_format
      , exp_members.timezone
      , exp_members.daylight_savings
      , exp_member_groups.group_title
      , exp_member_groups.group_description
      , exp_member_data.*
    FROM exp_members
    INNER JOIN exp_member_groups ON exp_member_groups.group_id = exp_members.group_id
    INNER JOIN exp_member_data ON exp_member_data.member_id = exp_members.member_id
    WHERE exp_members.username = \''.$entry_id['username'].'\'';
    $result = $this->EE->db->query($sql);
    if($result->num_rows() > 0){
      $member_data = $result->result_array();
      $member_data = $member_data[0];
    }
    foreach($member_fields as $field => $ar){
      if(isset($member_data[$field])){
        $member_data[$ar['field_name']] = $member_data[$field]; 
        $member_data[$ar['field_name'].'_label'] = $ar['field_label'];
        $member_data[$ar['field_name'].'_name'] = $ar['field_name'];
        $member_data[$ar['field_name'].'_description'] = $ar['field_description'];
        unset($member_data[$field]);
      }
    }
    return $member_data;
  }
  
  function send_notification($message_body){
    $this->EE->load->library('email');
    $this->EE->load->helper('text');
    $this->EE->email->mailtype = 'text';
    $this->EE->email->from($this->settings['system_from_email'], $this->settings['system_from_name']);
    $this->EE->email->to($this->settings['notification_email']);
    $this->EE->email->subject($this->settings['notification_subject']);
    $this->EE->email->message(entities_to_ascii($message_body));
    $x = $this->EE->email->Send();
  }
  
  function send_welcome($message_body, $recip){
    $this->EE->load->library('email');
    $this->EE->load->helper('text');
    $this->EE->email->mailtype = 'text';
    $this->EE->email->from($this->settings['system_from_email'], $this->settings['system_from_name']);
    $this->EE->email->to($recip);
    $this->EE->email->subject($this->settings['welcome_subject']);
    $this->EE->email->message(entities_to_ascii($message_body));
    $x = $this->EE->email->Send();
  }
}
