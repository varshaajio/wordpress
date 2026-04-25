<?php
/**
 *Plugin Name:Charity Fund Collection
 *Description:Simple charity campaigning code for receiving donations.Supports Offline Razorpay, and PayPal Donate.
 *Author:Varshaa
 *Text Domain:fundhelper
 */

classCharityFundCollection{
   privatestatic$instance=null;
   private$optionKey='cfOptions';
   private$options=[];

   publicstaticfunctioninit(){
      if(self::$instance===null)self::$instance=newself();
      returnself::$instance;
   }

   privatefunction__construct(){
      $this->options=get_option($this->optionKey,[
         'gateway'=>'offline',
         'razorpayKey'=>'',
         'razorpaySecret'=>'',
         'paypalEmail'=>'',
         'currency'=>'INR',
      ]);

      add_action('init',[$this,'registerPostTypes']);
      add_action('add_meta_boxes',[$this,'addCampaignMetabox']);
      add_action('save_post',[$this,'saveCampaignMeta']);
      add_action('admin_menu',[$this,'adminMenu']);
      add_action('admin_init',[$this,'registerSettings']);
      add_shortcode('cfCampaigns',[$this,'scCampaigns']);
      add_shortcode('cfCampaign',  [$this,'scCampaign']);
      add_shortcode('cfDonate',   [$this,'scDonate']);
      add_action('wp_enqueue_scripts',[$this,'enqueueAssets']);
      add_action('init',[$this,'handleOfflineDonationSubmit']);
      add_action('rest_api_init',[$this,'registerRestRoutes']);
   }

   publicfunctionregisterPostTypes(){
      register_post_type('cfCampaign',[
         'labels'=>[
            'name'=>'Campaigns',
            'singular_name'=>'Campaign',
            'add_new_item'=>'AddNewCampaign',
            'edit_item'=>'EditCampaign',
         ],
         'public'=>true,
         'has_archive'=>true,
         'supports'=>['title','editor','thumbnail'],
         'menu_icon'=>'dashicons-heart',
         'rewrite'=>['slug'=>'campaigns'],
         'show_in_rest'=>true,
      ]);

      register_post_type('cfDonation',[
         'labels'=>[
            'name'=>'Donations',
            'singular_name'=>'Donation',
         ],
         'public'=>false,
         'show_ui'=>true,
         'supports'=>['title'],
         'menu_icon'=>'dashicons-tickets',
      ]);
   }

   publicfunctionaddCampaignMetabox(){
      add_meta_box(
         'cfCampaignMeta',
         'CampaignDetails',
         [$this,'renderCampaignMetabox'],
         'cfCampaign',
         'side',
         'high'
      );
   }

   publicfunctionrenderCampaignMetabox($post){
      wp_nonce_field('cfSaveCampaign','cfCampaignNonce');
      $goal    =get_post_meta($post->ID,'cfGoal',true);
      $raised   =get_post_meta($post->ID,'cfRaised',true);
      $deadline=get_post_meta($post->ID,'cfDeadline',true);
      ?>
      <p><labelfor="cfGoal"><strong>GoalAmount(<?phpechoesc_html($this->currency());?>)</strong></label>
      <inputtype="number"min="0"step="0.01"id="cfGoal"name="cfGoal"class="widefat"value="<?phpechoesc_attr($goal);?>"></p>

      <p><labelfor="cfRaised"><strong>RaisedAmount(<?phpechoesc_html($this->currency());?>)</strong></label>
      <inputtype="number"min="0"step="0.01"id="cfRaised"name="cfRaised"class="widefat"value="<?phpechoesc_attr($raised);?>"></p>

      <p><labelfor="cfDeadline"><strong>Deadline(YYYY-MM-DD)</strong></label>
      <inputtype="date"id="cfDeadline"name="cfDeadline"class="widefat"value="<?phpechoesc_attr($deadline);?>"></p>
      <?php
   }

   publicfunctionsaveCampaignMeta($postId){
      if(!isset($_POST['cfCampaignNonce'])||!wp_verify_nonce($_POST['cfCampaignNonce'],'cfSaveCampaign'))return;
      if(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE)return;
      if(!current_user_can('edit_post',$postId))return;

      if(isset($_POST['cfGoal']))    update_post_meta($postId,'cfGoal',$this->num($_POST['cfGoal']));
      if(isset($_POST['cfRaised']))   update_post_meta($postId,'cfRaised',$this->num($_POST['cfRaised']));
      if(isset($_POST['cfDeadline']))update_post_meta($postId,'cfDeadline',sanitize_text_field($_POST['cfDeadline']));
   }

   publicfunctionadminMenu(){
      add_options_page('CharityFundraising','CharityFundraising','manage_options','cfSettings',[$this,'settingsPage']);
   }

   publicfunctionregisterSettings(){
      register_setting('cfSettingsGroup',$this->optionKey,[$this,'sanitizeOptions']);

      add_settings_section('cfMain','GeneralSettings','__return_false','cfSettings');

      add_settings_field('gateway','PaymentGateway',[$this,'fieldGateway'],'cfSettings','cfMain');
      add_settings_field('currency','Currency',[$this,'fieldCurrency'],'cfSettings','cfMain');
      add_settings_field('razorpay','RazorpayKeys',[$this,'fieldRazorpay'],'cfSettings','cfMain');
      add_settings_field('paypal','PayPalEmail',[$this,'fieldPaypal'],'cfSettings','cfMain');
   }

   publicfunctionsanitizeOptions($opts){
      $out=$this->options;
      $out['gateway']=in_array($opts['gateway']??'offline',['offline','razorpay','paypal'],true)?$opts['gateway']:'offline';
      $out['currency']=preg_replace('/[^A-Z]/','',strtoupper($opts['currency']??'INR'));
      $out['razorpayKey']=sanitize_text_field($opts['razorpayKey']??'');
      $out['razorpaySecret']=sanitize_text_field($opts['razorpaySecret']??'');
      $out['paypalEmail']=sanitize_email($opts['paypalEmail']??'');
      return$out;
   }

   publicfunctionsettingsPage(){
      ?>
      <divclass="wrap">
         <h1>CharityFundraising</h1>
         <formmethod="post"action="options.php">
            <?php
            settings_fields('cfSettingsGroup');
            do_settings_sections('cfSettings');
            submit_button();
            ?>
         </form>
      </div>
      <?php
   }

   publicfunctionfieldGateway(){
      $g=$this->options['gateway']??'offline';
      ?>
      <selectname="<?phpechoesc_attr($this->optionKey);?>[gateway]">
         <optionvalue="offline"<?phpselected($g,'offline');?>>Offline/Test(nocharge)</option>
         <optionvalue="razorpay"<?phpselected($g,'razorpay');?>>RazorpayCheckout</option>
         <optionvalue="paypal"<?phpselected($g,'paypal');?>>PayPalDonate</option>
      </select>
      <?php
   }

   publicfunctionfieldCurrency(){
      $c=$this->currency();
      ?>
      <inputtype="text"name="<?phpechoesc_attr($this->optionKey);?>[currency]"value="<?phpechoesc_attr($c);?>"class="regular-text"/>
      <pclass="description">3-letterISOcode(e.g.,INR,USD,EUR)</p>
      <?php
   }

      publicfunctionfieldRazorpay(){
      ?>
      <inputtype="text"name="<?phpechoesc_attr($this->optionKey);?>[razorpayKey]" 
            value="<?phpechoesc_attr($this->options['razorpayKey']);?>" 
            placeholder="KeyID"class="regular-text"/>
      <br>
      <inputtype="text"name="<?phpechoesc_attr($this->optionKey);?>[razorpaySecret]" 
            value="<?phpechoesc_attr($this->options['razorpaySecret']);?>" 
            placeholder="KeySecret"class="regular-text"/>
      <pclass="description">
         RequiredifRazorpayisselected.Remembertosetupwebhooksto: 
         <?phpechoesc_url($this->webhookUrl());?>
      </p>
      <?php
   }
   publicfunctionfieldPaypal(){
      ?>
      <inputtype="email"name="<?phpechoesc_attr($this->optionKey);?>[paypalEmail]" 
            value="<?phpechoesc_attr($this->options['paypalEmail']);?>" 
            class="regular-text"/>
      <pclass="description">PayPalbusinessemailfordonations.</p>
      <?php
   }
   publicfunctionscCampaigns($atts=[]){
      $q=newWP_Query([
         'post_type'=>'cfCampaign',
         'posts_per_page'=>-1,
         'post_status'=>'publish',
         'orderby'=>'date',
         'order'=>'DESC',
      ]);

      ob_start();
      echo'<divclass="cf-campaigns">';
      if($q->have_posts()){
         while($q->have_posts()){
            $q->the_post();
            $id=get_the_ID();
            $goal=$this->num(get_post_meta($id,'cfGoal',true));
            $raised=$this->num(get_post_meta($id,'cfRaised',true));
            $pct=$goal>0?min(100,round(($raised/$goal)*100)):0;

            echo'<divclass="cf-campaign-card">';
            if(has_post_thumbnail()){
               echo'<divclass="cf-thumb">'.get_the_post_thumbnail($id,'medium').'</div>';
            }
            echo'<h3class="cf-title"><ahref="'.esc_url(get_permalink()).'">'.esc_html(get_the_title()).'</a></h3>';
            echo'<divclass="cf-excerpt">'.wp_kses_post(wp_trim_words(get_the_content(),25)).'</div>';
            echo$this->progressBar($pct,$raised,$goal);
            echo'<divclass="cf-actions"><aclass="button"href="'.esc_url(get_permalink()).'">View&Donate</a></div>';
            echo'</div>';
         }
         wp_reset_postdata();
      }else{
         echo'<p>Nocampaignsyet.</p>';
      }
      echo'</div>';
      returnob_get_clean();
   }
   publicfunctionscCampaign($atts){
      $atts=shortcode_atts(['id'=>''],$atts);
      $id=intval($atts['id']?:get_the_ID());
      if(!$id||get_post_type($id)!=='cfCampaign')return'<p>Campaignnotfound.</p>';

      $post=get_post($id);
      $goal=$this->num(get_post_meta($id,'cfGoal',true));
      $raised=$this->num(get_post_meta($id,'cfRaised',true));
      $deadline=get_post_meta($id,'cfDeadline',true);
      $pct=$goal>0?min(100,round(($raised/$goal)*100)):0;

      ob_start();
      echo'<divclass="cf-campaign-single">';
      echo'<h2>'.esc_html(get_the_title($post)).'</h2>';
      if(has_post_thumbnail($id)){
         echo'<divclass="cf-thumb">'.get_the_post_thumbnail($id,'large').'</div>';
      }
      echo'<divclass="cf-content">'.apply_filters('the_content',$post->post_content).'</div>';
      echo$this->progressBar($pct,$raised,$goal);
      if(!empty($deadline)){
         echo'<pclass="cf-deadline"><strong>Deadline:</strong>'.esc_html($deadline).'</p>';
      }
      echodo_shortcode('[cfDonateid="'.intval($id).'"]');
      echo'</div>';
      returnob_get_clean();
   }
   publicfunctionscDonate($atts){
      $atts=shortcode_atts(['id'=>''],$atts);
      $campaignId=intval($atts['id']);
      if(!$campaignId||get_post_type($campaignId)!=='cfCampaign')return'<p>Invalidcampaign.</p>';

      $gateway=$this->options['gateway']??'offline';
      $currency=$this->currency();

      ob_start();
      ?>
      <formclass="cf-donate-form"method="post">
         <?phpwp_nonce_field('cfDonate','cfDonateNonce');?>
         <inputtype="hidden"name="cfCampaignId"value="<?phpechoesc_attr($campaignId);?>">
         <p><label><strong>Name</strong></label>
            <inputtype="text"name="cfName"required></p>
         <p><label><strong>Email</strong></label>
            <inputtype="email"name="cfEmail"required></p>
         <p><label><strong>Amount(<?phpechoesc_html($currency);?>)</strong></label>
            <inputtype="number"name="cfAmount"min="1"step="0.01"required></p>
         <p><label><inputtype="checkbox"name="cfAnonymous"value="1">Donateanonymously</label></p>

         <?phpif($gateway==='paypal'&&!empty($this->options['paypalEmail'])):?>
            <inputtype="hidden"name="cfGateway"value="paypal">
            <buttontype="submit"class="buttonbutton-primary">DonatewithPayPal</button>
         <?phpelseif($gateway==='razorpay'&&!empty($this->options['razorpayKey'])):?>
            <inputtype="hidden"name="cfGateway"value="razorpay">
            <buttontype="submit"class="buttonbutton-primary">DonatewithRazorpay</button>
            <pclass="description">YouwillseeasecureRazorpaycheckoutpopup.</p>
         <?phpelse:?>
            <inputtype="hidden"name="cfGateway"value="offline">
            <buttontype="submit"class="button">RecordTestDonation</button>
            <pclass="description">Offline/Testmoderecordsthedonationwithoutcharging.</p>
         <?phpendif;?>
      </form>
      <?php
      returnob_get_clean();
   }
   privatefunctionprogressBar($pct,$raised,$goal){
      $currency=$this->currency();
      $pct=max(0,min(100,intval($pct)));
      $raisedFmt=number_format((float)$raised,2);
      $goalFmt=number_format((float)$goal,2);
      return'<divclass="cf-progress"><divclass="cf-bar"style="width:'.$pct.'%"></div></div>'
          .'<divclass="cf-progress-label"><strong>'.$pct.'%</strong>funded—'.$currency.''.$raisedFmt.'raisedof'.$currency.''.$goalFmt.'</div>';
   }
   privatefunctioncurrency(){
      return$this->options['currency']??'INR';
   }
   privatefunctionnum($v){
      returnis_numeric($v)?(float)$v:0.0;
   }
   privatefunctionwebhookUrl(){
      returnrest_url('cf/v1/razorpay-webhook');
   }
}

CharityFundCollection::init();
