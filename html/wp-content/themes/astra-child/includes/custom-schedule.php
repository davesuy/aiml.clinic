<?php

function gf_custom_schedule_field() {

if (class_exists('GF_Field')) {

    class CustomSchedule extends GF_Field {


        public $type = 'custom_schedule';
     


        // The rest of the code is added here...

        public function get_form_editor_field_title() {
            return esc_attr__('Custom Schedule', 'txtdomain');
        }

        public function get_form_editor_field_settings() {
            return [
                'label_setting',
                //'choices_setting',
                'description_setting',
                'rules_setting',
                'error_message_setting',
                'css_class_setting',
                'conditional_logic_field_setting',
                'prepopulate_field_setting',
                'placeholder_setting',
                'label_placement_setting',
                'error_message_setting',
                'css_class_setting',
                'size_setting',
                'enable_enhanced_ui_setting'
            ];
        }

        public function is_value_submission_array() {
            return true;
        }

        public function get_field_input($form, $value = '', $entry = null) {

            $postid = 1684; 
            $jet_apb_post_meta = 'jet_apb_post_meta';
            $custom_schedules = get_post_meta(1684, 'jet_apb_post_meta' , true);
            
            if ($this->is_form_editor()) {
                return '';
            }

            $id = (int) $this->id;

      
           
            $work_name = "input_".$id."[workingDays]";

            $output .= '<div id="repeater">
            <!-- Repeater Heading -->
            <div class="repeater-heading">
                <h5 class="pull-left">Repeater</h5>
                <button class="btn btn-primary pt-5 pull-right repeater-add-btn">
                    Add
                </button>
            </div>
            <div class="clearfix"></div>
            <!-- Repeater Items -->
            <div class="items" data-group="'.$work_name.'">
                <!-- Repeater Content -->
                <div class="item-content">
                    <div class="form-group">
                        <label for="inputStartDate" class="col-lg-2 control-label">Start Date</label>
                        <div class="col-lg-10">
                            <input class="wrk_in" type="text" class="form-control" id="inputStartDate" placeholder="Start Date" data-name="start-date">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputEndDate" class="col-lg-2 control-label">End Date</label>
                        <div class="col-lg-10">
                            <input class="wrk_in" type="text" class="form-control" id="inputEndDate" placeholder="End Date" data-name="end-date">
                        </div>
                    </div>
                </div>
                <!-- Repeater Remove Btn -->
                <div class="pull-right repeater-remove-btn">
                    <button class="btn btn-danger remove-btn">
                        Remove
                    </button>
                </div>
                <div class="clearfix"></div>
            </div>       
        </div>';
       // $outputx = '<input name="test" />';
           return $output;
          
        }



        public function get_value_save_entry($value, $form, $input_name, $lead_id, $lead) {

            $postid = 1684; 
            $jet_apb_post_meta = 'jet_apb_post_meta';
            $custom_schedules = get_post_meta(1684, 'jet_apb_post_meta' , true);
            $post_id_arr = array('ID' => $postid);

           

       

                $working_start = $value['workingDays'][0]['start-date'];
               $working_end = $value['workingDays'][0]['end-date'];
               $working_start1 = $value['workingDays'][1]['start-date'];
               $working_end1 = $value['workingDays'][1]['end-date'];

                $add_cal = array(
                    'start' => $working_start,
                    'startTimeStamp' => '1628611200000',
                    'end' => $working_end,
                    'endTimeStamp' => '629129600000',
                    'name' => 'Days 0',
                    'type' => 'working_days'
        
                );

                
                $add_cal1 = array(
                    'start' => $working_start1,
                    'startTimeStamp' => '1628611200000',
                    'end' => $working_end1,
                    'endTimeStamp' => '629129600000',
                    'name' => 'Days 0',
                    'type' => 'working_days'
        
                );

                $count_val = count($value['workingDays']) -1;

    
              
                for ($x = 0; $x <= $count_val; $x++) {

                    
                   // $add_cal_items = array();
                  
                    if($value['workingDays'][0]['start-date'] != '' || $value['workingDays'][0]['end-date'] != '') {

                        $add_cal_items[$x] = array(

                            'start' => $value['workingDays'][$x]['start-date'],
                            'startTimeStamp' => '1628611200000',
                            'end' =>$value['workingDays'][$x]['end-date'],
                            'endTimeStamp' => '629129600000',
                            'name' => 'Days 0',
                            'type' => 'working_days'
                
                        );

                    } else {
                        $add_cal_items = array();
                    }
    
                }
                    

                $custom_schedules['custom_schedule']['working_days'] = $add_cal_items;
               // $working_days_arr = $custom_schedules['custom_schedule']['working_days'][] =  $add_cal;
        echo '<pre>'.print_r( $value, true).'</pre>';
         // echo '<pre>'.print_r( count($value['workingDays']), true).'</pre>';
         
            update_post_meta($postid, $jet_apb_post_meta, $custom_schedules);
             
                //$working_days_arr['custom_schedule'] =  $this->workingDaysfindandReplace($custom_schedules, $working_start , $working_end, $add_cal, $add_cal1);
               
                
                //$working_days_postid_merge = array_merge($working_days_arr, $post_id_arr );
                
   
                 
            // update_post_meta($postid, $jet_apb_post_meta, $working_days_postid_merge);
             
       

       
            
            //$a = '<pre>'.print_r($value, true).'</pre>';

 

            return $value;
        }



        public function get_value_entry_list($value, $entry, $field_id, $columns, $form) {
            return __('See cs Edit Entry details', 'txtdomain');
        }

        public function get_value_entry_detail($value, $currency = '', $use_text = false, $format = 'html', $media = 'screen') {

            $value = maybe_unserialize($value);	

            if (empty($value)) {

                return '';
            }

            return $value;
           
        }


        public function is_value_submission_empty($form_id) {
            // $value = rgpost('input_' . $this->id);
            // foreach ($value as $input) {
            //     if (strlen(trim($input)) > 0) {
            //         return false;
            //     }
            // }
            // return true;
        }
        
   

        

    }

    
    GF_Fields::register(new CustomSchedule());

}




}

add_action('init', 'gf_custom_schedule_field');

