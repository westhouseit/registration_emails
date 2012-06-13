<?php
if(!class_exists('EE_Template')){
	require APPPATH.'libraries/Template'.EXT;
}

/**
 * Registration_emails_EE_Template
 * Simple class that adds a little functionality for template parsing
 */
class Registration_emails_EE_Template extends EE_Template
{
	/**
	 * run_template_engine
	 *
	 * Does the same functions as EE_Template::run_template_engine, with an added param to
	 * parse tags from an array
	 *
	 * @param	string
	 * @param	string
	 * @param 	array
	 * @return	void
	 */	
	public function run_template_engine($template_group = '', $template = '', $variables = '')
	{
		$this->log_item(" - Begin Template Processing - ");
				
		// Set the name of the cache folder for both tag and page caching
		
		if ($this->EE->uri->uri_string != '')
		{
			$this->t_cache_path .= md5($this->EE->functions->fetch_site_index().$this->EE->uri->uri_string).'/';
			$this->p_cache_path .= md5($this->EE->functions->fetch_site_index().$this->EE->uri->uri_string).'/';		
		}
		else
		{
			$this->t_cache_path .= md5($this->EE->config->item('site_url').'index'.$this->EE->uri->query_string).'/';
			$this->p_cache_path .= md5($this->EE->config->item('site_url').'index'.$this->EE->uri->query_string).'/';
		}
		
		// We limit the total number of cache files in order to
		// keep some sanity with large sites or ones that get
		// hit by over-ambitious crawlers.
		if ($this->disable_caching == FALSE)
		{		
			if ($dh = @opendir(APPPATH.'cache/page_cache'))
			{
				$i = 0;
				while (FALSE !== (readdir($dh)))
				{
					$i++;
				}
				
				$max = ( ! $this->EE->config->item('max_caches') OR ! is_numeric($this->EE->config->item('max_caches')) OR $this->EE->config->item('max_caches') > 1000) ? 1000 : $this->EE->config->item('max_caches');
				
				if ($i > $max)
				{
					$this->EE->functions->clear_caching('page');
				}
			}
		}
		
		$this->log_item("URI: ".$this->EE->uri->uri_string);
		$this->log_item("Path.php Template: {$template_group}/{$template}");
		
		// only added $variables to the call
		$this->fetch_and_parse($template_group, $template, FALSE, '', $variables);
		
		$this->log_item(" - End Template Processing - ");
		$this->log_item("Parse Global Variables");

		if ($this->template_type == 'static')
		{
			$this->final_template = $this->restore_xml_declaration($this->final_template);
		}
		else
		{
			$this->final_template = $this->parse_globals($this->final_template);
		}
		
		$this->log_item("Template Parsing Finished");
	
		$this->EE->output->out_type = $this->template_type;
		$this->EE->output->set_output($this->final_template); 
	}
	
	/**
	 * fetch_and_parse
	 * Simply added the $variables parameter to function.
	 */
	public function fetch_and_parse($template_group = '', $template = '', $sub = FALSE, $site_id = '', $variables = '')
	{
		// add this template to our subtemplate tracker
		$this->templates_sofar = $this->templates_sofar.'|'.$site_id.':'.$template_group.'/'.$template.'|';
		
		// Fetch the requested template
		// The template can either come from the DB or a cache file
		// Do not use a reference!
		
		$this->cache_status = 'NO_CACHE';
		
		$this->log_item("Retrieving Template");
		
		$this->template = ($template_group != '' AND $template != '') ? 
			$this->fetch_template($template_group, $template, FALSE, $site_id) : 
			$this->parse_template_uri();
		
		$this->log_item("Template Type: ".$this->template_type);
		
		// this is the section that is added to this function
		if(is_array($variables)){
			foreach($variables as $k => $v){
				$this->template = str_replace(LD.$k.RD, $v, $this->template);
			}
		}
		//echo '<pre>';print_r($this);echo'</pre>';
		//var_dump(LD);var_dump(RD);
		//exit;
		
		$this->parse($this->template, $sub, $site_id);
	}
}
