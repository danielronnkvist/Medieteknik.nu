<?php
class Group_model extends CI_Model
{

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }

    function get_all_groups()
    {
    	$this->db->select("groups.id, groups.official, groups_descriptions_language.name, groups_descriptions_language.description,  COUNT(groups_year.groups_id) as count");
		$this->db->from("groups");
		$this->db->join("groups_descriptions_language", "groups.id = groups_descriptions_language.groups_id", "");
		$this->db->join("groups_year", "groups.id = groups_year.groups_id", "left");
		$this->db->group_by("groups.id");

		$query = $this->db->get();

        return $query->result();
    }

	function get_group($id)
	{
		$this->db->select("groups.id, groups.official, groups_descriptions_language.name, groups_descriptions_language.description");
		$this->db->from("groups");
		$this->db->join("groups_descriptions_language", "groups.id = groups_descriptions_language.groups_id", "");
		$this->db->where("groups.id", $id);
		$query = $this->db->get();
		$result = $query->result();

		foreach($result as &$res)
		{
			$res->members = $this->get_group_members($res->id);
		}

        return $result;
	}

	/**
	 * fetches a specific page, admin-style => more data included
	 *
	 * @param  integer	$id		The ID of the news item
	 * @return array
	 */
	function admin_get_group($id)
    {
		$this->db->select("*");
		$this->db->from("groups");
		$this->db->from("language");
		$this->db->join("groups_descriptions", 'groups_descriptions.groups_id = groups.id AND groups_descriptions.lang_id = language.id', 'left');
		$this->db->where("groups.id",$id);
		$query = $this->db->get();
		$translations = $query->result();

		$this->db->select("groups.id, groups.official, groups_descriptions_language.name");
		$this->db->from("groups");
		$this->db->join("groups_descriptions_language", "groups.id = groups_descriptions_language.groups_id", "");
		$this->db->where("groups.id",$id);
		$this->db->limit(1);
		$query = $this->db->get();
		$group_array = $query->result();
		$group = $group_array[0];

		$group->translations = array();

		foreach($translations as $t)
		{
			array_push($group->translations, $t);
		}

		return $group;

	}

	function get_group_members($id)
	{
		$this->db->select("groups_year.id, groups_year.start_year, groups_year.stop_year");
		$this->db->select("users.first_name, users.last_name");
		$this->db->select("groups_year_members.position, groups_year_members.email, groups_year_members.user_id");
		$this->db->select("users_data.gravatar");
		$this->db->from("groups");
		$this->db->join("groups_year", "groups_year.groups_id = groups.id", 'left');
		$this->db->join("groups_year_members", "groups_year_members.groups_year_id = groups_year.id", 'left');
		$this->db->join("users", "users.id = groups_year_members.user_id", 'left');
		$this->db->join("users_data", "users_data.users_id = users.id", 'left');
		$this->db->where("groups.id", $id);
		$query = $this->db->get();
		$result = $query->result();
		return $result;
	}

	function get_group_members_year($groups_year_id)
	{
		$this->db->select("groups_year.start_year, groups_year.stop_year");
		$this->db->select("users.first_name, users.last_name");
		$this->db->select("groups_year_members.position, groups_year_members.email, groups_year_members.user_id");
		$this->db->select("users_data.gravatar");
		$this->db->from("groups");
		$this->db->join("groups_year", "groups_year.groups_id = groups.id", 'left');
		$this->db->join("groups_year_members", "groups_year_members.groups_year_id = groups_year.id", 'left');
		$this->db->join("users", "users.id = groups_year_members.user_id", 'left');
		$this->db->join("users_data", "users_data.users_id = users.id", 'left');
		$this->db->where("groups_year_members.groups_year_id", $groups_year_id);
		$query = $this->db->get();
		$result = $query->result();
		return $result;
	}

	function get_group_year($groups_year_id)
	{
		$this->db->where('id', $groups_year_id);
		$this->db->from('groups_year');
		$query = $this->db->get();
		$result = $query->result();

		return $result[0];
	}

	function get_group_year_member($groups_year_id, $user_id)
	{
		$this->db->select("groups_year.start_year, groups_year.stop_year");
		$this->db->select("users.first_name, users.last_name");
		$this->db->select("groups_year_members.position, groups_year_members.email, groups_year_members.user_id");
		$this->db->select("users_data.gravatar");
		$this->db->from("groups");
		$this->db->join("groups_year", "groups_year.groups_id = groups.id", 'left');
		$this->db->join("groups_year_members", "groups_year_members.groups_year_id = groups_year.id", 'left');
		$this->db->join("users", "users.id = groups_year_members.user_id", 'left');
		$this->db->join("users_data", "users_data.users_id = users.id", 'left');
		$this->db->where("groups_year_members.groups_year_id", $groups_year_id);
		$this->db->where("groups_year_members.user_id", $user_id);
		$query = $this->db->get();
		$result = $query->result();
		return $result;
	}

	function get_group_years($id)
	{
		$this->db->select("groups_year.id, groups_year.start_year, groups_year.stop_year");
		$this->db->from("groups_year");
		$this->db->where("groups_year.groups_id", $id);
		$this->db->order_by("groups_year.start_year", "asc");
		$query = $this->db->get();
		$result = $query->result();

		foreach ($result as $key => $year) {
			// stupid way of doing this! Lots of database calls.
			$result[$key]->members = $this->Group_model->get_group_members_year($year->id);
		}

		return $result;
	}

	function group_exists($id)
	{
		$this->db->where('id', $id);
		$query = $this->db->get('groups');

		return $query->num_rows();
	}

	function get_group_name($name, $lang = 'se')
	{
		if (preg_match ('/[^A-Za-z0-9_]/i', $name))
		{
		    return null;
		}

		$use_name = uncompact_name($name);

		$this->db->select("groups.id, groups.group_name, groups_descriptions_language.description");
		$this->db->from("groups");
		$this->db->join("groups_descriptions_language", "groups.id = groups_descriptions_language.group_id", "");
		$this->db->where("groups.id IN (SELECT groups.id FROM groups WHERE groups.group_name REGEXP '^(".$use_name.")$' )");
		$this->db->limit(1);
		$query = $this->db->get();

	    return $query->result();
	}

	/**
	 * Create a new news
	 *
	 * @param  integer	$user_id		The ID os the user who creates the news
	 * @param  array 	$translations	All translations of the news item, array("lang_abbr" => "se", "title" => "Inte klistrad!", "text" => "Den här nyheten är inte klistrad eller översatt!")
	 * @param  date		$post_date		The date of the news item
	 * @param  integer	$draft			Specify if the news item is a draft, 1 = Draft, 0 = Not draft
	 * @param  integer	$approved		Specify if the news item is approved, 1 = Approved, 0 = Not approved
	 * @param  integer	$group_id		The id of the group the user belongs to when posting
	 * @return The news id
	 */
	function add_group($translations = array(), $official = 1)
	{
		if(!is_array($translations))
			return false;

		$arr_keys = array_keys($translations);
		if(!is_numeric($arr_keys[0]))
		{
			$theTranslations = array($translations);
		}
		else
		{
			$theTranslations = $translations;
		}

		foreach($theTranslations as &$translation)
		{
			$arr_keys = array_keys($translation);
			if((!in_array("lang_abbr",$arr_keys) && !in_array("lang",$arr_keys)) || !in_array("name",$arr_keys) || !in_array("description",$arr_keys)) {
				return false;
			}
			if(!in_array("lang_abbr",$arr_keys) && in_array("lang",$arr_keys)){
				$translation["lang_abbr"] = $translation["lang"];
			}
		}

		//if($use_transaction)
		$this->db->trans_begin();

		$data = array(
		   'official' => $official,
		);
		$this->db->insert('groups', $data);
		$group_id = $this->db->insert_id();

		$success = true;
		foreach($theTranslations as &$translation)
		{
			$lang_abbr = $translation["lang_abbr"];
			$title = $translation["name"];
			$text = $translation["description"];
			$theSuccess = $this->update_group_translation($group_id, $lang_abbr, $title, $text);
			if(!$theSuccess)
			{
				$success = $theSuccess;
			}

		}
		if ($this->db->trans_status() === FALSE || !$success)
		{
			$this->db->trans_rollback();
			return false;
		} else {
			$this->db->trans_commit();
		}

		return $group_id;
	}

	/**
	 * Update a translation of a specific news item
	 *
	 * @param  integer	$news_id		The ID of the news item
	 * @param  string	$lang_abbr		The language translation abbreviation
	 * @param  string	$title			The title of the news item translation
	 * @param  string	$text			The text of the news item translation
	 * @return bool		True or false depending on success or failure
	 */
	function update_group_translation($news_id, $lang_abbr, $title, $text)
	{
		$theTitle = trim($title);
		$theText = trim($text);

		// check if the group exists
		$this->db->where('id', $news_id);
		$query = $this->db->get('groups');
		if($query->num_rows != 1)
		{
			return false;
		}

		// check if the language exists
		$this->db->where('language_abbr', $lang_abbr);
		$query = $this->db->get('language');
		if($query->num_rows != 1)
		{
			return false;
		}
		$lang_id = $query->result(); $lang_id = $lang_id[0]->id;

		// if both title and text is null then delete the translation
		if($theTitle == '' && $theText == '')
		{
			$this->db->delete('groups_descriptions', array('groups_id' => $news_id, 'lang_id' => $lang_id));
			return true;
		}

		// if one of the title and the text is null then exit
		if($theTitle == '' || $theText == '')
		{
			return false;
		}

		$query = $this->db->get_where('groups_descriptions', array('groups_id' => $news_id, 'lang_id' => $lang_id), 1, 0);
		if ($query->num_rows() == 0)
		{
			// A record does not exist, insert one.
			$data = array(	'groups_id' 	=> $news_id,
							'lang_id' 	=> $lang_id,
							'name'		=> $theTitle,
							'description'		=> $theText,
						);
			$query = $this->db->insert('groups_descriptions', $data);
			// Check to see if the query actually performed correctly
			if ($this->db->affected_rows() > 0)
			{
				return TRUE;
			}
		} else {
			// A record does not exist, insert one.
			$data = array(	'name'		=> $theTitle,
							'description'		=> $theText,
						);
			$this->db->where('groups_id', $news_id);
			$this->db->where('lang_id', $lang_id);
			$this->db->update('groups_descriptions', $data);
			return true;
		}
		return FALSE;
	}

	/**
	 * add a year to a group
	 * @param 	integer $groups_id 	the id of the group to add the year to
	 * @param 	integer $start_year	the start year
	 * @param 	integer $stop_year	the end year
	 * @param 	array 	$user_list	list of users
	 * @return 	integer 			the id for the created group year
	 */
	function add_group_year($groups_id, $start_year = 0, $stop_year = 0, $user_list = array())
	{
		if($start_year == 0 || $stop_year == 0)
			return false;

		// check if group exists, return false if not
		$query = $this->db->get_where('groups', array('id' => $groups_id), 1, 0);
		if ($query->num_rows() == 0)
			return false;

		// group id exists, create group year
		$data = array(	'groups_id' 	=> $groups_id,
						'start_year'	=> $start_year,
						'stop_year'		=> $stop_year,
					);
		$query = $this->db->insert('groups_year', $data);

		// get id from from insert query
		$group_year_id = $this->db->insert_id();
		// add users to group year
		if(!empty($user_list))
			$this->add_users_to_group_year($group_year_id, $user_list);

		// return the created group year id
		return $group_year_id;
	}

	/**
	 * add users to a year group
	 */
	function add_users_to_group_year($groups_year_id, $user_list = array())
	{
		$list = $user_list;

		if(!is_array($list))
		{
			return false;
		}

		$arr_keys = array_keys($list);
		if(!is_numeric($arr_keys[0]))
		{
			$list = array($list);
		}

		foreach($list as &$l)
		{
			$arr_keys = array_keys($l);
			if(!in_array("user_id",$arr_keys)) {
				return false;
			}
			if(!in_array("email",$arr_keys)) {
				$l['email'] = '';
			}
			if(!in_array("position",$arr_keys)) {
				$l['position'] = '';
			}

			$l['groups_year_id'] = $groups_year_id;

			$query = $this->db->get_where('groups_year_members', array('groups_year_id' => $groups_year_id, 'user_id' => $l['user_id']), 1, 0);
			if ($query->num_rows() != 0)
				return false;
		}

		return $this->db->insert_batch('groups_year_members', $list);
	}

	function update_member_info($groups_year_id, $user_id, $position, $email)
	{
		$thePosition = trim($position);
		$theEmail = trim($email);

		// check if the user exists in the group
		$this->db->where('groups_year_id', $groups_year_id);
		$this->db->where('user_id', $user_id);
		$query = $this->db->get('groups_year_members');
		if($query->num_rows != 1)
		{
			//Should be one user with that user_id in that group
			return false;
		}
		else
		{
			$data = array(	'position'		=> $thePosition,
							'email'		=> $theEmail,
						);
			$this->db->where('groups_year_id', $groups_year_id);
			$this->db->where('user_id', $user_id);
			$this->db->update('groups_year_members', $data);
			return true;
		}

		return false;
	}

	function remove_member($groups_year_id, $user_id)
	{
		$this->db->where('groups_year_id', $groups_year_id);
		$this->db->where('user_id', $user_id);
		$this->db->delete('groups_year_members');
	}

	function remove_groups_year($groups_year_id)
	{
		$this->db->where('id', $groups_year_id);
		$this->db->delete('groups_year');

		$this->db->where('groups_year_id', $groups_year_id);
		$this->db->delete('groups_year_members');
	}

	function delete_group($id)
	{
		$groups = $this->db->delete('groups', array('id' => $id));
		$groups_descriptions = $this->db->delete('groups_descriptions', array('groups_id' => $id));
		$groups_year = $this->db->delete('groups_year', array('groups_id' => $id));

		return ($groups && $groups_descriptions && $groups_year);
	}
}

